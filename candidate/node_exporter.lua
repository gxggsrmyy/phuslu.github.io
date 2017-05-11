#!/usr/bin/lua
-- Prometheus node_exporter (lua version)
-- Apache 2.0 License
-- Usage: 
--     cp node_exporter.lua /www/cgi-bin/metrics && chmod +x /www/cgi-bin/metrics
--     or
--     /sbin/start-stop-daemon -S -b -n node_exporter -x /usr/bin/lua -- /root/node_exporter.lua
--
-- TODO:
--     1. /proc/diskstats /proc/block/sd[X]/size /sys/class/hwmon/
--     2. process_cpu_seconds_total/process_max_fds/process_open_fds/process_resident_memory_bytes/process_start_time_seconds/process_virtual_memory_bytes

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

function print_metric_type(s, metric, mtype)
  this_metric = metric
  s = s .. "# TYPE " .. metric .. " " .. mtype .. "\n"
  return s
end

function print_metric(s, labels, value)
  if labels then
    s = s .. string.format("%s{%s} %g\n", this_metric, labels, value)
  else
    s = s .. string.format("%s %g\n", this_metric, value)
  end
  return s
end

function print_uname(s)
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

  s = print_metric_type(s, "node_uname_info", "gauge")
  s = print_metric(s, uname_info, 1)

  return s
end

function print_time(s)
  s = print_metric_type(s, "node_time", "counter")
  s = print_metric(s, nil, os.time())
  return s
end

function print_loadavg(s)
  local loadavg = split(read_file("/proc/loadavg"))

  s = print_metric_type(s, "node_load1", "gauge")
  s = print_metric(s, nil, loadavg[1])
  s = print_metric_type(s, "node_load15", "gauge")
  s = print_metric(s, nil, loadavg[3])
  s = print_metric_type(s, "node_load5", "gauge")
  s = print_metric(s, nil, loadavg[2])

  return s
end

function print_filefd(s)
  local file_nr = split(read_file("/proc/sys/fs/file-nr"))

  s = print_metric_type(s, "node_filefd_allocated", "gauge")
  s = print_metric(s, nil, file_nr[1])
  s = print_metric_type(s, "node_filefd_maximum", "gauge")
  s = print_metric(s, nil, file_nr[3])

  return s
end

function print_nf_conntrack(s)
  local nf_conntrack_count = rtrim(read_file("/proc/sys/net/netfilter/nf_conntrack_count"))
  local nf_conntrack_max = rtrim(read_file("/proc/sys/net/netfilter/nf_conntrack_max"))

  s = print_metric_type(s, "node_nf_conntrack_entries", "gauge")
  s = print_metric(s, nil, nf_conntrack_count)
  s = print_metric_type(s, "node_nf_conntrack_entries_limit", "gauge")
  s = print_metric(s, nil, nf_conntrack_max)

  return s
end

function print_memory(s)
  local meminfo = splitlines(read_file(
                    "/proc/meminfo"):gsub("[):]", ""):gsub("[(]", "_"))

  for i, mi in ipairs(meminfo) do
    local mia = split(mi)
    print_metric_type(s, "node_memory_" .. mia[1], "gauge")
    if #mia == 3 then
      s= print_metric(s, nil, mia[2] * 1024)
    else
      s = print_metric(s, nil, mia[2])
    end
  end

  return s
end

function print_netstat(s)
  local netstat = splitlines(read_file("/proc/net/netstat") .. read_file("/proc/net/snmp"))

  for i = 1,#netstat,2 do
    local prefix, keystr = netstat[i]:match('(%S+): (.+)')
    local prefix, valuestr = netstat[i+1]:match('(%S+): (.+)')
    local keys = split(keystr)
    local values = split(valuestr)
    for ii, ss in ipairs(keys) do
      s = print_metric_type(s, "node_netstat_" .. prefix .. "_" .. ss, "gauge")
      s = print_metric(s, nil, values[ii])
    end
  end

  return s
end

function print_vmstat(s)
  local vmstat = splitlines(read_file("/proc/vmstat"))

  for i, vm in ipairs(vmstat) do
    local vma = split(vm)
    s = print_metric_type(s, "node_vmstat_" .. vma[1], "gauge")
    s = print_metric(s, nil, vma[2])
  end

  return s
end

