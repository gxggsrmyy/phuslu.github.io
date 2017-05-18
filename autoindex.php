<?php
define("PASSWORD", "123");
define("BOOTSTRAP_CSS", "https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css");
define("MARKED_JS", "https://cdn.bootcss.com/marked/0.3.6/marked.min.js");
define("GITHUB_MARKDOWN_CSS", "https://cdn.bootcss.com/github-markdown-css/2.6.0/github-markdown.min.css");

chdir($_SERVER['DOCUMENT_ROOT'] . '/' . (isset($_REQUEST['d']) ? $_REQUEST['d'] : dirname($_SERVER['SCRIPT_NAME'])));
//var_dump($_SERVER);
//var_dump($_FILES);
if (isset($_FILES['photo']) && !$_FILES['photo']['error'])
{
  if (PASSWORD != "" && $_POST['password'] != PASSWORD)
  {
    echo '<!DOCTYPE html><script>alert("incorrect password");location=location.href;</script>';
  }
  else
  {
    $filename = $_FILES['photo']['name'];
    if (preg_match('/\.(php|url)$/', $filename))
    {
      $filename .= '.txt';
    }

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $filename))
    {
      echo '<!DOCTYPE html><script>alert("upload failed, please check folder permisson");location=location.href;</script>';
    }
    else
    {
      echo '<!DOCTYPE html><script>location=location.href;</script>Upload Finished.';
    }
  }
}

function human_filesize($bytes, $decimals = 1) {
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  $hz = sprintf("%.{$decimals}f", $bytes / pow(1024, $factor));
  $unit = @$sz[$factor];
  if (($pos=strpos($hz, '.')) > 0)
  {
    if ($pos >= 3)
      $hz = substr($hz, 0, $pos);
    else if ($pos >= 2)
      $hz = substr($hz, 0, $pos+2);
    else if (substr($hz, $pos+1, 1) == '0')
      $hz = substr($hz, 0, -2);
  }
  $hz = preg_replace('/\\.0+$/', '', $hz);
  return $hz . ($unit == 'B' ? '' : $unit);
}

$files = scandir(".");
usort($files, function ($a, $b) {
    $aIsDir = is_dir($a);
    $bIsDir = is_dir($b);
    if ($aIsDir === $bIsDir)
        return strnatcmp($a, $b); // both are dirs or files
    elseif ($aIsDir && !$bIsDir)
        return -1; // if $a is dir - it should be before $b
    elseif (!$aIsDir && $bIsDir)
        return 1; // $b is dir, should be before $a
});

$info = array();
foreach ($files as $file)
{
  if ($file[0] == '.')
    continue;

  if ($file == 'index.php' && basename($_SERVER['SCRIPT_NAME']) == 'index.php')
    continue;

  if (strtolower($file) == 'readme.md')
    $readme_filename = $file;

  $item = array();
  $is_dir = is_dir($file);
  $item['link'] = $file;
  $item['display_name'] = htmlspecialchars($file);
  $item['mtime'] = date("d-M-Y H:i", filemtime($file));
  $item['size'] = $is_dir?'-':human_filesize(filesize($file));
  $item['class'] = $is_dir?'octicon file-directory':'octicon file';

  if (preg_match('/\.url$/', $file))
  {
    $ini_array = parse_ini_file($file, true);
    $item['link'] = $ini_array['InternetShortcut']['URL'];
    $item['display_name'] = preg_replace('/\.url$/', '', $file);
    $item['class'] = 'octicon bookmark';
    $item['is_url'] = true;
  }
  else if (preg_match('/\.(zip|7z|bz2|gz|tar|tgz|tbz2)$/', $file))
  {
    $item['class'] = 'octicon file-zip';
  }
  else if (preg_match('/\.(jpg|png|bmp|gif|ico|webp|flv|mp4|mkv|avi|mkv)$/', $file))
  {
    $item['class'] = 'octicon file-media';
  }
  

  array_push($info, $item);
}

//var_dump($info); die;

