<?php
declare(strict_types=1);

/*
 * 	MIME decoder / encoder for ActiveSync document class
 *
 *	@package	sync*gw
 *	@subpackage	MIME support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\mime;

use syncgw\lib\DataStore;

class mimAsdocLib extends mimAs {

	// module version number
	const VER = 2;

	const MIME = [

		// note: this is a virtual non-existing MIME type
		[ 'application/activesync.docLib+xml', 1.0 ],
	];
	const MAP = [
	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	// Document source     													Exchange ActiveSync: Document Class Protocol
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
		'LinkId'															=> 'fldLinkId',
        'DisplayName'														=> 'fldFullName',
	    'CreationDate'														=> 'fldCreated',
	    'LastModifiedDate'													=> 'fldLastMod',
        'IsFolder'															=> 'fldIsFolder',
	    'IsHidden'															=> 'fldIsHidden',
	    'ContentLength'														=> 'fldContentLength',
	    'ContentType'														=> 'fldContentType',
	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	];

   	/**
     * 	Singleton instance of object
     * 	@var mimAsdocLib
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): mimAsdocLib {

		if (!self::$_obj) {
            self::$_obj = new self();

			self::$_obj->_ver  = self::VER;
			self::$_obj->_mime = self::MIME;
			self::$_obj->_hid  = DataStore::docLib;
			foreach (self::MAP as $tag => $class) {
			    $class = 'syncgw\\document\\field\\'.$class;
			    $class = $class::getInstance();
			    self::$_obj->_map[$tag] = $class;
			}
		}

		return self::$_obj;
	}

}

?>