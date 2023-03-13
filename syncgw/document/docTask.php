<?php
declare(strict_types=1);

/*
 * 	Task document handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document;

use syncgw\lib\DataStore;
use syncgw\lib\Util;

class docTask extends docHandler {

	// module version number
	const VER = 5;

    /**
     * 	Singleton instance of object
     * 	@var docTask
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): docTask {

	   	if (!self::$_obj) {

            self::$_obj = new self();
			if (file_exists(Util::mkpath('document/mime/mimvTask.php'))) {
				$class = '\\syncgw\\document\\mime\\mimvTask';
				self::$_obj->_mimeClass[] = $class::getInstance();
		   	}
			if (file_exists(Util::mkpath('document/mime/mimAsTask.php'))) {
				$class = '\\syncgw\\document\\mime\\mimAsTask';
				self::$_obj->_mimeClass[] = $class::getInstance();
		   	}
 			self::$_obj->_hid 		= DataStore::TASK;
			self::$_obj->_docVer 	= self::VER;

			self::$_obj->_init();
	   	}

		return self::$_obj;
	}

}

?>