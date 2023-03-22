<?php
declare(strict_types=1);

/*
 * 	Document library document handler class
 *
 *  @package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document;

use syncgw\lib\DataStore;
use syncgw\document\mime\mimAsDocLib;

class DocLib extends docHandler {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var DocLib
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): DocLib {

	   	if (!self::$_obj) {

            self::$_obj = new self();
			self::$_obj->_mimeClass = [
	    				mimAsDocLib::getInstance(),
			];
			self::$_obj->_hid 		= DataStore::DOCLIB;
			self::$_obj->_docVer 	= self::VER;
			self::$_obj->_init();
	   	}

		return self::$_obj;
	}

}

?>