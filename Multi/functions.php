<?
function get_file_content($filename)
{
	if (!file_exists($filename)) fclose(fopen($filename, "w"));
	if (!file_exists($filename)) die("Файл $filename не найден. Создать его на ходу не удалось, так что придется это сделать вам.");
	$content = (filesize($filename) > 0) ? @fread(fopen($filename, "r"), filesize($filename)) : NULL;
	if ($content === false) die("Не удается получить доступ к $filename.<br>\n<br>\nПроверьте, правильно ли вы установили CHMOD (должно быть 666).");
	return $content;
}

function put_file_content($filename, $content)
{
	global $user;
	$fp = fopen($filename, "w");
	fputs($fp, $content);
	fclose($fp);
}

function del_entries($data, $user_data, $user2all, $select, $count)
{
	global $user, $data_filename;
	foreach ($select as $id) if (isset($user_data[$id])) unset($data[$user2all[$id]]);
	$data_content = serialize($data);
	put_file_content($data_filename, $data_content);
	return count($data);
}

function del_users($select)
{
	global $users_filename;
	$users = array();
	$users_content = get_file_content($users_filename);
	if ($users_content !== NULL) $users = unserialize($users_content);
	$users = del_id($users, $select);
	$users_content = serialize($users);
	put_file_content($users_filename, $users_content);
	clean_user();
}

function clean_user($user = false)
{
	global $users_filename, $data_filename;
	$data = array(); 
	$data_content = get_file_content($data_filename);
	if ($data_content !== NULL) $data = unserialize($data_content);
	if (!$user) {
		$users = array();
		$users_content = get_file_content($users_filename);
		if ($users_content !== NULL) $users = unserialize($users_content);
		$usernames = array();
		foreach ($users as $id => $info) {
			$some_user = unserialize($info);
			$usernames[] = $some_user['login'];
		}
	}
	$temp_data = array();
	foreach ($data as $key => $entrie) {
		$sniffed = unserialize($entrie);
		if (!$user) {
			if (in_array($sniffed['user'], $usernames)) $temp_data[] = serialize($sniffed);
		}
		elseif ($user) {
			if ($sniffed['user'] != $user) $temp_data[] = serialize($sniffed);
		}
	}
	$data_content = serialize($temp_data);
	put_file_content($data_filename, $data_content);
}


function cleaner()
{
	global $user, $data_filename, $max_entries, $i_max_entries;
	if (isset($i_max_entries[$user])) $max_entries = $i_max_entries[$user];
	if ($max_entries == 0) return false;
	$data = array();
	$data_content = get_file_content($data_filename);
	if ($data_content !== NULL) $data = unserialize($data_content);
	$entries_num = count($data);
	$user_data = array(); $user2all = array(); $i=0;
	foreach ($data as $key => $entrie) {
		$sniffed = unserialize($entrie);
		if ($user == $sniffed['user']) {
			$user_data[$i] = serialize($sniffed);
			$user2all[$i] = $key;
			$i++;
		}
	}
	$i=0;
	while (count($user_data) >= $max_entries) {
		if (isset($user_data[$i])) {
			unset($user_data[$i]);
			unset($data[$user2all[$i]]);
		}
		$i++;
	}
	$data_content = serialize($data);
	put_file_content($data_filename, $data_content);
	return true;
}

function del_id($array, $id_array)
{
	foreach ($id_array as $id) if (isset($array[$id])) unset($array[$id]);
	$temp = array();
	foreach ($array as $value) $temp[] = $value;
	return $temp;
}

function get_self_image()
{
	global $user, $users_filename;
	$users = array();
	$users_content = get_file_content($users_filename);
	if ($users_content !== NULL) $users = unserialize($users_content);
	foreach ($users as $id => $info) {
		$some_user = unserialize($info);
		if ($user == $some_user['login']) {
			$self_image = ($some_user['self_image']) ? $some_user['self_image'] : NULL;
		}
	}
	return $self_image;
}

function show_image()
{
	global $image_filename;
	$self_image = get_self_image();
	$image_filename = ($self_image) ? $self_image : $image_filename;
	if (!$image = @fopen($image_filename, "rb")) {
		if (!file_exists($filename)) die("Файл $image_filename не найден.");
			else die("Не удается открыть файл $image_filename.");
	}
	header("Content-Type: image/gif");
	fpassthru($image);
	fclose($image);
	die;
}

function checkmail($email)
{
	$email = trim($email);
	if (!$email) return false;
	if (!preg_match("/^[\.a-z0-9_-]{1,20}@(([a-z0-9-]+\.)+(com|net|org|info|biz|name|int|edu|gov|mil|arpa|aero|coop|museum|pro|[a-z]{2})|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})$/i", $email)) return false;
		else return $email;
}
?>