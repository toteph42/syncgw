<?php

/*
 *  Config handler test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\Config;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'Config';

Debug::$Conf['Exclude']['syncgw\lib\XML:hasChild'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:addVar'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:saveXML'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$cnf = Config::getInstance();
msg('Setting invalid configuration paramater "KO"');
$cnf->updVar('KO', 'Geht nicht');

msg('Setting valid configuration paramater "Usr_KO"');
$cnf->updVar('Usr_KO', 'Mamma');
msg('Parameter value for "Usr_KO": '.$cnf->getVar('Usr_KO'));

msg('Setting invalid value "klo" to "Config::LOG_LVL"');
$cnf->updVar(Config::LOG_LVL, 'klo');
msg('Parameter value for "Config::LOG_LVL": '.$cnf->getVar(Config::LOG_LVL));

msg('Setting valid value "Warn" for "Config::LOG_LVL"');
$cnf->updVar(Config::LOG_LVL, 'Warn').'<br />';
msg('Parameter value for "Config::LOG_LVL": '.$cnf->getVar(Config::LOG_LVL));

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>