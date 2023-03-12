<?php

/*
 *  Locking test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\Lock;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'Lock';

Debug::$Conf['Exclude']['syncgw\lib\Config:getVar'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$lock = 'test';

$lck = Lock::getInstance();

msg('Set lock with counter');
$lck->lock($lock, TRUE);

msg('Unlock without deletion');
$lck->unlock($lock, FALSE);

msg('Set lock with counter');
$lck->lock($lock, TRUE);

msg('Unlock without deletion');
$lck->unlock($lock, FALSE);

# msg('Unlock with deletion');
# $lck->unlock($lock);

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>