<?php
if (isset($_GET['CheckLock'])) {
    if (!($fp = fopen(base64_decode($_GET['CheckLock']), 'wb'))) {
        echo base64_encode('Cannot open lock file!');
        exit;
    }
    if (!flock($fp, LOCK_EX|LOCK_NB))
        echo $_GET['CheckLock'];
    else
        echo base64_encode('Lock file failed!');
    flock($fp, LOCK_UN);
    fclose($fp);
    exit;
}
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head><title><b>sync&bull;gw</b> test script</title></head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"></meta>
<body>
<?php

/**
 *
 *  Stand alone environement check
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 *
 */

echo '<pre>Version 9.06<br /><br />';

#----------------------------------------------------------------------------------------------------------------------------------------

$ext = stripos($_SERVER['REQUEST_URI'], 'ext');
$cgi = stripos($_SERVER['REQUEST_URI'], 'cgi');

#----------------------------------------------------------------------------------------------------------------------------------------

chk('PHP version');
if (PHP_MAJOR_VERSION < 7 && PHP_MINOR_VERSION < 1)
	err('Unsupported PHP version "'.phpversion().'"',
 	 	'<strong>sync&bull;gw</strong> expects at least PHP7.1 - your installation is incompatible',
 	 	'Update to at least PHP version "7.1"');
else
	ok();

#----------------------------------------------------------------------------------------------------------------------------------------

if ($ext)
 	var_dump($_SERVER);

if (!isset($_POST['CheckCT'])) {
 	chk('PHP call mode '.($ext ? '"'.php_sapi_name().'"' : ''));
 	if (stripos(php_sapi_name(), 'cgi') !== FALSE || $cgi) {
  		warn('Your PHP interface to web server is "'.php_sapi_name().'"'.
 	   		(isset($_SERVER['REDIRECT_HANDLER']) ? 'The redirect handler is "'.$_SERVER['REDIRECT_HANDLER']. '".' : ' ').
	   		'This might be a problem.',
 	   		'PHP started by CGI interface may prevent those variables from being available. '.
 	   		'Please hit "Submit" button below to restart this script and enable us to perform in deep test.', '');
   		echo '<form action="http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'" method="post" '.
   	    	 'enctype="multipart/form-data" accept-charset="utf-8">'.
  	   		 '<input type="hidden" name="CheckCT" value="1"><input type="submit" value=" Submit "></form>';
   		exit;
 	} else
  		ok();
} else {
 	chk('PHP call mode - server variable check');
 	if (!isset($_SERVER['CONTENT_TYPE']) ||
 	 	!isset($_SERVER['CONTENT_LENGTH'])) {
			err('Your PHP interface to web server is "'.php_sapi_name().'"'.
 	  			(isset($_SERVER['REDIRECT_HANDLER']) ? 'The redirect handler is "'.$_SERVER['REDIRECT_HANDLER']. '".' : ' '),
 	   			'<strong>sync&bull;gw</strong> requires global PHP variables <span style="color:blue;">$_SERVER["CONTENT_TYPE"]</span> and '.
 	  			'<span style="color:blue;">$_SERVER["CONTENT_LENGTH"]</span>. '.
 	  			'Your current configuration does not provide those variables. ',
 	  			'Please check with your hosting provider.');
 	} else
 		ok();
}

#----------------------------------------------------------------------------------------------------------------------------------------

chk('Register globals');
if (ini_get('register_globals'))
 	err('Invalid PHP.INI configuration "register_globals = On"',
 		 '<strong>sync&bull;gw</strong> cannot run with this configuration enabled',
		 'Disable PHP configuration in PHP.INI',
	 	 'https://secure.php.net/manual/en/ini.core.php#ini.register-globals');
else
 	ok();

#----------------------------------------------------------------------------------------------------------------------------------------

chk('"Document Object Model" (DOM) PHP extension');
if (!class_exists('DOMDocument'))
 	err('Missing PHP extension',
 	    '<strong>sync&bull;gw</strong> is coded to use this PHP extension to process XML data',
 		 'Enable PHP extension in PHP.INI',
 		 'https://secure.php.net/manual/en/dom.setup.php');
else
 	ok();

#----------------------------------------------------------------------------------------------------------------------------------------

chk ('"DOM_XML" PHP extension');
if (function_exists('domxml_new_doc'))
 	err('Unallowed PHP extension',
 		 'This PHP extension is not allowed to be enabled if you have "DOM" PHP extension enabled',
 		 'Disable PHP extension in PHP.INI',
 		 'https://secure.php.net/manual/en/domxml.installation.php');
