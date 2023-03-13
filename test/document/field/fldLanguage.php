<?php
declare(strict_types=1);

/*
 *  Language field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldLanguage extends \syncgw\document\field\fldLanguage {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldLanguage {

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

		if ($typ == 'text/vcard' || $typ == 'text/x-vcard') {
			$ext = [[ 'T' => $xpath, 'P' => [], 'D' => 'German' ]];
			$cmp = '<Data><'.self::TAG.'>German</'.self::TAG.'></Data>';
			if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp))
				if ($ver == 4.0)
					$obj->testExport($this, $typ, $ver, $xpath, $int, $ext);
		}
	}

}

?>