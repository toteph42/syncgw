<?php
declare(strict_types=1);

/*
 *  Debug test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\lib;

use syncgw\lib\XML;
use syncgw\lib\Debug;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'Debug';
Debug::CleanDir('x*.*');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('Basic ouput functions');

Debug::Msg('Hello world string - standard level');
Debug::Warn('Hello world string - warning level');
Debug::Err('Hello world string - error level');

Debug::Msg('Hello world string - dumped as hex string', 'Hex string dump', 0);

$a = [ 'Key' => 'Value' ];
Debug::Msg($a, 'Array dump');

$x = new XML();
$x->loadXML('<syncgw><Msg>Hello World</Msg></syncgw>');
Debug::Msg($x, 'XML dump');

msg('Debug stack');
$a = new Test();
$a->test1();

Debug::$Conf['Exclude']['test\lib\Test:test2'] = 1;
msg('Excluded test2() (and childs) from debugging');
$a = new Test();
$a->test1();

Debug::$Conf['Exclude']['test\lib\Test'] = 1;
msg('Excluded class "Test" from debugging');
$a = new Test();
$a->test1();

msg('Save XML data');
Debug::Save('x%d.txt', $x);
if (file_exists('x1.txt'))
	unlink('x1.txt');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

class Test {
    public function test1(): void {
	    Debug::Msg('We\'re in function test1()');
        self::test2();
    }

    public function test2(): void {
	    Debug::Msg('We\'re in function test2()');
        self::test3();
    }

    public function test3(): void {
	    Debug::Msg('We\'re in function test3()');
	    self::test4();
    }

    public function test4(): void {
	    Debug::Msg('We\'re in function test4()');
    }
}

?>