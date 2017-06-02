#!/usr/bin/env python
# coding:utf-8
# License:
#     MIT License, Copyright phuslu@hotmail.com
# Usage:
#     /usr/bin/env DAEMON=1 PORT=9101 SSH_HOST=192.168.2.1 SSH_USER=admin SSH_PASS=123456 /home/phuslu/phuslu.github.io/candidate/remote_node_exporter.py
# TODO:
#     /proc/diskstats
#     /sys/class/hwmon
#     process_cpu_seconds_total
#     process_max_fds
#     process_open_fds
#     process_resident_memory_bytes
#     process_start_time_seconds
#     process_virtual_memory_bytes


import BaseHTTPServer
import ctypes
import ctypes.util
import logging
import os
import paramiko
import re
import time

logging.basicConfig(format='%(asctime)s [%(levelname)s] process@%(process)s thread@%(thread)s %(filename)s@%(lineno)s - %(funcName)s(): %(message)s', level=logging.INFO)

ENV_DAEMON = os.environ.get('DAEMON')
ENV_SSH_HOST = os.environ.get('SSH_HOST')
ENV_SSH_PORT = os.environ.get('SSH_PORT')
ENV_SSH_USER = os.environ.get('SSH_USER')
ENV_SSH_PASS = os.environ.get('SSH_PASS')
ENV_SSH_KEYFILE = os.environ.get('SSH_KEYFILE')
ENV_SSH_TIMEZONE_OFFSET = os.environ.get('SSH_TIMEZONE_OFFSET')
ENV_PORT = os.environ.get('PORT')

ssh_client = None
this_metric = ''

libc = ctypes.CDLL(ctypes.util.find_library('c'))

