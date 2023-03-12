<?php
declare(strict_types=1);

/*
 *  Decode group attribute
 *
 *	@package	sync*gw
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\helper;

use syncgw\lib\Debug;
use syncgw\lib\Util;
use syncgw\document\field\fldAttribute;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'ShowAttr';

if (!strlen($_SERVER['QUERY_STRING'])) {
	\test\scripts\msg('+++ Missing parameter', Util::CSS_ERR);
	exit;
}
$args = explode('&', $_SERVER['QUERY_STRING']);

if (!isset($args[0])) {
	\test\scripts\msg('+++ Missing attribute', Util::CSS_ERR);
	exit;
}

\test\scripts\msg('+++ Attributes: '.fldAttribute::showAttr(intval($args[0])));

\test\scripts\msg('+++ End of script');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

?>