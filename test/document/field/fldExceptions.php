<?php
declare(strict_types=1);

/*
 *  Exception field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldExceptions extends \syncgw\document\field\fldExceptions {

 	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldExceptions {

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
					'D' => '19960402T010000Z,19960403T010000Z,19960404T093300Z' ]];
			$cmp1 = '<Data><'.self::TAG.'>'.
					'<'.self::SUB_TAG[0].'><'.self::SUB_TAG[2].'>828406800</'.self::SUB_TAG[2].
					'><'.self::SUB_TAG[1].'/></'.self::SUB_TAG[0].'>'.
					'<'.self::SUB_TAG[0].'><'.self::SUB_TAG[2].'>828493200</'.self::SUB_TAG[2].
					'><'.self::SUB_TAG[1].'/></'.self::SUB_TAG[0].'>'.
					'<'.self::SUB_TAG[0].'><'.self::SUB_TAG[2].'>828610380</'.self::SUB_TAG[2].
					'><'.self::SUB_TAG[1].'/></'.self::SUB_TAG[0].'>'.
					'</'.self::TAG.'></Data>';
			$cmp2 = [[ 'T' => $xpath, 'P' => [ 'DUMMY' => 'error' ], 'D' => $ver == 1.0 ?
					'19960402T010000Z;19960403T010000Z;19960404T093300Z' :
					'19960402T010000Z,19960403T010000Z,19960404T093300Z' ]];
			unset($cmp2[0]['P']['DUMMY']);
			if ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);

			$ext = [[ 'T' => $xpath, 'P' => [ 'CALSCALE' => 'gregorian' ],
					'D' => '20220402T010000Z,20220403T010000Z,20220404T093300Z' ]];
			$cmp1 = '<Data><'.self::TAG.'>'.
					'<'.self::SUB_TAG[0].'><'.self::SUB_TAG[2].'>1648861200</'.self::SUB_TAG[2].
					'><'.self::SUB_TAG[1].'/></'.self::SUB_TAG[0].'>'.
					'<'.self::SUB_TAG[0].'><'.self::SUB_TAG[2].'>1648947600</'.self::SUB_TAG[2].
					'><'.self::SUB_TAG[1].'/></'.self::SUB_TAG[0].'>'.
					'<'.self::SUB_TAG[0].'><'.self::SUB_TAG[2].'>1649064780</'.self::SUB_TAG[2]
					.'><'.self::SUB_TAG[1].'/></'.self::SUB_TAG[0].'>'.
					'</'.self::TAG.'></Data>';
			$cmp2 = [[ 'T' => $xpath, 'P' => [], 'D' => $ver == 1.0 ?
					'20220402T010000Z;20220403T010000Z;20220404T093300Z' :
					'20220402T010000Z,20220403T010000Z,20220404T093300Z' ]];
			unset($cmp2[0]['P']['CALSCALE']);
		}

		if ($typ == 'application/activesync.calendar+xml') {
			$ext = new XML();
			$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'><Exception>'.
					'<Deleted>1</Deleted><InstanceId>20090424T170000Z</InstanceId>'.
					'</Exception></'.$xpath.'></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'><'.self::SUB_TAG[0].'>'.
					'<'.self::SUB_TAG[1].'/>'.
					'<'.self::SUB_TAG[2].'>1240592400</'.self::SUB_TAG[2].'>'.
					'</'.self::SUB_TAG[0].'></'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Calendar"><'.self::SUB_TAG[0].'>'.
					'<Deleted>1</Deleted><InstanceId xml-ns="activesync:AirSyncBase">20090424T170000Z</InstanceId>'.
					'</'.self::SUB_TAG[0].'></'.$xpath.'></Data>');
			if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
				$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);

			$ext->loadXML('<Data><ApplicationData><'.$xpath.'><'.self::SUB_TAG[0].' xml-ns="activesync:Calendar">'.
					'<InstanceId xml-ns="activesync:AirSyncBase">20200522T040000Z</InstanceId>'.
					'<ExceptionStartTime xml-ns="activesync:Calendar">20200522T050000Z</ExceptionStartTime>'.
					'<Location xml-ns="activesync:AirSyncBase">Mantra</Location>'.
					'<EndTime xml-ns="activesync:Calendar">20200522T053000Z</EndTime><MeetingStatus>0</MeetingStatus>'.
					'</'.self::SUB_TAG[0].'></'.$xpath.'></ApplicationData></Data>');
			$cmp1 = '<Data><'.self::TAG.'><'.self::SUB_TAG[0].'><EndTime>1590125400</EndTime>'.
					'<StartTimeException>1590123600</StartTimeException><'.self::SUB_TAG[2].'>1590120000</'.
					self::SUB_TAG[2].'>'.'<Location><DisplayName>Mantra</DisplayName></Location>'.
					'<MeetingStatus>NO-ATTENDEES</MeetingStatus></'.self::SUB_TAG[0].'></'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.self::TAG.' xml-ns="activesync:Calendar"><Exception>'.
					'<EndTime>20200522T053000Z</EndTime>'.
					'<AllDayEvent>0</AllDayEvent><InstanceId xml-ns="activesync:AirSyncBase">20200522T040000Z</InstanceId>'.
					'<Location><DisplayName>Mantra</DisplayName></Location>'.
					'<MeetingStatus xml-ns="activesync:Calendar">0</MeetingStatus>'.
					'</Exception></'.self::TAG.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>