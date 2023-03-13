<?php
declare(strict_types=1);

/*
 * 	Administration interface handler class
 *
  *	@package	sync*gw
 *	@subpackage	RoundCube data base
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\interfaces\roundcube;

use syncgw\interfaces\DBAdmin;
use syncgw\gui\guiHandler;
use syncgw\lib\Config;
use syncgw\lib\DataStore;
use syncgw\lib\ErrorHandler;
use syncgw\lib\Util;
use syncgw\lib\XML;
use rcube_db;

class Admin extends \syncgw\interfaces\mysql\Admin implements DBAdmin {

	// module version number
	const VER = 9;

    /**
     * 	Singleton instance of object
     * 	@var Admin
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Admin {

	   	if (!self::$_obj)
            self::$_obj = new self();

		return self::$_obj;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Opt', 'RoundCube administration handler');
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Show/get installation parameter
	 */
	public function getParms(): void {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		if(!($c = $gui->getVar('rcPref')))
			$c = $cnf->getVar(Config::DB_PREF);
		$gui->putQBox(_('MySQL <strong>sync&bull;gw</strong> data base table name prefix'),
					'<input name="rcPref" type="text" size="20" maxlength="40" value="'.$c.'" />',
					_('Table name prefix for <strong>sync&bull;gw</strong> data base tables '.
					'(to avaoid duplicate table names in data base).'), FALSE);
	}

    /**
	 * 	Connect to handler
	 *
	 * 	@return - TRUE=Ok; FALSE=Error
	*/
	public function Connect(): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		// already connected?
		if ($cnf->getVar(Config::DATABASE))
			return TRUE;

		// get application root directory
		$path = Util::mkPath('..');
		// check for hidden parameter
		if ($c = $cnf->getVar(Config::RC_DIR))
			$path = $c;
		$path = realpath($path);

		$file = $path.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.inc.php';
		if (!file_exists($file)) {
			$gui->clearAjax();
			$gui->putMsg(sprintf(_('Error loading required RoundCube configuration file \'%s\''), $file), Util::CSS_ERR);
			return FALSE;
		}
		$cnf->updVar(Config::RC_DIR, $path);

		// set error filter
		ErrorHandler::filter(E_NOTICE|E_WARNING, $path);

		$config = []; // disable Eclipse warning

		// load RoundCube configuration
		require_once ($file);

		// load and save data base configuration
		require_once ($path.DIRECTORY_SEPARATOR.'program'.DIRECTORY_SEPARATOR.'lib'.
		              DIRECTORY_SEPARATOR.'Roundcube'.DIRECTORY_SEPARATOR.'rcube_db.php');

		// variable $config is defined in rcube_db.php
		$conf = rcube_db::parse_dsn($config['db_dsnw']);
		ErrorHandler::resetReporting();

		if ($conf['dbsyntax'] != 'mysql') {
			$gui->clearAjax();
			$gui->putMsg(sprintf(_('We do not support \'%s\' connection'), $conf['dbsyntax']), Util::CSS_ERR);
			return FALSE;
		}

		$cnf->updVar(Config::DB_HOST, $conf['hostspec']);
		$cnf->updVar(Config::DB_PORT, '3306');
		$cnf->updVar(Config::DB_USR, $conf['username']);
		$cnf->updVar(Config::DB_UPW, $conf['password']);
		$cnf->updVar(Config::DB_NAME, $conf['database']);
		$cnf->updVar(Config::DB_PREF, $gui->getVar('rcPref'));

		// create tables
		return parent::mkTable();
	}

	/**
	 * 	Disconnect from handler
	 *
	 * 	@return - TRUE=Ok; FALSE=Error
	 */
	public function DisConnect(): bool {

		return parent::delTable();
	}

	/**
	 * 	Return list of supported data store handler
	 *
	 * 	@return - Bit map of supported data store handler
	 */
	public function SupportedHandlers(): int {

		return DataStore::EXT|DataStore::CONTACT|DataStore::CALENDAR|DataStore::TASK|DataStore::NOTE;
	}

}

?>