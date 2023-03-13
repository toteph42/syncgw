<?php
declare(strict_types=1);

/*
 * 	Calendar document handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document;

use syncgw\lib\DataStore;
use syncgw\lib\Util;

class docCalendar extends docHandler {

	// module version number
	const VER = 5;

    /**
     * 	Singleton instance of object
     * 	@var docCalendar
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): docCalendar {

	   	if (!self::$_obj) {

            self::$_obj = new self();
			self::$_obj->_mimeClass = [];
			if (file_exists(Util::mkpath('document/mime/mimvCal.php'))) {
				$class = '\\syncgw\\document\\mime\\mimvCal';
				self::$_obj->_mimeClass[] = $class::getInstance();
		   	}
			if (file_exists(Util::mkpath('document/mime/mimAsCalendar.php'))) {
				$class = '\\syncgw\\document\\mime\\mimAsCalendar';
				self::$_obj->_mimeClass[] = $class::getInstance();
		   	}
			self::$_obj->_hid = DataStore::CALENDAR;
			self::$_obj->_docVer = self::VER;
			self::$_obj->_init();
		}

		return self::$_obj;
	}

}

?>