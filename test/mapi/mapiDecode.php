<?php
declare(strict_types=1);

/*
 *  Decode MAPI / HTTP
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\mapi;

use syncgw\lib\Debug;
use syncgw\lib\Config;
use syncgw\lib\Encoding;
use syncgw\lib\HTTP;
use syncgw\lib\DB;
use syncgw\lib\Util;
use syncgw\mapi\mapiHandler;
use syncgw\lib\XML;
use syncgw\mapi\mapiHTTP;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'LoadMapiDecode';

Debug::$Conf['Exclude']['syncgw\lib\User'] 				= 1;
Debug::$Conf['Exclude']['syncgw\lib\Log'] 				= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML'] 				= 1;
Debug::$Conf['Exclude']['syncgw\lib\Config:getVar'] 	= 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$parms = explode('&', $_SERVER['QUERY_STRING']);

foreach ($parms as $parm) {
	list($c, $p) = explode('=', $parm);
	switch($c) {
	// Cmd=GetProps
	case 'Cmd':
		$cmd = $p;
		break;

	// Typ=req(n)
	// Typ=resp(n)
	case 'Typ':
		$mod = $p;
		break;

	default:
		break;
	}
}

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

msg('Authorizing user "'.Debug::$Conf['ScriptUID'].'"');
$uid  = Debug::$Conf['ScriptUID'];
$host = '';
$db   = DB::getInstance();
if (strpos(Debug::$Conf['ScriptUID'], '@'))
	list($uid, $host) = explode('@', Debug::$Conf['ScriptUID']);
if (!$db->Authorize($uid, $host, Debug::$Conf['ScriptUPW'])) {
	msg('+++ Login failed!', Util::CSS_ERR);
   	exit;
}

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

$cnf = Config::getInstance();
$cnf->updVar(Config::HD, 'MAPI');

$enc = Encoding::getInstance();
$enc->setEncoding('UTF-8');

$http = HTTP::getInstance();

$n = str_replace([ 'req', 'mkresp', 'resp' ], [ NULL, NULL, NULL ], $mod);
$reqXML = $cnf->getVar(Config::TMP_DIR).$cmd.$n.'.xml';

if (substr($mod, 0, 3) != 'req') {
	if (!file_exists($reqXML)) {
		Msg('+++ Please call first decoding of <'.$cmd.'> request ['.$n.']', Util::CSS_WARN);
		exit;
	}
	$xml = new XML();
	$xml->loadFile($reqXML);
	$http->updHTTPVar(HTTP::RCV_BODY, NULL, $xml);
	Debug::Msg('Loading "'.$reqXML.'" as XML converted request body');
}

if (substr($mod, 0, 2) == 're') {
	$fnam = '..'.DIRECTORY_SEPARATOR.'mimedata'.DIRECTORY_SEPARATOR.'mapi'.DIRECTORY_SEPARATOR.strtolower($cmd).
			'_'.$mod.'.bin';
	Msg('Loading "'.$fnam.'"');
	$bdy = file_get_contents($fnam);
}

if (substr($mod, 0, 3) == 'req') {
	$http->updHTTPVar(HTTP::SERVER, 'REQUEST_METHOD', 'POST');
	$http->updHTTPVar(HTTP::SERVER, 'REQUEST_URI', '/mapi/1');
	$http->updHTTPVar(HTTP::SERVER, 'HTTP_X_REQUESTTYPE', $cmd);
	$http->updHTTPVar(HTTP::RCV_BODY, NULL, $bdy);

	Msg('Decoding "'.$cmd.'" request');
	$http->checkIn();
	Debug::Msg($http->getHTTPVar(HTTP::RCV_BODY), 'Decoded <'.$cmd.'> Request');

	$xml = $http->getHTTPVar(HTTP::RCV_BODY);
	$xml->saveFile($reqXML);
} else { // resp / mkresp
	$http->updHTTPVar(HTTP::SND_HEAD, 'X-Requesttype', $cmd);

	if (substr($mod, 0, 4) == 'resp') {
		$http->addBody($bdy);
		Msg('Encoding "'.$cmd.'" response');
	} else {
		$mapi = mapiHandler::getInstance();
		if ($xml = $mapi->Parse($cmd, mapiHTTP::MKRESP))
			$http->addBody($xml);
		Debug::$Conf['Script'] = 'LoadMapiDecodeResp';
		Msg('Creating "'.$cmd.'" response');
	}
	$http->checkOut();
##	Debug::Save('req999', $http->getHTTPVar(HTTP::SND_BODY));
}

Msg('+++ End of script');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

?>