<?php
define('JQUERY_JS_URL', 'https://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js');
define('BOOTSTRAP_CSS_URL', 'https://apps.bdimg.com/libs/bootstrap/3.2.0/css/bootstrap.min.css');

//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
error_reporting(0); //抑制所有错误信息
@header("content-Type: text/html; charset=utf-8"); //语言强制
ob_start();
date_default_timezone_set('Asia/Shanghai');//此句用于消除时间差

define('HTTP_HOST', preg_replace('~^www\.~i', '', $_SERVER['HTTP_HOST']));

//修正 $_SERVER['REMOTE_ADDR']
if (isset($_SERVER["HTTP_X_REAL_IP"]))
{
  $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_X_REAL_IP"];
}
else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
{
  $_SERVER['REMOTE_ADDR'] = preg_replace('/^.+,\s*/', '', $_SERVER["HTTP_X_FORWARDED_FOR"]);
}

$time_start = microtime_float();

function memory_usage()
{
  $memory  = ( ! function_exists('memory_get_usage')) ? '0' : round(memory_get_usage()/1024/1024, 2).'MB';
  return $memory;
}

// 计时
function microtime_float()
{
  $mtime = microtime();
  $mtime = explode(' ', $mtime);
  return $mtime[1] + $mtime[0];
}

//单位转换
function formatsize($size)
{
  $danwei=array(' B ',' K ',' M ',' G ',' T ');
  $allsize=array();
  $i=0;

  for($i = 0; $i <5; $i++)
  {
    if(floor($size/pow(1024,$i))==0){break;}
  }

  for($l = $i-1; $l >=0; $l--)
  {
    $allsize1[$l]=floor($size/pow(1024,$l));
    $allsize[$l]=$allsize1[$l]-$allsize1[$l+1]*1024;
  }

  $len=count($allsize);

  for($j = $len-1; $j >=0; $j--)
  {
    $fsize=$fsize.$allsize[$j].$danwei[$j];
  }
  return $fsize;
}

function valid_email($str)
{
  return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? FALSE : TRUE;
}

//检测PHP设置参数
function show($varName)
{
  switch($result = get_cfg_var($varName))
  {
    case 0:
      return '<span color="red">×</span>';
    break;

    case 1:
      return '<span color="green">√</span>';
    break;

    default:
      return $result;
    break;
  }
}

//保留服务器性能测试结果
$valInt = isset($_POST['pInt']) ? $_POST['pInt'] : "未测试";
$valFloat = isset($_POST['pFloat']) ? $_POST['pFloat'] : "未测试";
$valIo = isset($_POST['pIo']) ? $_POST['pIo'] : "未测试";

if ($_GET['act'] == "phpinfo")
{
  phpinfo();
  exit();
}
elseif($_POST['act'] == "整型测试")
{
  $valInt = test_int();
}
elseif($_POST['act'] == "浮点测试")
{
  $valFloat = test_float();
}
elseif($_POST['act'] == "IO测试")
{
  $valIo = test_io();
}
//网速测试-开始
elseif($_POST['act']=="开始测试")
{
?>
  <script>
    var acd1;
    acd1 = new Date();
    acd1ok=acd1.getTime();
  </script>
  <?php
  for($i=1;$i<=100000;$i++)
  {
    echo "<!--567890#########0#########0#########0#########0#########0#########0#########0#########012345-->";
  }
  ?>
  <script>
    var acd2;
    acd2 = new Date();
    acd2ok=acd2.getTime();
    window.location = '?speed=' +(acd2ok-acd1ok)+'#w_networkspeed';
  </script>
<?php
}
//网速测试-结束

//网络速度测试
if(isset($_POST['speed']))
{
  $speed=round(100/($_POST['speed']/1000),2);
}
elseif($_GET['speed']=="0")
{
  $speed=6666.67;
}
elseif(isset($_GET['speed']) and $_GET['speed']>0)
{
  $speed=round(100/($_GET['speed']/1000),2); //下载速度：$speed kb/s
}
else
{
  $speed="<span color=\"red\">&nbsp;未探测&nbsp;</span>";
}

function isfun($funName = '')
{
  if (!$funName || trim($funName) == '' || preg_match('~[^a-z0-9\_]+~i', $funName, $tmp)) return '错误';
    return (false !== function_exists($funName)) ? '<span color="green">√</span>' : '<span color="red">×</span>';
}

//整数运算能力测试
function test_int()
{
  $timeStart = gettimeofday();
  for($i = 0; $i < 3000000; $i++)
  {
    $t = 1+1;
  }
  $timeEnd = gettimeofday();
  $time = ($timeEnd["usec"]-$timeStart["usec"])/1000000+$timeEnd["sec"]-$timeStart["sec"];
  $time = round($time, 3)."秒";
  return $time;
}

//浮点运算能力测试
function test_float()
{
  //得到圆周率值
  $t = pi();
  $timeStart = gettimeofday();

  for($i = 0; $i < 3000000; $i++)
  {
    //开平方
    sqrt($t);
  }

  $timeEnd = gettimeofday();
  $time = ($timeEnd["usec"]-$timeStart["usec"])/1000000+$timeEnd["sec"]-$timeStart["sec"];
  $time = round($time, 3)."秒";
  return $time;
}

//IO能力测试
function test_io()
{
  $fp = @fopen(PHPSELF, "r");
  $timeStart = gettimeofday();
  for($i = 0; $i < 10000; $i++)
  {
    @fread($fp, 10240);
    @rewind($fp);
  }
  $timeEnd = gettimeofday();
  @fclose($fp);
  $time = ($timeEnd["usec"]-$timeStart["usec"])/1000000+$timeEnd["sec"]-$timeStart["sec"];
  $time = round($time, 3)."秒";
  return($time);
}

