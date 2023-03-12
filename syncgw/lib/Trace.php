<?php
declare(strict_types=1);

/*
 * 	Trace handler class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\lib;

use syncgw\document\field\fldAttach;
use syncgw\document\field\fldKey;
use syncgw\document\field\fldLogo;
use syncgw\document\field\fldPhoto;
use syncgw\document\field\fldSound;

class Trace extends XML {

	// module version number
	const VER 		= 21;

	// trace version number
	const TRACE_VER = Server::MVER.'7';

	/**
	 * 	Trace record files
	 *
	 * 	self::TVER 	- Trace version file
	 * 				  [0] self::TVER
	 * 				  [1] self::TRACE_VER
	 *  self::PVER	- PHP version under which trace file has been created
	 *  			  [0] self::PVER
	 *  			  [1] phpversion()
	 *  self::CONF	- sync*gw configuration parameter
	 *  			  [0] self::CONF
	 *  			  [1] serialize($cnf->getVar(''))
	 *  self::LOG   - Log message
	 *  			  [0] self::LOG
	 *  			  [1] SEP.$msg
	 *  self::RCV   - HTTP received data
	 *  			  [0] self::RCV
	 *  			  [1] time()
	 *  			  [2] serialize($_SERVER)
	 *  			  [3] $body
	 *  self::SND 	- HTTP send data
	 *  			  [0] self::SND
	 *  			  [1] time()
	 *  			  [2] serialize($header)
	 *  			  [3] $body
	 *  self::ADD 	- Data record operation
	 *  			  [0] self::ADD
	 *  			  [1] $hid
	 *    ATTACHMENT  [2] $att->getVar('GUID')
	 *    			  [3] $att->getVar('MIME')
	 *    			  [4] $att->getVar('Encoding')
	 *    			  [5] $att->read($gid));
	 *    DATASTORE   [2] $doc->getVar('GUID')
	 *    			  [3] $doc->saveXML()
	 *
	 */

	const SEP		= "\x01";		// field separator

	// trace record data types
	const TVER		= 'V';    		// trace version
	const PVER 		= 'P';			// PHP version
	const CONF		= 'C';			// configuration
	const LOG 		= 'L';			// log message
	const RCV	    = 'R';			// HTTP received data
	const SND  		= 'S';			// HTTP send data
	const ADD	 	= 'A';			// add data store record

  	// binary data replacement
    const BIN_DATA  = '>>> BINARY DATA <<<'; //3

    /**
	 *  Trace (directory) name
	 *  @var string
	 */
	private $_gid = NULL;

    /**
	 * 	File counter
	 * 	@var int
	 */
	private $_cnt = 1;

	/**
	 *  Enabled data stores
	 *  @var int
	 */
	private $_ena;

	/**
	 * 	Saved data records
	 * 	@var array
	 */
	private $_done;

	/**
	 * 	Control file name
	 * 	@var string
	 */
	private $_ctl = NULL;

    /**
     * 	Singleton instance of object
     * 	@var Trace
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Trace {

		if (!self::$_obj) {

            self::$_obj = new self();

			// set messages 11201-11300
			$log = Log::getInstance();
			$log->setMsg([
				11201 => _('Trace [%s] started'),
				11202 => 'Trace [%s] restarted', //3
				11203 => 'Trace [%s] prolongated from [%s]', //3
				11204 => _('Error creating trace [%s] - disabling trace'),
				11205 => _('Error creating trace [%s] - disabling trace'),
				11206 => _('Cleanup %d trace files'),
			]);

	        // register shutdown function
			$srv = Server::getInstance();
			$srv->regShutdown(__CLASS__);

			// we don't wanna get warnings
			ErrorHandler::filter(E_WARNING, 'Trace.php');
		}

		return self::$_obj;
	}

   	/**
	 * 	Shutdown function
	 */
	public function delInstance(): void {

		if (!self::$_obj)
			return;

		if (self::$_obj->_ctl)
			file_put_contents(self::$_obj->_ctl, serialize(self::$_obj->_done));

		self::$_obj = NULL;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Name', _('Trace handler'));
		$xml->addVar('Ver', strval(self::VER));

		if (!$status)
			return;

		$xml->addVar('Opt', _('Trace recording'));
		$cnf = Config::getInstance();
		$mod = $cnf->getVar(Config::TRACE_MOD, TRUE);
		if (strtolower($mod) == 'on')
			$v = _('Enabled for all user');
		elseif (strtolower($mod) == 'off')
			$v = _('Disabled');
		else
			$v = sprintf(_('Enabled for user (%s)'), $mod);
		$xml->addVar('Stat', $v);
	}

	/**
     *  Start trace
     *
     *  @param  - used for forced trace only - HEADER
     * 	@param  - used for forced trace only - BODY
     *
     */
    public function Start(array $header = [], string $body = NULL): void {

		$cnf   = Config::getInstance();
       	$http  = HTTP::getInstance();
       	$log   = Log::getInstance();
       	$force = ($cnf->getVar(Config::TRACE) & Config::TRACE_FORCE); //3

       	// are we in replay and do not have a forced trace?
		if ($cnf->getVar(Config::HD) == 'GUI' && !$force) //3
			return; //3

		// don't save empty body for forced trace
		if ($force && !count($header)) //3
		  return; //3

		// allocated new trace record
        if ($force) //3
			$oid = $id = '#Forced Trace#'; //3
        else { //3

        	// no trace file name
        	$id = NULL;

			// general enabled?
   		    if (($mod = $cnf->getVar(Config::TRACE_MOD)) === 'On')
   				$id = $http->getHTTPVar('REMOTE_ADDR');
   			// check for specific IP
   			elseif (stripos($mod, $http->getHTTPVar('REMOTE_ADDR')) !== FALSE)
   			    $id = $http->getHTTPVar('REMOTE_ADDR');
        	// check for specific user?
            elseif (($unam = $http->getHTTPVar('User')) && stripos($mod, $unam) !== FALSE)
            	$id = $http->getHTTPVar('REMOTE_ADDR');

            // trace disabled?
            if (!$id)
    		    return;

 			// load request time
			$t = $http->getHTTPVar('REQUEST_TIME');

			// get session timeout
			$w = $cnf->getVar(Config::SESSION_TIMEOUT);
			$t = intval($t / $w);

			// old session id
			$oid = $id.'-'.($t - 1);

			// create new trace ID
			$id = $id.'-'.$t;

		} //3

		// save trace status
        $cnf->updVar(Config::TRACE, ($cnf->getVar(Config::TRACE) & ~Config::TRACE_OFF) | Config::TRACE_ON);

		// does old trace directory exists?
		$path = $cnf->getVar(Config::TRACE_DIR);
		if ($id != $oid && is_dir($path.$oid)) {
			$log->Msg(Log::DEBUG, 11203, $oid, $id); //3
			Util::rmDir($path.$id);
			rename($path.$oid, $path.$id);
		}

		// set trace file name
		$this->_gid = $cnf->getVar(Config::TRACE_DIR).$id.DIRECTORY_SEPARATOR;

		// set control file name
		$this->_ctl = $cnf->getVar(Config::TMP_DIR).$id;

		// set trace file counter
		$this->_cnt = 1;

		// does trace directory exists?
		if (!is_dir($this->_gid)) {

			// no control data
			$this->_done = [];

			// create trace directory
			mkdir($this->_gid);

			$log->Msg(Log::INFO, 11201, $id);

			// save trace version
			if (!self::_write(self::TVER.self::SEP.self::TRACE_VER))
				return;

			// save PHP version
			if (!self::_write(self::PVER.self::SEP.phpversion()))
				return;

			// save configuration
			if (!self::_write(self::CONF.self::SEP.serialize($cnf->getVar(''))))
				return;

		} else {

			// find last used trace file
			if ($d = @opendir($this->_gid)) {
				while (($file = @readdir($d)) !== FALSE) {
					if ($file == '.' || $file == '..' || substr($file, 0, 1) != 'R')
						continue;
					if (($i = substr($file, 1)) > $this->_cnt)
						$this->_cnt = $i;
				}
				@closedir($d);

				$log->Msg(Log::DEBUG, 11202, $id); //3

				// load control data
				$this->_done = file_exists($this->_cnt) ? unserialize(file_get_contents($this->_ctl)) : [];

			} else {
				$this->_gid = NULL;
				return;
			}
		}

		// save enabled data stores
		$this->_ena = $cnf->getVar(Config::ENABLED);

		// enable log reader
		$log->Plugin('readLog', $this);

		// enable http reader
		$http->catchHTTP('readHTTP', $this);

		// save client connection data
		if ($header)
			self::_write(self::RCV.self::SEP.time().self::SEP.serialize($header).self::SEP.$body);
		else
			self::_write(self::RCV.self::SEP.time().self::SEP.serialize($_SERVER).self::SEP.file_get_contents('php://input'));
    }

    /**
	 * 	Read trace record
	 *
	 *	@param  - Trace GUID (need not to be opened)
	 * 	@param	- Trace record number
	 * 	@return - Record [] or NULL = Error
	 */
	public function Read(string $gid, int $idx): ?array {

		$cnf  = Config::getInstance();
		$path = $cnf->getVar(Config::TRACE_DIR);

		$gid = $path.$gid.DIRECTORY_SEPARATOR;

		if (!($buf = file_get_contents($gid.'R'.$idx)))
			return NULL;

		$rec = explode(self::SEP, $buf);

		switch ($rec[0]) {
		case self::CONF:
			$rec[1] = unserialize($rec[1]);
			break;

		case self::ADD:

			// [1] $hid
			$rec[1] = intval($rec[1]);
			// [2] GUID
			// we need to glue attachment record here, since attachment data may contain self::SEP
			if ($rec[1] & DataStore::ATTACHMENT) {
				// [3] MIME type
				// [4] Encoding
				$rec[4] = intval($rec[4]);
				// [5-n] Attachment data
				for($i=6; isset($rec[$i]); $i++) {
					$rec[5] .= self::SEP.$rec[$i];
					unset($rec[$i]);
				}
			}
// ++ Start: Hack for TRACE_VER 6
			elseif (!isset($rec[3])) {
				$xml = new XML();
				$xml->loadXML($rec[2]);
				$rec[3] = $rec[2];
				$rec[2] = $xml->getVar('GUID');
			}
// +++ End
			break;

		case self::RCV:
		case self::SND:
			// [1] time()
			$rec[1] = intval($rec[1]);
			// [2] serialize($_SERVER)
			$rec[2] = unserialize($rec[2]);
			// [3] $body
			for($i=4; isset($rec[$i]); $i++) {
				$rec[3] .= self::SEP.$rec[$i];
				unset($rec[$i]);
			}
			break;

 		default:
			break;
		}

		return $rec;
	}

	/**
	 * 	Log reader
	 *
	 * 	@param	- Message typ (ERR, WARN, INFO, APP)
	 * 	@param	- Output message
	 */
	public function readLog(int $typ, string $data): void {

		// we don't save our own messages - else we end in looping
		if (substr($data, 7, 3) == '112'
		    // and of course the general message
			|| substr($data, 7, 4) == '10001' //3
		)
			return;

	    // save trace record
	    self::_write(self::LOG.self::SEP.date('Y M d H:i:s').' '.$data);
	}

	/**
	 * 	HTTP output reader
	 *
	 * 	@param	- HTTP output header
	 *  @param  - HTTP Body
	 * 	@return - TRUE = Ok; FALSE = Stop sending output
	 */
	public function readHTTP(array $header, $body): bool {

	    // save trace record
	    $body = is_object($body) ? $body->saveXML() : $body;
	    return self::_write(self::SND.self::SEP.time().self::SEP.serialize($header).self::SEP.$body);
	}

	/**
	 * 	Save data store records
	 *
	 * 	@param	- Handler ID
	 *  @param  - XML object to save
	 */
	public function Save(int $hid, XML &$xml): void {

   		// trace file allocated / database operations locked?
		if (!$this->_gid)
   		    return;

		// double check, if external data stores is enabled
		if (($hid & DataStore::DATASTORES) && ($hid & DataStore::EXT))
			// external enabled?
			if (!($this->_ena & DataStore::EXT) ||
				// data store enabled?
				!($hid & ~DataStore::EXT & $this->_ena))
				return;

		// get record ID
		if ($hid & DataStore::EXT)
			$gid = $xml->getVar('extID');
		else
			$gid = $xml->getVar('GUID');

		// already saved?
		if (isset($this->_done[$hid][$gid]))
				return;

		$this->_done[$hid][$gid] = 1;

		// get record data
		$data = $xml->saveXML();

		$att_recs = [];
		if ($hid & DataStore::ATTACHMENT)
			$att_recs[] = $gid;
		else
			self::_write(self::ADD.self::SEP.$hid.self::SEP.$gid.self::SEP.$data);

		// check for attachments
		foreach ([ 	'//'.fldPhoto::TAG.'/.',	'//'.fldAttach::TAG.'/'.fldAttach::SUB_TAG[1],
					'//'.fldSound::TAG.'/.', 	'//'.fldLogo::TAG.'/.',
					'//'.fldKey::TAG.'/.' ] as $tag) {
			$xml->xpath($tag);
            while (($gid = $xml->getItem()) !== NULL)
            	$att_recs[] = $gid;
		}

       	$att  = Attachment::getInstance();
		$cnf  = Config::getInstance();
		$hack = $cnf->getVar(Config::HACK);
		$cnf->updVar(Config::HACK, $hack | Config::HACK_SIZE);

		foreach ($att_recs as $gid) {

			// we assume $name is attachment record id
            self::_write(self::ADD.self::SEP.DataStore::ATTACHMENT.self::SEP.$gid.self::SEP.
            			 $att->getVar('MIME').self::SEP.$att->getVar('Encoding').self::SEP.$att->read($gid));
		}

		$cnf->updVar(Config::HACK, $hack);
	}

	/**
	 * 	Perform trace expiration
	 */
	public function Expiration(): void {

		$cnf = Config::getInstance();

		if (!($path = $cnf->getVar(Config::TRACE_DIR)))
			return;

		if (!($tme = $cnf->getVar(Config::TRACE_EXP)))
			return;

		// convert hour to seconds
		$tme *= 3600;

		if (!($d = @opendir($path)))
			return;

		$cnt = 0;
		while (($file = @readdir($d)) !== FALSE) {

			if ($file == '.' || $file == '..' || !is_dir($path.$file))
				continue;

			if (filectime($path.$file) + $tme < time()) {
				Util::rmDir($path.$file);
				$cnt++;
			}
		}
		@closedir($d);

		if ($cnt) {
			$log = Log::getInstance();
			$log->Msg(Log::DEBUG, 11206, $cnt);
		}
	}

	/**
	 * 	Create trace record
	 *
	 * 	@param	- Trace record
	 * 	@return - TRUE = Ok; FALSE = Error
	 */
	private function _write(string $rec): bool {

		// trace file allocated / database operations locked?
   		if (!$this->_gid)
			return FALSE;

		$name = $this->_gid.'R'.$this->_cnt;
		while (@file_exists($name))
			$name = $this->_gid.'R'.++$this->_cnt;

		if (!file_put_contents($name, $rec)) {
			$this->_gid = NULL;
			return FALSE;
		}

		return TRUE;
	}

}

?>