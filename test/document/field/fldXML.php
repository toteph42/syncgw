<?php
declare(strict_types=1);

/*
 *  XML field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

class fldXML extends \syncgw\document\field\fldXML {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldXML {

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

		if ($typ == 'text/x-vcard' || $typ == 'text/vcard') {
			$ext = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'error' ], 'D' => 'text' ]];
	   		$cmp1 = '<Data><'.self::TAG.'>text</'.self::TAG.'></Data>';
			$cmp2 = $ext;
	   		unset($cmp2[0]['P']['DUMMY']);
	   		if (($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
			$ext = [[ 'T' => $xpath, 'P' => [ 'ALTID' => '371' ], 'D' => 'text' ]];
	   		$cmp1 = '<Data><'.self::TAG.' ALTID="371">text</'.self::TAG.'></Data>';
			$cmp2 = $ext;
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>