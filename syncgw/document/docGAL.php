<?php
declare(strict_types=1);

/*
 * 	Global address list document handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document;

use syncgw\lib\DataStore;
use syncgw\document\mime\mimAsGAL;

class docGAL extends docHandler {

	// module version number
	const VER = 1;

    /**
     * 	Singleton instance of object
     * 	@var docGAL
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): docGAL {

	   	if (!self::$_obj) {

            self::$_obj = new self();
			self::$_obj->_mimeClass = [
					mimAsGAL::getInstance(),
			];
			self::$_obj->_hid 		= DataStore::GAL;
			self::$_obj->_docVer	= self::VER;
			self::$_obj->_init();
	   	}

		return self::$_obj;
	}

}

?>