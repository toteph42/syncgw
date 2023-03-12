<?php

/*
 *  Log and error test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\ErrorHandler;
use syncgw\lib\Log;
use syncgw\lib\Config;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'Log';

Debug::$Conf['Exclude']['syncgw\lib\XML:hasChild'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:addVar'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\Config:getVar'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\Log:Msg'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$cnf = Config::getInstance();
$cnf->updVar(Config::LOG_LVL, Log::ERR|Log::WARN|Log::INFO|Log::APP|Log::DEBUG);

ErrorHandler::getInstance();

$log = Log::getInstance();

$msg = [ 9999 => 'Hello world', 9998 => 'User error raised with "%s"', ];
$log->setMsg($msg);

msg("All currently defined messages");
print_r($log->getMsg());

msg('Message "9999" should be shown!');
$log->Msg(Log::INFO, 9999);

msg('User error "9998"');
ErrorHandler::Raise(9998, 'Param');

msg('Unknown log message "9997"');
$log->Msg(Log::ERR, 9997, 'Parm');

msg('+++ End of script');
# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

?>