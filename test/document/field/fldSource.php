<?php
declare(strict_types=1);

/*
 *  Source field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

class fldSource extends \syncgw\document\field\fldSource {

  	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldSource {

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
			$ext = [[ 'T' => $xpath, 'P' => [], 'D' => 'bad value' ]];
			if ($int = $obj->testImport($this, FALSE, $typ, $ver, $xpath, $ext, ''))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $ext);

			$ext = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'error' ], 'D' => 'http://xxx.com' ]];
	   		$cmp1 = '<Data><'.self::TAG.'>http://xxx.com</'.self::TAG.'></Data>';
	   		$cmp2 = $ext;
	   		unset($cmp2[0]['P']['DUMMY']);
	   		if (($int = $obj->testImport($this, FALSE, $typ, $ver, $xpath, $ext, $cmp1)))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);

			$ext = [[ 'T' => $xpath, 'P' => [ 'PREF' => '1' ], 'D' => 'http://xxx.com' ]];
	   		$cmp1 = '<Data><'.self::TAG.' PREF="1">http://xxx.com</'.self::TAG.'></Data>';
	   		$cmp2 = $ext;
		}

		if ($typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
			$ext = [[ 'T' => $xpath, 'P' => [], 'D' => 'bad value' ]];
			if ($int = $obj->testImport($this, FALSE, $typ, $ver, $xpath, $ext, ''))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $ext);

			$ext = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'error' ], 'D' => 'http://xxx.com' ]];
	   		$cmp1 = '<Data><'.self::TAG.'>http://xxx.com</'.self::TAG.'></Data>';
	   		$cmp2 = $ext;
	   		unset($cmp2[0]['P']['DUMMY']);
			if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);

	   		$ext = [[ 'T' => $xpath, 'P' => [ 'PREF' => '1' ], 'D' => 'http://xxx.com' ]];
	   		$cmp1 = '<Data><'.self::TAG.'>http://xxx.com</'.self::TAG.'></Data>';
	   		$cmp2 = $ext;
	   		unset($cmp2[0]['P']['PREF']);
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>