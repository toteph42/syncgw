<?php
declare(strict_types=1);

/*
 * 	WebDAV user administration handler class
 *
 *	@package	sync*gw
 *	@subpackage	SabreDAV support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 *  @link       Sabre/DAV/Auth/Backend/AbstactBasic.php
 */

namespace syncgw\dav;

use syncgw\lib\Config;
use syncgw\lib\DataStore;
use syncgw\lib\Debug; //3
use syncgw\lib\Device;
use syncgw\lib\HTTP;
use syncgw\lib\Session;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;

class davUser extends \Sabre\DAV\Auth\Backend\AbstractBasic {

	// module version number
	const VER = 8;

    /**
     * 	Singleton instance of object
     * 	@var davUser
     */
    static private $_obj = NULL;

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): davUser {

	  	if (!self::$_obj) {
           	self::$_obj = new self();

			self::$_obj->setRealm('davUser');
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

    	$xml->addVar('Opt', _('WebDAV user handler'));
		$xml->addVar('Ver', strval(self::VER));
		$xml->addVar('Opt', '<a href="http://tools.ietf.org/html/rfc2617" target="_blank">RFC2617</a> '.
					  _('Basic access authentication handler'));
		$xml->addVar('Stat', _('Implemented'));
	}

    /**
     * Validates a username and password.
     *
     * This method should return TRUE or FALSE depending on if login
     * succeeded.
     *
     * @param 	- User name
     * @param 	- Password
     * @return 	- TRUE = Ok; FALSE = Error
     */
	protected function validateUserPass($username, $password) {

		Debug::Msg('Validate "'.$username.'" with "'.$password.'"'); //3

		$usr  = User::getInstance();
		$http = HTTP::getInstance();
		$cnf  = Config::getInstance();

		$dev = $http->getHTTPVar('REMOTE_ADDR');
		if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE //2
			&& !($cnf->getVar(Config::TRACE) & Config::TRACE_FORCE) //3
			)  //2
			if (strncmp($dev, Util::DBG_PREF, Util::DBG_PLEN)) //2
				$dev = Util::DBG_PREF.$dev; //2

		if (!$usr->Login($http->getHTTPVar('User'), $http->getHTTPVar('Password'), $dev))
			return FALSE;

		// we will only synchronize data stores once per session
		$sess = Session::getInstance();
		if (!$sess->getVar('DAVSync')) {

			$cnf  = Config::getInstance();

			// disable double calls
			$sess->updVar('DAVSync', '1');

			$ena = $cnf->getVar(Config::ENABLED);
			$dev = Device::getInstance();

			foreach ([ DataStore::CONTACT, DataStore::CALENDAR, DataStore::TASK] as $hid) {

				if (!($ena & $hid))
					continue;

				// update synchronization key
				$usr->syncKey('DAV-'.$hid, 1);
				Debug::Msg('Synchronizing data store ['.Util::HID(Util::HID_ENAME, $hid). //3
						   ']. Incrementing synchronization key to ['.$usr->syncKey('DAV-'.$hid).']'); //3

				// sync all groups (-> no late loading!)
				$ds = Util::HID(Util::HID_CNAME, $hid);
		        $ds = $ds::getInstance();
				$ds->syncDS('', TRUE);
			}
		}

		return TRUE;
	}

 }

?>