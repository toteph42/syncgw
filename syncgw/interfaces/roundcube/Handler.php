<?php
declare(strict_types=1);

/*
 * 	Data base handler class
 *
 *	@package	sync*gw
 *	@subpackage	RoundCube data base
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\interfaces\roundcube;

use syncgw\lib\Debug; //3
use syncgw\interfaces\DBextHandler;
use syncgw\lib\Config;
use syncgw\lib\DB; //3
use syncgw\lib\DataStore;
use syncgw\lib\ErrorHandler;
use syncgw\lib\Log;
use syncgw\lib\Server;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;
use rcmail;
use rcube_user;

class Handler extends \syncgw\interfaces\mysql\Handler implements DBextHandler {

	// module version number
	const VER = 25;

	/**
	 * 	Group record
	 *
	 *  Handler::GROUP 	- Group id
	 *  Handler::NAME 	- Name
	 *  Handler::COLOR	- Color
	 *  Handler::LOAD	- Group loaded
	 *  Handler::ATTR	- fldAttribute flags
	 *
	 *	Categories
	 *
	 *  Handler::GROUP 	- Group id
	 *  Handler::NAME 	- Name
	 *  Handler::REFS 	- Number of references
	 *
	 *  Data record
	 *
	 *  Handler::GROUP 	- Group id
	 *  Handler::CID	- Category1;Category2...
	 *
	 **/
	const GROUP       	 = 'Group';							// record group
	const NAME        	 = 'Name';							// name of record
	const COLOR       	 = 'Color';							// color of group
	const LOAD 		  	 = 'Loaded';						// group is loaded
	const ATTR 		  	 = 'Attr';							// group attributes
	const REFS        	 = 'References';					// file reference
	const CID         	 = 'Category';						// record category

	const PLUGIN      	 = [ 'syncgw_rc', '1.0.1' ];

	// constants from roundcube_select_for_sync.php
	const MAIL_FULL   	 = 'M';								// full mail box
	const ABOOK_MERGE 	 = 'X';								// merge address books
	const ABOOK_FULL  	 = 'A';								// full address book
	const ABOOK_SMALL  	 = 'P';								// only contacts with tlephone number assigned
	const CAL_FULL    	 = 'C';								// full calendar
	const TASK_FULL   	 = 'T';								// full task list
	const NOTES_FULL  	 = 'N';								// full notes

 	/**
	 * 	Roundcube mail handler
	 * 	@var rcmail
	 */
	public $RCube		 = NULL;

	/**
	 *  Time zone offset in seconds
	 *  @var int
	 */
	public $tzOffset 	 = 0;

	/**
	 * 	Retry counter
	 * 	@var int
	 */
	public $Retry 		 = 0;

    /**
	 * 	Handler table
	 * 	@var array
	 */
	private $_hd		 = [];

    /**
     * 	Singleton instance of object
     * 	@var Handler
     */
    private static $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Handler {

		$cnf = Config::getInstance();

		// set error filter
		if (isset(Debug::$Conf['Status'])) { //3
			ErrorHandler::filter(E_NOTICE|E_DEPRECATED|E_WARNING, 'rcube_vcard.php'); //3
			ErrorHandler::filter(E_NOTICE|E_DEPRECATED|E_WARNING, 'bootstrap.php'); //3
			ErrorHandler::filter(E_NOTICE|E_DEPRECATED|E_WARNING, 'plugins'.DIRECTORY_SEPARATOR.'ident_switch'); //3
			ErrorHandler::filter(E_NOTICE|E_DEPRECATED|E_WARNING, 'plugins'.DIRECTORY_SEPARATOR.'globaladdressbook'); //3
			ErrorHandler::filter(E_NOTICE|E_DEPRECATED|E_WARNING, 'plugins'.DIRECTORY_SEPARATOR.'contextmenu_folder'); //3
			ErrorHandler::filter(E_NOTICE|E_DEPRECATED|E_WARNING, 'plugins'.DIRECTORY_SEPARATOR.'message_highlight'); //3
			ErrorHandler::filter(E_NOTICE|E_DEPRECATED|E_WARNING, 'plugins'.DIRECTORY_SEPARATOR.'calendar'); //3
			ErrorHandler::filter(E_NOTICE|E_DEPRECATED|E_WARNING, 'plugins'.DIRECTORY_SEPARATOR.'libcalendaring'); //3
		} else //3
			ErrorHandler::filter(E_NOTICE|E_WARNING|E_DEPRECATED, $cnf->getVar(Config::RC_DIR));

		if (!self::$_obj) {

			self::$_obj = new self();

			// set messages 20301-20400
			$log = Log::getInstance();
			$log->setMsg([

					// warning messages
					20301 => _('Cannot locate RoundCube file [%s]'),
			        20302 => _('Plugin \'%s\' not available - handler disabled'),

					// Error reading external contact record [R91919]
					20311 => _('Error reading external %s [%s]'),
					// Error adding external address book
					20312 => _('Error adding external %s'),
					// Error updating external contact record [R774]
					20313 => _('Error updating external %s [%s]'),
					// Error deleting external address book [G383]
					20314 => _('Error deleting external %s [%s]'),
					// Record [R774] in adress book is read-only
					20315 => _('Record [%s] in %s is read-only'),
					20316 => _('RoundCube authorization failed for user (%s) (Error code: %d)'),
					20317 => _('Record [%s] in %s not found')
							._(' - if you\'re debugging please check synchonization status in RoundCube') //3
					,

					// error messages
					20350 => _('No %s enabled for synchronization [%s]'),
					20351 => _('MySQL error: %s in %s driver in line %d'),
					20352 => _(' set_include_path() failed'),
			]);

			// data base enabled?
			if (strpos($be = $cnf->getVar(Config::DATABASE), 'roundcube') === NULL && strcmp($be, 'mail') === NULL)
				return self::$_obj;

			// roundcube ini file name
			$path = $cnf->getVar(Config::RC_DIR).DIRECTORY_SEPARATOR;
			// required for roundcube
			if (!defined('INSTALL_PATH'))
		    	define('INSTALL_PATH', $path);

		    // check file names
		    $ini = $path.'program'.DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'iniset.php';
		    if (!file_exists($ini)) {
		        $log->Msg(Log::ERR, 20301, $ini);
		        ErrorHandler::resetReporting();
 		        return self::$_obj;
		    }
		    $mail = $path.'program'.DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'rcmail.php';
		    if (!file_exists($mail)) {
		        $log->Msg(Log::ERR, 20301, $mail);
		        ErrorHandler::resetReporting();
 		        return self::$_obj;
		    }

			// include ./program/include in loading
			if (!strpos($i = ini_get('include_path'), INSTALL_PATH)) {
	            $i = INSTALL_PATH . 'program'.DIRECTORY_SEPARATOR.'include'.PATH_SEPARATOR.$i;
	            if (!set_include_path($i)) {
		        	$log->Msg(Log::ERR, 20352);
		        	 return self::$_obj;
	            }
			}

	        // startup RoundCube environment
	        require_once($ini);
			require_once($mail);

			// reset max. execution timeout
			@set_time_limit($cnf->getVar(Config::EXECUTION));

			// get instance
			self::$_obj->RCube = rcmail::get_instance();

			// check main plugin
			if ((!$a = self::$_obj->RCube->plugins->get_info(self::PLUGIN[0])) ||
				($a['version'] != 'dev-master' && version_compare(self::PLUGIN[1], $a['version']) > 0)) {
	        	$log->Msg(Log::WARN, 20302, self::PLUGIN[0]);
		        ErrorHandler::resetReporting();
 	        	return self::$_obj;
	    	}

	        // check and allocate handlers
	        foreach ([ DataStore::CALENDAR, DataStore::CONTACT, DataStore::NOTE, DataStore::TASK, DataStore::MAIL] as $hid) {

	 	    	// no plugin required
	        	if ($hid & DataStore::CONTACT)
	   	       		$class = 'Contact';
	        	elseif ($hid & DataStore::CALENDAR)
	    	       	$class  = 'Calendar';
			    elseif ($hid & DataStore::TASK)
	    	       	$class  = 'Task';
			    elseif ($hid & DataStore::NOTE)
		   	       	$class  = 'Note';

	             // get handler file name
		       	$file = Util::mkPath('interfaces/roundcube/').$class.'.php';
				if (!file_exists($file))
					continue;

				// allocate handler
				$class = 'syncgw\\interfaces\\roundcube\\'.$class;
				if (!(self::$_obj->_hd[$hid] = $class::getInstance(self::$_obj))) {
					unset(self::$_obj->_hd[$hid]);
	       	   		$log->Msg(Log::WARN, 20302, Util::HID(Util::HID_ENAME, $hid));
					Debug::Msg('Enabling data store handler "'.$file.'"'); //3
				}
	        }

	        // initialize parent handler
	        self::$_obj->_hd[DataStore::SYSTEM] = parent::getInstance();

			// register shutdown function
			$srv = Server::getInstance();
			$srv->regShutdown(__CLASS__);

	        ErrorHandler::resetReporting();
		}

		return self::$_obj;
	}

	/**
	 * 	Shutdown function
	 */
	public function delInstance(): void {

		// save synchronization preferences
		if (!self::$_obj)
			return;

		self::$_obj->_hd[DataStore::SYSTEM]->delInstance();

		// reset error reporting
		ErrorHandler::resetReporting();

		self::$_obj = NULL;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$n = 'RoundCube data base handler ';
		$xml->addVar('Name', $n);
		$xml->addVar('Ver', strval(self::VER));

		$class = '\\syncgw\\interfaces\\roundcube\\Admin';
		$class = $class::getInstance();
		$class->getInfo($xml, $status);

		if (!$status) {
			$xml->addVar('Opt', 'RoundCube v'.RCMAIL_VERSION);
			$xml->addVar('Stat', _('Impelemented'));
		} else {
			$xml->addVar('Opt', _('Status'));
			$cnf = Config::getInstance();
			if (($be = $cnf->getVar(Config::DATABASE)) == 'roundcube')
				$xml->addVar('Stat', _('Enabled'));
			elseif ($be == 'mail')
				$xml->addVar('Stat', _('Sustentative'));
			else {
				$xml->addVar('Stat', _('Disabled'));
				return;
			}

			$xml->addVar('Opt', _('Application root directory'));
			$p = $cnf->getVar(Config::RC_DIR);
			if (!file_exists($p.DIRECTORY_SEPARATOR.'program')) {
				$xml->addVar('Stat', _('+++ ERROR: Base directory not found!'));
				returnn;
			}
			$xml->addVar('Stat', '"'.$p.'"');

			$xml->addVar('Opt', _('Connected to RoundCube'));
			$xml->addVar('Stat', (count($this->_hd) ? _('v').' '.RCMAIL_VERSION : _('Off')));

			$xml->addVar('Opt', _('Data base handler'));
			if (!count($this->_hd)) {
				$xml->addVar('Stat', _('+++ ERROR: Initialization failed!'));
				return;
			}
			$xml->addVar('Stat', _('Initialized'));
		}

		$p = self::PLUGIN[0];
		$i = $this->RCube->plugins->get_info($p);
		$a = $this->RCube->plugins->active_plugins;
		$xml->addVar('Opt', '<a href="https://plugins.roundcube.net/#/packages/syncgw/roundcube-select_for_sync" target="_blank">'.$p.'</a> '.
				      ' plugin v'.self::PLUGIN[1]);
		if (!in_array($p, $a)) {
			ErrorHandler::resetReporting();
			$xml->addVar('Stat', sprintf(_('+++ ERROR: "%s" not active!'), $p));
		} elseif ($i['version'] != 'dev-master' && version_compare(self::PLUGIN[1], $i['version']) > 0) {
			ErrorHandler::resetReporting();
			$xml->addVar('Stat', sprintf(_('+++ ERROR: Require plugin version "%s" - "%s" found!'),
						  self::PLUGIN[1], $i['version']));
		} else
			$xml->addVar('Stat', _('Implemented'));

		// get handler info
		foreach ($this->_hd as $hd => $obj) {
			if (is_object(($obj)) && $hd != DataStore::SYSTEM)
				$obj->getInfo($xml, $status);
		}

		ErrorHandler::resetReporting();
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

		// any use domain specified?
		if (strpos($user, '@'))
			list($user, $host) = explode('@', $user);
		elseif ($dom = $this->RCube->config->get('username_domain')) {
			// force domain?
			if ($this->RCube->config->get('username_domain_forced'))
				$host = is_array($dom) ? $dom[$host] : $dom;
			// add host?
			elseif (!$host)
		        $host = is_array($dom) ? $dom[$host] : $dom;
		}

		// see roundcobe/index.php
	    $auth = $this->RCube->plugins->exec_hook('authenticate', [
       			'host' 		  => $this->RCube->autoselect_host(),
       			'user' 		  => $user.($host ? '@'.$host : ''),
       			'pass' 		  => $passwd,
       			'cookiecheck' => FALSE,
       			'valid'       => 1,
	    		'error' 	  => NULL,
        ]);

        // perform real login
   	   	if (!$auth['valid'] || $auth['abort'] || !$this->RCube->login($auth['user'], $auth['pass'], $auth['host'], $auth['cookiecheck'])) {
			$log = Log::getInstance();
			$log->Msg(Log::DEBUG, 20316, $auth['user'], $this->RCube->login_error());
			ErrorHandler::resetReporting();
   	   		return FALSE;
   	    }

	    // load internal user object
	    $usr = User::getInstance();
	    $usr->loadUsr($user, $host);

        // set external user id
        $this->RCube->set_user(new rcube_user($this->RCube->get_user_id()));

        // compile time zone offset relativ to UTC (internal time zone format)
        $cnf = Config::getInstance();
		$def = new \DateTimeZone($cnf->getVar(Config::TIME_ZONE));
		$tz  = new \DateTimeZone('UTC');
    	$udt = new \DateTime("now", $def);
    	$dt  = new \DateTime("now", $tz);

	    $this->tzOffset = $tz->getOffset($dt) - $def->getOffset($udt);
    	Debug::Msg('Local time zone offset to UTC is '.$this->tzOffset); //3

		// reset error reporting
		ErrorHandler::resetReporting();

		return TRUE;
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

		$rc = FALSE;

		// we don't serve internal calls
		if (!($hid & DataStore::EXT))
			return $this->_hd[DataStore::SYSTEM]->Query($hid, $cmd, $parm);

		$hid &= ~DataStore::EXT;

		// we do not support locking
		if (!isset($this->_hd[$hid]))
			return $rc;

		if (!isset(DB::OPS[$cmd])) //3
            Debug::Err(' Unknown query "'.sprintf('0x%04x', $cmd).'" on external data base for handler "'. //3
            		   Util::HID(Util::HID_ENAME, $hid).'"'); //3
        else { //3
        	if (!($cmd & (DataStore::GRPS|DataStore::RIDS|DataStore::RNOK|DataStore::ADD))) { //3
        		$gid = $cmd & DataStore::UPD ? $parm->getVar('extID') : $parm; //3
            	Debug::Msg('Perform "'.DB::OPS[$cmd].'" query on external data base for handler "'. //3
            				Util::HID(Util::HID_ENAME, $hid).'" on record ['.$gid.']'); //3
        	} else //3
        		Debug::Msg('Perform "'.DB::OPS[$cmd].'" query on external data base for handler "'. //3
            			Util::HID(Util::HID_ENAME, $hid).'"'); //3
        } //3

        $rc = $this->_hd[$hid]->Query($hid, $cmd, $parm);

		// reset error reporting
		ErrorHandler::resetReporting();

		return $rc;
	}

	/**
	 * 	Get list of supported fields in external data base
	 *
	 * 	@param	- Handler ID
	 * 	@return	- [ field name ]
	 */
	public function getflds(int $hid): array {

		if (!isset($this->_hd[$hid]))
			return [];

		return $this->_hd[$hid]->getflds($hid);
	}

	/**
	 * 	Reload any cached record information in external data base
	 *
	 * 	@param	- Handler ID
	 * 	@return	- TRUE=Ok; FALSE=Error
	 */
	public function Refresh(int $hid): bool {

		if (!isset($this->_hd[$hid]))
			return FALSE;

		return $this->_hd[$hid]->Refresh($hid);
	}

	/**
	 * 	Check trace record references
	 *
	 *	@param 	- Handler ID
	 * 	@param 	- External record array [ GUID ]
	 * 	@param 	- Mapping table [HID => [ GUID => NewGUID ] ]
	 */
	public function chkTrcReferences(int $hid, array $rids, array $maps): void {

		if (isset($this->_hd[$hid & ~DataStore::EXT]))
			$this->_hd[$hid & ~DataStore::EXT]->chkTrcReferences($hid, $rids, $maps);
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

	/**
	 * 	Check Mysql handler
	 *
	 * 	@param 	- Handler ID
	 * 	@param 	- Line number
	 * 	@return - TRUE = Error; FALSE = No error
	 */
	public function chkRetry(int $hid, int $line): bool {

		if ($this->Retry < 1) {
	        $cnf = Config::getInstance();
    	    $this->Retry = $cnf->getVar(Config::DB_RETRY);
	    }

	    // get databse handler
		$db = $this->RCube->get_dbh();

    	// get error message
    	if (!($err = $db->is_error()))
    		return FALSE;

    	// get error code
		$code = substr($err, 1, 4);

    	// [2006] MySQL server has gone away
    	if ($code == 2006) {
    		if ($this->Retry--) {
				Util::Sleep(300);
			   	$log = Log::getInstance();
				$log->Msg(Log::DEBUG, 20351, $err, Util::Hid(Util::HID_ENAME, $hid), $line);
				return TRUE;
    		}
    	}

    	$log = Log::getInstance();
		$log->Msg(Log::WARN, 20351, $err, Util::Hid(Util::HID_ENAME, $hid), $line);

		// unrecoverable error
		return FALSE;
	}

}

?>