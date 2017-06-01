#!/usr/bin/env python
# coding:utf-8

import BaseHTTPServer
import ctypes
import ctypes.util
import os
import paramiko
import re
import socket
import sys
import time

ENV_DAEMON = os.environ.get('DAEMON')
ENV_HTTP_HOST = os.environ.get('HTTP_HOST')
ENV_SSH_HOST = os.environ.get('SSH_HOST')
ENV_SSH_PORT = os.environ.get('SSH_PORT')
ENV_SSH_USER = os.environ.get('SSH_USER')
ENV_SSH_PASS = os.environ.get('SSH_PASS')
ENV_PORT = os.environ.get('PORT')

ssh_client = None
this_metric = ''

libc = ctypes.CDLL(ctypes.util.find_library('c'))

PREREAD_FILES = {}
PREREAD_FILELIST = [
    '/proc/driver/rtc',
    '/proc/loadavg',
    '/proc/meminfo',
    '/proc/net/dev',
    '/proc/net/netstat',
    '/proc/net/snmp',
    '/proc/stat',
    '/proc/sys/fs/file-nr',
    '/proc/sys/kernel/hostname',
    '/proc/sys/kernel/osrelease',
    '/proc/sys/kernel/ostype',
    '/proc/sys/kernel/version',
    '/proc/sys/net/netfilter/nf_conntrack_count',
    '/proc/sys/net/netfilter/nf_conntrack_max',
    '/proc/vmstat',
]


def do_connect():
    global ssh_client
    host = ENV_SSH_HOST
    port = int(ENV_SSH_PORT or '22')
    username = ENV_SSH_USER
    password = ENV_SSH_PASS
    if ssh_client is None:
        ssh_client = paramiko.SSHClient()
        ssh_client.set_missing_host_key_policy(paramiko.MissingHostKeyPolicy())
    ssh_client.connect(host, int(port), username, password, compress=True)
    ssh_client._transport.set_keepalive(60)


def do_preread():
    global PREREAD_FILES
    cmd = '/bin/fgrep "" ' + ' '.join(PREREAD_FILELIST)
    _, stdout, _ = ssh_client.exec_command(cmd)
    lines = stdout.read().splitlines(True)
    PREREAD_FILES = {}
    for line in lines:
        name, value = line.split(':', 1)
        PREREAD_FILES[name] = PREREAD_FILES.setdefault(name, '') + value


def read_file(filename):
    MAX_RETRY = 3
    for i in xrange(MAX_RETRY):
        try:
            if filename in PREREAD_FILELIST:
                output = PREREAD_FILES.get(filename, '')
            else:
                _, stdout, _ = ssh_client.exec_command('/bin/cat ' + filename)
                output = stdout.read()
            return output
        except StandardError as e:
            if i < MAX_RETRY - 1:
                time.sleep(0.5)
                do_connect()
            else:
                raise


def print_metric_type(metric, mtype):
    global this_metric
    this_metric = metric
    return '# TYPE %s %s\n' % (metric, mtype)


def print_metric(labels, value):
    if isinstance(value, int):
        value = float(value)
    if labels:
        s = '%s{%s} %e\n' % (this_metric, labels, value)
    else:
        s = '%s %e\n' % (this_metric, value)
    return s


def print_uname():
    domainname = "(none)"
    machine = "unknown"
    nodename = read_file("/proc/sys/kernel/hostname").strip()
    release = read_file("/proc/sys/kernel/osrelease").strip()
    sysname = read_file("/proc/sys/kernel/ostype").strip()
    version = read_file("/proc/sys/kernel/version").strip()
    uname_info = 'domainname="%s",machine="%s",nodename="%s",release="%s",sysname="%s",version="%s"' % (
                  domainname, machine, nodename, release, sysname, version)
    s = ''
    s += print_metric_type("node_uname_info", "gauge")
    s += print_metric(uname_info, 1)
    return s


def print_time():
    rtc = read_file('/proc/driver/rtc').strip()
    if rtc:
        info = dict(re.split(r'\s*:\s*', line, maxsplit=1) for line in rtc.splitlines())
        ts = time.mktime(time.strptime('%(rtc_date)s %(rtc_time)s' % info, '%Y-%m-%d %H:%M:%S'))
    else:
        _, stdout, _ = ssh_client.exec_command('date +%s')
        ts = float(stdout.read().strip() or '0.0')
    s = ''
    s += print_metric_type('node_time', 'counter')
    s += print_metric(None, ts)
    return s


def print_loadavg():
    loadavg = read_file("/proc/loadavg").strip().split()
    s = ''
    s += print_metric_type("node_load1", "gauge")
    s += print_metric(None, float(loadavg[0]))
    s += print_metric_type("node_load15", "gauge")
    s += print_metric(None, float(loadavg[2]))
    s += print_metric_type("node_load5", "gauge")
    s += print_metric(None, float(loadavg[1]))
    return s


def print_filefd():
    file_nr = read_file("/proc/sys/fs/file-nr").split()
    s = ''
    s += print_metric_type("node_filefd_allocated", "gauge")
    s += print_metric(None, float(file_nr[0]))
    s += print_metric_type("node_filefd_maximum", "gauge")
    s += print_metric(None, float(file_nr[2]))
    return s


