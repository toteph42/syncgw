<?php

/*
 *  <ConversationIndex> test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\mas;

use syncgw\interfaces\mail\Handler;
use syncgw\lib\Debug;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'cvIndex';

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
# $idx = 'Ac3pCr/g148OQoCCQSCy8dDjwH7QBwAAzLowAAARRGA=';
# $idx = 'AdQc2DN7rLoS3hgnE/O76rpFzxN/EwAddF4A';
# $idx = 'AQHUScMXfhNHq8RH+hMn2xwW+kELIQ==';
# $idx = 'AQHWLRNo4NaOjvXU8EODe0ZotrA8B6itzaxf';
// this is an invalid index
# $idx = 'É\'lý&#x1F;';
# $idx = 'CA2CFA8A23';
// this is from https://www.meridiandiscovery.com/how-to/e-mail-conversation-index-metadata-computer-forensics/
$idx = base64_encode(hex2bin('01CDE90ABFE0D78F0E4280824120B2F1D0E3C07ED0070000CCBA300000114460'));
$idx = 'AdW6j/iHjC8LSDIb/28=';
$idx = 'Adi+BTT0x4kuu7E5iGY=';

$hd = Handler::getInstance();

Debug::Msg($rc = $hd->decodeCVI($idx), 'Decoded "ConversationIndex"');

## $rc[0] = time() * 1.0e7 + 116444736000000000;

$ndx = $hd->_encodeCVI($rc);
msg('<ConversationIndex> check');
Debug::Msg('Index returned: '.$idx);
Debug::Msg('            is: '.$ndx);

Debug::Msg($hd->decodeCVI($ndx), 'Decoded "ConversationIndex"');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>

