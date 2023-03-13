<?php
declare(strict_types=1);

/*
 *  MeetingRequest field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\activesync\masHandler;
use syncgw\lib\XML;

class fldMeetingRequest extends fldHandler {

	// module version number
	const VER = 2;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'MeetingRequest';

    const ASM_SUB			= [
	        'BusyStatus'				=> 'fldBusyStatus',
		    'DisallowNewTimeProposal'	=> 'fldDisallowNewProposal',
    		'EndTime'					=> 'fldEndTime',
			// 'AllDayEvent'    		=> Handled by fldEndTime
		    'Forwardees'				=> 'fldMailOther',
    		'GlobalObjId'				=> 'fldGlobalId',
    		'InstanceType'				=> 'InstanceType',
    	    'Location'					=> 'fldLocation',
		    'MeetingMessageType'		=> 'MeetingMessageType',
    		'Organizer'					=> 'fldOrganizer',
		    'ProposedEndTime'			=> 'fldEndTimeProposal',
    		'ProposedStartTime'			=> 'fldStartTimeProposal',
    		'RecurrenceId'				=> 'fldRecurrenceId',
            'Recurrences'				=> 'fldRecurrence',
			'Reminder'					=> 'fldAlarm',
    		'ResponseRequested'			=> 'ResponseRequested',
			'Sensitivity'				=> 'fldClass',
			'StartTime'					=> 'fldStartTime',
			'Timezone'					=> 'fldTimezone',
    		'Uid'						=> 'fldUid',
    ];

   	/**
     * 	Singleton instance of object
     * 	@var fldMeetingRequest
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldMeetingRequest {
		if (!self::$_obj) {
            self::$_obj = new self();
			// clear tag deletion status
			unset(parent::$Deleted[self::TAG]);
		}

		return self::$_obj;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {
		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; field handler'), self::TAG));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Import field
	 *
	 *  @param  - MIME type
	 *  @param  - MIME version
	 *	@param  - External path
	 *  @param  - [[ 'T' => Tag; 'P' => [ Parm => Val ]; 'D' => Data ]] or external document
	 *  @param  - Internal path
	 * 	@param 	- Internal document
	 *  @return - TRUE = Ok; FALSE = Skipped
	 */
	public function import(string $typ, float $ver, string $xpath, $ext, string $ipath, XML &$int): bool {
		$rc    = FALSE;
		$ipath .= self::TAG;

		switch ($typ) {
		case 'application/activesync.mail+xml':
			if (!$ext->xpath($xpath, FALSE))
	        	break;
	        self::delTag($int, $ipath);
			$ip = $int->savePos();
			$int->addVar(self::TAG);

	        $xp = $ext->savePos();
    	    foreach (self::ASM_SUB as $key => $class) {
	        	if (substr($class, 0, 3) == 'fld') {
               		$class = 'syncgw\\document\\field\\'.$class;
	               	$field = $class::getInstance();
					$field->import($typ, $ver, $xpath.'/'.$key, $ext, $ipath.'/', $int);
				} elseif ($val = $ext->getVar($key, FALSE))
  		        	$int->addVar($key, $val);
				$rc = TRUE;
            	$ext->restorePos($xp);
			}
			$int->restorePos($ip);
            break;

		default:
			break;
		}

		return $rc;
	}

	/**
	 * 	Export field
	 *
	 *  @param  - MIME type
	 *  @param  - MIME version
 	 *	@param  - Internal path
	 * 	@param 	- Internal document
	 *  @param  - External path
	 *  @param  - External document
	 *  @return - [[ 'T' => Tag; 'P' => [ Parm => Val ]; 'D' => Data ]] or FALSE=Not found
	 */
	public function export(string $typ, float $ver, string $ipath, XML &$int, string $xpath, ?XML $ext = NULL) {
		$rc = FALSE;

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'application/activesync.mail+xml':
			$mas  = masHandler::getInstance();
			$mver = $mas->callParm('BinVer');
			$ext->addVar('MeetingRequest', NULL, FALSE, $ext->setCP(XML::AS_MAIL));
        	while ($int->getItem() !== NULL) {
		        $ip = $int->savePos();
        		foreach (self::ASM_SUB as $key => $class) {
					if (substr($class, 0, 3) == 'fld') {
						$class = 'syncgw\\document\\field\\'.$class;
	               		$field = $class::getInstance();
	                    $field->export($typ, $ver, '', $int, self::TAG.'/'.$key, $ext);
					} elseif ($val = $int->getVar($key, FALSE)) {
						// 0 A single appointment.
						// 1 A master recurring appointment.
						// 2 A single instance of a recurring appointment.
						// 3 An exception to a recurring appointment.
						// 4 An orphan instance of a recurring appointment
						if ($class == 'InstanceType' && $mver < 16.0 && $val == '4')
							continue;
						if ($key == 'MeetingMessageType') {
							if ($mver < 14.1)
								continue;
							$ext->addVar($class, $val, FALSE, $ext->setCP(XML::AS_MAIL2));
						} else
							$ext->addVar($class, $val, FALSE, $ext->setCP(XML::AS_MAIL));
						$int->restorePos($ip);
					}
					$int->restorePos($ip);
	            }
	            $rc = TRUE;
       		}
       		break;

		default:
			break;
		}

		return $rc;
	}

}

?>