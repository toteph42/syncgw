<?php
declare(strict_types=1);

/*
 *  MeetingRequest field handler
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\document\field;

use syncgw\lib\XML;

class fldMeetingRequest extends \syncgw\document\field\fldMeetingRequest {

	/**
     * 	Singleton instance of object
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldMeetingRequest {

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
			$ext->loadXML('<syncgw><ApplicationData><'.$xpath.'>'.
					'<StartTime>21.11.2011</StartTime><DtStamp>30.08.1999</DtStamp><EndTime>22.11.2011</EndTime>'.
					'<InstanceType>3</InstanceType><Location>somewhere</Location><Organizer>Jan Smith</Organizer>'.
					'<RecurrenceId>20061029T010000Z</RecurrenceId><Reminder>33</Reminder>'.
					'<ResponseRequested>1</ResponseRequested><Recurrences><Recurrence><Type>3</Type><CalendarType>4</CalendarType>'.
					'<Occurrences>7</Occurrences>'.
					'<Interval>4</Interval><WeekOfMonth>1</WeekOfMonth><DayOfWeek>4</DayOfWeek>'.
					'<DayOfMonth>8</DayOfMonth><MonthOfYear>2</MonthOfYear>></Recurrence></Recurrences>'.
					'<Location><DisplayName>somewhere</DisplayName><Street>Wanko. 17</Street>'.
					'</Location><Sensitivity>2</Sensitivity><BusyStatus>3</BusyStatus>'.
					'<GlobalObjId>BAAAAIIA4AB0xbcQGoLgCAfUCRDgQMnBJoXEAQAAAAAAAAAAEAAAAAvw7UtuTulOnjnjhns3jvM=</GlobalObjId>'.
					'<MeetingMessageType>3</MeetingMessageType><DisallowNewTimeProposal>1</DisallowNewTimeProposal>// '.
					'<ProposedStartTime>20061029T023300Z</ProposedStartTime>'.
					'<ProposedEndTime>20061029T024300Z</ProposedEndTime><Forwardees>john@lucky.com</Forwardees>'.
					'</'.$xpath.'></ApplicationData></syncgw>');
			$cmp1 = '<Data><'.self::TAG.'>'.
			'<BusyStatus>OOF</BusyStatus><DisallowNewProposal>0</DisallowNewProposal><EndTime>1321920000</EndTime>'.
			'<MailOther>john@lucky.com</MailOther><GlobalId>040000008200e00074c5b7101a82e00830303010e040c9c12685c4'.
			'010000000000000000100000000bf0ed4b6e4ee94e9e39e3867b378ef3</GlobalId><InstanceType>3</InstanceType>'.
			'<Location><DisplayName>somewhere</DisplayName></Location>'.
			'<MeetingMessageType>3</MeetingMessageType><Organizer>Jan Smith</Organizer><EndTimeProposal>1162089780</EndTimeProposal>'.
			'<StartTimeProposal>1162089180</StartTimeProposal><RecurrenceId>1162083600</RecurrenceId><Recurrence>'.
			'<MonthDay>8</MonthDay><DayPos>TU</DayPos><Interval>4</Interval><Month>2</Month><Count>7</Count>'.
			'<Frequency>MONTHLY</Frequency><WeekOfMonth>1</WeekOfMonth></Recurrence><Alarm><Action>DISPLAY</Action><Trigger VALUE="duration" '.
			'RELATED="start">33</Trigger></Alarm>'.
			'<ResponseRequested>1</ResponseRequested><Class>PRIVATE</Class><StartTime>1321833600</StartTime>'.
			'</'.self::TAG.'></Data>';
			$cmp2 = new XML();
			$cmp2->loadXML('<Data><'.$xpath.' xml-ns="activesync:Mail"><BusyStatus>3</BusyStatus>'.
					'<DisallowNewTimeProposal>1</DisallowNewTimeProposal><EndTime>2011-11-22T00:00:00.000Z</EndTime>'.
					'<AllDayEvent>0</AllDayEvent><Forwardees xml-ns="activesync:ComposeMail">john@lucky.com</Forwardees>'.
					'<InstanceType xml-ns="activesync:Mail">3</InstanceType>'.
					'<Location xml-ns="activesync:AirSyncBase"><DisplayName>somewhere</DisplayName></Location>'.
					'<MeetingMessageType xml-ns="activesync:Mail2">3</MeetingMessageType>'.
					'<Organizer xml-ns="activesync:Mail">Jan Smith</Organizer>'.
					'<ProposedEndTime xml-ns="activesync:MeetingResponse">20061029T024300Z</ProposedEndTime>'.
					'<ProposedStartTime>20061029T023300Z</ProposedStartTime>'.
					'<RecurrenceId xml-ns="activesync:Mail">2006-10-29T01:00:00.000Z</RecurrenceId>'.
					'<Recurrences><Recurrence><DayOfMonth>8</DayOfMonth><DayOfWeek>4</DayOfWeek>'.
					'<Interval>4</Interval><MonthOfYear>2</MonthOfYear><Occurrences>7</Occurrences><Type>3</Type>'.
					'<WeekOfMonth>1</WeekOfMonth></Recurrence></Recurrences><ReminderTime xml-ns="activesync:Tasks">33</ReminderTime>'.
					'<ResponseRequested xml-ns="activesync:Mail">1</ResponseRequested><Sensitivity>2</Sensitivity>'.
					'<StartTime>2011-11-21T00:00:00.000Z</StartTime></'.$xpath.'></Data>');
		}

		if ($ext && ($int = $obj->testImport($this, TRUE, $typ, $ver, $xpath, $ext, $cmp1)))
			$obj->testExport($this, $typ, $ver, $xpath, $int, $cmp2);
	}

}

?>