<?php

/*
 *  HTTP handler test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\HTTP;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'HTTP';

Debug::$Conf['Exclude']['syncgw\lib\DTD:getInstance'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$http = HTTP::getInstance();

$http->receive($_SERVER, file_get_contents('php://input'));

msg('Get/Put');
Debug::Msg($http->getHTTPVar(HTTP::RCV_HEAD), 'Input header');
Debug::Msg('getVar "SCRIPT_FILENAME" = "'.$http->getHTTPVar('SCRIPT_FILENAME').'"');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>