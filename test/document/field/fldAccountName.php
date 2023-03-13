<?php
declare(strict_types=1);

/*
 *  Account name field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldAccountName extends \syncgw\document\field\fldAccountName {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldAccountName {

		if (!self::$_obj)
            self::$_obj = new self();

		return self::$_obj;
	}

	/**
	 *  Test this class
	 *
	 *	@param  - MIME type
	 *  @param  - MIME version
	 *  $param  - External path
	 */
	public function testClass(string $typ, float $ver, string $xpath): void {

		$ext = NULL;
		$obj = new fldHandler;

		if ($typ == 'application/activesync.contact+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Type>3</Type><EstimatedDataSize>5500</EstimatedDataSize>'.
						  '<'.$xpath.'>Florian Daeumling</'.$xpath.'><NameMiddle>M.</NameMiddle>'.
						  '<NativeBodyType>3</NativeBodyType></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>Florian Daeumling</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Contacts2">Florian Daeumling</'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>