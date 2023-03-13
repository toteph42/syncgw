<?php
declare(strict_types=1);

/*
 *  Flag field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldFlag extends \syncgw\document\field\fldFlag {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldFlag {

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

		if ($typ == 'application/activesync.mail+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><Flag><Subject>Subject line </Subject><Status>1</Status>'.
					'<FlagType>string</FlagType><DateCompleted>01.08.2010</DateCompleted><StartDate>01.07.2010</StartDate>'.
					'<DueDate>15.07.2010</DueDate><ReminderSet>1</ReminderSet><ReminderTime>30.09.1999</ReminderTime>'.
					'<OrdinalDate>16.07.2010</OrdinalDate><SubOrdinalDate>20100718A</SubOrdinalDate>'.
					'</Flag></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'><Summary>Subject line </Summary><Status X-PC="0">COMPLETED</Status><FlagType>string</FlagType>'.
					'<Completed>1280620800</Completed><StartTime>1277942400</StartTime><DueDate>1279152000</DueDate>'.
					'<Alarm><Action>DISPLAY</Action><Trigger VALUE="date-time">938649600</Trigger></Alarm><Ordinal>1279238400</Ordinal>'.
					'<OrdinalSub>20100718A</OrdinalSub></'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Mail"><Subject xml-ns="activesync:Tasks">Subject line </Subject>'.
					'<Status xml-ns="activesync:Mail">1</Status><FlagType>string</FlagType>'.
					'<DateCompleted xml-ns="activesync:Tasks">2010-08-01T00:00:00.000Z</DateCompleted>'.
					'<CompleteTime xml-ns="activesync:Mail">2010-08-01T00:00:00.000Z</CompleteTime>'.
					'<StartDate xml-ns="activesync:Tasks">2010-07-01T00:00:00.000Z</StartDate>'.
					'<UtcStartDate>2010-07-01T00:00:00.000Z</UtcStartDate>'.
					'<DueDate>2010-07-15T00:00:00.000Z</DueDate>'.
					'<UtcDueDate>2010-07-15T00:00:00.000Z</UtcDueDate>'.
					'<ReminderTime>1999-09-30T00:00:00.000Z</ReminderTime><OrdinalDate>2010-07-16T00:00:00.000Z</OrdinalDate>'.
					'<SubOrdinalDate>20100718A</SubOrdinalDate></'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>