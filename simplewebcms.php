<?php
//
// ###################################################
//
// Simple Web CMS
//
// Release: 20100109
//
// ###################################################
//
// Set multiple usernames with password or just one username with a password:
$users = array(
	'username'=>'password',
//	'username2'=>'password2',
//	'username3'=>'password3',
);

// Set path to current working directory:
$path = getcwd(); // Your current working directory.

// Set an include folder:
$inc_folder = '/include'; // This sets the include folder in your current working directory.

// Set an upload folder:
$upl_folder = '/upload'; // This sets the upload folder in your current working directory.

// Set maximum upload size in bytes:
$upload_max = 1000000; // 1 MB.

// Set upload only images to true or false:
$upload_only_images = true;

/*
 * Below this line not for editing purposes.
 */

// ###################################################

error_reporting(0);
ini_set('default_charset','UTF-8');

if(isset($_GET['logout'])){
	setcookie('swc','',time() - 3600);
	header('Location: simplewebcms.php');
	exit;
}
if(!check_login($users)){
	echo get_html_login();
	exit;
}
if(substr($path,-1) == '/'){
	$path = substr($path,0,-1);
}
if(substr($inc_folder,-1) == '/'){
	$inc_folder = substr($inc_folder,0,-1);
}
$inc_path = $path.$inc_folder;
if(substr($upl_folder,-1) == '/'){
	$upl_folder = substr($upl_folder,0,-1);
}
$upl_path = $path.$upl_folder;
$mfile = false;
if(isset($_FILES['file']) && !empty($_FILES['file'])
	&& ($_FILES['file']['error'] == 0) && ($_FILES['file']['size'] <= $upload_max)
	&& check_img($upload_only_images)){

	$upload_file = $upl_path.'/'.basename($_FILES['file']['name']);
	if(move_uploaded_file($_FILES['file']['tmp_name'],$upload_file)){
		chmod($upload_file,0777);
		$mfile = true;
	}
}
$mdelf = false;
if(isset($_GET['del'])){
	if(file_exists($upl_path.'/'.basename($_GET['del']))){
		chmod($upl_path.'/'.$_GET['del'],0777);
		clearstatcache();
		unlink($upl_path.'/'.$_GET['del']);
		$mdelf = true;
	}
}

clearstatcache();
echo get_html_head($path);

if($mfile){echo '<span class="r">File is uploaded!</span><hr />';}
if($mdelf){echo '<span class="r">File is gone!</span><hr />';}

if(isset($_GET['file'])	&& file_exists($inc_path.'/'.$_GET['file'])){
	$medit = false;
	if(isset($_POST['edit'])){
		$edit = $_POST['edit'];
		if(get_magic_quotes_gpc() == 1){
			$edit = stripslashes($edit);
		}
		$edit = trim($edit);
		if(file_put_contents($inc_path.'/'.$_GET['file'],
				htmlspecialchars_decode($edit,ENT_NOQUOTES))){
			chmod($inc_path.'/'.$_GET['file'],0777);
			clearstatcache();
			$medit = true;
		}
	}
	if($medit){echo '<span class="r">File is saved!</span><hr />';}
	echo '<table><tr><td>'.
	'<img src="img/file.gif" alt="Current Edit:" />'.
	'</td><td>'.
	'- '.substr($inc_folder,1).'/'.$_GET['file'].' | '.
	get_rights_per($inc_path.'/'.$_GET['file']).' | '.
	get_rights_oct($inc_path.'/'.$_GET['file']).' | '.
	get_rights($inc_path.'/'.$_GET['file']).' | '.
	'</td><td>'.
	'<a href="simplewebcms.php" title="Back">'.
	'<img src="img/back.gif" alt="Back" />'.
	'</a></td></tr></table>'.
	'<hr />';
	echo '<form action="simplewebcms.php?file='.$_GET['file'].'" method="post">';
	echo '<input type="submit" name="save" value="Save File" />';
	echo '<br />';
	echo '<textarea name="edit">';
	echo htmlspecialchars(file_get_contents($inc_path.'/'.$_GET['file']),
		ENT_NOQUOTES,'UTF-8');
	echo PHP_EOL.'</textarea>';
	echo '</form><hr />';
}else{
	echo show_dir($inc_path,$upl_path);
	echo '<hr />';
}

