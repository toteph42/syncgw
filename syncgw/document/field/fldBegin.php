<?php
declare(strict_types=1);

/*
 *  Begin field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Config;
use syncgw\lib\XML;

class fldBegin extends fldHandler {

	// module version number
	const VER = 4;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 			 = 'Begin';

   /**
     * 	Singleton instance of object
     * 	@var fldBegin
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldBegin {

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
		return TRUE;
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
		$cnf  = Config::getInstance();
		$tags = explode('/', $xpath);

		switch ($typ) {
		case 'text/x-vnote':
		case 'text/x-vnote':
			$rc = [[ 'T' => $tags[1],		'P' => [], 'D' => $tags[0] ],
				   [ 'T' => 'VERSION', 		'P' => [], 'D' => sprintf('%.1F', $ver) ],
		   		   [ 'T' => 'X-PRODID', 	'P' => [], 'D' => '-//Florian Daeumling//NONSGML syncgw '.$cnf->getVar(Config::FULLVERSION) ]];
			break;

		case 'text/vcard':
		case 'text/x-vcard':
			$rc = [[ 'T' => $tags[1],		'P' => [], 'D' => $tags[0] ],
				   [ 'T' => 'VERSION', 		'P' => [], 'D' => sprintf('%.1F', $ver) ],
		   		   [ 'T' => 'PRODID', 		'P' => [], 'D' => '-//Florian Daeumling//NONSGML syncgw '.$cnf->getVar(Config::FULLVERSION) ]];
			break;

		case 'text/calendar':
		case 'text/x-vcalendar':
			if ($tags[1] != 'BEGIN')
				$rc = [[ 'T' => 'BEGIN',	'P' => [], 'D' => $tags[1] ]];
			else
				$rc = [[ 'T' => 'BEGIN',	'P' => [], 'D' => $tags[0] ],
					   [ 'T' => 'VERSION', 	'P' => [], 'D' => sprintf('%.1F', $ver) ],
	   		   		   [ 'T' => 'PRODID', 	'P' => [], 'D' => '-//Florian Daeumling//NONSGML syncgw '.$cnf->getVar(Config::FULLVERSION) ]];
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>