<?php
declare(strict_types=1);

/*
 * 	Data base handler class
 *
 *	@package	sync*gw
 *	@subpackage	myApp cpnnector
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\interfaces\myapp;

use syncgw\interfaces\DBextHandler;
use syncgw\lib\Config;
use syncgw\lib\Log;
use syncgw\lib\Server;
use syncgw\lib\DataStore;
use syncgw\lib\User;
use syncgw\lib\XML;

require_once(__DIR__.DIRECTORY_SEPARATOR.'Notes.php');

class Handler extends \syncgw\interfaces\mysql\Handler implements DBextHandler {

	/**
	 * 	Note handler
	 * 	@var Notes
	 */
	private $_hd;

	/**
	 * 	External user id
	 * 	@var int
	 */
	private $_uid = -1;

	/**
	 * 	External data base handler
	 * 	@var \mysqli
	 */
	private $_ext;

	/**
	 * 	Internal data base handler
	 * 	@var Handler
	 */
	private $_int;

	/**
     * 	Singleton instance of object
     * 	@var Handler
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Handler {

		if (!self::$_obj) {

            self::$_obj = new self();

			// set messages to use 90001-91000
			$log = Log::getInstance();
			$log->setMsg([
					90001 => 'myApp application handler error "%s"',
			]);

			$cnf = Config::getInstance();

			// are we responsible?
			if ($cnf->getVar(Config::DATABASE) != 'myapp')
				return self::$_obj;

			// load data base access parameter
			$parm = [ Config::DB_HOST, 	Config::DB_PORT, 		Config::DB_USR,
					  Config::DB_UPW, 	Config::DB_NAME, 		Config::DB_PREF ];
			$conf = [];
			foreach ($parm as $k)
				$conf[$k] = $cnf->getVar($k);

			// connect to data base
			if (!(self::$_obj->_ext = new \mysqli($conf[Config::DB_HOST], $conf[Config::DB_USR], $conf[Config::DB_UPW],
	                         		      $conf[Config::DB_NAME], $conf[Config::DB_PORT])))
	            $log->Msg(Log::INFO, 90001, $cnf->getVar('Usr_Parm'));

	        // initialize parent handler
	        self::$_obj->_int = parent::getInstance();

			// create data store handler object
			self::$_obj->_hd = new Notes();

			// register shutdown function
			$srv = Server::getInstance();
			$srv->regShutdown(__CLASS__);
		}

		return self::$_obj;
	}

    /**
	 * 	Shutdown function
	 */
	public function delInstance(): void {

		// do your cleanup here
		// ...

		self::$_obj->_int::delInstance();

		self::$_obj = NULL;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Name', _('MyApp data base handler'));
		$xml->addVar('Ver', '1');

		$class = '\\syncgw\\interfaces\\myapp\\Admin';
		$class = $class::getInstance();
		$class->getInfo($xml, $status);

		if (!$status)
			return;

		$xml->addVar('Opt', _('Status'));
		$cnf = Config::getInstance();
		if ($cnf->getVar(Config::DATABASE) == 'myapp')
			$xml->addVar('Stat', _('Enabled'));
		else
			$xml->addVar('Stat', _('Disabled'));
	}

	/**
	 * 	Authorize user in external data base
	 *
	 * 	@param	- User name
	 * 	@param 	- Host name
	 * 	@param	- User password
	 * 	@return - TRUE=Ok; FALSE=Not authorized
 	 */
	public function Authorize(string $user, string $host, string $passwd): bool {

		// get password
    	if (!($obj = $this->_ext->query('SELECT `password`, `id` FROM `myapp_usertable` WHERE `username` = "'.$user.'"')))
    		return FALSE;

		// get data
    	$rec = $obj->fetch_assoc();

    	// user not found?
    	if (!isset($rec['password']))
    		return FALSE;

    	// check password
    	if (strcmp($passwd, $rec['password']))
    		return FALSE;

	    // save user id
	    $this->_uid = intval($rec['id']);

	    // load internal user object
	    $usr = User::getInstance();

	    return $usr->loadUsr($user, $host);
	}

	/**
	 * 	Perform query on external data base
	 *
	 * 	@param	- Handler ID
	 * 	@param	- Query command:<fieldset>
	 * 			  DataStore::ADD 	  Add record                             $parm= XML object<br>
	 * 			  DataStore::UPD 	  Update record                          $parm= XML object<br>
	 * 			  DataStore::DEL	  Delete record or group (inc. sub-recs) $parm= GUID<br>
	 * 			  DataStore::RGID     Read single record       	             $parm= GUID<br>
	 * 			  DataStore::GRPS     Read all group records                 $parm= None<br>
	 * 			  DataStore::RIDS     Read all records in group              $parm= Group ID or '' for record in base group
	 * 	@return	- According  to input parameter<fieldset>
	 * 			  DataStore::ADD 	  New record ID or FALSE on error<br>
	 * 			  DataStore::UPD 	  TRUE=Ok; FALSE=Error<br>
	 * 			  DataStore::DEL	  TRUE=Ok; FALSE=Error<br>
	 * 			  DataStore::RGID	  XML object; FALSE=Error<br>
	 * 			  DataStore::GRPS	  [ "GUID" => Typ of record ]<br>
	 * 			  DataStore::RIDS     [ "GUID" => Typ of record ]
	 */
	public function Query(int $hid, int $cmd, $parm = '') {

		// we don't serve internal calls
		if (!($hid & DataStore::EXT))
			return $this->_int->Query($hid, $cmd, $parm);

		// user ID set?
		// check handler called
		if ($this->_uid == -1 || !($hid & DataStore::EXT|DataStore::NOTE))
			return ($cmd & (DataStore::RIDS|DataStore::RNOK)|DataStore::GRPS) ? [] : FALSE;

		// perform query
		return $this->_hd->Query($this->_ext, $this->_uid, $cmd, $parm);
	}

	/**
	 * 	Get list of supported fields in external data base
	 *
	 * 	@param	- Handler ID
	 * 	@return	- [ field name ]
	 */
	public function getflds(int $hid): array {

		return $this->_hd->getflds($hid);
	}

	/**
	 * 	Reload any cached record information in external data base
	 *
	 * 	@param	- Handler ID
	 * 	@return	- TRUE=Ok; FALSE=Error
	 */
	public function Refresh(int $hid): bool {

		return $this->_hd->Refresh($hid);
	}

	/**
	 * 	Check trace record references
	 *
	 *	@param 	- Handler ID
	 * 	@param 	- External record array [ GUID ]
	 * 	@param 	- Mapping table [HID => [ GUID => NewGUID ] ]
	 */
	public function chkTrcReferences(int $hid, array $rids, array $maps): void {

		$this->_hd->chkTrcReferences($hid, $rids, $maps);
	}

	/**
	 * 	Convert internal record to MIME
	 *
	 * 	@param	- Internal document
	 * 	@return - MIME message or NULL
	 */
	public function cnv2MIME(XML &$int): ?string {

		return NULL;
	}

	/**
	 * 	Convert MIME string to internal record
	 *
	 *	@param 	- External record id
	 * 	@param	- MIME message
	 * 	@return	- Internal record or NULL
	 */
	public function cnv2Int(string $rid, string $mime): ?XML {

		return NULL;
	}

	/**
	 * 	Send mail
	 *
	 * 	@param	- TRUE=Save in Sent mail box; FALSE=Only send mail
	 * 	@param	- MIME data OR XML document
	 * 	@return	- Internal XML document or NULL on error
	 */
	public function sendMail(bool $save, $doc): ?XML {

		return NULL;
	}

}
?>