PREREAD_FILES = {}
PREREAD_FILELIST = [
    '/etc/storage/system_time',
    '/proc/diskstats',
    '/proc/driver/rtc',
    '/proc/loadavg',
    '/proc/meminfo',
    '/proc/mounts',
    '/proc/net/dev',
    '/proc/net/netstat',
    '/proc/net/snmp',
    '/proc/stat',
    '/proc/sys/fs/file-nr',
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
    keyfile = ENV_SSH_KEYFILE
    if ssh_client is None:
        ssh_client = paramiko.SSHClient()
        ssh_client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh_client.connect(host, port=int(port), username=username, password=password, key_filename=keyfile, compress=True)
    ssh_client.get_transport().set_keepalive(60)


def do_exec_command(cmd, redirect_stderr=False):
    SSH_COMMAND_TIMEOUT = 8
    MAX_RETRY = 3
    if redirect_stderr:
        cmd += ' 2>&1'
    for _ in range(MAX_RETRY):
        try:
            _, stdout, _ = ssh_client.exec_command(cmd, timeout=SSH_COMMAND_TIMEOUT)
            return stdout.read()
        except (paramiko.SSHException, StandardError) as e:
            logging.error('do_exec_command(%r) error: %s', cmd, e)
            time.sleep(0.5)
            do_connect()
    return ''


def do_preread():
    global PREREAD_FILES
    cmd = '/bin/fgrep "" ' + ' '.join(PREREAD_FILELIST)
    output = do_exec_command(cmd)
    lines = output.splitlines(True)
    PREREAD_FILES = {}
    for line in lines:
        name, value = line.split(':', 1)
        PREREAD_FILES[name] = PREREAD_FILES.setdefault(name, '') + value


def read_file(filename):
    if filename in PREREAD_FILELIST:
        return PREREAD_FILES.get(filename, '')
    else:
        return do_exec_command('/bin/cat ' + filename)


def print_metric_type(metric, mtype):
    global this_metric
    this_metric = metric
    return '# TYPE %s %s\n' % (metric, mtype)


def print_metric(labels, value):
    if isinstance(value, float):
        value = '%e' % value if value else '0'
    else:
        value = str(value)
    if labels:
        s = '%s{%s} %s\n' % (this_metric, labels, value)
    else:
        s = '%s %s\n' % (this_metric, value)
    return s


def collect_time():
    rtc = read_file('/proc/driver/rtc').strip()
    system_time = read_file('/etc/storage/system_time').strip()
    if rtc:
        info = dict(re.split(r'\s*:\s*', line, maxsplit=1) for line in rtc.splitlines())
        ts = time.mktime(time.strptime('%(rtc_date)s %(rtc_time)s' % info, '%Y-%m-%d %H:%M:%S'))
        if ENV_SSH_TIMEZONE_OFFSET:
            ts += int(ENV_SSH_TIMEZONE_OFFSET) * 60
    elif system_time:
        ts = float(system_time)
    else:
        ts = float(do_exec_command('date +%s').strip() or '0')
    s = ''
    s += print_metric_type('node_time', 'counter')
    s += print_metric(None, ts)
    return s


def collect_loadavg():
    loadavg = read_file('/proc/loadavg').strip().split()
    s = ''
    s += print_metric_type('node_load1', 'gauge')
    s += print_metric(None, float(loadavg[0]))
    s += print_metric_type('node_load15', 'gauge')
    s += print_metric(None, float(loadavg[2]))
    s += print_metric_type('node_load5', 'gauge')
    s += print_metric(None, float(loadavg[1]))
    return s


def collect_filefd():
    file_nr = read_file('/proc/sys/fs/file-nr').split()
    s = ''
    s += print_metric_type('node_filefd_allocated', 'gauge')
    s += print_metric(None, float(file_nr[0]))
    s += print_metric_type('node_filefd_maximum', 'gauge')
    s += print_metric(None, float(file_nr[2]))
    return s


def collect_nf_conntrack():
    nf_conntrack_count = read_file('/proc/sys/net/netfilter/nf_conntrack_count').strip()
    nf_conntrack_max = read_file('/proc/sys/net/netfilter/nf_conntrack_max').strip()
    s = ''
    if nf_conntrack_count:
        s += print_metric_type('node_nf_conntrack_entries', 'gauge')
        s += print_metric(None, float(nf_conntrack_count))
    if nf_conntrack_max:
        s += print_metric_type('node_nf_conntrack_entries_limit', 'gauge')
        s += print_metric(None, float(nf_conntrack_max))
    return s


def collect_memory():
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


def collect_netstat():
    netstat = (read_file('/proc/net/netstat') + read_file('/proc/net/snmp')).splitlines()
    s = ''
    for i in range(0, len(netstat), 2):
        prefix, keystr = netstat[i].split(': ', 1)
        prefix, valuestr = netstat[i+1].split(': ', 1)
        keys = keystr.split()
        values = valuestr.split()
        for ii, ss in enumerate(keys):
            s += print_metric_type('node_netstat_%s_%s' % (prefix, ss), 'gauge')
            s += print_metric(None, float(values[ii]))
    return s


def collect_vmstat():
    vmstat = read_file('/proc/vmstat').splitlines()
    s = ''
    for vm in vmstat:
        vma = vm.split()
        s += print_metric_type('node_vmstat_%s' % vma[0], 'gauge')
        s += print_metric(None, float(vma[1]))
    return s


def collect_stat():
    cpu_mode = 'user nice system idle iowait irq softirq steal guest guest_nice'.split()
    stat = read_file('/proc/stat')
    s = ''
    s += print_metric_type('node_boot_time', 'gauge')
    s += print_metric(None, float(re.search(r'btime ([0-9]+)', stat).group(1)))
    s += print_metric_type('node_context_switches', 'counter')
    s += print_metric(None, float(re.search(r'ctxt ([0-9]+)', stat).group(1)))
    s += print_metric_type('node_forks', 'counter')
    s += print_metric(None, float(re.search(r'processes ([0-9]+)', stat).group(1)))
    s += print_metric_type('node_intr', 'counter')
    s += print_metric(None, float(re.search(r'intr ([0-9]+)', stat).group(1)))
    s += print_metric_type('node_procs_blocked', 'gauge')
    s += print_metric(None, float(re.search(r'procs_blocked ([0-9]+)', stat).group(1)))
    s += print_metric_type('node_procs_running', 'gauge')
    s += print_metric(None, float(re.search(r'procs_running ([0-9]+)', stat).group(1)))
    s += print_metric_type('node_cpu', 'counter')
    cpulines = re.findall(r'(?m)cpu\d+ .+', stat)
    for i, line in enumerate(cpulines):
        cpu = line.split()[1:]
        for ii, mode in enumerate(cpu_mode):
            s += print_metric('cpu="cpu%d",mode="%s"' % (i, mode), float(cpu[ii]) / 100)
    return s


def collect_netdev():
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
        s += print_metric_type('node_network_%s_%s' % (inter, faces[i]), 'gauge')
        for ii, value in enumerate(devices):
            s += print_metric('device="%s"' % value, float(statss[ii][i]))
    return s


def collect_diskstats():
    suffixs = [
        'reads_completed',
        'reads_merged',
        'sectors_read',
        'read_time_ms',
        'writes_completed',
        'writes_merged',
        'sectors_written',
        'write_time_ms',
        'io_now',
        'io_time_ms',
        'io_time_weighted',
    ]
    diskstats = read_file('/proc/diskstats').splitlines()
    devices = {}
    for line in diskstats:
        values = line.split()[2:14]
        device = values.pop(0)
        devices[device] = values
    s = ''
    for i, suffix in enumerate(suffixs):
        s += print_metric_type('node_disk_%s' % suffix, 'gauge')
        for device, values in devices.items():
            s += print_metric('device="%s"' % device, float(values[i]))
    return s


def collect_filesystem():
    suffixs = 'avail free size'.split()
    ignore_mountpoints = '/sys /dev /proc'.split()
    ignore_fstypes = 'autofs procfs sysfs'.split()
    mountinfo = read_file('/proc/mounts').strip().splitlines()
    df = do_exec_command('df').splitlines()
    if not df:
        return ''
    df.pop(0)
    mountpoints = {}
    for line in mountinfo:
        device, mountpoint, fstype = line.split()[:3]
        if mountpoint in ignore_mountpoints or any(mountpoint.startswith(x+'/') for x in ignore_mountpoints):
            continue
        if fstype in ignore_fstypes:
            continue
        mountpoints[mountpoint] = dict(mountpoint=mountpoint, fstype=fstype, device=device)
    for line in df:
        device, size, used, avail, _, mountpoint = line.split()[:6]
        if mountpoint in mountpoints:
            mountpoints[mountpoint].update(dict(size=int(size)*1024, free=(int(size)-int(used))*1024, avail=int(avail)*1024))
    s = ''
    for suffix in suffixs:
        s += print_metric_type('node_filesystem_%s' % suffix, 'gauge')
        for mountpoint, info in mountpoints.items():
            s += print_metric('device="%(device)s",fstype="%(fstype)s",mountpoint="%(mountpoint)s"' % info, float(info.get(suffix, 0)))
    return s


def collect_all():
    if ssh_client is None:
        do_connect()
    do_preread()
    s = ''
    s += collect_time()
    s += collect_loadavg()
    s += collect_stat()
    s += collect_vmstat()
    s += collect_memory()
    s += collect_filefd()
    s += collect_nf_conntrack()
    s += collect_netstat()
    s += collect_netdev()
    s += collect_diskstats()
    # s += collect_filesystem()
    return s


class MetricsHandler(BaseHTTPServer.BaseHTTPRequestHandler):
    def do_GET(self):
        body = collect_all()
        self.send_response(200, 'OK')
        self.end_headers()
        self.wfile.write(body)
        self.wfile.close()


def main():
    if ENV_DAEMON:
        libc.daemon(1, 0)
    port = int(ENV_PORT or '9101')
    logging.info('Serving HTTP on 0.0.0.0 port %d ...', port)
    BaseHTTPServer.HTTPServer(('', port), MetricsHandler).serve_forever()


if __name__ == '__main__':
    main()

