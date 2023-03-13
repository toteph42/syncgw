<?php
declare(strict_types=1);

/*
 *  Phone field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldPhones extends \syncgw\document\field\fldPhones {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldPhones {

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
			$ext = [[ 'T' => $xpath, 'P' => [ 'TYPE' => 'bad value' ], 'D' => '01928 1111111111' ]];
			if ($int = $obj->testImport($this, FALSE, $typ, $ver, $xpath, $ext, ''))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $ext);

			$ext = [[ 'T' => $xpath, 'P' => [ 'PREF' => '2', 'TYPE' => 'home' ], 'D' => '01928 22222222222' ]];
			$cmp1 = '<Data><'.fldHomePhone2::TAG.'>01928 22222222222</'.fldHomePhone2::TAG.'></Data>';
			$cmp2 = $ext;
			if ($ver != 4.0) {
				$cmp2[0]['P']['TYPE'] = 'home,pref';
				unset($cmp2[0]['P']['PREF']);
			}
			if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);

			$ext = [[ 'T' => $xpath, 'P' => [ 'TYPE' => 'work,x-assistant' ], 'D' => '01928 333333333333' ]];
			$cmp1 = '<Data><'.fldAssistantPhone::TAG.'>01928 333333333333</'.fldAssistantPhone::TAG.'></Data>';
			$cmp2 = $ext;
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>