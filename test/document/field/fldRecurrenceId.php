<?php
declare(strict_types=1);

/*
 *  DateRecurrenceId field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

class fldRecurrenceId extends \syncgw\document\field\fldRecurrenceId {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldRecurrenceId {

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

		if ($typ == 'text/calendar' || $typ == 'text/x-vcalendar') {
			$ext = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'error' ],
					'D' => '20061029T010000Z' ]];
			$cmp1 = '<Data><'.self::TAG.' VALUE="date-time">1162083600</'.self::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['DUMMY']);
			$cmp2[0]['P']['VALUE'] = 'date-time';
			if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);

			$ext = [[ 'T' => $xpath, 'P' => [ 'CALSCALE' => 'gregorian' ], 'D' => '20061029T010000Z' ]];
			$cmp1 = '<Data><'.self::TAG.' VALUE="date-time">1162083600</'.self::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['CALSCALE']);
			$cmp2[0]['P']['VALUE'] = 'date-time';
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>