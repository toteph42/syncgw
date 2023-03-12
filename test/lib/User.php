<?php

/*
 *  Basic data base handler functions
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\DataStore;
use syncgw\lib\User;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'User';

Debug::$Conf['Exclude']['syncgw\lib\XML'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\Config:getVar'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
setDB('file', DataStore::USER);

$usr = User::getInstance();

msg('Login to internal file datastore');
$usr->Login(Debug::$Conf['ScriptUID'], Debug::$Conf['ScriptUPW'], 'BubbaDevice');
$usr->setTop();
Debug::Msg($usr, 'User object');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>