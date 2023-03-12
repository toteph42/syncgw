<?php
declare(strict_types=1);

/*
 * 	Process HTTP input / output
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\lib\Config;
use syncgw\lib\XML;
use syncgw\lib\HTTP;

class guiHTTP extends HTTP {

	// module version number
	const VER = 2;

    /**
     * 	Singleton instance of object
     * 	@var guiHTTP
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiHTTP {

		if (!self::$_obj) {
            self::$_obj = new self();
            parent::getInstance();
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

		$xml->addVar('Opt', _('User interface HTTP handler'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Check HTTP input
	 *
	 * 	@return - HTTP status code
	 */
	public function checkIn(): int {

		$cnf = Config::getInstance();

		// is debug running?
		if ($cnf->getVar(Config::DBG_LEVEL) != Config::DBG_OFF) //3
			return 200; //3

		// check for common browser types
		if ($ua = isset(self::$_http[HTTP::SERVER]['HTTP_USER_AGENT']) ? self::$_http[HTTP::SERVER]['HTTP_USER_AGENT'] : '')
	      	foreach ([ 'firefox', 'safari', 'webkit', 'opera', 'netscape', 'konqueror', 'gecko' ] as $name) {

			    // are we called by a internet browser?
      			if (stripos($ua, $name) !== FALSE) {
				   $cnf->updVar(Config::HD, 'GUI');
				   break;
		     	}
	    	}

		return 200;
	}

	/**
	 * 	Check HTTP output
	 *
	 * 	@return - HTTP status code
	 */
	public function checkOut(): int {

		// output processing
		$cnf = Config::getInstance();
		if ($cnf->getVar(Config::HD) != 'GUI')
			return 200;

		self::$_http[self::SND_HEAD]['Content-Length'] = strlen(self::$_http[self::SND_BODY]);

		return 200;
	}

}

?>