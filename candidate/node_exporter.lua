#!/usr/bin/lua
-- Metrics web server (0.1)
-- Copyright (c) 2015 Kevin Lyda
-- Apache 2.0 License
-- Usage: /sbin/start-stop-daemon -S -b -n node_exporter -x /usr/bin/lua -- /root/node_exporter.lua
-- TODO: /proc/diskstats /proc/block/sd[X]/size /sys/class/hwmon/
-- TODO: process_cpu_seconds_total/process_max_fds/process_open_fds/process_resident_memory_bytes/process_start_time_seconds/process_virtual_memory_bytes

debug = os.getenv('DEBUG') ~= nil

function dump(o)
  if type(o) == 'table' then
    local s = '{ '
    for k,v in pairs(o) do
      if type(k) ~= 'number' then k = '"'..k..'"' end
        s = s .. '['..k..'] = ' .. dump(v) .. ','
      end
      return s .. '} '
  else
    return tostring(o)
  end
end

function split(s)
  elements = {}
  for element in s:gmatch("%S+") do
    table.insert(elements, element)
  end
  return elements
end

function splitlines(s)
  elements = {}
  for element in s:gmatch("[^\n]+") do
    table.insert(elements, element)
  end
  return elements
end

function rtrim(s)
  local n = #s
  while n > 0 and s:find("^%s", n) do n = n - 1 end
  return s:sub(1, n)
end

function read_file(filename)
  local f = io.open(filename, "rb")
  local contents = ""
  if f then
    contents = f:read "*a"
    f:close()
  end
  return contents
end

function print_metric_type(metric, mtype)
  this_metric = metric
  if debug then
    print("# TYPE " .. metric .. " " .. mtype)
  else
    client:send("# TYPE " .. metric .. " " .. mtype .. "\n")
  end
end

function print_metric(labels, value)
  if labels then
    if debug then
      print(string.format("%s{%s} %g", this_metric, labels, value))
    else
      client:send(string.format("%s{%s} %g\n", this_metric, labels, value))
    end
  else
    if debug then
      print(string.format("%s %g", this_metric, value))
    else
      client:send(string.format("%s %g\n", this_metric, value))
    end
  end
end

function print_uname()
  local domainname = "(none)"
  local machine = "unknown"
  local nodename = rtrim(read_file("/proc/sys/kernel/hostname"))
  local release = rtrim(read_file("/proc/sys/kernel/osrelease"))
  local sysname = rtrim(read_file("/proc/sys/kernel/ostype"))
  local version = rtrim(read_file("/proc/sys/kernel/version"))
  local uname_info = string.format('domainname="%s",machine="%s",nodename="%s",' ..
                             'release="%s",sysname="%s",version="%s"',
                             domainname, machine, nodename,
                             release, sysname, version)

  print_metric_type("node_uname_info", "gauge")
  print_metric(uname_info, 1)
end

function print_time()
  print_metric_type("node_time", "counter")
  print_metric(nil, os.time())
end

function print_loadavg()
  local loadavg = split(read_file("/proc/loadavg"))

  print_metric_type("node_load1", "gauge")
  print_metric(nil, loadavg[1])
  print_metric_type("node_load15", "gauge")
  print_metric(nil, loadavg[3])
  print_metric_type("node_load5", "gauge")
  print_metric(nil, loadavg[2])
end

function print_filefd()
  local file_nr = split(read_file("/proc/sys/fs/file-nr"))

  print_metric_type("node_filefd_allocated", "gauge")
  print_metric(nil, file_nr[1])
  print_metric_type("node_filefd_maximum", "gauge")
  print_metric(nil, file_nr[3])
end

function print_nf_conntrack()
  local nf_conntrack_count = rtrim(read_file("/proc/sys/net/netfilter/nf_conntrack_count"))
  local nf_conntrack_max = rtrim(read_file("/proc/sys/net/netfilter/nf_conntrack_max"))

  print_metric_type("node_nf_conntrack_entries", "gauge")
  print_metric(nil, nf_conntrack_count)
  print_metric_type("node_nf_conntrack_entries_limit", "gauge")
  print_metric(nil, nf_conntrack_max)
end

function print_memory()
  local meminfo = splitlines(read_file(
                    "/proc/meminfo"):gsub("[):]", ""):gsub("[(]", "_"))

  for i, mi in ipairs(meminfo) do
    local mia = split(mi)
    print_metric_type("node_memory_" .. mia[1], "gauge")
    if #mia == 3 then
      print_metric(nil, mia[2] * 1024)
    else
      print_metric(nil, mia[2])
    end
  end
