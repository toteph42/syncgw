<?php
declare(strict_types=1);

/*
 *  PhoneWork2 field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldBusinessPhone2 extends \syncgw\document\field\fldBusinessPhone2 {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldBusinessPhone2 {

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
			$ext->loadXML('<syncgw><ApplicationData><Body><Type>3</Type><EstimatedDataSize>5500</EstimatedDataSize>'.
					'<Truncated>1</Truncated></Body><WebPage>http://www.contoso.com/</WebPage>'.
					'<BusinessAddressCountry>United States of America</BusinessAddressCountry>'.
					'<Email1Address>"Anat Kerry (anat@contoso.com)"&lt;anat@contoso.com&gt;</Email1Address>'.
					'<Business2PhoneNumber>(206) 555-0100</Business2PhoneNumber><FileAs>Kerry, Anat</FileAs>'.
					'<NativeBodyType>3</NativeBodyType></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>(206) 555-0100</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><Business2PhoneNumber xml-ns="activesync:Contacts">(206) 555-0100</Business2PhoneNumber></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>