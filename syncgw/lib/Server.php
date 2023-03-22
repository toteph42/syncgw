<?php
declare(strict_types=1);

/*
 * 	Server class
 *
 * 	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\lib;

use syncgw\gui\guiHandler;
use syncgw\activesync\masHandler;
use syncgw\dav\davHandler;

class Server {

	// module version number
	const VER  = 13;

	// major version number
	const MVER = '9.';

	// list of handler
	const HANDLER = [
		[ 'updHandler', 	'upgrade' ],
		[ 'guiHandler',		'gui' ],
		[ 'Handler',		'interfaces\\file' ],
		[ 'Handler',		'interfaces\\mysql' ],
		[ 'Handler',		'interfaces\\myapp'],
		[ 'Handler',		'interfaces\\roundcube' ],
		[ 'Handler',		'interfaces\\mail' ],
		[ 'masHandler',		'activesync' ],
		[ 'davHandler',		'dav' ],
		[ 'docContact',		'document\\docContact.php' ],
		[ 'docCalendar',	'document\\docCalendar.php' ],
		[ 'docTask',		'document\\docTask.php' ],
		[ 'docNote',		'document\\docNote.php' ],
		[ 'docGAL',			'document\\docGAL.php' ],
		[ 'DocLib',			'document\\DocLib.php' ],
		[ 'docMail',		'document\\docMail.php' ],
		[ 'fldHandler',	'document\\field' ],
		[ 'mapiHandler',	'mapi' ],
		[ 'rpcHandler',		'mapi\\rpcs' ],
		[ 'ropHandler',		'mapi\\rops' ],
		[ 'icsHandler',		'mapi\\ics' ],
	];

    /**
     * 	Singleton instance of object
     * 	@var Server
     */
    static private $_obj = NULL;

	/**
     * 	Shutdown array
     * 	@var array
     */
    private $_mods = [];

	/**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Server {

	   	if (!self::$_obj) {

            self::$_obj = new self();

			// allocate error handler
			ErrorHandler::getInstance();

			// set messages 10101-10200
			$log = Log::getInstance();
			$log->setMsg([
					10101 => _('sync*gw not available for devices until upgrade of server has been performed'),
			]);

			// register shutdown function on __destruct()
			register_shutdown_function([ self::$_obj, 'shutDown' ]);

			// load default language
			if (!Debug::$Conf['Script'] != 'Log') { //3
				$enc = Encoding::getInstance();
				$enc->setLang();
			} //3
		}

		return self::$_obj;
	}

    /**
	 * 	Get information about handler class
     *
     *	@param 	- TRUE = Provide status information only (if available)
     * 	@return	- XML object
	 */
	public function getInfo(bool $status): XML {

		$xml = new XML();
		$xml->addVar('syncgw');
		$xml->addVar('Name', _('<strong>sync&bull;gw</strong> server'));
		$xml->addVar('Ver', strval(self::VER));

		if (!$status) {
			$xml->addVar('Opt', '<a href="http://www.iana.org/time-zones" target="_blank">IANA</a> Time zone data base source');
			$xml->addVar('Stat', _('Implemented'));
		}

		// scan library classes
		self::getSupInfo($xml, $status, 'lib', [ 'Server', 'Loader' ]);

		// show supporting classes
		foreach (self::HANDLER as $class) {

			if (!file_exists(Util::mkPath($class[1])))
				continue;

			if (strpos($class[1], '.php'))
				$class[1] = 'document';
			$class = 'syncgw\\'.$class[1].'\\'.$class[0];
			$class = method_exists($class, 'getInstance') ? $class::getInstance() : new $class();
			$class->getInfo($xml, $status);
		}

		return $xml;
	}

    /**
	 * 	Get information about supporting classes
     *
     *	@param 	- Output document
     *	@param 	- TRUE = Check status; FALSE = Provide supported features
     *	@param 	- Path to directory
     *	@param 	- File exlision list
	 */
	public function getSupInfo(XML &$xml, bool $status, string $path, array $exclude = []): void {

		// get supporting handler information
		if ($d = @opendir($dir = Util::mkPath($path))) {
			$path .= '\\';
			while (($file = @readdir($d)) !== FALSE) {
				if (is_dir($dir.DIRECTORY_SEPARATOR.$file) || strpos($file, 'Handler') !== FALSE)
					continue;
				$ex = 'ok';
				foreach ($exclude as $ex) {
					if (strpos($file, $ex) !== FALSE) {
						$ex = NULL;
						break;
					}
				}
				if (!$ex)
					continue;
				// strip off file extension
				$class = 'syncgw\\'.$path.substr($file, 0, -4);
				$class = method_exists($class, 'getInstance') ? $class::getInstance() : new $class();
				if ($class && method_exists($class, 'getInfo'))
					$class->getInfo($xml, $status);
			}
			@closedir($d);
		}
	}

	/**
	 * 	Process input data
	 */
	public function Process(): void {

		$http = HTTP::getInstance();
		$cnf  = Config::getInstance();

		// allocate log object to start output catching
		$log  = Log::getInstance();

		// get responsible handler
		$mod = $cnf->getVar(Config::HD);

		// receive and format HTTP data
		if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_OFF) { //2
			if (!$http->receive($_SERVER, file_get_contents('php://input')))
				return;
			// reload modus
			$mod = $cnf->getVar(Config::HD);
		} //2

		if ($mod == 'GUI') {
		   $gui = guiHandler::getInstance();
		   $gui->Process();
		   return;
		}

		// handle record expiration
		global $argv;
		if (($cron = $cnf->getVar(Config::CRONJOB)) == 'N' ||
			(isset($argv[1]) && stripos($argv[1], 'cleanup') !== FALSE) ||
			// special hack for PLESK
			stripos($http->getHTTPVar('QUERY_STRING'), 'cleanup') !== FALSE) {
			$log->Expiration();
			$sess = Session::getInstance();
			$sess->Expiration();
			$trc = Trace::getInstance();
			$trc->Expiration();
			// cron job call?
			if ($cron == 'Y')
				return;
		}

		// is update pending?
		$ver = $cnf->getVar(Config::VERSION);
		if (version_compare($cnf->getVar(Config::UPGRADE), substr($ver, strrpos($ver, ' ') + 1)) < 0) {
			$log->Msg(Log::WARN, 10101);
			$http->send(503);
			return;
		}

		// start trace
        $trc = Trace::getInstance();
        $trc->Start();

        // check for ActiveSync
		if ($mod == 'MAS') {
			$hd = masHandler::getInstance();
			$hd->Process();
			return;
		}

		// check for MAPI over HTTP
		if ($mod == 'MAPI') { //3
			$hd = '\\syncgw\\mapi\\mapiHandler'; //3
			$hd = $hd::getInstance(); //3
			$hd->Process(); //3
			return; //3
		} //3

		// we assume it is WebDAV

		$hd = davHandler::getInstance();
		$hd->Process();
	}

	/**
	 * 	Register shutdown functions
	 *
	 * 	@param 	Class name
	 */
	public function regShutdown(string $class) {
		$this->_mods[$class] = 1;
	}

	/**
	 * 	Unregister shutdown functions
	 *
	 * 	@param 	Class name
	 */
	public function unregShutdown(string $class) {
		unset($this->_mods[$class]);
	}

	/**
	 * 	Shutdown server<br>
	 * 	Calls to __destruct() cannot be used, since all classes are kept in memory until last reference is removed
	 *  - A constant reference is also a reference!
	 */
	public function shutDown () {

		$db = NULL;
		$mods = array_reverse($this->_mods);
		foreach ($mods as $class => $unused) {
			// skip data base handler
			if (substr($class, 0, 2) == 'DB')
				$db = $class;
			else {
				$obj = $class::getInstance();
				// double check for shutdown function
				if (method_exists($obj, 'delInstance')) {
					Debug::Msg('Shutting down "'.$class.'"'); //3
					$obj->delInstance();
				}
			}
		}
		$unused; // disable Eclipse warning

		// stop data base at end
		if ($db) {
			$obj = $db::getInstance();
			if (method_exists($obj, 'delInstance')) {
				Debug::Msg('Shutting down "'.$db.'"'); //3
				$obj->delInstance();
			}
		}

		// device unlock will be done automatically
	}

}

?>