function get_dist_name()
{
    foreach (glob("/etc/*release") as $name) {
      if ($name == '/etc/centos-release' || $name == '/etc/redhat-release' || $name == '/etc/system-release') {
        return array_shift(file($name));
      }
      $release_info = @parse_ini_file($name);
      if (isset($release_info["DISTRIB_DESCRIPTION"]))
        return $release_info["DISTRIB_DESCRIPTION"];
      if (isset($release_info["PRETTY_NAME"]))
        return $release_info["PRETTY_NAME"];
    }

  return php_uname('s').' '.php_uname('r');
}

//linux系统探测
$sysInfo = sys_linux();

function sys_linux()
{
  // CPU
  if (false === ($str = @file("/proc/cpuinfo"))) return false;
  $str = implode("", $str);
  @preg_match_all("/processor\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $str, $processor);
  @preg_match_all("/model\s+name\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $str, $model);
  if (count($model[0]) == 0)
  {
    @preg_match_all("/Hardware\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $str, $model);
  }
  @preg_match_all("/cpu\s+MHz\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/", $str, $mhz);
  if (count($mhz[0]) == 0)
  {
    $values = @file("/sys/devices/system/cpu/cpu0/cpufreq/cpuinfo_max_freq");
    $mhz = array("", array(sprintf('%.3f', intval($values[0])/1000)));
  }
  @preg_match_all("/cache\s+size\s{0,}\:+\s{0,}([\d\.]+\s{0,}[A-Z]+[\r\n]+)/", $str, $cache);
  @preg_match_all("/(?i)bogomips\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/", $str, $bogomips);
  @preg_match_all("/(?i)(flags|Features)\s{0,}\:+\s{0,}(.+)[\r\n]+/", $str, $flags);
  if (false !== is_array($model[1]))
  {
    $res['cpu']['num'] = sizeof($processor[1]);
    if($res['cpu']['num']==1)
      $x1 = '';
    else
      $x1 = ' ×'.$res['cpu']['num'];
    $mhz[1][0] = ' | 频率:'.$mhz[1][0];
    if (count($cache[0]) > 0)
      $cache[1][0] = ' | 二级缓存:'.$cache[1][0];
    $bogomips[1][0] = ' | Bogomips:'.$bogomips[1][0];
    $res['cpu']['model'][] = $model[1][0].$mhz[1][0].$cache[1][0].$bogomips[1][0].$x1;
    $res['cpu']['flags'] = $flags[2][0];
    if (false !== is_array($res['cpu']['model'])) $res['cpu']['model'] = implode("<br>", $res['cpu']['model']);
    if (false !== is_array($res['cpu']['mhz'])) $res['cpu']['mhz'] = implode("<br>", $res['cpu']['mhz']);
    if (false !== is_array($res['cpu']['cache'])) $res['cpu']['cache'] = implode("<br>", $res['cpu']['cache']);
    if (false !== is_array($res['cpu']['bogomips'])) $res['cpu']['bogomips'] = implode("<br>", $res['cpu']['bogomips']);
  }

  // UPTIME
  if (false === ($str = @file("/proc/uptime"))) return false;
  $str = explode(" ", implode("", $str));
  $str = trim($str[0]);
  $min = $str / 60;
  $hours = $min / 60;
  $days = floor($hours / 24);
  $hours = floor($hours - ($days * 24));
  $min = floor($min - ($days * 60 * 24) - ($hours * 60));
  if ($days !== 0) $res['uptime'] = $days."天";
  if ($hours !== 0) $res['uptime'] .= $hours."小时";
  $res['uptime'] .= $min."分钟";

  // MEMORY
  if (false === ($str = @file("/proc/meminfo"))) return false;
  $str = implode("", $str);
  preg_match_all("/MemTotal\s{0,}\:+\s{0,}([\d\.]+).+?MemFree\s{0,}\:+\s{0,}([\d\.]+).+?Cached\s{0,}\:+\s{0,}([\d\.]+).+?SwapTotal\s{0,}\:+\s{0,}([\d\.]+).+?SwapFree\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buf);
  preg_match_all("/Buffers\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buffers);

  $res['memTotal'] = round($buf[1][0]/1024, 2);
  $res['memFree'] = round($buf[2][0]/1024, 2);
  $res['memBuffers'] = round($buffers[1][0]/1024, 2);
  $res['memCached'] = round($buf[3][0]/1024, 2);
  $res['memUsed'] = $res['memTotal']-$res['memFree'];
  $res['memPercent'] = (floatval($res['memTotal'])!=0)?round($res['memUsed']/$res['memTotal']*100,2):0;

  $res['memRealUsed'] = $res['memTotal'] - $res['memFree'] - $res['memCached'] - $res['memBuffers']; //真实内存使用
  $res['memRealFree'] = $res['memTotal'] - $res['memRealUsed']; //真实空闲
  $res['memRealPercent'] = (floatval($res['memTotal'])!=0)?round($res['memRealUsed']/$res['memTotal']*100,2):0; //真实内存使用率

  $res['memCachedPercent'] = (floatval($res['memCached'])!=0)?round($res['memCached']/$res['memTotal']*100,2):0; //Cached内存使用率

  $res['swapTotal'] = round($buf[4][0]/1024, 2);
  $res['swapFree'] = round($buf[5][0]/1024, 2);
  $res['swapUsed'] = round($res['swapTotal']-$res['swapFree'], 2);
  $res['swapPercent'] = (floatval($res['swapTotal'])!=0)?round($res['swapUsed']/$res['swapTotal']*100,2):0;

  // LOAD Board
  if (is_file("/sys/devices/virtual/dmi/id/board_name")) {
      $res['boardVendor'] = array_shift(file('/sys/devices/virtual/dmi/id/board_vendor'));
      $res['boardName'] = array_shift(file('/sys/devices/virtual/dmi/id/board_name'));
      $res['boardVersion'] = array_shift(file('/sys/devices/virtual/dmi/id/board_version'));
  } else if (is_file("/sys/devices/virtual/android_usb/android0/f_rndis/manufacturer")) {
      $res['boardVendor'] = array_shift(file('/sys/devices/virtual/android_usb/android0/f_rndis/manufacturer'));
      $res['boardName'] = '';
      $res['boardVersion'] = '';
  }

  // LOAD BIOS
  if (is_file("/sys/devices/virtual/dmi/id/bios_vendor")) {
      $res['BIOSVendor'] = array_shift(file('/sys/devices/virtual/dmi/id/bios_vendor'));
      $res['BIOSVersion'] = array_shift(file('/sys/devices/virtual/dmi/id/bios_version'));
      $res['BIOSDate'] = array_shift(file('/sys/devices/virtual/dmi/id/bios_date'));
  } else if (is_file("/sys/devices/virtual/android_usb/android0/iProduct")) {
      $res['BIOSVendor'] = array_shift(file('/sys/devices/virtual/android_usb/android0/iManufacturer'));
      $res['BIOSVersion'] = array_shift(file('/sys/devices/virtual/android_usb/android0/iProduct'));
      $res['BIOSDate'] = '';
  }

  // LOAD DISK
  if ($dirs=glob("/sys/class/block/s*")) {
      $res['diskModel'] = array_shift(file($dirs[0]."/device/model"));
      $res['diskVendor'] = array_shift(file($dirs[0]."/device/vendor"));
  } else if ($dirs=glob("/sys/class/block/mmc*")) {
      $res['diskModel'] = array_shift(file($dirs[0]."/device/name"));
      $res['diskVendor'] = array_shift(file($dirs[0]."/device/type"));
  }

  // LOAD AVG
  if (false === ($str = @file("/proc/loadavg"))) return false;
  $str = explode(" ", implode("", $str));
  $str = array_chunk($str, 4);
  $res['loadAvg'] = implode(" ", $str[0]);

  return $res;
}

$uptime = $sysInfo['uptime']; //在线时间
$stime = date('Y-m-d H:i:s'); //系统当前时间

//硬盘
$dt = round(@disk_total_space(".")/(1024*1024*1024),3); //总
$df = round(@disk_free_space(".")/(1024*1024*1024),3); //可用
$du = $dt-$df; //已用
$hdPercent = (floatval($dt)!=0)?round($du/$dt*100,2):0;

$load = $sysInfo['loadAvg'];  //系统负载


//判断内存如果小于1G，就显示M，否则显示G单位
if($sysInfo['memTotal']<1024)
{
  $memTotal = $sysInfo['memTotal']." M";
  $mt = $sysInfo['memTotal']." M";
  $mu = $sysInfo['memUsed']." M";
  $mf = $sysInfo['memFree']." M";
  $mc = $sysInfo['memCached']." M"; //cache化内存
  $mb = $sysInfo['memBuffers']." M";  //缓冲
  $st = $sysInfo['swapTotal']." M";
  $su = $sysInfo['swapUsed']." M";
  $sf = $sysInfo['swapFree']." M";
  $swapPercent = $sysInfo['swapPercent'];
  $memRealUsed = $sysInfo['memRealUsed']." M"; //真实内存使用
  $memRealFree = $sysInfo['memRealFree']." M"; //真实内存空闲
  $memRealPercent = $sysInfo['memRealPercent']; //真实内存使用比率
  $memPercent = $sysInfo['memPercent']; //内存总使用率
  $memCachedPercent = $sysInfo['memCachedPercent']; //cache内存使用率
}
else
{
  $memTotal = round($sysInfo['memTotal']/1024,3)." G";
  $mt = round($sysInfo['memTotal']/1024,3)." G";
  $mu = round($sysInfo['memUsed']/1024,3)." G";
  $mf = round($sysInfo['memFree']/1024,3)." G";
  $mc = round($sysInfo['memCached']/1024,3)." G";
  $mb = round($sysInfo['memBuffers']/1024,3)." G";
  $st = round($sysInfo['swapTotal']/1024,3)." G";
  $su = round($sysInfo['swapUsed']/1024,3)." G";
  $sf = round($sysInfo['swapFree']/1024,3)." G";
  $swapPercent = $sysInfo['swapPercent'];
  $memRealUsed = round($sysInfo['memRealUsed']/1024,3)." G"; //真实内存使用
  $memRealFree = round($sysInfo['memRealFree']/1024,3)." G"; //真实内存空闲
  $memRealPercent = $sysInfo['memRealPercent']; //真实内存使用比率
  $memPercent = $sysInfo['memPercent']; //内存总使用率
  $memCachedPercent = $sysInfo['memCachedPercent']; //cache内存使用率
}

//网卡流量
$strs = @file("/proc/net/dev");

for ($i = 2; $i < count($strs); $i++ )
{
  preg_match_all( "/([^\s]+):[\s]{0,}(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/", $strs[$i], $info );
  $NetOutSpeed[$i] = $info[10][0];
  $NetInputSpeed[$i] = $info[2][0];
  $NetInput[$i] = formatsize($info[2][0]);
  $NetOut[$i]  = formatsize($info[10][0]);
}

//ajax调用实时刷新
if ($_GET['act'] == "rt")
{
  $arr=array('useSpace'=>"$du",
             'freeSpace'=>"$df",
             'hdPercent'=>"$hdPercent",
             'barhdPercent'=>"$hdPercent%",
             'TotalMemory'=>"$mt",
             'UsedMemory'=>"$mu",
             'FreeMemory'=>"$mf",
             'CachedMemory'=>"$mc",
             'Buffers'=>"$mb",
             'TotalSwap'=>"$st",
             'swapUsed'=>"$su",
             'swapFree'=>"$sf",
             'loadAvg'=>"$load",
             'uptime'=>"$uptime",
             'freetime'=>"$freetime",
             'bjtime'=>"$bjtime",
             'stime'=>"$stime",
             'memRealPercent'=>"$memRealPercent",
             'memRealUsed'=>"$memRealUsed",
             'memRealFree'=>"$memRealFree",
             'memPercent'=>"$memPercent%",
             'memCachedPercent'=>"$memCachedPercent",
             'barmemCachedPercent'=>"$memCachedPercent%",
             'swapPercent'=>"$swapPercent",
             'barmemRealPercent'=>"$memRealPercent%",
             'barswapPercent'=>"$swapPercent%",
             'NetOut2'=>"$NetOut[2]",
             'NetOut3'=>"$NetOut[3]",
             'NetOut4'=>"$NetOut[4]",
             'NetOut5'=>"$NetOut[5]",
             'NetOut6'=>"$NetOut[6]",
             'NetOut7'=>"$NetOut[7]",
             'NetOut8'=>"$NetOut[8]",
             'NetOut9'=>"$NetOut[9]",
             'NetOut10'=>"$NetOut[10]",
             'NetInput2'=>"$NetInput[2]",
             'NetInput3'=>"$NetInput[3]",
             'NetInput4'=>"$NetInput[4]",
             'NetInput5'=>"$NetInput[5]",
             'NetInput6'=>"$NetInput[6]",
             'NetInput7'=>"$NetInput[7]",
             'NetInput8'=>"$NetInput[8]",
             'NetInput9'=>"$NetInput[9]",
             'NetInput10'=>"$NetInput[10]",
             'NetOutSpeed2'=>"$NetOutSpeed[2]",
             'NetOutSpeed3'=>"$NetOutSpeed[3]",
             'NetOutSpeed4'=>"$NetOutSpeed[4]",
             'NetOutSpeed5'=>"$NetOutSpeed[5]",
             'NetInputSpeed2'=>"$NetInputSpeed[2]",
             'NetInputSpeed3'=>"$NetInputSpeed[3]",
             'NetInputSpeed4'=>"$NetInputSpeed[4]",
             'NetInputSpeed5'=>"$NetInputSpeed[5]");
  $jarr=json_encode($arr);
  $_GET['callback'] = htmlspecialchars($_GET['callback']);
  echo $_GET['callback'],'(',$jarr,')';
  exit;
}

//ajax调用计算CPU使用率
if ($_GET['act'] == "cpu")
{
  $duration = 1;

  $stat1=array_slice(preg_split('/\s+/', trim(array_shift(file('/proc/stat')))), 1);
  sleep($duration);
  $stat2=array_slice(preg_split('/\s+/', trim(array_shift(file('/proc/stat')))), 1);

  $diff=array_map(function ($x,$y) {return intval($y)-intval($x);}, $stat1, $stat2);
  $total=array_sum($diff)/100;

  $cpu=array();
  $cpu['user'] = $diff[0]/$total;
  $cpu['nice'] = $diff[1]/$total;
  $cpu['sys'] = $diff[2]/$total;
  $cpu['idle'] = $diff[3]/$total;
  $cpu['iowait'] = $diff[4]/$total;
  $cpu['irq'] = $diff[5]/$total;
  $cpu['softirq'] = $diff[6]/$total;
  $cpu['steal'] = $diff[7]/$total;

  $jarr=json_encode($cpu);
  $_GET['callback'] = htmlspecialchars($_GET['callback']);
  echo $_GET['callback'],'(',$jarr,')';
  exit;
}

//调用ipip.net取得IP位置
if ($_GET['act'] == "iploc")
{
  $ip = $_SERVER['REMOTE_ADDR'];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://www.ipip.net/ip.html");
  curl_setopt($ch, CURLOPT_REFERER, "https://www.ipip.net/ip.html");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_USERAGENT, "curl/7.47.0");
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, "ip=".$ip);
  $result = curl_exec($ch);
  curl_close($ch);

  $jarr = array();
  if (preg_match("/<span id=\"myself\">\s*(.+?)\s*</", $result, $matches))
  {
    array_push($jarr, $matches[1]);
  }
  preg_match_all('/<div style=".*?color:red;.*?">(.+?)<\/div>/', $result, $matches);
  if (count($matches) > 1)
  {
    array_push($jarr, preg_replace('/\s+/', '', end($matches[1])));
  }

  $_GET['callback'] = htmlspecialchars($_GET['callback']);
  echo $_GET['callback'],'(',json_encode($jarr),')';
  exit;
}

?>
<!DOCTYPE html>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?php echo $_SERVER['SERVER_NAME']; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<link rel="dns-prefetch" href="//<?php $parts = parse_url(JQUERY_JS_URL); echo $parts['host']; ?>" />


<a id="w_top"></a>

<div class="container">
<table class="table table-striped table-bordered table-hover table-condensed">
  <tr>
    <th><a href="?act=phpinfo">PHP Info</a></th>
    <th><a href="/files/">文件下载</a></th>
    <th><a href="//gateway.<?php echo $_SERVER['HTTP_HOST'];?>">网关管理</a></th>
    <th><a href="//grafana.<?php echo $_SERVER['HTTP_HOST'];?>/dashboard/db/system-overview">性能监控</a></th>
  </tr>
</table>

<!--服务器相关参数-->
<table class="table table-striped table-bordered table-hover table-condensed">
  <tr><th colspan="4">服务器参数</th></tr>
  <tr>
    <td>服务器域名/IP 地址</td>
    <td colspan="3"><?php echo @get_current_user();?> - <?php echo $_SERVER['SERVER_NAME'];?>(<?php echo @gethostbyname($_SERVER['SERVER_NAME']); ?>)&nbsp;&nbsp;你的 IP 地址是：<?php echo @$_SERVER['REMOTE_ADDR'];?> (<span id="iploc">未知位置</span>)</td>
  </tr>
  <tr>
    <td>服务器标识</td>
    <td colspan="3"><?php if($sysInfo['win_n'] != ''){echo $sysInfo['win_n'];}else{echo @php_uname();};?></td>
  </tr>
  <tr>
    <td>服务器操作系统</td>
    <td><?php echo @get_dist_name(); ?> &nbsp;内核版本：<?php if('/'==DIRECTORY_SEPARATOR){$os = explode(' ',php_uname()); echo $os[2];}else{echo $os[1];} ?></td>
    <td>服务器解译引擎</td>
    <td><?php echo $_SERVER['SERVER_SOFTWARE'];?></td>
  </tr>
  <tr>
    <td>服务器语言</td>
    <td><?php $lc_ctype = setlocale(LC_CTYPE,0); echo $lc_ctype=='C'?'POSIX':$lc_ctype;?></td>
    <td>服务器端口</td>
    <td><?php echo $_SERVER['SERVER_PORT'];?></td>
  </tr>
  <tr>
    <td>服务器主机名</td>
    <td><?php if('/'==DIRECTORY_SEPARATOR ){echo $os[1];}else{echo $os[2];} ?></td>
    <td>管理员邮箱</td>
    <td><?php echo $_SERVER['SERVER_ADMIN'];?></td>
  </tr>
  <tr>
    <td>探针路径</td>
    <td colspan="3"><?php echo str_replace('\\','/',__FILE__)?str_replace('\\','/',__FILE__):$_SERVER['SCRIPT_FILENAME'];?></td>
  </tr>
</table>

<table class="table table-striped table-bordered table-hover table-condensed">
  <tr><th colspan="4">服务器实时数据</th></tr>
  <tr>
    <td>服务器当前时间</td>
    <td><span id="stime"><?php echo $stime;?></span></td>
    <td>服务器已运行时间</td>
    <td><span id="uptime"><?php echo $uptime;?></span></td>
  </tr>
  <tr>
    <td>CPU 型号 [<?php echo $sysInfo['cpu']['num'];?>核]</td>
    <td colspan="3"><?php echo $sysInfo['cpu']['model'];?></td>
  </tr>
  <tr>
    <td>CPU 指令集</td>
    <td colspan="3" style="word-wrap: break-word;width: 64em;"><?php echo $sysInfo['cpu']['flags'];?></td>
  </tr>
<?php if (isset($sysInfo['boardVendor'])) : ?>
  <tr>
    <td>主板型号</td>
    <td><?php echo $sysInfo['boardVendor'] . " " . $sysInfo['boardName'] . " " . $sysInfo['boardVersion'];?></td>
    <td>主板 BIOS</td>
    <td><?php echo $sysInfo['BIOSVendor'] . " " . $sysInfo['BIOSVersion'] . " " . $sysInfo['BIOSDate'];?></td>
  </tr>
<?php endif; ?>
<?php if (isset($sysInfo['diskModel'])) : ?>
  <tr>
    <td>硬盘型号</td>
    <td colspan="3"><?php echo $sysInfo['diskModel'] . " " . $sysInfo['diskVendor'];?></td>
  </tr>
<?php endif; ?>
  <tr>
    <td>CPU 使用状况</td>
    <td colspan="3">
      <span id="cpuUSER" class="text-info">0.0</span> user,
      <span id="cpuSYS" class="text-info">0.0</span> sys,
      <span id="cpuNICE">0.0</span> nice,
      <span id="cpuIDLE" class="text-info">99.9</span> idle,
      <span id="cpuIOWAIT">0.0</span> iowait,
      <span id="cpuIRQ">0.0</span> irq,
      <span id="cpuSOFTIRQ">0.0</span> softirq,
      <span id="cpuSTEAL">0.0</span> steal
      <div class="progress"><div id="barcpuPercent" class="progress-bar progress-bar-success" role="progressbar" style="width:1px" >&nbsp;</div></div>
    </td>
  </tr>
  <tr>
    <td>内存使用状况</td>
    <td colspan="3">
<?php
$tmp = array(
    'memTotal', 'memUsed', 'memFree', 'memPercent',
    'memCached', 'memRealPercent',
    'swapTotal', 'swapUsed', 'swapFree', 'swapPercent'
);
foreach ($tmp AS $v) {
    $sysInfo[$v] = $sysInfo[$v] ? $sysInfo[$v] : 0;
}
?>
          物理内存：共
          <span class="text-info"><?php echo $memTotal;?> </span>
           , 已用
          <span id="UsedMemory" class="text-info"><?php echo $mu;?></span>
          , 空闲
          <span id="FreeMemory" class="text-info"><?php echo $mf;?></span>
          , 使用率
          <span id="memPercent"><?php echo $memPercent;?></span>
          <div class="progress"><div id="barmemPercent" class="progress-bar progress-bar-success" role="progressbar" style="width:<?php echo $memPercent?>%" ></div></div>
<?php
//判断如果cache为0，不显示
if($sysInfo['memCached']>0)
{
?>
      Cache 化内存为 <span id="CachedMemory"><?php echo $mc;?></span>
      , 使用率
          <span id="memCachedPercent"><?php echo $memCachedPercent;?></span>
      % | Buffers 缓冲为  <span id="Buffers"><?php echo $mb;?></span>
          <div class="progress"><div id="barmemCachedPercent" class="progress-bar progress-bar-info" role="progressbar" style="width:<?php echo $memCachedPercent?>%" ></div></div>

          真实内存使用
          <span id="memRealUsed"><?php echo $memRealUsed;?></span>
      , 真实内存空闲
          <span id="memRealFree"><?php echo $memRealFree;?></span>
      , 使用率
          <span id="memRealPercent"><?php echo $memRealPercent;?></span>
          %
          <div class="progress"><div id="barmemRealPercent" class="progress-bar progress-bar-warning" role="progressbar" style="width:<?php echo $memRealPercent?>%" ></div></div>
<?php
}
//判断如果 SWAP 区为0，不显示
if($sysInfo['swapTotal']>0)
{
?>
          SWAP 区：共
          <?php echo $st;?>
          , 已使用
          <span id="swapUsed"><?php echo $su;?></span>
          , 空闲
          <span id="swapFree"><?php echo $sf;?></span>
          , 使用率
          <span id="swapPercent"><?php echo $swapPercent;?></span>
          %
          <div class="progress"><div id="barswapPercent" class="progress-bar progress-bar-danger" role="progressbar" style="width:<?php echo $swapPercent?>%" ></div> </div>

<?php
}
?>
    </td>
  </tr>
  <tr>
    <td>硬盘使用状况</td>
    <td colspan="3">
    总空间 <?php echo $dt;?>&nbsp;G，
    已用 <span id="useSpace"><?php echo $du;?></span>&nbsp;G，
    空闲 <span id="freeSpace"><?php echo $df;?></span>&nbsp;G，
    使用率 <span id="hdPercent"><?php echo $hdPercent;?></span>%
    <div class="progress"><div id="barhdPercent" class="progress-bar progress-bar-black" role="progressbar" style="width:<?php echo $hdPercent?>%" ></div> </div>
    </td>
  </tr>
  <tr>
    <td>系统平均负载</td>
    <td colspan="3" class="text-danger"><span id="loadAvg"><?php echo $load;?></span></td>
  </tr>
</table>

<?php if (false !== ($strs = @file("/proc/net/dev"))) : ?>
<table class="table table-striped table-bordered table-hover table-condensed">
    <tr><th colspan="5">网络使用状况</th></tr>
<?php for ($i = 2; $i < count($strs); $i++ ) : ?>
<?php preg_match_all( "/([^\s]+):[\s]{0,}(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/", $strs[$i], $info );?>
  <tr>
    <td style="width:13%"><?php echo $info[1][0]?> : </td>
    <td style="width:29%">入网: <span class="text-info" id="NetInput<?php echo $i?>"><?php echo $NetInput[$i]?></span></td>
    <td style="width:14%">实时: <span class="text-info" id="NetInputSpeed<?php echo $i?>">0B/s</span></td>
    <td style="width:29%">出网: <span class="text-info" id="NetOut<?php echo $i?>"><?php echo $NetOut[$i]?></span></td>
    <td style="width:14%">实时: <span class="text-info" id="NetOutSpeed<?php echo $i?>">0B/s</span></td>
  </tr>
<?php endfor; ?>
</table>
<?php endif; ?>

<?php if (0 < count($strs = array_splice(@file("/proc/net/arp"), 1))) : ?>
<table class="table table-striped table-bordered table-hover table-condensed">
    <tr>
      <th colspan="4">网络邻居</th>
    </tr>
<?php $seen = array(); ?>
<?php for ($i = 0; $i < count($strs); $i++ ) : ?>
<?php $info = preg_split('/\s+/', $strs[$i]); ?>
<?php if ('0x2' == $info[2] && !isset($seen[$info[3]])) : ?>
<?php $seen[$info[3]] = true; ?>
    <tr>
        <td><?php echo $info[0];?> </td>
        <td>MAC: <span class="text-info"><?php  echo $info[3];?></span></td>
        <td>类型: <span class="text-info"><?php echo $info[1]=='0x1'?'ether':$info[1];?></span></td>
        <td>接口: <span class="text-info"><?php echo $info[5];?></span></td>
    </tr>
<?php endif; ?>
<?php endfor; ?>
</table>
<?php endif; ?>

<a id="w_performance"></a>
<form action="<?php echo $_SERVER['PHP_SELF']."#w_performance";?>" method="post">
<!--服务器性能检测-->
<table class="table table-striped table-bordered table-hover table-condensed">
  <tr><th colspan="4">服务器性能检测</th></tr>
  <tr>
    <td>参照对象</td>
    <td>整数运算能力检测<br>(1+1运算300万次)</td>
    <td>浮点运算能力检测<br>(圆周率开平方300万次)</td>
    <td>数据I/O能力检测<br>(读取10K文件1万次)</td>
  </tr>
  <tr>
    <td>4 x Xeon L5520 @ 2.27GHz</td>
    <td>0.357秒</td>
    <td>0.802秒</td>
    <td>0.023秒</td>
  </tr>
  <tr>
    <td>8 x Xeon E5520 @ 2.27GHz</td>
    <td>0.431秒</td>
    <td>1.024秒</td>
    <td>0.034秒</td>
  </tr>
  <tr>
    <td>4 x Core i7 920 @ 2.67GHz</td>
    <td>0.421秒</td>
    <td>1.003秒</td>
    <td>0.038秒</td>
  </tr>
  <tr>
    <td>2 x Pentium4 3.00GHz</td>
    <td>0.521秒</td>
    <td>1.559秒</td>
    <td>0.054秒</td>
  </tr>
  <tr>
    <td>2 x Core2Duo E4600 @ 2.40GHz</td>
    <td>0.343秒</td>
    <td>0.761秒</td>
    <td>0.023秒</td>
  </tr>
  <tr>
    <td>4 x Xeon E5530 @ 2.40GHz</td>
    <td>0.535秒</td>
    <td>1.607秒</td>
    <td>0.058秒</td>
  </tr>
  <tr>
    <td>本台服务器</td>
    <td><?php echo $valInt;?><br><input class="btn btn-primary btn-xs" name="act" type="submit" value="整型测试"></td>
    <td><?php echo $valFloat;?><br><input class="btn btn-primary btn-xs" name="act" type="submit" value="浮点测试"></td>
    <td><?php echo $valIo;?><br><input class="btn btn-primary btn-xs" name="act" type="submit" value="IO测试"></td>
  </tr>
</table>
<input type="hidden" name="pInt" value="<?php echo $valInt;?>">
<input type="hidden" name="pFloat" value="<?php echo $valFloat;?>">
<input type="hidden" name="pIo" value="<?php echo $valIo;?>">

<a id="w_networkspeed"></a>
<!--网络速度测试-->
<table class="table table-striped table-bordered table-hover table-condensed">
  <tr><th colspan="2">网络速度测试</th></tr>
  <tr>
  <td style="width:20%"><input name="act" type="submit" class="btn btn-primary btn-xs" value="开始测试"><br>
  向客户端传送 1MB 字节数据<br>
  带宽比例按理想值计算
  </td>

  <td>
  <table style="border-collapse: collapse; width:100%">
    <tr>
    <td>带宽</td>
    <td>1M</td>
    <td>2M</td>
    <td>3M</td>
    <td>4M</td>
    <td>5M</td>
    <td>6M</td>
    <td>7M</td>
    <td>8M</td>
    </tr>
   <tr>
    <td colspan="9">
    <div class="progress"><div class="progress-bar progress-bar-success" role="progressbar" style="width:<?php echo (isset($_GET['speed']))?($speed/1024/8*100):0; ?>%"></div></div>
   </td>
  </tr>
  </table>
  <?php echo (isset($_GET['speed']))?"下载1000KB数据用时 <span class='text-info'>".$_GET['speed']."</span> 毫秒，下载速度："."<span class='text-info'>".$speed."</span>"." kb/s，需测试多次取平均值，超过8M直接看下载速度":"<span class='text-info'>&nbsp;未探测&nbsp;</span>" ?>
  </td>
  </tr>
</table>
</form>

<table class="table table-striped table-bordered table-hover table-condensed">
  <tr>
    <td>PHP探针(雅黑修改版) v1.0</td>
    <td><?php $run_time = sprintf('%0.4f', microtime_float() - $time_start);?>Processed in <?php echo $run_time?> seconds. <?php echo memory_usage();?> memory usage.</td>
    <td><a href="#w_top">返回顶部</a></td>
  </tr>
</table>

</div>

<link href="<?php echo BOOTSTRAP_CSS_URL; ?>" rel="stylesheet">
<style>
<!--
.table-condensed>thead>tr>th,
.table-condensed>tbody>tr>th,
.table-condensed>tfoot>tr>th,
.table-condensed>thead>tr>td,
.table-condensed>tbody>tr>td,
.table-condensed>tfoot>tr>td {
    padding: 3px;
}
.progress-bar-black {
  background-color: #333;
}
.progress {
  height:10px;
  width:90%;
}
body {
  font-family: Tahoma, "Microsoft Yahei", Arial, Serif;
}
-->
</style>

<script src="<?php echo JQUERY_JS_URL; ?>"></script>
<script>
<!--
$(document).ready(function(){getData();});
var OutSpeed2=<?php echo floor($NetOutSpeed[2]) ?>;
var OutSpeed3=<?php echo floor($NetOutSpeed[3]) ?>;
var OutSpeed4=<?php echo floor($NetOutSpeed[4]) ?>;
var OutSpeed5=<?php echo floor($NetOutSpeed[5]) ?>;
var InputSpeed2=<?php echo floor($NetInputSpeed[2]) ?>;
var InputSpeed3=<?php echo floor($NetInputSpeed[3]) ?>;
var InputSpeed4=<?php echo floor($NetInputSpeed[4]) ?>;
var InputSpeed5=<?php echo floor($NetInputSpeed[5]) ?>;
function getData()
{
  setTimeout("getData()", 1000);
  $.getJSON('?act=rt&callback=?', displayData);
}
function ForDight(Dight,How)
{
  if (Dight<0){
    var Last=0+"B/s";
  }else if (Dight<1024){
    var Last=Math.round(Dight*Math.pow(10,How))/Math.pow(10,How)+"B/s";
  }else if (Dight<1048576){
    Dight=Dight/1024;
    var Last=Math.round(Dight*Math.pow(10,How))/Math.pow(10,How)+"K/s";
  }else{
    Dight=Dight/1048576;
    var Last=Math.round(Dight*Math.pow(10,How))/Math.pow(10,How)+"M/s";
  }
  return Last;
}
function displayData(data)
{
  $("#useSpace").html(data.useSpace);
  $("#freeSpace").html(data.freeSpace);
  $("#hdPercent").html(data.hdPercent);
  $("#barhdPercent").width(data.barhdPercent);
  $("#TotalMemory").html(data.TotalMemory);
  $("#UsedMemory").html(data.UsedMemory);
  $("#FreeMemory").html(data.FreeMemory);
  $("#CachedMemory").html(data.CachedMemory);
  $("#Buffers").html(data.Buffers);
  $("#TotalSwap").html(data.TotalSwap);
  $("#swapUsed").html(data.swapUsed);
  $("#swapFree").html(data.swapFree);
  $("#swapPercent").html(data.swapPercent);
  $("#loadAvg").html(data.loadAvg);
  $("#uptime").html(data.uptime);
  $("#freetime").html(data.freetime);
  $("#stime").html(data.stime);
  $("#bjtime").html(data.bjtime);
  $("#memRealUsed").html(data.memRealUsed);
  $("#memRealFree").html(data.memRealFree);
  $("#memRealPercent").html(data.memRealPercent);
  $("#memPercent").html(data.memPercent);
  $("#barmemPercent").width(data.memPercent);
  $("#barmemRealPercent").width(data.barmemRealPercent);
  $("#memCachedPercent").html(data.memCachedPercent);
  $("#barmemCachedPercent").width(data.barmemCachedPercent);
  $("#barswapPercent").width(data.barswapPercent);
  $("#NetOut2").html(data.NetOut2);
  $("#NetOut3").html(data.NetOut3);
  $("#NetOut4").html(data.NetOut4);
  $("#NetOut5").html(data.NetOut5);
  $("#NetOut6").html(data.NetOut6);
  $("#NetOut7").html(data.NetOut7);
  $("#NetOut8").html(data.NetOut8);
  $("#NetOut9").html(data.NetOut9);
  $("#NetOut10").html(data.NetOut10);
  $("#NetInput2").html(data.NetInput2);
  $("#NetInput3").html(data.NetInput3);
  $("#NetInput4").html(data.NetInput4);
  $("#NetInput5").html(data.NetInput5);
  $("#NetInput6").html(data.NetInput6);
  $("#NetInput7").html(data.NetInput7);
  $("#NetInput8").html(data.NetInput8);
  $("#NetInput9").html(data.NetInput10);
  $("#NetOutSpeed2").html(ForDight((data.NetOutSpeed2-OutSpeed2),3)); OutSpeed2=data.NetOutSpeed2;
  $("#NetOutSpeed3").html(ForDight((data.NetOutSpeed3-OutSpeed3),3)); OutSpeed3=data.NetOutSpeed3;
  $("#NetOutSpeed4").html(ForDight((data.NetOutSpeed4-OutSpeed4),3)); OutSpeed4=data.NetOutSpeed4;
  $("#NetOutSpeed5").html(ForDight((data.NetOutSpeed5-OutSpeed5),3)); OutSpeed5=data.NetOutSpeed5;
  $("#NetInputSpeed2").html(ForDight((data.NetInputSpeed2-InputSpeed2),3)); InputSpeed2=data.NetInputSpeed2;
  $("#NetInputSpeed3").html(ForDight((data.NetInputSpeed3-InputSpeed3),3)); InputSpeed3=data.NetInputSpeed3;
  $("#NetInputSpeed4").html(ForDight((data.NetInputSpeed4-InputSpeed4),3)); InputSpeed4=data.NetInputSpeed4;
  $("#NetInputSpeed5").html(ForDight((data.NetInputSpeed5-InputSpeed5),3)); InputSpeed5=data.NetInputSpeed5;
}

$(document).ready(function(){getCPUData();});
function getCPUData()
{
  setTimeout("getCPUData()", 2000);
  $.getJSON('?act=cpu&callback=?', function (data) {
    $("#cpuUSER").html(data.user.toFixed(1));
    $("#cpuSYS").html(data.sys.toFixed(1));
    $("#cpuNICE").html(data.nice.toFixed(1));
    $("#cpuIDLE").html(data.idle.toFixed(1).substring(0,4));
    $("#cpuIOWAIT").html(data.iowait.toFixed(1));
    $("#cpuIRQ").html(data.irq.toFixed(1));
    $("#cpuSOFTIRQ").html(data.softirq.toFixed(1));
    $("#cpuSTEAL").html(data.steal.toFixed(1));

    usage = 100 - (data.idle+data.iowait);
    if (usage > 75)
      $("#barcpuPercent").width(usage+'%').removeClass().addClass('progress-bar-danger');
    else if (usage > 50)
      $("#barcpuPercent").width(usage+'%').removeClass().addClass('progress-bar-warning');
    else if (usage > 25)
      $("#barcpuPercent").width(usage+'%').removeClass().addClass('progress-bar-info');
    else
      $("#barcpuPercent").width(usage+'%').removeClass().addClass('progress-bar-success');
  });
}

$(document).ready(function(){
  $.getJSON('?act=iploc&callback=?', function (data) {
    if (data[1] != null && data[1].substring(0,4) == data[0].substring(0,4)) {
      $("#iploc").html(data[1] + data[0].replace(/^\S+/, ''));
    } else {
      $("#iploc").html(data[0]);
    }
  });
});
-->
</script>