$index_of_string = '';
$parts = explode('/', trim(preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']), '/'));
while (count($parts))
{
 $link = '/' . implode('/', $parts) . '/';
 $name = urldecode(array_pop($parts));
 $index_of_string = "/<a href='$link'>$name</a>" . $index_of_string;
}
$index_of_string = $index_of_string;

$is_webkit = stripos($_SERVER["HTTP_USER_AGENT"], 'webkit/') > 0;

?>

<!DOCTYPE html>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Index of <?php echo urldecode($_SERVER['REQUEST_URI']); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<script>if(top != self) top.location.replace(location);</script>
<link href="<?php echo BOOTSTRAP_CSS; ?>" rel="stylesheet">
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
body {
  font-family: Tahoma, "Microsoft Yahei", Arial, Serif;
}
-->
</style>

<style>
.octicon {
background-position: center left;
background-repeat: no-repeat;
padding-left: 16px;
}
.reply {
<?php if ($is_webkit): ?>
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg width='14px' height='16px' viewBox='0 0 14 16' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'><path d='M6,3.5 C9.92,3.94 14,6.625 14,13.5 C11.688,8.438 9.25,7.5 6,7.5 L6,11 L0.5,5.5 L6,0 L6,3.5 Z' fill='#7D94AE' /></svg>");
<?php else: ?>
background-image: linear-gradient(transparent,transparent),url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAQCAMAAAARSr4IAAAAeFBMVEUAAACAgIBVqqqAgL+AkraAj697lbB8lrF7k6x+la1+lK5+la59k659la19la58la19lK58la99lK59lK1+la5+lK19k659la1+lK5+lK99lK19k659lK59lK59la99lK5+lK5+lK1+lK59lK59lK59lK59lK7///+yznbQAAAAJnRSTlMAAgMEDhAdJzRBX2Voanh9fn+Bg4SGh4mKjJmhp7q7v8HFydjx/b/b+a0AAAABYktHRCctD6gjAAAAXElEQVQIHX3BRxLCMAAAsSWE3nsn1P3/E7E9ZHAuSCRTcjvJ7JWfmTru8zU3eQ5bBANrl5Jgae3WJliph9P9rQuitQK9yleHaCNBcXREsiUqrmdy3QcNExpK/voA8ZEItj9lCn0AAAAASUVORK5CYII=");
<?php endif ?>
}
.file {
<?php if ($is_webkit): ?>
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg width='12px' height='16px' viewBox='0 0 12 16' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'><path d='M6,5 L2,5 L2,4 L6,4 L6,5 L6,5 Z M2,8 L9,8 L9,7 L2,7 L2,8 L2,8 Z M2,10 L9,10 L9,9 L2,9 L2,10 L2,10 Z M2,12 L9,12 L9,11 L2,11 L2,12 L2,12 Z M12,4.5 L12,14 C12,14.55 11.55,15 11,15 L1,15 C0.45,15 0,14.55 0,14 L0,2 C0,1.45 0.45,1 1,1 L8.5,1 L12,4.5 L12,4.5 Z M11,5 L8,2 L1,2 L1,14 L11,14 L11,5 L11,5 Z' fill='#7D94AE' /></svg>");
<?php else: ?>
background-image: linear-gradient(transparent,transparent),url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAQBAMAAADQT4M0AAAALVBMVEUAAACAkq2AkaqAlbF+k61+lK59lK5+lK5+lK59lK19lK59lK59lK59lK7///94FNP0AAAADXRSTlMAHB4kgIi/wcPb3N/iJ7nCaQAAAAFiS0dEDm+9ME8AAAA2SURBVAjXY2CAgNy7d+9OZGC4AGRGKYAplsMMF+7eZWCIBvMYeOEUUOUF4ngQqhcoePc61B4A1WogLkcr910AAAAASUVORK5CYII=");
<?php endif ?>
}
.file-zip {
<?php if ($is_webkit): ?>
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg width='12px' height='16px' viewBox='0 0 12 16' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'><path d='M8.5,1 L1,1 C0.44771525,1 0,1.44771525 0,2 L0,14 C0,14.5522847 0.44771525,15 1,15 L11,15 C11.5522847,15 12,14.5522847 12,14 L12,4.5 L8.5,1 Z M11,14 L1,14 L1,2 L4,2 L4,3 L5,3 L5,2 L8,2 L11,5 L11,14 L11,14 Z M5,4 L5,3 L6,3 L6,4 L5,4 L5,4 Z M4,4 L5,4 L5,5 L4,5 L4,4 L4,4 Z M5,6 L5,5 L6,5 L6,6 L5,6 L5,6 Z M4,6 L5,6 L5,7 L4,7 L4,6 L4,6 Z M5,8 L5,7 L6,7 L6,8 L5,8 L5,8 Z M4,9.28 C3.38491093,9.63510459 3.00428692,10.2897779 3,11 L3,12 L7,12 L7,11 C7,9.8954305 6.1045695,9 5,9 L5,8 L4,8 L4,9.28 L4,9.28 Z M6,10 L6,11 L4,11 L4,10 L6,10 L6,10 Z' fill='#7D94AE' /></svg>");
<?php else: ?>
background-image: linear-gradient(transparent,transparent),url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAQCAMAAAAVv241AAAAM1BMVEUAAACAkq2Akap7lK17lK18lKx+lK59lK5+lK5+lK59lK19lK59lK59lK59lK59lK7///+xudmjAAAAD3RSTlMAHB4fPkqIv8HD29zd3+A2GAq6AAAAAWJLR0QQlbINLAAAAEFJREFUCB0FwYcBgDAAwCBcdZv/vxUAgL2quiaEMO6ZIIxnEQLjFQQSAhIEEgISa88GiYsPElWQAEgA5KyqqgMA/F0fA3rVuoUZAAAAAElFTkSuQmCC");
<?php endif ?>
}
.file-media {
<?php if ($is_webkit): ?>
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg width='12px' height='16px' viewBox='0 0 12 16' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'><path d='M6,5 L8,5 L8,7 L6,7 L6,5 L6,5 Z M12,4.5 L12,14 C12,14.55 11.55,15 11,15 L1,15 C0.45,15 0,14.55 0,14 L0,2 C0,1.45 0.45,1 1,1 L8.5,1 L12,4.5 L12,4.5 Z M11,5 L8,2 L1,2 L1,13 L4,8 L6,12 L8,10 L11,13 L11,5 L11,5 Z' fill='#7D94AE' /></svg>");
<?php else: ?>
background-image: linear-gradient(transparent,transparent),url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAQCAMAAAAVv241AAAASFBMVEUAAACAmbOAkq2AkaqAlbGAlK59k618la58lK58k65+k61+lK59lK59lK5+lK5+lK59lK19lK59lK59lK99lK59lK59lK7////0+4niAAAAFnRSTlMAChweJCY7SHd7gIizv8HD29zf4eL43qOWEgAAAAFiS0dEFwvWmI8AAABOSURBVAgdVcEHEkAwAADB0wnR3f+fatRhl5/oZUwBubRTBnILa4482hk5KCByUEDkJUJVchJJlshJpNGSugax2DQGDYidtyB+MPjq+doBOD4G5atFHdAAAAAASUVORK5CYII=");
<?php endif ?>
}
.file-directory {
<?php if ($is_webkit): ?>
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg width='14px' height='16px' viewBox='0 0 14 16' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'><path d='M13,4 L7,4 L7,3 C7,2.34 6.69,2 6,2 L1,2 C0.45,2 0,2.45 0,3 L0,13 C0,13.55 0.45,14 1,14 L13,14 C13.55,14 14,13.55 14,13 L14,5 C14,4.45 13.55,4 13,4 L13,4 Z M6,4 L1,4 L1,3 L6,3 L6,4 L6,4 Z' fill='#7D94AE' /></svg>");
<?php else: ?>
background-image: linear-gradient(transparent,transparent),url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAQCAYAAAAmlE46AAAABmJLR0QA/wD/AP+gvaeTAAAATElEQVQokWNgoDdgrJ2ybj8DA4MDFrlrzTlB2jh11k5Z958UcWQb8SrAAfYxkaGJgYGBwYlcjQyjGmmikZGB4QipmhgZGA6RayH5AACLIg/XosT5EgAAAABJRU5ErkJggg==");
<?php endif ?>
}
.bookmark {
<?php if ($is_webkit): ?>
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg width='10px' height='16px' viewBox='0 0 10 16' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'><path d='M9,0 L1,0 C0.27,0 0,0.27 0,1 L0,16 L5,12.91 L10,16 L10,1 C10,0.27 9.73,0 9,0 L9,0 Z M8.22,4.25 L6.36,5.61 L7.08,7.77 C7.14,7.99 7.06,8.05 6.88,7.94 L5,6.6 L3.12,7.94 C2.93,8.05 2.87,7.99 2.92,7.77 L3.64,5.61 L1.78,4.25 C1.61,4.09 1.64,4.02 1.87,4.02 L4.17,3.99 L4.87,1.83 L5.12,1.83 L5.82,3.99 L8.12,4.02 C8.35,4.02 8.39,4.1 8.21,4.25 L8.22,4.25 Z' id='Shape' fill='#7D94AE' /></svg>");
<?php else: ?>
background-image: linear-gradient(transparent,transparent),url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAQCAMAAAAYoR5yAAAAYFBMVEUAAACAgICAlap6ma19kq59krB7lK18la5+k65+la99k69+lK19la59lK99la58k659la59lK19lK59lK19lK59lK59lK59lK59lK59lK59lK59lK59lK59lK59lK7///9ZwL50AAAAHnRSTlMAAhgZLz0+UlVZXF1eZqOmqsjZ2+rs7fH3+Pn6+/1Z+ItmAAAAAWJLR0QfBQ0QvQAAAFFJREFUCB0FwYUBwgAAwLAOZ7h7/j+TpAvAOXi+INgfIFiuILd51eIuvpvafgnryWSNeI/X6/ghAAIgAAIg+P0gnGazE/LYDTXsHjpOq2p6/ANPqw6TnVNdgQAAAABJRU5ErkJggg==");
<?php endif ?>
}
.external {
background-position: center right;
background-repeat: no-repeat;
<?php if ($is_webkit): ?>
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12'><path fill='#fff' stroke='#06c' d='M1.5 4.518h5.982V10.5H1.5z'/><path d='M5.765 1H11v5.39L9.427 7.937l-1.31-1.31L5.393 9.35l-2.69-2.688 2.81-2.808L4.2 2.544z' fill='#06f'/><path d='M9.995 2.004l.022 4.885L8.2 5.07 5.32 7.95 4.09 6.723l2.882-2.88-1.85-1.852z' fill='#fff'/></svg>");
<?php else: ?>
background-image: linear-gradient(transparent,transparent),url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAMCAYAAABWdVznAAAABmJLR0QA/wD/AP+gvaeTAAABAElEQVQokZ2Qv0rDcBSFvxuiIojWSRDcOgviAwjOARGskzhJMvnnCdxcnCq6qDjZRQcfQFALTkJbN9EOjo5uEVqS33EI1bTpomc89557Pw78UVZwKhqlxDXGSmF2ZsV9Qh1uXEhJqh85JxFKAN6Qrwe1J1pRLYeRu+sDWNhIzDJbatvYSNnWFvtvmYF+MRrqJk63L06lXemmlaHU36S9qwzJizIkv5f5jI3KKVTXYXUB6m0IThxxx/uIu8x6xrPLB2YmYT+A4weYGoetS/jqvIu0vHT+SJWUTfJIPR3dSRPb0v2rkxc108FG/EFjZxmCeZibHtJfPmBRszDsK+a/+gakjnkHYspRSgAAAABJRU5ErkJggg==");
<?php endif ?>
padding-right: 13px;
}
</style>

<div class="container">
<table class="table table-striped table-bordered table-hover table-condensed">
  <tr><th colspan="3">Index of <?php echo $index_of_string; ?></th></tr>
</table>

<table class="table table-striped table-bordered table-hover table-condensed">
  <tr>
    <td colspan="3">
    <form id="upload" enctype="multipart/form-data" method="POST">
      <input type="submit" style="display:none" id="upload_button" value="Upload File">
      <span id="upload_name"></span><span id="password0" style="display:none">
      <input type="password" id="password" name="password" placeholder="password">
      </span>
      <input type="hidden" name="d" value="<?php echo $_REQUEST['d'];?>">
      <input type="file" id="photo" name="photo">
      <noscript><input type="submit" value="Upload"></noscript>
    </form>
    </td>
  </tr>
  <tr>
    <td><a href="../" class="octicon reply" >..</a></td>
    <td></td>
    <td>-</td>
  </tr>
<?php foreach ($info as $item): ?>
  <tr>
    <td><a href="<?php echo $item['link']; ?>" class="<?php echo $item['class']; ?>" <?php echo isset($item['is_url'])?'target="_blank"':''; ?>><?php echo $item['display_name']; ?></a><?php echo isset($item['is_url'])?'<span class="external"/>':''; ?></td>
    <td><?php echo $item['mtime']; ?></td>
    <td><?php echo $item['size']; ?></td>
  </tr>
<?php endforeach; ?>
</table>

<script>
$ = typeof($) == "undefined" ? (function (x) {return document.getElementById(x.replace(/^#/, ''))}) : $;
$("#photo").onchange = function () {
  if (/^zh/.test((navigator.language || navigator.userLanguage))) {
    $('#upload_button').value = "上传文件";
  }
  $('#upload_button').style.display = "";
  $('#upload_button').onclick = function () {
    $('#upload').submit();
  };
  $('#upload_name').innerHTML = ' ' + $('#photo').value;
  $('#photo').style.display = "none";
  <?php if (PASSWORD != ""): ?>
  $('#password0').style.display = "";
  if (!navigator.userAgent.match(/Trident/g) && !navigator.userAgent.match(/MSIE/g))
  {
    $('#password').focus();
  }
  $('#upload').onsubmit = function () {
    $('#password').readOnly = true
    $('#password').style.backgroundColor = '#ebebe4'
  }
  <?php endif ?>
};
</script>

<?php if (isset($readme_filename)) : ?>
<textarea id="readme-text" style="display:none"><?php echo file_get_contents($readme_filename); ?></textarea>
<table class="table table-striped table-bordered table-condensed">
  <tr><th colspan="3"><?php echo $readme_filename; ?></th></tr>
  <tr><td colspan="3">
    <div id="readme" class="markdown-body"></div>
  </td></tr>
</table>

<script src="<?php echo MARKED_JS; ?>"></script>
<script>
  document.getElementById('readme').innerHTML = marked(document.getElementById('readme-text').value);
</script>

<link href="<?php echo GITHUB_MARKDOWN_CSS; ?>" rel="stylesheet">
<style>
.markdown-body {
  float: left;
  font-family: "ubuntu", "Tahoma", "Microsoft YaHei", arial,sans-serif;
}
</style>
<?php endif; ?>


</div>

