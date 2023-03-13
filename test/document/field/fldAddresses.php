<?php
declare(strict_types=1);

/*
 *  Address field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldAddresses extends \syncgw\document\field\fldAddresses {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldAddresses {

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
		$int = new XML();
		$obj = new fldHandler;

		if ($typ == 'text/x-vcard' || $typ == 'text/vcard') {
   			$ext  = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'error' ], 'D' => 'will work' ]];
		 	$cmp1 = '<Data><'.fldAddressOther::TAG.'><'.self::SUB_TAG[0].'>will work</'.self::SUB_TAG[0].'></'.
				   fldAddressOther::TAG.'></Data>';
		 	$cmp2 = [[ 'T' => $xpath, 'P' => [], 'D' => 'will work;;;;;;' ]];
   			if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);

			$ext = [[ 'T' => $xpath, 'P' => [ 'TYPE' => 'home' ],
					  'D' => 'PostOffice;ExtendedAddress;Street;City;Region;PostalCode;Country' ]];
			$cmp = '<Data><'.fldAddressHome::TAG.'><'.self::SUB_TAG[0].'>PostOffice</'.self::SUB_TAG[0].'><'.
				   self::SUB_TAG[1].'>ExtendedAddress</'.self::SUB_TAG[1].'><'.self::SUB_TAG[2].'>Street</'.self::SUB_TAG[2].'><'.
				   self::SUB_TAG[3].'>City</'.self::SUB_TAG[3].'><'.self::SUB_TAG[4].'>Region</'.self::SUB_TAG[4].'><'.
				   self::SUB_TAG[5].'>PostalCode</'.self::SUB_TAG[5].'><'.self::SUB_TAG[6].'>Country</'.self::SUB_TAG[6].'>'.
				   '</'.fldAddressHome::TAG.'></Data>';
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $ext);
	}

}

?>