<?php
declare(strict_types=1);

/*
 *  URL field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\document\field\fldURLWork;
use syncgw\lib\XML;

class fldURLs extends \syncgw\document\field\fldURLs {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldURLs {

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
			$ext = [[ 'T' => $xpath, 'P' => [ 'TYPE' => 'bad value' ], 'D' => 'http://xxx.com' ]];
			$cmp1 = '<Data><'.fldURLOther::TAG.'>http://xxx.com</'.fldURLOther::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['TYPE']);
			if ($ver == 4.0)
				$cmp2[0]['P']['TYPE'] = 'x-other';
			if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);

			$ext = [[ 'T' => $xpath, 'P' => [ 'TYPE' => 'work' ], 'D' => 'http://xxx.com' ]];
			$cmp1 = '<Data><'.fldURLWork::TAG.'>http://xxx.com</'.fldURLWork::TAG.'></Data>';
			$cmp2 = $ext;
			if ($ver != 4.0)
				unset($cmp2[0]['P']['TYPE']);
		}

		if ($typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
			$ext = [[ 'T' => $xpath, 'P' => [ 'TYPE' => 'bad value' ], 'D' => 'http://xxx.com' ]];
			$cmp1 = '<Data><'.fldURLOther::TAG.'>http://xxx.com</'.fldURLOther::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['TYPE']);
			if ($ver == 4.0)
				$cmp2[0]['P']['TYPE'] = 'x-other';
			if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);

			$ext = [[ 'T' => $xpath, 'P' => [ 'TYPE' => 'x-profile' ], 'D' => 'http://xxx.com' ]];
			$cmp1 = '<Data><'.fldURLOther::TAG.'>http://xxx.com</'.fldURLOther::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['TYPE']);
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>