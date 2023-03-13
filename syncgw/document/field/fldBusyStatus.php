<?php
declare(strict_types=1);

/*
 *  Busy status field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\lib\XML;

class fldBusyStatus extends fldHandler {

	// module version number
	const VER = 9;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'BusyStatus';

	const ASC_MAP		  	= [
	//	'-1'			   	=> 'UNKNOWN',				// not supported
		'0'					=> 'FREE',
		'1'					=> 'TENTATIV',
		'2'					=> 'BUSY',
		'3'					=> 'OOF',
	//	'4'					=> 'WORKINGELSEWHERE',		// not supported
	];

   /**
     * 	Singleton instance of object
     * 	@var fldBusyStatus
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldBusyStatus {

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
		case 'application/activesync.calendar+xml':
	   		if ($ext->xpath($xpath, FALSE))
				parent::delTag($int, $ipath, $typ = 'application/activesync.calendar+xml' ? '16.0' : '');
			while (($val = $ext->getItem()) !== NULL) {
				if (!strlen($val))
					continue;
				if (isset(self::ASC_MAP[$val])) {
					$int->addVar(self::TAG, self::ASC_MAP[$val]);
					$rc = TRUE;
				}
				else //3
					Debug::Msg('['.$xpath.'] invalid value "'.$val.'" - dropping record'); //3
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
		$cp   = NULL;
		$tags = explode('/', $xpath);
		$tag  = array_pop($tags);

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'application/activesync.mail+xml':
			$cp = XML::AS_MAIL;

		case 'application/activesync.calendar+xml':
			if (!$cp)
				$cp = XML::AS_CALENDAR;

			$a = array_flip(self::ASC_MAP);
			while (($val = $int->getItem()) !== NULL) {
				if ($a[$val] == -1 || $a[$val] == 4)
					continue;
				$ext->addVar($tag, strval($a[$val]), FALSE, $ext->setCP($cp));
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