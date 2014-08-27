<?
define("FILE", "index.php");

require "config.php";
require "functions.php";

function error_message($action, $num)
{
	if ($action == "create") header("Location: ?reg=1&error=$num");
	if ($action == "send") header("Location: ?retrieve=1&error=$num");
	die();
}

$reg = ($_GET['reg']) ? true : false;
$retrieve = ($retrieve_enabled and $_GET['retrieve'] and !$reg) ? true : false;
$error = $_GET['error'];

if ($reg and $error) {
	switch ($error) {
		case 1: $error_text = "Логин может состоять только из&nbsp;латинских букв и&nbsp;цифр."; break;
		case 2: $error_text = "Пароль может состоять только из&nbsp;латинских букв и&nbsp;цифр."; break;
		case 3: $error_text = "Такой логин уже используется, придумайте другой."; break;
		case 4: $error_text = "Вы ввели некорректный e-mail."; break;
		default: $error_text = ""; break;
	}
}
if ($retrieve and $error) {
	switch ($error) {
		case 1: $error_text = "Такого пользователя нет."; break;
	 	case 2: $error_text = "Нет возможности восстановить пароль: e-mail не&nbsp;был введен при регистрации."; break;
		case 3: $error_text = "На этого пользователя записан другой e-mail адрес."; break;
		default: $error_text = ""; break;
	}
}

$users = array();
$users_content = get_file_content($users_filename);
if ($users_content !== NULL) $users = unserialize($users_content);

if ($retrieve and $_POST['send']) {
	$user = array();
	$user['r_login'] = strtolower($_POST['r_login']);
	$user['r_email'] = trim($_POST['r_email']);
	foreach ($users as $key => $entrie) {
		$some_user = unserialize($entrie);
		if ($user['r_login'] == $some_user['login']) {
			$this_user = unserialize($entrie);
		}
	}
	if (!$this_user['password']) error_message("send", 1);
	if (!$this_user['email']) error_message("send", 2);
	if (strtolower($user['r_email']) == strtolower($this_user['email'])) {
		$message =
			"Здравствуйте.\n\n".
			"Вы запросили забытый пароль к веб-снифферу, расположенному по адресу $sniffer_path.\n\n".
			"Ваш пароль: ".$this_user['password']."\n\n".
			"Постарайтесь в будущем его не терять.\n\n".
			"--\n".
			"С уважением,\n".
			"Sniffer Bot";
		$send_ok = false;
		if (@mail($this_user['email'], "Забытый пароль", $message, "From: Sniffer Bot <$from_email>\nContent-Type: text/plain; charset=windows-1251")) $send_ok = true;
	}
		else error_message("send", 3);
}

if ($reg and $_POST['create']) {
	$user['login'] = strtolower($_POST['login']);
	if (!preg_match("/^[a-zA-Z1-9_]{1,20}$/", $user['login'])) error_message("create", 1);
	if ($user['login'] == "refresh" or $user['login'] == "i" or $user['login'] == "s") error_message("create", 3);
	$user['password'] = trim($_POST['password']);
	if (!preg_match("/^[a-zA-Z1-9_]{1,20}$/", $user['password'])) error_message("create", 2);
	foreach ($users as $key => $entrie) {
		$some_user = unserialize($entrie);
		if ($some_user['login'] == $user['login']) error_message("create", 3);
	}
	$user['email'] = checkmail($_POST['email']);
	if ($user['email'] != $_POST['email']) error_message("create", 4);
	$user['last_visited'] = time();
	$entrie = serialize($user);
	$users[] = $entrie;
	$users_content = serialize($users);
	put_file_content($users_filename, $users_content);
}

if ($styles_enabled) {
	if ($_GET['style'] and file_exists("styles/".$_GET['style'].".css")) $style_filename = $_GET['style'].".css";
		elseif ($_COOKIE['s_style'] and file_exists("styles/".$_COOKIE['s_style'].".css")) $style_filename = $_COOKIE['s_style'].".css";
	$current_style = substr($style_filename, 0, strpos($style_filename, ".css"));
	setcookie("s_style", $current_style, time()+7776000);
}

if ($reg) $page_title = "Регистрация";
	elseif ($retrieve) $page_title = "Восстановление пароля";
		else $page_title = "Вход";

$users_num = count($users);
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<title>Cниффер - <?=$page_title?></title>
<link rel="stylesheet" type="text/css" href="styles/<?=$style_filename?>">

<style type="text/css">
.input {font-size:12px;}
.visible {display:;}
.hidden {display:none;}
</style>

<? if (!$_POST['send'] and !$_POST['create']) { ?>
<script type="text/javascript">
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

function regAccount()
{
	showElement('reg');
	hideElement('reg_link');
<? if ($retrieve_enabled) { ?>
	hideElement('retrieve');
	showElement('retrieve_link');
<? } ?>
	return false;
}

<? if ($retrieve_enabled) { ?>
function retrievePass()
{
	showElement('retrieve');
	hideElement('retrieve_link');
	hideElement('reg');
	showElement('reg_link');
	return false;
}
<? } ?>

<? if ($retrieve_enabled) { ?>
function checkForm1() {
	if (!document.getElementById('r_login').value) {
		alert('Вы не ввели логин.');
		document.getElementById('login').focus();
		return false;
	}
	if (!document.getElementById('r_email').value) {
		alert('Вы не ввели e-mail адрес.');
		document.getElementById('r_email').focus();
		return false;
	}
	return true;
}
<? } ?>

function checkForm2() {
	if (!document.getElementById('login').value) {
		alert('Вы не ввели логин.');
		document.getElementById('login').focus();
		return false;
	}
	if (!document.getElementById('password').value) {
		alert('А пароль?');
		document.getElementById('password').focus();
		return false;
	}
	if (document.getElementById('password').value != document.getElementById('re_password').value) {
		alert('Пароли не совпадают.');
		document.getElementById('password').focus();
		return false;
	}
	return true;
}
</script>
<? } ?>
</head>

