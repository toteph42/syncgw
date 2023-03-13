<?php
declare(strict_types=1);

/*
 *  Other address field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\activesync\masHandler;
use syncgw\lib\XML;

class fldAddressOther extends fldHandler {

	// module version number
	const VER 				= 6;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'AddressOther';

	// Parameter		   	TYPE="x-other"   - Alternate address

	const ASA_TAG		  	= [
								'', '', 'OtherAddressStreet', 'OtherAddressCity',
								'OtherAddressState', 'OtherAddressPostalCode', 'OtherAddressCountry'
	];
	const ASC_TAG		  	= [
								'', '', 'Street', 'City',
								'State', 'PostalCode', 'Country'
	];

   /**
     * 	Singleton instance of object
     * 	@var fldAddressOther
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldAddressOther {
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
		$addr  = [];
		$ipath .= self::TAG;

		switch ($typ) {
		case 'application/activesync.contact+xml':
			$addr = [];
			$p    = $ext->savePos();
			for ($i=0; $i < 7; $i++) {
				if (!self::ASA_TAG[$i])
					continue;
		   		$ext->xpath(self::ASA_TAG[$i], FALSE);
				$cnt = 0;
				while (($val = $ext->getItem()) !== NULL) {
					if ($val)
						$addr[$cnt][$i] = $val;
					$cnt++;
				}
				$ext->restorePos($p);
			}
			break;

		case 'application/activesync.calendar+xml':
			$addr = [];
			$p    = $ext->savePos();
			for ($i=0; $i < 7; $i++) {
				if (!self::ASC_TAG[$i])
					continue;
		   		$ext->xpath(self::ASC_TAG[$i], FALSE);
				$cnt = 0;
				while (($val = $ext->getItem()) !== NULL) {
					if ($val)
						$addr[$cnt][$i] = $val;
					$cnt++;
				}
				$ext->restorePos($p);
			}
			break;

		default:
			break;
		}

		// any data loaded?
		if (count($addr)) {
	   		parent::delTag($int, $ipath);
	   		$ip = $int->savePos();
	   		for ($cnt=0; isset($addr[$cnt]); $cnt++) {
				$int->addVar(self::TAG);
				$p = $int->savePos();
				for ($i=0; $i < 7; $i++) {
					if (isset($addr[$cnt][$i]))
						$int->addVar(fldAddresses::SUB_TAG[$i], $addr[$cnt][$i]);
				}
				$int->restorePos($p);
			}
			$int->restorePos($ip);
			$rc = TRUE;
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
		case 'application/activesync.contact+xml':
			while ($int->getItem() !== NULL) {
				$ip = $int->savePos();
				for ($i=0; $i < 7; $i++) {
					if (self::ASA_TAG[$i] && ($val = $int->getVar(fldAddresses::SUB_TAG[$i], FALSE)) !== NULL) {
						$ext->addVar(self::ASA_TAG[$i], $val, FALSE, $ext->setCP(XML::AS_CONTACT));
						$rc = TRUE;
						$int->restorePos($ip);
					}
				}
			}
			break;

		case 'application/activesync.calendar+xml':
			$mas = masHandler::getInstance();
			if ($mas->callParm('BinVer') < 16.0)
				break;

			while ($int->getItem() !== NULL) {
				$ip = $int->savePos();
				for ($i=0; $i < 7; $i++) {
					if (self::ASC_TAG[$i] && ($val = $int->getVar(fldAddresses::SUB_TAG[$i], FALSE)) !== NULL) {
						$ext->addVar(self::ASC_TAG[$i], $val, FALSE, $ext->setCP(XML::AS_BASE));
						$rc = TRUE;
						$int->restorePos($ip);
					}
				}
			}
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>