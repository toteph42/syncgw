<?php

/*
 *  fld handler test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\mime;

use syncgw\lib\Debug;
use syncgw\lib\Config;
use syncgw\lib\DataStore;
use syncgw\lib\Device;
use syncgw\activesync\MASHandler;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../../Functions.php');

Debug::$Conf['Exclude']['syncgw\lib\XML:hasChild'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getVal'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getItem'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:saveXML'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\Config:getVar'] = 1;

Debug::$Conf['Script'] = 'MIME01';

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
switch (strtoupper($_SERVER['QUERY_STRING'])) {
case 'T=1':
    $mod = DataStore::NOTE;
    $dev = '';
    $tst = [
    	'mimPlain',
    	'mimvNote',
    ];
    break;

case 'T=3':
    $mod = DataStore::NOTE;
    $dev = 'MAS';
    $dev = '';
    $tst = [
    	'mimAsNote',
    ];
    break;

case 'T=10':
    $mod = DataStore::CONTACT;
    $dev = '';
    $tst = [
    	'mimvCard',
    ];
    break;

case 'T=12':
    $mod = DataStore::CONTACT;
    $dev = 'MAS';
    $tst = [
    	'mimAsGAL',
    ];
    break;

case 'T=13':
    $mod = DataStore::CONTACT;
    $dev = 'MAS';
    $tst = [
    	'mimAsContact',
    ];
    break;

case 'T=20':
    $mod = DataStore::CALENDAR;
    $dev = '';
    $tst = [
    	'mimvCal',
    ];
    break;

case 'T=22':
    $mod = DataStore::CALENDAR;
    $dev = 'MAS';
    $tst = [
    	'mimAsCalendar',
    ];
    break;

case 'T=30':
    $mod = DataStore::TASK;
    $dev = '';
    $tst = [
    	'MIMmimvTask',
    ];
    break;

case 'T=32':
    $mod = DataStore::TASK;
    $dev = 'MAS';
    $tst = [
    	'mimAsTask',
    ];
    break;

case 'T=42':
    $mod = DataStore::MAIL;
    $dev = 'MAS';
    $tst = [
    	'mimAsMail',
    ];
	break;

case 'T=43':
    $mod = DataStore::docLib;
    $dev = 'MAS';
    $tst = [
    	'mimAsdocLib',
    ];
	break;

default:
    msg('+++ Unknown parameter "'.$_SERVER['QUERY_STRING'].'"');
    exit;
}

// we need this for attachment handöer
setDB();

if ($dev) {
	$d = Device::getInstance();
	$d->actDev($dev);
}

$cnf = Config::getInstance();
$cnf->updVar(Config::ENABLED, $mod);

// we need to emulate setting of supported Active-Sync version
$mas = \syncgw\activesync\MASHandler::getInstance();
$mas->setCallParm('BinVer', floatval(MASHandler::MSVER));

require_once('../field/fldHandler.php');

foreach ($tst as $class) {
    $mime = 'syncgw\\document\\mime\\'.$class;
    $mime = $mime::getInstance();

    foreach ($mime->_mime as $typ) {
	    foreach ($mime->_map as $tag => $class) {
			$c = explode('\\', get_class($class));
			$c = array_pop($c);
			$class = 'test\\document\\field\\'.$c;
			$class = $class::getInstance();
	    	msg('Checking field <'.$c.'> for tag <'.$tag.'>');
			$class->testClass($typ[0], $typ[1], $tag);
	    }
    }
}

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>