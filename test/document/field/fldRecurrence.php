<?php
declare(strict_types=1);

/*
 *  Recurrence field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldRecurrence extends \syncgw\document\field\fldRecurrence {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldRecurrence {

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

		if (($typ == 'text/calendar' || $typ == 'text/x-vcalendar') && $ver == 2.0) {
			// Monthly on the 1st Friday until 12/24/94: MP1 1+ FR 19941224T000000
			$ext = [[ 'T' => $xpath, 'P' => [ 'ALTREP' => 'droo' ],
					'D' => 'FREQ=MONTHLY;UNTIL=19971224T000000Z;BYDAY=1FR' ]];
			$cmp1 = '<Data><'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].
					'><'.self::RFC_SUB['UNTIL'].'>882921600</'.self::RFC_SUB['UNTIL'].'><'.self::RFC_SUB['BYDAY'].
					'>1FR</'.self::RFC_SUB['BYDAY'].'></'.self::TAG.'></Data>';
			$cmp2 = $ext;
			unset($cmp2[0]['P']['ALTREP']);
		}

		if (($typ == 'text/calendar' || $typ == 'text/x-vcalendar') && $ver == 1.0) {
			$xpath = explode('/', $xpath);
			$xpath = $xpath[2];
			// Daily for 1 occurrences
			$ext = [[ 'T' => $xpath, 'P' => [], 'D' => 'D1 #1' ],			// Daily until 12/24/94
			[ 'T' => $xpath, 'P' => [], 'D' => 'D1 19941224T000000Z', ],			// Every other day - forever
			[ 'T' => $xpath, 'P' => [], 'D' => 'D2 #0', ],			// Every 10 days, 5 occurrences
			[ 'T' => $xpath, 'P' => [], 'D' => 'D10 #5' ],			// Weekly for 20 occurrences
			[ 'T' => $xpath, 'P' => [], 'D' => 'W1 #20' ],			// Weekly until 12/24/94
			[ 'T' => $xpath, 'P' => [], 'D' => 'W1 19941224T000000Z' ],			// Every other week - forever
			[ 'T' => $xpath, 'P' => [], 'D' => 'W2 #0' ],			// Weekly on Tuesday and Thursday for 5 weeks
			[ 'T' => $xpath, 'P' => [], 'D' => 'W1 TU TH #5' ],			// Every other week on Monday Wednesday and Friday until 12/24/94
			[ 'T' => $xpath, 'P' => [], 'D' => 'W2 MO WE FR 19941224T000000Z'],			// Monthly on the 1st Friday for ten occurrences
			[ 'T' => $xpath, 'P' => [], 'D' => 'MP1 1+ FR #10' ],			// Monthly on the 2st Friday until 12/24/97
			[ 'T' => $xpath, 'P' => [], 'D' => 'MP1 2+ FR 19971224T000000Z' ],			// Every other month on the 1st and last Sunday of the month for 10 occurrences
			[ 'T' => $xpath, 'P' => [], 'D' => 'MP2 1+ SU 1- SU #10' ],			// Every six months on the 2nd Monday thru Friday for 10 occurrences
			[ 'T' => $xpath, 'P' => [], 'D' => 'MP6 2+ MO TU WE TH FR #10' ],			// Monthly on the second last Monday of the month for 6 months
			[ 'T' => $xpath, 'P' => [], 'D' => 'MP1 2- MO #6' ],			// 1st of every month
			[ 'T' => $xpath, 'P' => [], 'D' => 'MD1 #0' ],			// Monthly on the third to the last day of the month, forever
			[ 'T' => $xpath, 'P' => [], 'D' => 'MD1 3- #0' ],			// Monthly on the 2nd and 15th of the month for 9 occurrences
			[ 'T' => $xpath, 'P' => [], 'D' => 'MD1 2 15 #9' ],			// "LD" refers to LastDay in a monthly recurrence rule. Monthly on the 1st and last day of the month for 11 occurrences
			[ 'T' => $xpath, 'P' => [], 'D' => 'MD1 1 LD #11' ],			// Every 18 months on the 10th thru 15th of the month for 13 occurrences
			[ 'T' => $xpath, 'P' => [], 'D' => 'MD18 10 11 12 13 14 15 #13' ],			// Monthly on the second to the last day for 5 months
			[ 'T' => $xpath, 'P' => [], 'D' => 'MD1 2- #5' ],			// Other year on first of January
			[ 'T' => $xpath, 'P' => [], 'D' => 'YM2 #0' ],			// Yearly in June and July for 10 occurrences
			[ 'T' => $xpath, 'P' => [], 'D' => 'YM1 6 7 #10' ],			// Every other year on January, Feb, and March for 10 occurrences
			[ 'T' => $xpath, 'P' => [], 'D' => 'YM2 1 2 3 #10' ],			// Every 3rd year on the 1st, 100th and 200th day for 10 occurrences
			[ 'T' => $xpath, 'P' => [], 'D' => 'YD3 1 100 200 #10' ],			// Every five month three times
			[ 'T' => $xpath, 'P' => [], 'D' => 'MD5 #3' ]];	   		$cmp1 = '<Data>'.
	 	   			'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>DAILY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['COUNT'].'>1</'.	   				self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>DAILY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['UNTIL'].'>788227200</'.	   				self::RFC_SUB['UNTIL'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>DAILY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['INTERVAL'].	   				'>2</'.self::RFC_SUB['INTERVAL'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>DAILY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['INTERVAL'].'>10</'.	   				self::RFC_SUB['INTERVAL'].'><'.self::RFC_SUB['COUNT'].'>5</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>WEEKLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['COUNT'].'>20</'.	   				self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>WEEKLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['UNTIL'].	   				'>788227200</'.self::RFC_SUB['UNTIL'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>WEEKLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['INTERVAL'].'>2</'.	   				self::RFC_SUB['INTERVAL'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>WEEKLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['BYDAY'].'>TU,TH</'.	   				self::RFC_SUB['BYDAY'].'><'.self::RFC_SUB['COUNT'].'>5</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>WEEKLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['INTERVAL'].	   				'>2</'.self::RFC_SUB['INTERVAL'].'><'.self::RFC_SUB['BYDAY'].'>MO,WE,FR</'.self::RFC_SUB['BYDAY'].'><'.	   				self::RFC_SUB['UNTIL'].'>788227200</'.self::RFC_SUB['UNTIL'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['BYDAY'].'>+1FR</'.	   				self::RFC_SUB['BYDAY'].'><'.self::RFC_SUB['COUNT'].'>10</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['BYDAY'].'>+2FR</'.	   				self::RFC_SUB['BYDAY'].'><'.self::RFC_SUB['UNTIL'].'>882921600</'.self::RFC_SUB['UNTIL'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['INTERVAL'].'>2</'.	   				self::RFC_SUB['INTERVAL'].'><'.self::RFC_SUB['BYDAY'].'>+1SU,-1SU</'.self::RFC_SUB['BYDAY'].'><'.self::RFC_SUB['COUNT'].	   				'>10</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['INTERVAL'].	   				'>6</'.self::RFC_SUB['INTERVAL'].'><'.self::RFC_SUB['BYDAY'].'>+2MO,TU,WE,TH,FR</'.self::RFC_SUB['BYDAY'].'><'.	   				self::RFC_SUB['COUNT'].'>10</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['BYDAY'].'>-2MO</'.	   				self::RFC_SUB['BYDAY'].'><'.self::RFC_SUB['COUNT'].'>6</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['BYMONTHDAY'].	   				'>-3</'.self::RFC_SUB['BYMONTHDAY'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['BYMONTHDAY'].'>2,15</'.	   				self::RFC_SUB['BYMONTHDAY'].'><'.self::RFC_SUB['COUNT'].'>9</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['BYMONTHDAY'].'>1,-1</'.	   				self::RFC_SUB['BYMONTHDAY'].'><'.self::RFC_SUB['COUNT'].'>11</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['INTERVAL'].'>18</'.	   				self::RFC_SUB['INTERVAL'].'><'.self::RFC_SUB['BYMONTHDAY'].'>10,11,12,13,14,15</'.self::RFC_SUB['BYMONTHDAY'].'><'.	   				self::RFC_SUB['COUNT'].'>13</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['BYMONTHDAY'].'>-2</'.	   				self::RFC_SUB['BYMONTHDAY'].'><'.self::RFC_SUB['COUNT'].'>5</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>YEARLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['INTERVAL'].'>2</'.	   				self::RFC_SUB['INTERVAL'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>YEARLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['BYMONTH'].'>6,7</'.	   				self::RFC_SUB['BYMONTH'].'><'.self::RFC_SUB['COUNT'].'>10</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>YEARLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['INTERVAL'].'>2</'.	   				self::RFC_SUB['INTERVAL'].'><'.self::RFC_SUB['BYMONTH'].'>1,2,3</'.self::RFC_SUB['BYMONTH'].'><'.self::RFC_SUB['COUNT'].	   				'>10</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>YEARLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['INTERVAL'].'>3</'.	   				self::RFC_SUB['INTERVAL'].'><'.self::RFC_SUB['BYYEARDAY'].'>1,100,200</'.self::RFC_SUB['BYYEARDAY'].'><'.	   				self::RFC_SUB['COUNT'].'>10</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'<'.self::TAG.'><'.self::RFC_SUB['FREQ'].'>MONTHLY</'.self::RFC_SUB['FREQ'].'><'.self::RFC_SUB['INTERVAL'].'>5</'.	   				self::RFC_SUB['INTERVAL'].'><'.self::RFC_SUB['COUNT'].'>3</'.self::RFC_SUB['COUNT'].'></'.self::TAG.'>'.
	   				'</Data>';
			$cmp2 = $ext;
		}

		if ($typ == 'application/activesync.calendar+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'><Type>3</Type><Occurrences>7</Occurrences>'.
					'<CalendarType>4</CalendarType><Interval>4</Interval><WeekOfMonth>1</WeekOfMonth><DayOfWeek>4</DayOfWeek>'.
					'<DayOfMonth>8</DayOfMonth><MonthOfYear>2</MonthOfYear><IsLeapMonth>0</IsLeapMonth></'.
					$xpath.'></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'><CalendarType>4</CalendarType><MonthDay>8</MonthDay><DayPos>TU</DayPos>'.
			'<Interval>4</Interval><IsLeap>0</IsLeap><Month>2</Month><Count>7</Count><'.
			self::ASC_SUB['Type'][2].'>MONTHLY</'.
			self::ASC_SUB['Type'][2].'><WeekOfMonth>1</WeekOfMonth></'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Calendar"><CalendarType>4</CalendarType><DayOfMonth>8</DayOfMonth>'.
					'<DayOfWeek>4</DayOfWeek><Interval>4</Interval><IsLeapMonth>0</IsLeapMonth><MonthOfYear>2</MonthOfYear>'.
					'<Occurrences>7</Occurrences><Type>3</Type><WeekOfMonth>1</WeekOfMonth></'.$xpath.'></Data>');
		}

		if ($typ == 'application/activesync.task+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'><Type>3</Type><Occurrences>7</Occurrences>'.
					'<CalendarType>4</CalendarType><Interval>4</Interval><DayOfWeek>4</DayOfWeek><DayOfMonth>8</DayOfMonth><WeekOfMonth>1'.
					'</WeekOfMonth><MonthOfYear>2</MonthOfYear><IsLeapMonth>0</IsLeapMonth></'.$xpath.'></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'><CalendarType>4</CalendarType><MonthDay>8</MonthDay><DayPos>TU</DayPos>'.
					'<Interval>4</Interval><IsLeap>0</IsLeap><Month>2</Month><Count>7</Count><'.self::AST_SUB['Type'][2].
					'>MONTHLY</'.self::AST_SUB['Type'][2].'><WeekOfMonth>1</WeekOfMonth></'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Tasks"><CalendarType>4</CalendarType><DayOfMonth>8</DayOfMonth>'.
					'<DayOfWeek>4</DayOfWeek><Interval>4</Interval><IsLeapMonth>0</IsLeapMonth><MonthOfYear>2</MonthOfYear>'.
					'<Occurrences>7</Occurrences><Type>3</Type><WeekOfMonth>1</WeekOfMonth></'.$xpath.'></Data>');
		}

		if ($typ == 'application/activesync.mail+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'><Type>3</Type><Occurrences>7</Occurrences>'.
					'<CalendarType>4</CalendarType><Interval>4</Interval><DayOfWeek>4</DayOfWeek><DayOfMonth>8</DayOfMonth><WeekOfMonth>1'.
					'</WeekOfMonth><MonthOfYear>2</MonthOfYear><IsLeapMonth>0</IsLeapMonth></'.$xpath.'></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'><'.self::ASM_SUB['Type'][2].'>MONTHLY</'.self::ASM_SUB['Type'][2].
					'><Count>7</Count><Interval>4</Interval><DayPos>TU</DayPos><MonthDay>8</MonthDay>'.
					'<WeekOfMonth>1</WeekOfMonth><Month>2</Month><CalendarType>4</CalendarType><IsLeap>0</IsLeap></'.
					self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Mail"><Type>3</Type><Occurrences>7</Occurrences>'.
					'<Interval>4</Interval><DayOfWeek>4</DayOfWeek><DayOfMonth>8</DayOfMonth><WeekOfMonth>1'.
					'</WeekOfMonth><MonthOfYear>2</MonthOfYear><CalendarType>4</CalendarType><IsLeapMonth>0</IsLeapMonth></'.
					$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>