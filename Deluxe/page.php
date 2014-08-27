<?
define("FILE", "page.php");

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

$url = stripslashes(urldecode($_GET['url']));
$cookies = stripslashes(urldecode($_GET['cookies']));
$gzip = ($_GET['gzip'] == "on") ? "on" : NULL;
$http = ($_GET['http'] == "1.1") ? "1.1" : "1.0";

preg_match("/^https?:\/\/([^\/]+)/i", $url, $matches);
$host = $matches[1];

if (empty($host)) die("Указан неверный URL.");
if (!$fp = @fsockopen($host, 80)) die("Хост $host не найден.");

fputs($fp, "GET $url HTTP/$http\r\n".
"Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, application/msword, application/vnd.ms-powerpoint, application/vnd.ms-excel, application/x-icq, */*\r\n".
"Accept-Language: ru\r\n".
"Accept-Encoding: deflate\r\n".
"User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)\r\n".
"Host: $host\r\n".
"Connection: Keep-Alive\r\n".
"Cookie: $cookies\r\n\r\n");
while (!feof($fp)) {
	$get .= fgets($fp);
	if (strpos($get, "\r\n\r\n") !== false) break;
}

if (!$gzip) {
	while (!feof($fp)) {
		$str = fgets($fp);
		if (!$base_added) {
			$last_str = $str;
			$str = preg_replace("/(<head>)/i", "\\1\n<base href=\"$url\">", $str);
			if ($str != $last_str) $base_added = true;
		}
		echo $str;
	}
}
elseif ($gzip) {
	while (!feof($fp)) $str .= fgets($fp);
	if (!$page = @gzinflate(substr($str, 2, strlen($str)))) die("Эта страница не является сжатой gzip.");
	$page = preg_replace("/(<head>)/i", "\\1\n<base href=\"$url\">", $page);
	echo $page;
}

fclose($fp);
?>