<?php
declare(strict_types=1);

/*
 * 	Administration interface handler class
 *
 *	@package	sync*gw
 *	@subpackage	mySQL handler
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\interfaces\mysql;

use syncgw\interfaces\DBAdmin;
use syncgw\gui\guiHandler;
use syncgw\lib\Config;
use syncgw\lib\DataStore;
use syncgw\lib\Server;
use syncgw\lib\Util;
use syncgw\lib\XML;

class Admin implements DBAdmin {

	// module version number
	const VER = 8;

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

		$xml->addVar('Opt', _('MySQL administration handler'));
		$xml->addVar('Stat', 'v'.Server::MVER.sprintf('%03d', self::VER));
	}

    /**
	 * 	Show/get installation parameter
	 */
	public function getParms(): void {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		if(!($c = $gui->getVar('MySQLHost')))
			$c = $cnf->getVar(Config::DB_HOST);
		$gui->putQBox(_('MySQL server name'),
					'<input name="MySQLHost" type="text" size="40" maxlength="250" value="'.$c.'" />',
					_('MySQL server name (default: "localhost").'), FALSE);
		if(!($c = $gui->getVar('MySQLPort')))
			$c = $cnf->getVar(Config::DB_PORT);
		$gui->putQBox(_('MySQL port address'),
					'<input name="MySQLPort" type="text" size="5" maxlength="6" value="'.$c.'" />',
					_('MySQL server port (default: 3306).'), FALSE);
		if(!($c = $gui->getVar('MySQLName')))
			$c = $cnf->getVar(Config::DB_NAME);
		$gui->putQBox(_('MySQL data base name'),
					'<input name="MySQLName" type="text" size="30" maxlength="64" value="'.$c.'" />',
					_('Name of MySQL data base to store tables. The tables will be created automatically.'), FALSE);
		if(!($c = $gui->getVar('MySQLUsr')))
			$c = $cnf->getVar(Config::DB_USR);
		$gui->putQBox(_('MySQL data base user name'),
					'<input name="MySQLUsr" type="text" size="20" maxlength="40" value="'.$c.'" />',
					_('User name to access MySQL data base.'), FALSE);
		if(!($c = $gui->getVar('MySQLPwd')))
			$c = $cnf->getVar(Config::DB_UPW);
		$gui->putQBox(_('MySQL data base user password'),
					'<input name="MySQLPwd" type="password" size="20" maxlength="40" value="'.$c.'" />',
					_('Password for MySQL data base user.'), FALSE);
		if(!($c = $gui->getVar('MySQLPref')))
			$c = $cnf->getVar(Config::DB_PREF);
		$gui->putQBox(_('MySQL <strong>sync&bull;gw</strong> data base table name prefix'),
					'<input name="MySQLPref" type="text" size="20" maxlength="40" value="'.$c.'" />',
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

		// connection established?
		if ($cnf->getVar(Config::DATABASE))
			return TRUE;

		// swap variables
		$cnf->updVar(Config::DB_NAME, $gui->getVar('MySQLName'));
		if (!$cnf->getVar(Config::DB_NAME)) {
			$gui->clearAjax();
			$gui->putMsg(_('Missing MySQL data base name'), Util::CSS_ERR);
			return FALSE;
		}
		$cnf->updVar(Config::DB_HOST, $gui->getVar('MySQLHost'));
		if (!$cnf->getVar(Config::DB_HOST)) {
			$gui->clearAjax();
			$gui->putMsg(_('Missing MySQL host name'), Util::CSS_ERR);
			return FALSE;
		}
		$cnf->updVar(Config::DB_PORT, $gui->getVar('MySQLPort'));
		if (!$cnf->getVar(Config::DB_PORT)) {
			$gui->clearAjax();
			$gui->putMsg(_('Missing MySQL port name'), Util::CSS_ERR);
			return FALSE;
		}
		$cnf->updVar(Config::DB_USR, $gui->getVar('MySQLUsr'));
		if (!$cnf->getVar(Config::DB_USR)) {
			$gui->clearAjax();
			$gui->putMsg(_('Missing MySQL data base user name'), Util::CSS_ERR);
			return FALSE;
		}
		$cnf->updVar(Config::DB_UPW, $gui->getVar('MySQLPwd'));
		if (!$cnf->getVar(Config::DB_UPW)) {
			$gui->clearAjax();
			$gui->putMsg(_('Missing MySQL data base user password'), Util::CSS_ERR);
			return FALSE;
		}
		$cnf->updVar(Config::DB_PREF, $gui->getVar('MySQLPref'));

		// create tables
		return self::mkTable();
	}

	/**
	 * 	Disconnect from handler
	 *
	 * 	@return - TRUE=Ok; FALSE=Error
	 */
	public function DisConnect(): bool {
		return self::delTable();
	}

	/**
	 * 	Return list of supported data store handler
	 *
	 * 	@return - Bit map of supported data store handler
	 */
	public function SupportedHandlers(): int {
		return DataStore::DATASTORES&~DataStore::MAIL;
	}

	/**
	 * 	Create data base tables
	 *
	 * 	@param	- Optional SQL commands
	 * 	@return	- TRUE=Ok; False=Error
	 */
	public function mkTable(array $cmds = NULL): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		// allocate MySQL handler
		$db = Handler::getInstance();

		if (!$cmds)
			$cmds = self::loadSQL(__DIR__.DIRECTORY_SEPARATOR.'tables.sql');

		$gui->clearAjax();

		// perform installation
		$pref = $cnf->getVar(Config::DB_PREF);
		foreach ($cmds as $cmd) {
			$cmd = str_replace([ "\n", '{prefix}' ], [ '', $pref ], $cmd);
			if (!strlen(trim($cmd)))
				continue;
			if (!$db->SQL($cmd)) {
				$gui->putMsg(sprintf(_('Error executing SQL command: "%s"'), $cmd), Util::CSS_ERR);
				return FALSE;
			}
		}
		$gui->putMsg(_('<strong>sync&bull;gw</strong> MySQL tables created'));

		return TRUE;
	}

	/**
	 * 	Delete data base table
	 *
	 * 	@param	- Optional SQL commands
	 * 	@return	- TRUE=Ok; False=Error
	 */
	public function delTable(array $cmds = NULL): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();

		// allocate MySQL handler
		$db = Handler::getInstance();

		if (!$cmds)
			$cmds = self::loadSQL(__DIR__.DIRECTORY_SEPARATOR.'tables.sql');

		$gui->clearAjax();

		// perform deinstallation
		$pref = $cnf->getVar(Config::DB_PREF);
		foreach ($cmds as $cmd) {
			if (!strlen(trim($cmd)) || stripos($cmd, 'DROP') === FALSE)
				continue;
			$cmd = str_replace('{prefix}', $pref, $cmd);
			if (!$db->SQL($cmd)) {
				$gui->putMsg(sprintf(_('Error executing SQL command: "%s"'), $cmd), Util::CSS_ERR);
				return FALSE;
			}
		}
		$gui->putMsg(_('<strong>sync&bull;gw</strong> MySQL tables deleted'));

		return TRUE;
	}

	/**
	 * 	Load SQL statements
	 *
	 * 	@param	- File name to load
	 * 	@return	- Command list or emtpy []
	 */
	public function loadSQL(string $file): array {

		$gui = guiHandler::getInstance();

		$path = realpath($file);
		if (!$path || !($cmds = @file_get_contents($path))) {
			$gui->putMsg(sprintf(_('Error loading MySQL tables from \'%s\''), $file), Util::CSS_ERR);
			return [];
		}

		$recs = explode("\n", $cmds);
		$wrk = '';
		foreach ($recs as $rec) {
			$rec = trim($rec);
			// strip comment lines
			if (!strlen($rec) || substr($rec, 0, 2) == '--')
				continue;
			$wrk .= $rec;
		}

		return explode(';', substr($wrk, 0, -1));
	}

}

?>