<body>
<p><b><a href="log.php">Войти в аккаунт &raquo;</a></b><br>
<? if ($reg and $_POST['create']) { ?>
<p><b class="col"><?=$user['login']?></b>, вы зарегистрированы.<br>
<? if ($user['email']) { ?>На&nbsp;вас записан следующий e-mail адрес: <a href="mailto:<?=$user['email']?>"><?=$user['email']?></a><? } ?></p>
<p>Адрес вашей картинки-сниффера: <a href="<?=$user['login']?>.gif" target="_blank"><?=$sniffer_path?><?=$user['login']?>.gif</a><br>
Прямая ссылка на лог: <a href="<?=$user['login']?>/log.php" target="_blank"><?=$sniffer_path?><?=$user['login']?>/log.php</a></p>
<p><i>Внимание:</i> если вы&nbsp;не&nbsp;будете пользоваться аккаунтом в&nbsp;течение месяца, он&nbsp;может быть удален.</p>
<a href="index.php">&larr; Назад</a></p>
<? }
if ($retrieve and $_POST['send']) { ?>
<p><? if ($send_ok) { ?>Пароль успешно выслан на адрес <a href="mailto:<?=$this_user['email']?>"><?=$this_user['email']?></a>.<? } else { ?>Возникли неполадки при отправке письма на адрес <a href="mailto:<?=$this_user['email']?>"><?=$this_user['email']?></a>. Обратитесь к администратору.<? } ?><br>
<a href="index.php">&larr; Назад</a></p>
<? }
if (!$_POST['send'] and !$_POST['create']) { ?>
<span id="reg" <? if (!$reg) { ?>class="hidden"<? } ?>>
<form action="?reg=1" method="post" onSubmit="return checkForm2();">
<input type="hidden" name="create" value="1">
<b>Регистрация</b><? if ($reg and $error) { ?>&nbsp;&#151; <span class="col"><?=$error_text?></span><? } ?>
<table>
<tr>
<td>Логин: <span class="col">*</span></td><td><input type="text" name="login" id="login" maxlength="20" class="input"></td>
</tr>
<tr>
<td>Пароль: <span class="col">*</span></td><td><input type="password" name="password" id="password" maxlength="20" class="input"></td>
</tr>
<tr>
<td>Еще раз пароль: <span class="col">*</span></td><td><input type="password" name="re_password" id="re_password" class="input"></td>
</tr>
<tr>
<td>E-mail:<br></td><td valign="top"><input type="text" name="email" id="email" class="input"></td>
</tr>
<tr>
<td colspan="2" class="small" style="padding-top:0;">(понадобится, если вы&nbsp;забудете пароль)</td>
</tr>
<tr>
<td colspan="2"><input type="submit" value="Создать аккаунт" class="input" style="font-weight:bold;"></td>
</tr>
</table>
</form>
</span>
<? if ($retrieve_enabled) { ?>
<span id="retrieve" <? if (!$retrieve) { ?>class="hidden"<? } ?>>
<form action="?retrieve=1" method="post" onSubmit="return checkForm1();">
<input type="hidden" name="send" value="1">
<b>Восстановление пароля</b><? if ($retrieve and $error) { ?>&nbsp;&#151; <span class="col"><?=$error_text?></span><? } ?>
<table>
<tr>
<td>Логин:</td><td><input type="text" name="r_login" id="r_login" maxlength="20" class="input"></td>
</tr>
<tr>
<td>E-mail:</td><td><input type="text" name="r_email" id="r_email" maxlength="20" class="input"></td>
</tr>
<tr>
<td colspan="2"><input type="submit" value="Выслать пароль" class="input"></td>
</tr>
</table>
</form>
</span>
<? } ?>
<p>
<span id="reg_link" <? if ($reg) { ?>class="hidden"<? } ?>>
<li type="circle"><a href="?reg=1" onClick="return regAccount();">Регистрация</a></li>
</span>
<? if ($retrieve_enabled) { ?>
<span id="retrieve_link" <? if ($retrieve) { ?>class="hidden"<? } ?>>
<li type="circle"><a href="?retrieve=1" onClick="return retrievePass();">Забыли пароль?</a></li>
</span>
<? } ?>
<li type="circle"><a href="faq.php" onClick="window.open('faq.php', 'info', 'width=500, height=402, scrollbars=yes'); return false;">Справка (FAQ)</a></li>
</p>
<p>Всего пользователей в системе: <span class="col"><?=$users_num?></span>.</p>
<!--<p>Исходники этого сниффера можно <a href="http://kanick.ru/sniffer/#buy">купить</a>.</p>-->
<? } ?>
<p id="copyright">
php Based Sniffer 4.1 Multi<br>
&copy; <a href="http://kanick.ru">Kanick</a> 2005—2006</p>
</body>
</html>