end

function print_netstat()
  local netstat = splitlines(read_file("/proc/net/netstat") .. read_file("/proc/net/snmp"))

  for i = 1,#netstat,2 do
    local prefix, keystr = netstat[i]:match('(%S+): (.+)')
    local prefix, valuestr = netstat[i+1]:match('(%S+): (.+)')
    local keys = split(keystr)
    local values = split(valuestr)
    for ii, ss in ipairs(keys) do
      print_metric_type("node_netstat_" .. prefix .. "_" .. ss, "gauge")
      print_metric(nil, values[ii])
    end
  end
end

function print_vmstat()
  local vmstat = splitlines(read_file("/proc/vmstat"))

  for i, vm in ipairs(vmstat) do
    local vma = split(vm)
    print_metric_type("node_vmstat_" .. vma[1], "gauge")
    print_metric(nil, vma[2])
  end
end

function print_stat()
  local cpu_mode = {"user", "nice", "system", "idle", "iowait", "irq",
                    "softirq", "steal", "guest", "guest_nice"}
  local stat = read_file("/proc/stat")

  print_metric_type("node_boot_time", "gauge")
  print_metric(nil, string.match(stat, "btime ([0-9]+)"))
  print_metric_type("node_context_switches", "counter")
  print_metric(nil, string.match(stat, "ctxt ([0-9]+)"))
  print_metric_type("node_cpu", "counter")
  local i = 0
  while string.match(stat, string.format("cpu%d ", i)) do
    cpu = split(string.match(stat, string.format("cpu%d ([0-9 ]+)", i)))
    local label = string.format('cpu="cpu%d",mode="%%s"', i)
    for ii, value in ipairs(cpu) do
      print_metric(string.format(label, cpu_mode[ii]), value / 100)
    end
    i = i + 1
  end
  print_metric_type("node_forks", "counter")
  print_metric(nil, string.match(stat, "processes ([0-9]+)"))
  print_metric_type("node_intr", "counter")
  print_metric(nil, string.match(stat, "intr ([0-9]+)"))
  print_metric_type("node_procs_blocked", "gauge")
  print_metric(nil, string.match(stat, "procs_blocked ([0-9]+)"))
  print_metric_type("node_procs_running", "gauge")
  print_metric(nil, string.match(stat, "procs_running ([0-9]+)"))
end

function print_netdev()
  local netdevstat = splitlines(read_file("/proc/net/dev"))
  local faces = split(netdevstat[2]:gsub('|', ' '))
  table.remove(faces, 1)

  devices = {}
  statss = {}
  for i = 3,#netdevstat do
    local stats = split(netdevstat[i]:gsub('[|:]', ' '))
    if #stats > 0 then
      table.insert(devices, table.remove(stats, 1))
      table.insert(statss, stats)
    end
  end

  for i = 1, #statss[1] do
    local inter = 2*i <= #statss[1] and 'receive' or 'transmit'
    print_metric_type('node_network_' .. inter .. '_' .. faces[i], "gauge")
    for ii, value in ipairs(devices) do
      print_metric('device="' .. value .. '"', statss[ii][i])
    end
  end
end

function print_all()
  print_uname()
  print_time()
  print_stat()
  print_vmstat()
  print_loadavg()
  print_memory()
  print_filefd()
  print_nf_conntrack()
  print_netdev()
  print_netstat()
end

function serve(request)
  if not string.match(request, "GET /metrics.*") then
    client:send("HTTP/1.1 404 Not Found\r\n" ..
                "Server: lua-metrics\r\n" ..
                "Content-Type: text/plain\r\n" ..
                "\r\n" ..
                "404 Not Found")
  else
    client:send("HTTP/1.1 200 OK\r\n" ..
                "Server: lua-metrics\r\n" ..
                "Content-Type: text/plain\r\n" ..
                "\r\n")
    print_all()
  end

  client:close()
  return true
end

if debug then
  print_all()
  os.exit()
end

-- Main program.
socket = require("socket")

server = assert(socket.bind("*", 9100))

while 1 do
  client = server:accept()
  client:settimeout(60)
  local request, err = client:receive()

  if not err then
    if not serve(request) then
      break
    end
  end
end

