<?php
declare(strict_types=1);

namespace syncgw\lib;

/*
 *  PHP interfaces functions
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

/**
 *  sync*gw class auto loader
 *
 *	@param 	- Class name to load
 *  @return - True or False
 */
spl_autoload_register(function ($class): bool {
	static $_Loader = [];

	if (isset($_Loader[$class]))
		return TRUE;

	// check for sync*gw classes
    if (($p = stripos($class, 'syncgw')) !== FALSE)
    	$file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, substr($class, $p + 7)).'.php';
    // load sabreDAV files
    elseif (substr($class, 0, 5) == 'Sabre') {
        $base = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.'Sabre'.DIRECTORY_SEPARATOR;
    	require_once($base.'HTTP'.DIRECTORY_SEPARATOR.'functions.php');
	    require_once($base.'Uri'.DIRECTORY_SEPARATOR.'functions.php');
	    require_once($base.'Xml'.DIRECTORY_SEPARATOR.'Serializer'.DIRECTORY_SEPARATOR.'functions.php');
	    require_once($base.'Xml'.DIRECTORY_SEPARATOR.'Deserializer'.DIRECTORY_SEPARATOR.'functions.php');
	    $file = $base.str_replace([ '_', '\\' ], [ DIRECTORY_SEPARATOR , DIRECTORY_SEPARATOR ], substr($class, 6)).'.php';
	} elseif (substr($class, 0, 3) == 'Psr') {
		$file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.'Sabre'.DIRECTORY_SEPARATOR.
				str_replace([  '_', '\\' ], [ DIRECTORY_SEPARATOR , DIRECTORY_SEPARATOR  ], $class).'.php';
	} elseif (substr($class, 0, 9) == 'PHPMailer')
        $file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.
				str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 10)).'.php';
	else {
    	if (strpos($class, 'test') !== FALSE) //3
	        $file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'. //3
	        		DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php'; //3
    	else //3
    		return FALSE;
	}

    // Only use this very carefully - everything might go into wrong direction
	if (!@file_exists($file)) { //3
        echo '<pre><code style="color:red;">+++ ERROR: Class "'.$class.'" in "'.$file.'" not found!</code><br />'; //3
		foreach (ErrorHandler::Stack() as $msg) //3
 	       echo '<code style="color:red;">'.htmlspecialchars($msg).'</code><br />'; //3
		echo '<br />'; //3
		exit; //3
	} //3

	// autoload class file - we only take care about our own files
    require_once($file);
  	$_Loader[$class] = 1;

    return TRUE;
});

// initialize debug object
Debug::getInstance(); //3

?>