echo get_html_foot();

function check_login($users){
	foreach($users as $user => $pass){
		if(isset($_COOKIE['swc']) && $_COOKIE['swc'] == md5($user.$pass)){
			return true;
		}elseif(isset($_POST['u']) && isset($_POST['p'])
			&& $_POST['u'] == $user && $_POST['p'] == $pass){
			setcookie('swc',md5($_POST['u'].$_POST['p']));
			return true;
		}
	}
	return false;
}
function check_img($upload_only_images){
	if(!$upload_only_images){
		return true;
	}
	$img_types = array('image/gif','image/jpeg','image/png');
	if(!$dims = getimagesize($_FILES['file']['tmp_name'])){
		return false;
	}
	if(!in_array($dims['mime'],$img_types)){
		return false;
	}
	return true;
}
function show_dir($inc_path,$upl_path){
	$inc_files = scandir($inc_path);
	$upl_files = scandir($upl_path);
	$out = '<div id="show_dirs"><table><tr>';
	if(empty($inc_files) || count($inc_files) == 2){
		$out .= '<td valign="top">'.
		'<span class="r">Include directory is empty!</span>'.
		'</td>';
	}else{
		$out .= '<td valign="top">'.
		'<div id="show_dir"><table><tr>'.
		'<th colspan="2">Include Folder<hr /></th>'.
		'<th colspan="3">Permissions<hr /></th>'.
		'<th>Size<hr /></th>'.
		'<th>Last Modified<hr /></th>'.
		'<th>Edit<hr /></th>'.
		'</tr>';
		foreach($inc_files as $file){
			if($file == '.' || $file == '..') continue;
			$out .= '<tr><td>'.
			'<img src="img/file.gif" alt="File" />'.
			'</td><td>'.
			'<a href="simplewebcms.php?file='.$file.'" title="Edit File">'.
			'- '.$file.
			'</a>'.
			'</td><td>'.
			get_rights_per($inc_path.'/'.$file).
			'</td><td>'.
			get_rights_oct($inc_path.'/'.$file).
			'</td><td>'.
			get_rights($inc_path.'/'.$file).
			'</td><td class="size">'.
			get_file_size($inc_path.'/'.$file).
			'</td><td>'.
			get_last_mod($inc_path.'/'.$file).
			'</td><td>'.
			'<a href="simplewebcms.php?file='.$file.'" title="Edit File">'.
			'<img src="img/edit.png" alt="Edit File" />'.
			'</a>'.
			'</td></tr>';
		}
		$out .= '</table></div>'.
		'</td>';
	}
	if(empty($upl_files) || count($upl_files) == 2){
		$out .= '<td valign="top">'.
		'<span class="r">Upload directory is empty!</span>'.
		'</td>';
	}else{
		$out .= '<td valign="top">'.
		'<div id="show_dir"><table><tr>'.
		'<th colspan="2">Upload Folder<hr /></th>'.
		'<th>Size<hr /></th>'.
		'<th>Delete<hr /></th>'.
		'</tr>';
		foreach($upl_files as $file){
			if($file == '.' || $file == '..') continue;
			$out .= '<tr><td>'.
			'<img src="img/file.gif" alt="File" />'.
			'</td><td>'.
			'- '.$file.
			'</td><td class="size">'.
			get_file_size($upl_path.'/'.$file).
			'</td><td>'.
			'<a href="simplewebcms.php?del='.$file.'" title="Delete File">'.
			'<img src="img/del.png" alt="Delete File" />'.
			'</a>'.
			'</td></tr>';
		}
		$out .= '</table></div>'.
		'</td>';
	}
	$out .= '</tr></table></div>';
	return $out;
}
function get_rights($file){
	$out = '';
	if(is_readable($file)){
		$out .= ' <span class="g">readable</span>';
	}else{
		$out .= ' <span class="r">not readable</span>';
	}
	if(is_writable($file)){
		$out .= ' <span class="g">writable</span>';
	}else{
		$out .= ' <span class="r">not writable</span>';
	}
	if(is_executable($file)){
		$out .= ' <span class="g">executable</span>';
	}else{
		$out .= ' <span class="r">not executable</span>';
	}
	return $out;
}
function get_rights_oct($file){
	return substr(sprintf('%o', fileperms($file)), -4);
}
function get_rights_per($file){
	$perms = fileperms($file);
	if(($perms & 0xC000) == 0xC000){
		// Socket
		$info = 's';
	}elseif(($perms & 0xA000) == 0xA000){
		// Symbolic Link
		$info = 'l';
	}elseif(($perms & 0x8000) == 0x8000){
		// Regular
		$info = '-';
	}elseif(($perms & 0x6000) == 0x6000){
		// Block special
		$info = 'b';
	}elseif(($perms & 0x4000) == 0x4000){
		// Directory
		$info = 'd';
	}elseif(($perms & 0x2000) == 0x2000){
		// Character special
		$info = 'c';
	}elseif(($perms & 0x1000) == 0x1000){
		// FIFO pipe
		$info = 'p';
	}else{
		// Unknown
		$info = 'u';
	}

	// Owner
	$info .= (($perms & 0x0100) ? 'r' : '-');
	$info .= (($perms & 0x0080) ? 'w' : '-');
	$info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));

	// Group
	$info .= (($perms & 0x0020) ? 'r' : '-');
	$info .= (($perms & 0x0010) ? 'w' : '-');
	$info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));

	// World
	$info .= (($perms & 0x0004) ? 'r' : '-');
	$info .= (($perms & 0x0002) ? 'w' : '-');
	$info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));

	return $info;
}
function get_last_mod($file){
	return date('Y-m-d H:i:s',filemtime($file));
}
function get_file_size($file){
	return bytes2format(filesize($file));
}
function bytes2format($bytes){
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);
	$bytes /= pow(1024, $pow);
	return round($bytes).' '.$units[$pow];
}
function get_html_login(){
	$html = ''.
	'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
	'<html xmlns="http://www.w3.org/1999/xhtml"><head>'.
	'<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'.
	'<title>Login @ Simple Web CMS</title>'.
	'<link href="css/style.css" rel="stylesheet" type="text/css" />'.
	'</head><body>'.
	'<div id="head"><h1>Login @ Simple Web CMS</h1></div>'.
	'<div id="content">'.
	'<form action="simplewebcms.php" method="post">'.
	'<table><tr><td>'.
	'Username:</td><td><input type="text" name="u" value="" />*</td>'.
	'</tr><tr><td>'.
	'Password:</td><td><input type="password" name="p" value="" />*</td>'.
	'</tr><tr><td>'.
	'&nbsp;</td><td><input type="submit" value="Login" />'.
	'</td></tr></table>'.
	'</form>'.
	get_html_foot();
	return $html;
}
function get_html_head($path){
	$html = ''.
	'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
	'<html xmlns="http://www.w3.org/1999/xhtml"><head>'.
	'<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'.
	'<title>Simple Web CMS</title>'.
	'<link href="css/style.css" rel="stylesheet" type="text/css" />'.
	'<script type="text/javascript" src="js/tinymce_3_2_7/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>'.
	'<script type="text/javascript" src="js/tinymce_3_2_7_conf.js"></script>'.
	'</head><body>'.
	'<div id="head">'.
	'<table><tr><td valign="top">'.
	'<a href="simplewebcms.php" title="Home">'.
	'<img src="img/home.png" alt="Home" /></a>'.
	'</td><td valign="top">'.
	'<a href="simplewebcms.php?logout=true" title="Logout">'.
	'<img src="img/exit.png" alt="Logout" /></a>'.
	'</td><td>'.
	'<form action="simplewebcms.php" method="post" enctype="multipart/form-data">'.
	'<input type="file" name="file" value="" />'.
	'<input type="submit" value="Upload File" />'.
	'</form>'.
	'</td><td valign="top">'.
	'<span class="r">Max upload: '.
	$GLOBALS['upload_max'].' Bytes ';
	if($GLOBALS['upload_only_images']){
		$html .= ' only images!';
	}
	$html .= '</span>'.
	'</td></tr></table>'.
	'</div>'.
	'<div id="content">'.
	'<hr />';
	return $html;
}
function get_html_foot(){
	$html = '</div>'.
	'<div id="foot">'.
	'<a href="http://jhwd.nl" target="_blank">Developer</a>'.
	'</div>'.
	'</body></html>';
	return $html;
}
?>
