<?php
declare(strict_types=1);

/*
 * 	sync*gw interface
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

/**
 *  Gettext extension loaded?
*/
if (!function_exists('_')) {
	function _($str): string 								{ return $str; }
	function bindtextdomain($dom, $path): string 			{ return ''; }
	function textdomain($dom): string			  			{ return ''; }
	function bind_textdomain_codeset($dom, $cs): string		{ return ''; }
}

/**
 * 	Multibyte extension loaded?
 */
if (!function_exists('mb_detect_encoding')) {
	function mb_detect_encoding($str): string				{ return !preg_match('/[^\x00-\x7F]/i', $str) ? 'ASCII' : 'UNKNOWN'; }
    function mb_internal_encoding($encoding) 		        { }
    //  function mb_convert_encoding()                      { }
}

use syncgw\lib\Server;

require_once('syncgw'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'Loader.php');

// get server object
$srv = Server::getInstance();

// process request
$srv->Process();



?>