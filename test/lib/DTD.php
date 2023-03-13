<?php

/*
 *  DTD handler test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\DTD;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'DTD';

Debug::$Conf['Exclude']['syncgw\lib\XML:loadFile'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:hasChild'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getVal'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getVar'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:saveXML'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$dtd = DTD::getInstance();
$n = 399282;
msg('Setting "UNKNOWN" DTD "'.$n.'"');
$dtd->actDTD($n);

$n = 8196;
msg('Setting "KNOWN" DTD "'.$n.'"');
$dtd->actDTD($n);

msg('Getting PID and Name');
Debug::Msg('PID="'.$dtd->getVar('PID', FALSE).'"');
Debug::Msg('Name="'.$dtd->getVar('Name', FALSE).'"');

msg('Getting "99" (should be "Unknown-0x63")');
$t = $dtd->getTag('99');
Debug::Msg('Return Value: "'.$t.'"');

$nn = 'Unknown-0x63';
msg('Getting "'.$nn.'" (should be 99)');
$t = $dtd->getTag($nn);
Debug::Msg('Return Value: "'.$t.'"');

msg('Getting "0x16" (should be "ExceptionStartTime")');
$t = $dtd->getTag(strval(0x16));
Debug::Msg('Return Value: '.$t);

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>