else
 	ok();

#----------------------------------------------------------------------------------------------------------------------------------------

chk('"GD" PHP extension');
if (!function_exists('gd_info'))
 	err('Missing PHP extension',
 		 '<strong>sync&bull;gw</strong> use functions provided by this PHP extension to convert image data in preparation of device synchronization',
 		 'Enable PHP extension in PHP.INI',
 		 'https://secure.php.net/manual/en/image.setup.php');
else
 	ok();

#----------------------------------------------------------------------------------------------------------------------------------------

chk('"MySQL improved" PHP extension');
if (!function_exists('mysqli_connect'))
 	warn('Optional PHP extension missing',
 		  'If you want to use <strong>sync&bull;gw</strong> with a MySQL data base you need to enable this PHP extension',
 		  'Decide whether to enable PHP extension in PHP.INI',
		  'https://secure.php.net/manual/en/mysqli.installation.php');
else
 	ok();

#----------------------------------------------------------------------------------------------------------------------------------------

chk('"ZIP" PHP extension');
if (!class_exists('ZipArchive'))
 	warn('Optional PHP extension missing',
 		 '<strong>sync&bull;gw</strong> use functions provided in this PHP extension to compress data before starting download of internal data '.
 	 	 'in administrator interface panel',
 		 'Decide whether to enable PHP extension in PHP.INI',
 		 'https://secure.php.net/manual/en/zip.setup.php');
else
 	ok();

#----------------------------------------------------------------------------------------------------------------------------------------

chk('"Multibyte string" PHP extension');
if (!function_exists('mb_convert_encoding'))
 	warn('Optional PHP extension missing',
 		 '<strong>sync&bull;gw</strong> use functions provided by this PHP extension to support Chinese and other multi-byte languages',
 		 'Decide whether to enable PHP extension in PHP.INI',
 	  	 'https://secure.php.net/manual/en/mbstring.installation.php');
else
 	ok();

#----------------------------------------------------------------------------------------------------------------------------------------

chk('"Gettext" PHP extension');
if (!function_exists('gettext'))
 	warn('Optional PHP extension missing',
 		  'If you want to use <strong>sync&bull;gw</strong> with a different language than English, you need to enable this PHP extension',
 		  'Decide whether to enable PHP extension in PHP.INI',
 		  'https://secure.php.net/manual/en/book.gettext.php');
else
 	ok();

#----------------------------------------------------------------------------------------------------------------------------------------

chk('Access to temporary file directory');
if (!strlen($dir = ini_get('upload_tmp_dir')))
    $dir = sys_get_temp_dir();
$fname = $dir.DIRECTORY_SEPARATOR.uniqid('syncgw').'.tmp';
if (!($fp = fopen($fname, 'wb')))
 	err('upload_tmp_dir "'.$dir.'" not accessible',
 		  'If you want to use <strong>sync&bull;gw</strong>, you need to provide a location where <strong>sync&bull;gw</strong> can store temporary files.',
 	      'Update your PHP.INI file',
 	      'https://secure.php.net/manual/en/ini.core.php#ini.upload-tmp-dir');
else
 	ok();

#----------------------------------------------------------------------------------------------------------------------------------------

chk('Portable advisory locking');
if (!flock($fp, LOCK_EX|LOCK_NB))
 	err('File Locking failed',
 		  'If you want to use <strong>sync&bull;gw</strong>, you need a locking sheme available.');
else
 	ok();

#----------------------------------------------------------------------------------------------------------------------------------------

