<?php
declare(strict_types=1);

/*
 *  Meeting message type status field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\activesync\masHandler;
use syncgw\lib\XML;

class fldMeetingType extends fldHandler {

	// module version number
	const VER = 2;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 			 = 'MeetingType';

   	/**
     * 	Singleton instance of object
     * 	@var fldMeetingType
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldMeetingType {
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
	   		if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath);
			// 0 A silent update was performed, or the message type is unspecified.
			// 1 Initial meeting request.
			// 2 Full update.
			// 3 Informational update.
			// 4 Outdated. A newer meeting request or meeting update was received after this message.
			// 5 Identifies the delegator's copy of the meeting request.
			// 6 Identifies that the meeting request has been delegated and the meeting request MUST NOT be responded to.<1>
			while (($val = $ext->getItem()) !== NULL) {
				if ($val > -1 && $val < 7) {
					$int->addVar(self::TAG, $val);
					$rc = TRUE;
				}
				else //3
					Debug::Msg('['.$xpath.'] - invalid value "'.$val.'"'); //3
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
		case 'application/activesync.mail+xml':
			$mas = masHandler::getInstance();
			if ($mas->callParm('BinVer') < 14.1)
				break;
			while (($val = $int->getItem()) !== NULL) {
				$ext->addVar($tag, $val, FALSE, $ext->setCP(XML::AS_MAIL2));
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