def print_nf_conntrack():
    nf_conntrack_count = read_file("/proc/sys/net/netfilter/nf_conntrack_count").strip()
    nf_conntrack_max = read_file("/proc/sys/net/netfilter/nf_conntrack_max").strip()
    s = ''
    if nf_conntrack_count:
        s += print_metric_type("node_nf_conntrack_entries", "gauge")
        s += print_metric(None, float(nf_conntrack_count))
    if nf_conntrack_max:
        s += print_metric_type("node_nf_conntrack_entries_limit", "gauge")
        s += print_metric(None, float(nf_conntrack_max))
    return s


def print_memory():
    meminfo = read_file('/proc/meminfo')
    meminfo = meminfo.replace(')', '').replace(':', '').replace('(', '_')
    meminfo = meminfo.splitlines()
    s = ''
    for mi in meminfo:
        mia = mi.split()
        print_metric_type('node_memory_%s' % mia[0], 'gauge')
        if len(mia) == 3:
            s += print_metric(None, float(int(mia[1]) * 1024))
        else:
            s += print_metric(None, float(mia[1]))
    return s


def print_netstat():
    netstat = (read_file("/proc/net/netstat") + read_file("/proc/net/snmp")).splitlines()
    s = ''
    for i in range(0, len(netstat), 2):
        prefix, keystr = netstat[i].split(': ', 1)
        prefix, valuestr = netstat[i+1].split(': ', 1)
        keys = keystr.split()
        values = valuestr.split()
        for ii, ss in enumerate(keys):
            s += print_metric_type("node_netstat_%s_%s" % (prefix, ss), "gauge")
            s += print_metric(None, float(values[ii]))
    return s


def print_vmstat():
    vmstat = read_file("/proc/vmstat").splitlines()
    s = ''
    for vm in vmstat:
        vma = vm.split()
        s += print_metric_type("node_vmstat_%s" % vma[0], "gauge")
        s += print_metric(None, float(vma[1]))
    return s


def print_stat():
    cpu_mode = 'user nice system idle iowait irq softirq steal guest guest_nice'.split()
    stat = read_file("/proc/stat")
    s = ''
    s += print_metric_type("node_boot_time", "gauge")
    s += print_metric(None, float(re.search(r"btime ([0-9]+)", stat).group(1)))
    s += print_metric_type("node_context_switches", "counter")
    s += print_metric(None, float(re.search(r"ctxt ([0-9]+)", stat).group(1)))
    s += print_metric_type("node_forks", "counter")
    s += print_metric(None, float(re.search(r"processes ([0-9]+)", stat).group(1)))
    s += print_metric_type("node_intr", "counter")
    s += print_metric(None, float(re.search(r"intr ([0-9]+)", stat).group(1)))
    s += print_metric_type("node_procs_blocked", "gauge")
    s += print_metric(None, float(re.search(r"procs_blocked ([0-9]+)", stat).group(1)))
    s += print_metric_type("node_procs_running", "gauge")
    s += print_metric(None, float(re.search(r"procs_running ([0-9]+)", stat).group(1)))
    s += print_metric_type("node_cpu", "counter")
    cpulines = re.findall(r'(?m)cpu\d+ .+', stat)
    for i, line in enumerate(cpulines):
        cpu = line.split()[1:]
        for ii, mode in enumerate(cpu_mode):
            s += print_metric('cpu="cpu%d",mode="%s"' % (i, mode), float(cpu[ii]) / 100)
    return s


def print_netdev():
    netdevstat = read_file('/proc/net/dev').splitlines()
    faces = netdevstat[1].replace('|', ' ').split()[1:]
    devices = []
    statss = []
    for i in range(2, len(netdevstat)):
        stats = netdevstat[i].replace('|', ' ').replace(':', ' ').split()
        if len(stats) > 0:
            devices.append(stats[0])
            statss.append(stats[1:])
    s = ''
    for i in range(len(statss[0])):
        inter = 'receive' if 2*i < len(statss[0]) else 'transmit'
        s += print_metric_type('node_network_%s_%s' % (inter, faces[i]), "gauge")
        for ii, value in enumerate(devices):
            s += print_metric('device="%s"' % value, float(statss[ii][i]))
    return s


def print_all():
    if ssh_client is None:
        do_connect()
    do_preread()
    s = ''
    s += print_time()
    s += print_uname()
    s += print_loadavg()
    s += print_stat()
    s += print_vmstat()
    s += print_memory()
    s += print_filefd()
    s += print_nf_conntrack()
    s += print_netstat()
    s += print_netdev()
    return s


class MetricsHandler(BaseHTTPServer.BaseHTTPRequestHandler):
    def do_GET(self):
        body = print_all()
        self.send_response(200, 'OK')
        self.end_headers()
        self.wfile.write(body)
        self.wfile.close()
        self.log_request(200, len(body))


def main():
    if ENV_HTTP_HOST:
        print('Content-Type: text/plain; charset=utf-8\n')
        print(print_all())
        return
    if ENV_DAEMON:
        libc.daemon(1, 0)
    port = int(ENV_PORT or '9101')
    BaseHTTPServer.HTTPServer(('', port), MetricsHandler).serve_forever()


if __name__ == '__main__':
    main()

