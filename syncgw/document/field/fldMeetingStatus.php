<?php
declare(strict_types=1);

/*
 *  Meeting status status field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\lib\XML;

class fldMeetingStatus extends fldHandler {

	// module version number
	const VER = 6;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'MeetingStatus';

	const ASC_MAP		  	= [
		'0'					=> 'NO-ATTENDEES',		   	// The event is an appointment, which has no attendees
		'1'					=> 'ORGANIZER-MEETING',	  	// The event is a meeting and the user is the meeting organizer
		'3'					=> 'MEETING',				// This event is a meeting, and the user is not the meeting organizer
		'5'					=> 'ORGANIZER-CANCELLED',	// The meeting has been canceled and the user was the meeting organizer
		'7'					=> 'CANCELLED',			  	// The meeting has been canceled
		'9'					=> 'ORGANIZER-MEETING',	  	// Same as 1
		'11'			   	=> 'MEETING',				// Same as 3
		'13'			   	=> 'ORGANIZER-CANCELLED',	// Same as 5
		'15'			   	=> 'CANCELLED',			  	// Same as 7
	];

   	/**
     * 	Singleton instance of object
     * 	@var fldMeetingStatus
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldMeetingStatus {
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
		case 'application/activesync.calendar+xml':
	   		if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath, '12.0');
			while (($val = $ext->getItem()) !== NULL) {
				if (!strlen($val))
					continue;
				if (isset(self::ASC_MAP[$val])) {
					$int->addVar(self::TAG, self::ASC_MAP[$val]);
					$rc = TRUE;
				}
				else //3
					Debug::Msg('['.$xpath.'] invalid value ['.$val.'] - dropping record'); //3
			}
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
		$rc   = FALSE;
		$tags = explode('/', $xpath);
		$tag  = array_pop($tags);

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'application/activesync.calendar+xml':
			while (($val = $int->getItem()) !== NULL) {
				foreach (self::ASC_MAP as $k => $v) {
					if ($val == $v)
						break;
				}
				$ext->addVar($tag, strval($k), FALSE, $ext->setCP(XML::AS_CALENDAR));
				$rc	= TRUE;
			}
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>