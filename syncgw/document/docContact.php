<?php
declare(strict_types=1);

/*
 * 	Contact document handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document;

use syncgw\lib\DataStore;
use syncgw\lib\Util;

class docContact extends docHandler {

	// module version number
	const VER = 6;

    /**
     * 	Singleton instance of object
     * 	@var docContact
     */
    static private $_obj = NULL;

 	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): docContact {

	   	if (!self::$_obj) {

            self::$_obj = new self();
			self::$_obj->_mimeClass = [];
			if (file_exists(Util::mkpath('document/mime/mimvCard.php'))) {
				$class = '\\syncgw\\document\\mime\\mimvCard';
				self::$_obj->_mimeClass[] = $class::getInstance();
		   	}
			if (file_exists(Util::mkpath('document/mime/mimAsContact.php'))) {
				$class = '\\syncgw\\document\\mime\\mimAsContact';
				self::$_obj->_mimeClass[] = $class::getInstance();
		   	}
 			self::$_obj->_hid = DataStore::CONTACT;
			self::$_obj->_docVer = self::VER;
			self::$_obj->_init();
	   	}

		return self::$_obj;
	}

}

?>