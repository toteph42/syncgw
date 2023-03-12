<?php

/*
 *  Encoding handler test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\Encoding;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'Encoding';

Debug::$Conf['Exclude']['syncgw\lib\XML:hasChild'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getItem'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getVal'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$enc = Encoding::getInstance();

$s  = 'Hello world';
msg('Input string: "'.$s.'"');

$cs = 1018;
msg('Setting external character set "'.$cs.'" - SHOULD WORK');
$enc->setEncoding($cs);
$t = $enc->getEncoding();
msg('External character set: "'.$t.'"');

$cs = 'iso-IR-1101';
msg('Setting illegal external character set "'.$cs.'"');
$enc->setEncoding($cs);
$t = $enc->getEncoding();
msg('External character set: "'.$t.'"');

$cs = 'UTF-32be';
msg('Setting external character set "'.$cs.'"');
$enc->setEncoding($cs);
$t = $enc->getEncoding();
msg('External character set: "'.$t.'"');

msg('Encode string to external');
$t = $enc->export($s, FALSE);
Debug::Msg($t, 'String dump', 0);

msg('Decode string back to internal');
$t = $enc->import($t, TRUE);
Debug::Msg($t, 'String dump', 0);

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>