function print_stat(s)
  local cpu_mode = {"user", "nice", "system", "idle", "iowait", "irq",
                    "softirq", "steal", "guest", "guest_nice"}
  local stat = read_file("/proc/stat")

  s = print_metric_type(s, "node_boot_time", "gauge")
  s = print_metric(s, nil, string.match(stat, "btime ([0-9]+)"))
  s = print_metric_type(s, "node_context_switches", "counter")
  s = print_metric(s, nil, string.match(stat, "ctxt ([0-9]+)"))
  s = print_metric_type(s, "node_cpu", "counter")
  local i = 0
  while string.match(stat, string.format("cpu%d ", i)) do
    cpu = split(string.match(stat, string.format("cpu%d ([0-9 ]+)", i)))
    local label = string.format('cpu="cpu%d",mode="%%s"', i)
    for ii, value in ipairs(cpu) do
      s = print_metric(s, string.format(label, cpu_mode[ii]), value / 100)
    end
    i = i + 1
  end
  s = print_metric_type(s, "node_forks", "counter")
  s = print_metric(s, nil, string.match(stat, "processes ([0-9]+)"))
  s = print_metric_type(s, "node_intr", "counter")
  s = print_metric(s, nil, string.match(stat, "intr ([0-9]+)"))
  s = print_metric_type(s, "node_procs_blocked", "gauge")
  s = print_metric(s, nil, string.match(stat, "procs_blocked ([0-9]+)"))
  s = print_metric_type(s, "node_procs_running", "gauge")
  s = print_metric(s, nil, string.match(stat, "procs_running ([0-9]+)"))

  return s
end

function print_netdev(s)
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
    s = print_metric_type(s, 'node_network_' .. inter .. '_' .. faces[i], "gauge")
    for ii, value in ipairs(devices) do
      s = print_metric(s, 'device="' .. value .. '"', statss[ii][i])
    end
  end

  return s
end

function print_all(s)
  s = print_uname(s)
  s = print_time(s)
  s = print_stat(s)
  s = print_vmstat(s)
  s = print_loadavg(s)
  s = print_memory(s)
  s = print_filefd(s)
  s = print_nf_conntrack(s)
  s = print_netdev(s)
  s = print_netstat(s)
  return s
end

if os.getenv('HTTP_HOST') ~= nil then
  print(print_all('\n'))
  os.exit()
end

-- Main program.
ok, http_server = pcall(require, 'http.server')
if ok then
  -- see https://github.com/daurnimator/lua-http/blob/master/examples/server_hello.lua
  local http_headers = require('http.headers')
  local myserver = assert(http_server.listen {
      host = "0.0.0.0";
      port = 9100;
      onstream = function (myserver, stream)
          local req_headers = assert(stream:get_headers())
          local req_path = req_headers:get(":path")
          local res_headers = http_headers.new()
          if req_path ~= "/metrics" then
              res_headers:append(":status", "404")
              res_headers:append("content-type", "text/plain")
              res_headers:append("server", "Metrics Server(lua-http)")
              stream:write_headers(res_headers, false)
              stream:write_headers("404 Not Found")
          else
              res_headers:append(":status", "200")
              res_headers:append("content-type", "text/plain")
              res_headers:append("server", "Metrics Server(lua-http)")
              assert(stream:write_headers(res_headers, false))
              assert(stream:write_chunk(print_all("")))
          end
      end;
      onerror = function(myserver, context, op, err, errno) -- luacheck: ignore 212
		local msg = op .. " on " .. tostring(context) .. " failed"
		if err then
			msg = msg .. ": " .. tostring(err)
		end
		assert(io.stderr:write(msg, "\n"))
      end;
  })
  assert(myserver:listen())
  assert(myserver:loop())
else
  socket = require("socket")
  server = assert(socket.bind("*", 9100))
  while 1 do
    local client = server:accept()
    client:settimeout(60)
    local request, err = client:receive()
    if not err then
      if not string.match(request, "GET /metrics .*") then
        client:send("HTTP/1.1 404 Not Found\r\n" ..
                    "Content-Type: text/plain\r\n" ..
                    "Server: Metrics Server(lua-socket)\r\n" ..
                    "\r\n" ..
                    "404 Not Found")
      else
        client:send(print_all("HTTP/1.1 200 OK\r\n" ..
                    "Content-Type: text/plain\r\n" ..
                    "Server: Metrics Server(lua-socket)\r\n" ..
                    "\r\n"))
      end
      client:close()
    end
  end
end

