<?php

/*
 *  Session handler test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\DataStore;
use syncgw\lib\Server;
use syncgw\lib\Session;
use syncgw\lib\Util;
use syncgw\lib\Config;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'Session';

Debug::$Conf['Exclude']['syncgw\lib\XML'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\Config:getVar'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('Setting data base to "file"');
setDB('file');

msg('Setting handler to "ActiveSync"');
$cnf = Config::getInstance();
$cnf->updVar(Config::HD, 'MAS');

$sess = Session::getInstance();
$sess->mkSession();

msg('Adding variable "Pitty=jugabuha" to session');
$sess->updSessVar('Pitty', 'jugabuha');
msg('Adding variable "Manga=pilox" in data store "'.Util::HID(Util::HID_CNAME, DataStore::CONTACT).'" to session');
$sess->updSessVar('Manga', 'pilox', DataStore::CONTACT);
$sess->getVar('syncgw');
Debug::Msg($sess);

msg('Overwrite variable "Pitty=dapaletaa" in session');
$sess->updSessVar('Pitty', 'dapaletaa');
$id = $sess->getVar('GUID');
$sess->getVar('syncgw');
Debug::Msg($sess);

msg('Shutdown sync*gw server');
$srv = Server::getInstance();
$srv->shutDown();
setDB('file', DataStore::CONTACT);

msg('Restarting session "'.$id.'"');
$sess = Session::getInstance();
if (!$sess->mkSession()) {
	msg('Failed!');
	exit();
}
msg('Ok');

msg('Variable "Pitty" is "'.$sess->updSessVar('Pitty').'"');
msg('Variable "Manga" is "'.$sess->updSessVar('Manga', '4711', DataStore::CONTACT).'"');

msg('Shutdown sync*gw server');
$srv->shutDown();
setDB('file', DataStore::CONTACT);

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>