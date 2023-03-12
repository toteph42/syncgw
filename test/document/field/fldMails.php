<?php
declare(strict_types=1);

/*
 *  EMail field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

class fldMails extends \syncgw\document\field\fldMails {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldMails {

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

		$obj = new fldHandler;

		if ($typ == 'text/vcard' || $typ == 'text/x-vcard') {
			$ext = [[ 'T' => $xpath, 'P' => [ 'TYPE' => 'bad value' ], 'D' => 'jamxxx.com' ]];
			$cmp1 = '<Data><'.fldMailHome::TAG.'>jamxxx.com</'.fldMailHome::TAG.'></Data>';
			$cmp2 = $ext;
			if ($ext && ($int = $obj->testImport($this, FALSE, $typ, $ver, $xpath, $ext, $cmp1)))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);

			$ext = [[ 'T' => $xpath, 'P' => [ 'TYPE' => 'work' ], 'D' => 'work@xxx.com' ]];
			$cmp1 = '<Data><'.fldMailWork::TAG.'>work@xxx.com</'.fldMailWork::TAG.'></Data>';
			$cmp2 = $ext;
			if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
		}
	}

}

?>