chk('Parallel file locking');
$t = ini_get('default_socket_timeout');
ini_set('default_socket_timeout', 1);
if ($rc = @fopen('http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'?CheckLock='.base64_encode($fname), 'r')) {
    $r = base64_decode(stream_get_contents($rc));
    if ($r != $fname)
     	err($r,
 	      	  'If you want to use <strong>sync&bull;gw</strong>, you need a valid locking sheme available.');
    else
     	ok();
    fclose($rc);
} else
 	err('Cannot open connection to "'.'http://'.$_SERVER['SERVER_NAME'].'"',
 		  'We tried to check locking sheme on your server.');

flock($fp, LOCK_UN);
fclose($fp);
ini_set('default_socket_timeout', $t);

#----------------------------------------------------------------------------------------------------------------------------------------

if (strpos(strtolower($_SERVER['REQUEST_URI']), 'err')) {
 	chk('PHP error logging details');
 	echo '<br /><fieldset style="background-color:#f2f2f2;">';
 	echo 'PHP error log file is located at "'.ini_get('error_log').'"<br />';
 	echo 'PHP error mesages will '.(ini_get('log_errors') ? '' : 'NOT ').'be logged (by default)<br />';
 	$mod = ini_get('error_reporting');
 	$msg = 'Error reporting is set to "';
 	if ($mod & E_ALL)
 		$msg .= 'E_ALL,';
 	else {
 	 	if ($mod & E_ERROR) $msg .= 'E_ERROR,';
 	 	if ($mod & E_RECOVERABLE_ERROR) $msg .= 'E_RECOVERABLE_ERROR,';
 	 	if ($mod & E_WARNING) $msg .= 'E_WARNING,';
 	 	if ($mod & E_PARSE) $msg .= 'E_PARSE,';
 	 	if ($mod & E_NOTICE) $msg .= 'E_NOTICE,';
 	 	if ($mod & E_STRICT) $msg .= 'E_STRICT,';
 	 	if ($mod & E_CORE_ERROR) $msg .= 'E_CORE_ERROR,';
 	 	if ($mod & E_CORE_WARNING) $msg .= 'E_CORE_WARNING,';
 	 	if ($mod & E_COMPILE_ERROR) $msg .= 'E_COMPILE_ERROR,';
 	 	if ($mod & E_COMPILE_WARNING) $msg .= 'E_COMPILE_WARNING,';
 	 	if ($mod & E_USER_ERROR) $msg .= 'E_USER_ERROR,';
 	 	if ($mod & E_USER_WARNING) $msg .= 'E_USER_WARNING,';
 	 	if ($mod & E_USER_NOTICE) $msg .= 'E_USER_NOTICE,';
 	 	if ($mod & E_DEPRECATED) $msg .= 'E_DEPRECATED,';
 	 	if ($mod & E_USER_DEPRECATED) $msg .= 'E_USER_DEPRECATED,';
 	}
 	echo substr($msg, 0, -1).'"<br />';
 	echo 'Display error messages is "'.(ini_get('display_errors') ? 'ON' : 'OFF').'" '.
 		  '- may '.(!ini_set('display_errors', 1) ? 'NOT ' : '').'be modified<br />';
 	echo '</fieldset>';
}

#----------------------------------------------------------------------------------------------------------------------------------------

exit;

function chk(string $msg): void {
 	echo '<fieldset style="width:600px;white-space:normal;">'.
 		  '<div style="width:100px;float:left">Checking:</div><div style="padding-left:100px">'.$msg.'</div>';
}
function ok(string $msg = ''): void {
 	echo '<div style="width:100px;color:#00ff00;float:left">Status:</div>'.
 		  '<div style="color:#00ff00;padding-left:100px">'.($msg ? $msg : 'Passed successfully').'</div></fieldset><br />';
}
function warn(string $msg, string $reason, string $action, string $help = ''): void {
 	echo '<strong><div style="color:#ffcc00;width:100px;float:left">Warning:</div>'.
		  '<div style="color:#ffcc00;padding-left:100px">'.$msg.'</div></strong>'.
	 	  '<div style="width:100px;float:left">Reason:</div><div style="padding-left:100px">'.$reason.'</div>'.
	 	  '<div style="width:100px;float:left">Action:</div><div style="padding-left:100px">'.$action.'</div>';
	 if ($help)
		  echo '<div style="width:100px;float:left">Help:</div>'.
		  	   '<div style="padding-left:100px"><a href="'.$help.'" target="_blank">More information</a></div>';
 	echo '</fieldset><br />';
}
function err(string $err, string $reason, string $action, string $help = ''): void {
 	echo '<strong><div style="color:red;width:100px;float:left">Error:</div>'.
		  '<div style="color:red;padding-left:100px">'.$err.'</div></strong>'.
	 	  '<div style="width:100px;float:left">Reason:</div><div style="padding-left:100px">'.$reason.'</div>'.
	 	  '<div style="width:100px;float:left">Action:</div><div style="padding-left:100px">'.$action.'</div>';
	 if ($help)
	  	echo '<div style="width:100px;float:left">Help:</div>'.
  	 		 '<div style="padding-left:100px"><a href="'.$help.'" target="_blank">More information</a></div>';
 	echo '</fieldset><br />';
}

?>
</body>
</html>