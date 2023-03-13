<?php
declare(strict_types=1);

/*
 *  AddressWork field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldAddressBusiness extends \syncgw\document\field\fldAddressBusiness {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldAddressBusiness {

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
	   		$xml = '<syncgw><ApplicationData>';
	   		for ($i=0; $i < 7; $i++) {
	   			if (self::ASA_TAG[$i])
		   			$xml .= '<'.self::ASA_TAG[$i].'>'.self::ASA_TAG[$i].'</'.self::ASA_TAG[$i].'>';
	   		}
	   		$ext->loadXML($xml.'</ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'><'.fldAddresses::SUB_TAG[2].
				   '>'.self::ASA_TAG[2].'</'.fldAddresses::SUB_TAG[2].'><'.fldAddresses::SUB_TAG[3].'>'.self::ASA_TAG[3].
				   '</'.fldAddresses::SUB_TAG[3].'><'.fldAddresses::SUB_TAG[4].'>'.self::ASA_TAG[4].'</'.fldAddresses::SUB_TAG[4].
				   '><'.fldAddresses::SUB_TAG[5].'>'.self::ASA_TAG[5].'</'.fldAddresses::SUB_TAG[5].'><'.fldAddresses::SUB_TAG[6].
				   '>'.self::ASA_TAG[6].'</'.fldAddresses::SUB_TAG[6].'>'.'</'.self::TAG.'></Data>';
		 	$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.self::ASA_TAG[2].' xml-ns="activesync:Contacts">'.
				   	self::ASA_TAG[2].'</'.self::ASA_TAG[2].'><'.self::ASA_TAG[3].'>'.self::ASA_TAG[3].
				   '</'.self::ASA_TAG[3].'><'.self::ASA_TAG[4].'>'.self::ASA_TAG[4].'</'.self::ASA_TAG[4].
				   '><'.self::ASA_TAG[5].'>'.self::ASA_TAG[5].'</'.self::ASA_TAG[5].'><'.self::ASA_TAG[6].
				   '>'.self::ASA_TAG[6].'</'.self::ASA_TAG[6].'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>