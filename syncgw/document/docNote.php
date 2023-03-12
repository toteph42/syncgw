<?php
declare(strict_types=1);

/*
 * 	Notes document handler class
 *
 *  @package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document;

use syncgw\lib\Debug; //3
use syncgw\lib\DataStore;
use syncgw\lib\Util;

class docNote extends docHandler {

	// module version number
	const VER = 5;

    /**
     * 	Singleton instance of object
     * 	@var docNote
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): docNote {

	   	if (!self::$_obj) {

            self::$_obj = new self();
			// specify supported mime types. First is the preferred method
			if (file_exists(Util::mkpath('document/mime/mimPlain.php'))) {
				$class = '\\syncgw\\document\\mime\\mimPlain';
				self::$_obj->_mimeClass[] = $class::getInstance();
		   	}
			if (file_exists(Util::mkpath('document/mime/mimvNote.php'))) {
				$class = '\\syncgw\\document\\mime\\mimvNote';
				self::$_obj->_mimeClass[] = $class::getInstance();
		   	}
            if (Debug::$Conf['Script'] != 'DB' && Debug::$Conf['Script'] != 'docNote') //3
			if (file_exists(Util::mkpath('document/mime/mimAsNote.php'))) {
				$class = '\\syncgw\\document\\mime\\mimAsNote';
				self::$_obj->_mimeClass[] = $class::getInstance();
		   	}
			self::$_obj->_hid = DataStore::NOTE;
			self::$_obj->_docVer = self::VER;
			self::$_obj->_init();
	   	}

		return self::$_obj;
	}

 }

?>