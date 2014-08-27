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
}
?>