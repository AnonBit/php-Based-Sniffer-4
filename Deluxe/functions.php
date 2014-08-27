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
	$fp = fopen($filename, "w");
	fputs($fp, $content);
	fclose($fp);
}

function del_entries($data, $select, $count)
{
	global $data_filename;
	$data = del_id($data, $select);
	$data_content = serialize($data);
	put_file_content($data_filename, $data_content);
	return count($data);
}

function add_filter($add_field, $add_type, $add_value)
{
	global $filters_filename;
	$add_value = trim(htmlspecialchars(stripslashes($add_value)));
	if (($add_type == "contain" or $add_type == "uncontain") and $add_value == "") return false;
	if ($add_field == "ip" and !preg_match("/^[0-9\.]{1,15}$/", $add_value)) return false;
	if ($add_field == "ip" and ($add_type == "equal" or $add_type == "unequal") and !preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $add_value)) return false;
	if ($add_field == "referer" and ($add_type == "equal" or $add_type == "unequal") and !preg_match("/^https?:\/\//i", $add_value)) return false;
	$filter = array();
	$filter['field'] = $add_field;
	$filter['type'] = $add_type;
	$filter['value'] = $add_value;
	$filters_content = get_file_content($filters_filename);
	$filters = unserialize($filters_content);
	$filters[] = serialize($filter);
	$filters_content = serialize($filters);
	put_file_content($filters_filename, $filters_content);
	return true;
}

function save_filters($delete, $field, $type, $value)
{
	global $filters_filename;
	$filter = array(); $filters = array();
	for ($i=0; $i<count($field); $i++) {
		if ($delete[$i] !== NULL) continue;
		$value[$i] = trim(htmlspecialchars(stripslashes($value[$i])));
		if ($type[$i] == "contain" and $value[$i] == "") $type[$i] = "equal";
		if ($type[$i] == "uncontain" and $value[$i] == "") $type[$i] = "unequal";
		if ($field[$i] == "ip" and !preg_match("/^[0-9\.]{1,15}$/", $value[$i])) continue;
		if ($field[$i] == "ip" and ($type[$i] == "equal" or $type[$i] == "unequal") and !preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $value[$i])) continue;
		if ($field[$i] == "referer" and ($type[$i] == "equal" or $type[$i] == "unequal") and !preg_match("/^https?:\/\//i", $value[$i])) continue;
		$filter['field'] = $field[$i];
		$filter['type'] = $type[$i];
		$filter['value'] = $value[$i];
		$filters[] = serialize($filter);
	}
	$filters_content = serialize($filters);
	put_file_content($filters_filename, $filters_content);
}

function cleaner()
{
	global $data_filename, $max_entries;
	if ($max_entries == 0) return false;
	$data = array();
	$data_content = get_file_content($data_filename);
	if ($data_content !== NULL) $data = unserialize($data_content);
	while (count($data) >= $max_entries) $data = del_id($data, array(0));
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

function show_image()
{
	global $image_filename;
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

function is_uin($uin)
{
	return is_numeric($uin) and strlen($uin) >= 5 and strlen($uin) <= 9;
}
?>