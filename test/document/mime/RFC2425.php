<?php

/*
 *  RFC2425 (RFC2045) test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\mime;

use syncgw\lib\Debug;
use syncgw\document\mime\mimRFC2425;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../../Functions.php');

Debug::$Conf['Script'] = 'RFC2425';

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

$data = [
    "DESCRIPTION;PARM1=VALUE:This is a short description\r\n",
	"DESCRIPTION:This is a long description\r\n".
    "  on two lines.\r\n",
	"DeSCRIPTION;Parm1=val1;parm2=\"kli;lok\":".
	"This is a long descrip\r\n".
    " tion that is o\r\n".
    "\tn three lines\r\n",
	"PN:a string\;with newline \"\\n\" and &char\r\n",
	"NOTE;ENCODING=QUOTED-PRINTABLE:this=0D=0A=\r\nis a=0D=0A=\r\nnote!<>;;-:_%&XXX",
    'NOTE:This is a short text.\n# comment \nThis <Pitty>tag</Pitty> should survive.\n$%äöü)([]=^\'"1!\n&nbsp\;&auml\;#&tag \ntab	2tab --END',
];

class tst extends mimRFC2425 {
    public function decode(string $data): array {
        return parent::decode($data);
    }
	public function encode(array $rec): string {
	    return parent::encode($rec);
	}
};
$obj = new tst();

foreach ($data as $r) {
	msg('Input');
	Debug::Msg($r);

	msg('RFC2425 Decoded');
	$n = $obj->decode($r);
	Debug::Msg($n);

	msg('RFC2425 Encoded');
	foreach ($n as $r) {
		$x = $obj->encode($n);
    	Debug::Msg($x);
	}
}

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>
