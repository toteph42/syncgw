<?php
declare(strict_types=1);

/*
 *  RelChild field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\XML;

class fldChild extends fldHandler {

	// module version number
	const VER = 5;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 			 = 'Child';

   /**
     * 	Singleton instance of object
     * 	@var fldChild
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldChild {

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
		$ipath .= self::TAG;

		switch ($typ) {
		case 'application/activesync.contact+xml':
	   		if ($ext->xpath($xpath.'/Child', FALSE))
				parent::delTag($int, $ipath, '2.5');
				while (($val = $ext->getItem()) !== NULL) {
				$int->addVar(self::TAG, $val);
				$rc = TRUE;
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

		$rc = FALSE;

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'application/activesync.contact+xml':
			while (($val = $int->getItem()) !== NULL) {
				if (!$rc) {
					$p = $ext->savePos();
					$ext->addVar('Children');
					$ext->setAttr($ext->setCP(XML::AS_CONTACT));
				}
				$ext->addVar('Child', $val);
				$rc = TRUE;
			}
			if ($rc)
				$ext->restorePos($p);
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>