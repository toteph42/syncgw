<?php
declare(strict_types=1);

/*
 *  MIME decoder / encoder for "text/x-vnote" data class
 *
 *	@package	sync*gw
 *	@subpackage	MIME support
 *  @uses		RFC2425, RFC5234
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\mime;

use syncgw\lib\DataStore;
use syncgw\lib\XML;

class mimvNote extends mimHandler {

	// module version number
	const VER = 6;

	const MIME = [

		[ 'text/x-vnote', 1.1 ],
	];
	const MAP = [
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
	// Document source     															IrMC_v1p1.pdf
	// Chapter Reference   															10.7.3
	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
		'VNOTE/BEGIN'																=> 'fldBegin',
	//  'VNOTE/VERSION'																	// Handled by fldBegin
	//	'VNOTE/X-PRODID'																// Handled by fldBegin
        'VNOTE/X-IRMC-LUID'															=> 'fldUid',
	    'VNOTE/DCREATED'															=> 'fldCreated',
	    'VNOTE/LAST-MODIFIED'														=> 'fldLastMod',
	    'VNOTE/SUMMARY'																=> 'fldSummary',
	    'VNOTE/BODY'																=> 'fldBody',
	    'VNOTE/CATEGORIES,'															=> 'fldCategories',
	    'VNOTE/CLASS'																=> 'fldClass',
	    'VNOTE/END'																	=> 'fldEnd',
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
	];

    /**
     * 	Singleton instance of object
     * 	@var mimvNote
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): mimvNote {

		if (!self::$_obj) {
            self::$_obj = new self();

			self::$_obj->_ver  = self::VER;
			self::$_obj->_mime = self::MIME;
			self::$_obj->_hid  = DataStore::NOTE;
			foreach (self::MAP as $tag => $class) {
			    $class = 'syncgw\\document\\field\\'.$class;
			    $class = $class::getInstance();
			    self::$_obj->_map[$tag] = $class;
			}
		}

		return self::$_obj;
	}

    /**
	 * 	Get information about class
	 *
     *	@param 	- TRUE = Check status; FALSE = Provide supported features
	 * 	@param 	- Object to store information
	 */
	public function Info(bool $mod, XML $xml): void {

		if (!$mod) {
			$xml->addVar('Opt', 'vNote handler');
			$xml->addVar('Ver', strval(self::VER));
		}

		parent::Info($mod, $xml);
	}

}

?>