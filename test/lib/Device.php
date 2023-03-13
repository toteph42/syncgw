<?php

/*
 *  Load (and sort) device information test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Device;
use syncgw\lib\Server;
use syncgw\lib\Util;
use syncgw\lib\XML;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'Device';

Debug::$Conf['Exclude']['syncgw\lib\XML'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\Config:getVar'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('Setting handler to "ActiveSync"');
$cnf = Config::getInstance();
$cnf->updVar(Config::HD, 'MAS');

msg('Setting data base to "file"');
setDB('file');
$dev = Device::getInstance();

$file = Util::mkPath('source/dev_MAS.xml');
msg('Loading XML file '.$file);
$xml = new XML();
$xml->loadFile($file);

msg('Activating "IMEI:dummy" device');
$dev->actDev('IMEI:empty');
$dev->getVar('syncgw');
Debug::Msg($dev, 'Device "IMEI:dummy (should be ActiveSync skeleton)"');

msg('Restart server');
$srv = Server::getInstance();
$srv->shutDown();
setDB('file', DataStore::CONTACT);
$dev = Device::getInstance();

msg('Activating device "IMEI:dummy" - should show imported device');
$dev->actDev('IMEI:dummy');
$dev->getVar('syncgw');
Debug::Msg($dev);

msg('Delete device information');
$db = DB::getInstance();
$db->Query(DataStore::DEVICE, DataStore::DEL, 'IMEI:dummy');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>