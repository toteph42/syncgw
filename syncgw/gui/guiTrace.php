<?php
declare(strict_types=1);

/*
 * 	View or debug trace
 *
 *	@package	sync*gw
 *	@subpackage	GUI
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\gui;

use syncgw\activesync\masFolderType; //2
use syncgw\document\field\fldAttribute; //2
use syncgw\document\field\fldRelated; //2
use syncgw\lib\Attachment; //2
use syncgw\lib\Config;
use syncgw\lib\DB; //2
use syncgw\lib\DataStore;
use syncgw\lib\Debug; //3
use syncgw\lib\Device; //2
use syncgw\lib\HTTP;
use syncgw\lib\Log; //2
use syncgw\lib\Server; //2
use syncgw\lib\Trace;
use syncgw\lib\User; //2
use syncgw\lib\Util;
use syncgw\lib\XML;

class guiTrace {

	// module version
	const VER 	   		= 7;

	const T_SERVER		= 'TServer';	// HTTP $_SERVER in trace file
	const T_HEADER 		= 'THeader';	// HTTP header in trace file
	const T_BODY   		= 'TBody';    	// BODY data in trace file
	const N_HEADER 		= 'NHeader';	// new HTTP header
	const N_BODY   		= 'NBody';    	// new BODY data

	const CUR_CONF 		= 'CurConfig';	// current configuration //2
	const TRC_RECS		= 'TrcRecs';	// trace records
	const TRC_MAP		= 'TrcMap';		// record mapping table (old -> new)
	const TRC_MAX		= 'TrcMax';		// max. # of records
	const TRC_CONF 		= 'TrcConfig';	// trace configuration //2
	const TRC_PATH 		= 'TrcPath';	// path to trace file
	const TRC_TIME 		= 'TrcTime';	// trace file time

	// message excluded from comparison
	const EXCLUDE  		= [ //2
 			// header
 			'user:', //2
			'date:', //2
			'content-length:', //2
			'x-starttime:', //2
			'set-cookie:', //2
			'x-requestid:', //2

            // SabreDAV
 	        'd:href', //2
			'd:displayname', //2
            ':getetag', //2
	        ':getctag', //2
    		':sync-token', //2
    		'related-to:', //2
    		'etag:', //2

    		// ActiveSync
            'action', //2
			'<policykey>', //2
			'<accountid>', //2
			'<primarysmtpaddress>', //2
			'<related>', //2
			'<lastmodifieddate>', //2
			'<emailaddress>', //2
			'<deploymentid>', //2
			'<legacydn>', //2

			// MAPI
			'<dn', //2

            // vCal, vCard
            'last-modified:', //2
			'created:', //2
    		'uid:', //2
    		'rev:', //2
			// Roundcube - Calendar
            // sequence may vary during debugging - this is clandar plugin specific - database_driver:php:_insert_event()
 	]; //2

	/**
     *  Trace control file
     *  @var array
     */
	public $_ctl;

    /**
     * 	Singleton instance of object
     * 	@var guiTrace
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): guiTrace {

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

		$xml->addVar('Opt', _('View or debug trace plugin'));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Perform action
	 *
	 * 	@param	- Action to perform
	 * 	@return	- guiHandler status code
	 */
	public function Action(string $action): string {

		$gui  = guiHandler::getInstance();
		$hid  = intval($gui->getVar('ExpHID'));
		$gid  = $gui->getVar('ExpGID');
		$http = HTTP::getInstance(); //2
		$cnf  = Config::getInstance(); //2

		$this->_ctl 				= [];
        $this->_ctl[self::N_BODY]   = NULL;
		$this->_ctl[self::N_HEADER] = [];

		switch ($action) {
		case 'ExpTraceDebug': //2
		case 'ExpTraceShow':

			// set debug status
			$cnf->updVar(Config::DBG_LEVEL, $action == 'ExpTraceShow' ? Config::DBG_VIEW : Config::DBG_TRACE); //2

			$gui->putMsg(sprintf(_('Starting processing of trace [%s]'), $gid), Util::CSS_TITLE);
			$gui->putMsg('');

			// load trace control file
			if (!self::_loadTrace($gid))
				break;

			// delete trace record?
			if ($idx = $gui->getVar('DelTraceRec')) //3
				self::_delRec($gid, intval($idx)); //3
			$gui->putHidden('DelTraceRec', '0'); //3

			// load and show trace configuration
			if (!self::_loadConfig($gid))
				break;

			if ($action == 'ExpTraceShow') { //2

				// process all trace records
			    for($idx=1; $idx <= $this->_ctl[self::TRC_MAX]; $idx++) {
	    			if (isset($this->_ctl[self::TRC_RECS][$idx]))
		    			self::_showRec(TRUE, $action, $idx, $this->_ctl[self::TRC_RECS][$idx]);
			    }
			    break;
			} //2

			// set configuration
	    	foreach ($this->_ctl[self::TRC_CONF] as $k => $v) //2
	    		if ($k != 'Loaded') //2
	    	   		$cnf->updVar($k, $v); //2

	   		// cleanup records
			if (!self::_cleanRecs($gid)) //2
				break; //2

	    	// restart server
			$srv = Server::getInstance(); //2
			$srv->shutDown(); //2

			$srv  = Server::getInstance(); //2
			$cnf  = Config::getInstance(); //2
			$gui  = guiHandler::getInstance(); //2
			$http = HTTP::getInstance(); //2
			$trc  = Trace::getInstance(); //3
			$log  = Log::getInstance(); //2

			// process all trace records
		    for($idx=1; $idx <= $this->_ctl[self::TRC_MAX]; $idx++) { //2

		    	if (!isset($this->_ctl[self::TRC_RECS][$idx])) //2
		    		continue; //2

		    	// show trace record
		    	self::_showRec(FALSE, $action, $idx, $this->_ctl[self::TRC_RECS][$idx]); //2

	    		if ($this->_ctl[self::TRC_RECS][$idx][0] != Trace::RCV) //2
	    			continue; //2

				// is trace forced?
	    		if ($gui->getVar('ForceTrace')) { //3
					$cnf->updVar(Config::TRACE, Config::TRACE_FORCE); //3

	    			// special hack to catch received server environment and data
	    			$trc->Start($http->getHTTPVar(HTTP::SERVER), $this->_ctl[self::TRC_RECS][$idx][3]); //3
	    		} //3

				// enable http reader
				$http->catchHTTP('readHTTP', $this); //2

				// enable trace messages
				Debug::Mod(TRUE); //3
				Debug::$Conf['Include']['chkTrcReferences'] = 1; //3
				Debug::$Conf['Include']['syncgw\mapi\MAPI_HTTP'] = 1; //3

   				// disable some debug messages
				Debug::$Conf['Exclude']['syncgw\\lib\\XML'] = 1; //3
				Debug::$Conf['Exclude']['syncgw\\lib\\WBXML'] = 1; //3
				Debug::$Conf['Exclude']['syncgw\\lib\\Encoding'] = 1; //3
				Debug::$Conf['Exclude']['syncgw\\lib\\Config'] = 1; //3
				Debug::$Conf['Exclude']['syncgw\\interfaces\\mysql\\Handler'] = 1; //3
				Debug::$Conf['Exclude']['syncgw\\lib\\Config:getVar'] = 1; //3
				Debug::$Conf['Exclude']['syncgw\\lib\\Attachment:getVar'] = 1; //3
				Debug::$Conf['Exclude']['syncgw\\lib\\Log:Msg'] = 1; //3
				Debug::$Conf['Exclude']['syncgw\\lib\\Session:updSessVar'] = 1; //3
				Debug::$Conf['Exclude']['syncgw\\lib\\Trace'] = 1; //3

				// check for record operations
				self::_chkRecs($action, $gid, $idx); //2

	    		// stop external logging
   				$log->delInstance(); //2

          		// the magic place...

				// process data
				$srv->Process(); //2

				// only allow warnings and error messages
				Debug::Mod(TRUE, Debug::ColorErr|Debug::ColorWarn); //3

				// save request buffer for MAPI debugging purpose
				$bdy = $http->getHTTPVar(HTTP::RCV_BODY); //2

				// shutdown server
				$srv->shutDown(); //2

           		// restart server
				$srv  = Server::getInstance(); //2
           		$cnf  = Config::getInstance(); //2
           		$log  = Log::getInstance(); //2
           		$http = HTTP::getInstance(); //2
           		$http->updHTTPVar(HTTP::RCV_BODY, NULL, $bdy); //2
				$trc  = Trace::getInstance(); //3
		    } //2

		    // last record not send, but new record created?
		    if (!empty($this->_ctl[self::N_HEADER])) //2
	    		self::_showHTTP($idx, FALSE, gmdate('Y-m-d H:i:s', $this->_ctl[self::TRC_RECS][1] ? //2
	    						$this->_ctl[self::TRC_RECS][$idx][1] : time()).' ', HTTP::SND_HEAD); //2

	    	$gui->putMsg('<br/><hr>+++ '._('End of trace')); //2
		}

		// restore configuration
		if (isset($this->_ctl[self::CUR_CONF])) //2
			foreach ($this->_ctl[self::CUR_CONF] as $k => $v) //2
		    	if ($k != 'Loaded') //2
	    	   		$cnf->updVar($k, $v); //2

		// set debug status
		$cnf->updVar(Config::DBG_LEVEL, Config::DBG_OFF); //2

		// allow only during explorer call
		if (substr($a = $gui->getVar('Action'), 0, 3) == 'Exp' && substr($a, 6, 4) != 'Edit' && ($hid & DataStore::TRACE)) {
			$but = $gui->getVar('Button').$gui->mkButton(_('View'), _('View internal record'), 'ExpTraceShow');
			if ($hid & DataStore::TRACE && $cnf->getVar(Config::DBG_USR)) //2
				$but .= $gui->mkButton(_('Debug'), //2
						_('Debug selected trace. All user references are redirected to debug user'), 'ExpTraceDebug'); //2
			$gui->setVal($but);
		}

		return guiHandler::CONT;
	}

	/**
	 * 	Load trace control data
	 *
	 * 	@param 	- Trace record <GUID>
	 * 	@return - TRUE = Ok; FALSE = Error
	 */
	private function _loadTrace(string $gid): bool {

		$gui = guiHandler::getInstance();
		$cnf = Config::getInstance();
		$trc = Trace::getInstance();

		// check for trace directory
		$path = $cnf->getVar(Config::TRACE_DIR);
		if (!is_dir($file = $path.$gid)) {
			$gui->putMsg(sprintf(_('Trace directory [%s] not found'), $file), Util::CSS_ERR);
			return FALSE;
		}

		// build trace control file
		$this->_ctl[self::TRC_MAP] = [];

		for ($idx=1; $r = $trc->Read($gid, $idx); $idx++) {
			if ($r[0] != Trace::ADD)
				$this->_ctl[self::TRC_RECS][$idx] = $r;
			else {
				// check if record is already loaded
				if (!isset($this->_ctl[self::TRC_MAP][$r[1]][$r[2]])) {
					$this->_ctl[self::TRC_MAP][$r[1]][$r[2]] = 0;
					$this->_ctl[self::TRC_RECS][$idx] = $r;
				}
			}
		}
		$this->_ctl[self::TRC_MAX] = $idx;

		// save trace information
		$this->_ctl[self::TRC_PATH] = $path.$gid; //3
		$this->_ctl[self::TRC_TIME] = filectime($path.$gid);

		return TRUE;
	}

	/**
	 * 	Load and show trace configuration
	 *
	 * 	@param 	- Trace record <GUID>
	 * 	@return - TRUE = Ok; FALSE = Error
	 */
	private function _loadConfig(string $gid): bool {

	    $cnf = Config::getInstance(); //2
		$gui = guiHandler::getInstance();

		// load configuration?
		if (!isset($this->_ctl[self::TRC_CONF]['Loaded'])) { //2

			// save some default settings
			$this->_ctl[self::TRC_CONF] = [ //2
					'Loaded' 			=> 1, //2
					Config::TRACE_MOD	=> 'Off', //2
					Config::LOG_FILE	=> 'Off', //2
					Config::CRONJOB		=> 'Y', //2
					Config::IMAP_HOST	=> -1, //2
					Config::IMAP_PORT	=> -1, //2
					Config::IMAP_ENC	=> -1, //2
					Config::IMAP_CERT	=> -1, //2
					Config::SMTP_HOST	=> -1, //2
					Config::SMTP_PORT	=> -1, //2
					Config::SMTP_AUTH	=> -1, //2
					Config::SMTP_ENC	=> -1, //2
			]; //2
			$this->_ctl[self::CUR_CONF] = [ //2
	            	Config::HD			=> 'GUI', //2
			]; //2

			// we need to find and save debug trace configuration
   		    for($idx=1; $idx < $this->_ctl[self::TRC_MAX]; $idx++ ) {

   		    	if (!isset($this->_ctl[self::TRC_RECS][$idx]) || $this->_ctl[self::TRC_RECS][$idx][0] != Trace::CONF)
   		    		continue;

				$out = '';
				foreach ($this->_ctl[self::TRC_RECS][$idx][1] as $kc => $v) {

				    // support old class names
					$k = 'syncgw\\lib\\'.$kc;
					if (!defined($k))
						$out .= '<code style="'.Util::CSS_WARN.'">'.
								sprintf(_('Ingnoring unknown parameter "%s" with value "%s"'), $k, $v).'</code><br />';
					else {
						$out .= '<code style="'.Util::CSS_CODE.'">'.sprintf(_('Configuration parameter "%s" = "%s"'), $kc, $v);
						$k 	  = constant($k);
						// special check  for mail parameter
						$org  = $cnf->getVar($k); //2
						if (isset($this->_ctl[self::TRC_CONF][$k]) && $this->_ctl[self::TRC_CONF][$k] == -1) { //2
							$out .= ' -- <code style="'.Util::CSS_WARN.'">'.sprintf(_('Updating to value "%s"'), $org).'</code>'; //2
							$this->_ctl[self::TRC_CONF][$k] =  $org; //2
						} elseif (!isset($this->_ctl[self::TRC_CONF][$k]) && $v != $org) { //2
							$out .= ' -- <code style="'.Util::CSS_WARN.'">'.sprintf(_('Updating from value "%s"'), $org).'</code>'; //2
							$this->_ctl[self::TRC_CONF][$k] =  $v; //2
						} //2
						if ($k == Config::DATABASE) { //2
							if ($v != $cnf->getVar(Config::DATABASE)) { //2
								$gui->putMsg(sprintf(_('Data base "%s" not active - cannot proceed'), $v), Util::CSS_ERR); //2
								return FALSE; //2
							} //2
						} //2
						$out .= '</code><br />';
					}
				}

				$gui->putQBox(_('<strong>sync&bull;gw</strong> trace file environment data'), '', $out, FALSE, 'Msg');
				$gui->putMsg('');
   		    }
	    } //2

	    return TRUE;
	}

	/**
	 * 	Show trace record
	 *
	 *	@param  - TRUE= Show data records only
	 *	@param 	- Running action
	 *	@param	- Trace record #
	 * 	@param 	- Trace record
	 */
	private function _showRec(bool $show, string $action, int $idx, array $rec): void {

		$gui  = guiHandler::getInstance();
		$http = HTTP::getInstance();
		$cnf  = Config::getInstance();

		// save time when trace was created
		$tme = gmdate('Y-m-d H:i:s', $this->_ctl[self::TRC_TIME]).' ';

		switch($rec[0]) {
		case Trace::TVER:
			// check supported trace version
			if ($rec[1] != Trace::TRACE_VER) {
				$gui->putMsg(sprintf(_('This version of <strong>sync&bull;gw</strong> does not support trace version \'%s\''),
							 $rec[1]), Util::CSS_ERR);
				return;
			}
			$gui->putMsg(sprintf(_('Trace created with <strong>sync&bull;gw</strong> trace version %s'), $rec[1]));
			break;

		case Trace::PVER:
			$gui->putMsg(sprintf(_('<strong>sync&bull;gw</strong> running on PHP version %s'), $rec[1]));
			break;

		case Trace::LOG:
            $rec[1] = str_replace("\r", '', $rec[1]);
			foreach (explode("\n", $rec[1]) as $r) {
      			$gui->putMsg('<code style="float:left;"><div style="width:423px;float:left;">'.
       						 '<code style="width:26px;display:inline-block">---</code>'.
       						 gmdate('Y-m-d H:i:s', intval(Util::unxTime(strval(substr($r, 0, 20))))).
       						 '</div><div>'.XML::cnvStr(strval(substr($r, 21))).'</div></code>');
			}
			break;

		case Trace::RCV:
			$msg = '<br/><hr>';
					$gui->mkButton(_('Del'), _('Delete trace record'), //3
										'document.getElementById(\'Action\').value=\''.$action.'\';'. //3
										'document.getElementById(\'DelTraceRec\').value=\''.$idx.'\';', FALSE).'  '; //3
			$msg .= '<strong>'._('Processing trace record ').sprintf('[R%03d]', $idx).'</strong>';
			$gui->putMsg($msg);

			$http->updHTTPVar(HTTP::SERVER, NULL, $this->_ctl[self::T_SERVER] = $rec[2]);
			$http->updHTTPVar(HTTP::RCV_BODY, NULL, $rec[3]);
			// clear handler
            $cnf->updVar(Config::HD, '');
            $http->checkIn();

			// save formatted data
			$this->_ctl[self::T_HEADER] = $http->getHTTPVar(HTTP::RCV_HEAD);
			$this->_ctl[self::T_BODY]   = $http->getHTTPVar(HTTP::RCV_BODY);

			// show _SERVER data
			$out = '';
			foreach ($http->getHTTPVar(HTTP::SERVER) as $k => $v)
				$out .= '<code style="'.Util::CSS_CODE.'">'.XML::cnvStr(str_replace("\n", '', $k.': '.$v)).'</code><br />';
			$gui->putQBox('<code>'.$tme._('SERVER data (including received header)'), '', $out.'</code>', FALSE, 'Msg');

		    // show received header and body
            self::_showHTTP($idx, $show, gmdate('Y-m-d H:i:s', $rec[1]).' ', HTTP::RCV_HEAD);
            break;

		case Trace::SND:
			// inject and convert HTTP data
			$http->updHTTPVar(HTTP::SERVER, NULL, $this->_ctl[self::T_SERVER]);

			$sh = $http->getHTTPVar(HTTP::SND_HEAD);
			$sb = $http->getHTTPVar(HTTP::SND_BODY);

			$http->updHTTPVar(HTTP::SND_HEAD, NULL, $rec[2]);
			$http->updHTTPVar(HTTP::SND_BODY, NULL, $rec[3]);
			$http->checkOut();

			// save formatted data
			$this->_ctl[self::T_HEADER] = $http->getHTTPVar(HTTP::SND_HEAD);
			$this->_ctl[self::T_BODY]   = $http->getHTTPVar(HTTP::SND_BODY);

			$http->updHTTPVar(HTTP::SND_HEAD, NULL, $sh);
			$http->updHTTPVar(HTTP::SND_BODY, NULL, $sb);

			// now we need to inject new output
			if (!$show) {

				$sh = $http->getHTTPVar(HTTP::SND_HEAD);
				$sb = $http->getHTTPVar(HTTP::SND_BODY);

				$http->updHTTPVar(HTTP::SND_HEAD, NULL, $this->_ctl[self::N_HEADER]);
				$http->updHTTPVar(HTTP::SND_BODY, NULL, $this->_ctl[self::N_BODY]);

				// clear handler
     			$cnf->updVar(Config::HD, '');
				$http->checkOut();

				// save formatted data
				$this->_ctl[self::N_HEADER] = $http->getHTTPVar(HTTP::SND_HEAD);
				$this->_ctl[self::N_BODY]   = $http->getHTTPVar(HTTP::SND_BODY);

				$http->updHTTPVar(HTTP::SND_HEAD, NULL, $sh);
				$http->updHTTPVar(HTTP::SND_BODY, NULL, $sb);
			}

			// show send header and body (if available)
			self::_showHTTP($idx, $show, gmdate('Y-m-d H:i:s', $rec[1]).' ', HTTP::SND_HEAD);
			$this->_ctl[self::N_HEADER] = NULL;
			break;

		// data store record
		case Trace::ADD:
			if (!$show || $rec[1] & DataStore::ATTACHMENT)
				break;
			$xml = new XML();
			if ($rec[3])
				$xml->loadXML($rec[3]);
			$out = '<code style="'.Util::CSS_CODE.'">'.$xml->mkHTML();
			$hdr = '';
			$hdr = $gui->mkButton(_('Del'), _('Delete trace record'), //3
						  'document.getElementById(\'Action\').value=\''.$action.'\';'. //3
						  'document.getElementById(\'DelTraceRec\').value=\''.$idx.'\';', FALSE).'  '; //3
			$id  = $xml->getVar($rec[1] & DataStore::EXT ? 'extID' : 'GUID');
			$gui->putQBox($hdr.'<code style="'.Util::CSS_CODE.'">[R'.sprintf('%03d', $idx).'] '.
						  ($rec[1] & DataStore::EXT ? 'External' : 'Internal').' record ['.$id.'] in datastore '.
						  Util::HID(Util::HID_ENAME, $rec[1], TRUE), '', $out.'</code>', FALSE, 'Msg');

		// configuration already loaded
		case Trace::CONF:
			break;

		default:
			$gui->putMsg(sprintf(_('Unknown trace record [R%s] type \'%s\''), $idx, $rec[0]), Util::CSS_ERR);
			break;
		}
	}

	/**
	 * 	HTTP output reader
	 *
	 * 	@param	- HTTP output header
	 *  @param  - HTTP Body string or XML object
	 * 	@return - TRUE = Ok; FALSE = Stop sending output
	 */
	public function readHTTP(array $header, $body): bool { //2

		$this->_ctl[self::N_HEADER] = $header; //2
		$this->_ctl[self::N_BODY]   = $body; //2

		return FALSE;	// disable sending out data //2
	} //2

	/**
	 * 	Delete trace record
	 *
	 * 	@param 	- Trace record <GUID>
	 * 	@param	- Trace record number
	 */
	private function _delRec(string $trc_id, int $idx): void { //3

		// delete trace record
		unlink($this->_ctl[self::TRC_PATH].DIRECTORY_SEPARATOR.'R'.$idx); //3
		unset($this->_ctl[self::TRC_RECS][$idx]); //3

		// check remaining records
		for ($idx++; $idx <= $this->_ctl[self::TRC_MAX]; $idx++) { //3
			if (!isset($this->_ctl[self::TRC_RECS][$idx])) //3
				continue; //3
			if ($this->_ctl[self::TRC_RECS][$idx][0] == Trace::RCV) //3
				break; //3
			if ($this->_ctl[self::TRC_RECS][$idx][0] == Trace::LOG || $this->_ctl[self::TRC_RECS][$idx][0] == Trace::SND) {//3
				unlink($this->_ctl[self::TRC_PATH].DIRECTORY_SEPARATOR.'R'.$idx); //3
				unset($this->_ctl[self::TRC_RECS][$idx]); //3
			} //3
		} //3
	} //3

	/**
	 * 	Cleanup data records
	 *
	 * 	@param 	- Trace record <GUID>
	 * 	@return - TRUE = Ok; FALSE = Error
	 */
	private function _cleanRecs(string $trc_id): bool { //2

		$usr = User::getInstance(); //2
		$cnf = Config::getInstance(); //2
		$db  = DB::getInstance(); //2
		$att = Attachment::getInstance(); //2
		$gui = guiHandler::getInstance(); //2

		// ------------------------------------------------------------------------------------------------------------------------------

		$gui->putMsg(_('Deleting internal and external data records in all data stores for debug user')); //2

		$db->Query(DataStore::USER, DataStore::DEL, $uid = $cnf->getVar(Config::DBG_USR)); //2
		$usr->updVar('GUID', ''); //2

		// we need to make a real login here to enable access to database handler
		if (!$usr->Login($uid, $cnf->getVar(Config::DBG_UPW))) { //2
			$gui->putMsg(_('Unable to authorize debug user - debugging terminated'), Util::CSS_ERR); //2
			return FALSE; //2
		} //2

		// disable logging
		$lvl  = $cnf->updVar(Config::LOG_LVL, Log::ERR); //2
		$log  = Log::getInstance(); //2
		$stat = $log->Suspend(); //2

   	    // we only delete enabled data store to prevent RoundCube synchronization status to disappear
		foreach (Util::HID(Util::HID_ENAME, DataStore::DATASTORES|DataStore::SESSION) as $hid => $name) { //2

	    	// delete internal records
			if (count($recs = $db->getRIDS($hid))) { //2
				foreach ($recs as $gid => $typ) //2
					$db->Query($hid, DataStore::DEL, $gid); //2
			} //2

			if ($hid & DataStore::SYSTEM) //2
				continue; //2

      		// delete external records
			if (count($recs = $db->getRIDS(DataStore::EXT|$hid))) { //2
		    	foreach ($recs as $gid => $typ) //2
		    		// try to delete record even if it is read-only
					if (!$db->Query(DataStore::EXT|$hid, DataStore::DEL, $gid)) //2
						// group is read-only, so we save group mapping (old -> new)
						if ($typ == DataStore::TYP_GROUP) //2
							$this->_ctl[self::TRC_MAP][DataStore::EXT|$hid][$gid] = $gid; //2
			} //2
		} //2
		$name; // disable Eclipse warnings //2
		$log->Resume($stat); //2

		// ------------------------------------------------------------------------------------------------------------------------------

		$gui->putMsg(_('Restoring attachment records')); //2

		foreach ($this->_ctl[self::TRC_RECS] as $rec) { //2

			if ($rec[0] == Trace::ADD && $rec[1] & DataStore::ATTACHMENT) { //2
		   		$att->_gid = $rec[2]; //2
	   			$att->create($rec[5], $rec[3], $rec[4]); //2
				$db->Query(DataStore::ATTACHMENT, DataStore::UPD, $att); //2
				// we don't need to save id's in mapping table, since attachment GUIDs were unique
	    	} //2
		} //2

   	    $gui->putMsg(''); //2

		$cnf->updVar(Config::LOG_LVL, $lvl); //2

   	    return TRUE; //2
	} //2

	/**
	 * 	Process data store add/upd/del (only external records)
	 *
	 *	@param 	- Running action
	 * 	@param 	- Trace record <GUID>
	 * 	@param 	- Trace record #
	 * 	@return - TRUE = Ok; FALSE = Error
	 */
	private function _chkRecs(string $action, string $trc_id, int $idx): bool { //2

		// be aware we only expect external records!
		$db   = DB::getInstance(); //2
		$gui  = guiHandler::getInstance(); //2
		$xml  = new XML(); //2
		$org  = $idx; //2
		$usr  = User::getInstance(); //2
		$cnf  = Config::getInstance(); //2
		$ngid = ''; //2

		// we need to make a real login here to enable access to database handler
		$uid = $cnf->getVar(Config::DBG_USR); //2
		if (!$usr->Login($uid, $cnf->getVar(Config::DBG_UPW))) { //2
			$gui->putMsg(_('Unable to authorize debug user - debugging terminated'), Util::CSS_ERR); //2
			return FALSE; //2
		} //2

		// ------------------------------------------------------------------------------------------------------------------------------
		// check for external records

		$recid = []; //2

		while(++$idx < $this->_ctl[self::TRC_MAX]) { //2

			// skip log records and non-existing records
			if (!isset($this->_ctl[self::TRC_RECS][$idx]) || $this->_ctl[self::TRC_RECS][$idx][0] == Trace::LOG) //2
				continue; //2

			// HTTP received data?
			if ($this->_ctl[self::TRC_RECS][$idx][0] == Trace::RCV) //2
				break; //2

			// get handler
			$hid = $this->_ctl[self::TRC_RECS][$idx][1]; //2

			// only external record actions
			if ($this->_ctl[self::TRC_RECS][$idx][0] != Trace::ADD || !($hid & DataStore::EXT)) //2
				continue; //2

			// load record
			$xml->loadXML($this->_ctl[self::TRC_RECS][$idx][3]); //2
			$xrid = $xml->getVar('extID'); //2

			// check for non-editable group records
			$a = $xml->getVar(fldAttribute::TAG); //2
			if (($typ = $xml->getVar('Type')) == DataStore::TYP_GROUP && !($a & fldAttribute::EDIT)) { //2

				// should we add to mapping table?
				if (!isset($this->_ctl[self::TRC_MAP][$hid][$xrid])) { //2
					foreach ($db->getRIDS($hid) as $rid => $unused) { //2
						$doc = $db->Query($hid, DataStore::RGID, $rid); //2
						if ($doc->getVar(fldAttribute::TAG) & $a) { //2
							// old -> new
							$this->_ctl[self::TRC_MAP][$hid][$xrid] = $doc->getVar('extID'); //2
							break; //2
						} //2
					} //2
					$unused; // disable Eclipse warning //2
				} //2

				$out = '<code style="'.Util::CSS_CODE.'">'.$xml->mkHTML(); //2
	       		$hdr = ''; //2
				$hdr = $gui->mkButton(_('Del'), _('Delete trace record'), //3
							  'document.getElementById(\'Action\').value=\''.$action.'\';'. //3
							  'document.getElementById(\'DelTraceRec\').value=\''.$idx.'\';', FALSE).'  '; //3
				$gui->putQBox($hdr.'<code style="'.Util::CSS_CODE.'">[R'.sprintf('%03d', $idx).'] Group in external datastore '. //2
							  Util::HID(Util::HID_ENAME, $hid).' is not editable - skipping', '', $out.'</code>', FALSE, 'Msg'); //2
				continue; //2
			} //2

			// if we cannot add, we assume synchronization is not enabled for this data store and we skip processing
			if ($ngid = $db->Query($hid, DataStore::ADD, $xml)) { //2

				// save new mapping (old -> new)
				$this->_ctl[self::TRC_MAP][$hid][$xrid] = $ngid; //2

				// new internal handler?
				if (!isset($this->_ctl[self::TRC_MAP][$hid & ~DataStore::EXT])) //2
					$this->_ctl[self::TRC_MAP][$hid & ~DataStore::EXT] = []; //2

				// save processed record id
				$recid[$hid][] = $ngid; //2
			} //2

			// update external record reference
			$out = '<code style="'.Util::CSS_CODE.'">'.$xml->mkHTML(); //2
			$hdr = ''; //2
			$hdr = $gui->mkButton(_('Del'), _('Delete trace record'), //3
						  'document.getElementById(\'Action\').value=\''.$action.'\';'. //3
						  'document.getElementById(\'DelTraceRec\').value=\''.$idx.'\';', FALSE).'  '; //3
			$gui->putQBox($hdr.'<code style="'.Util::CSS_CODE.'">[R'.sprintf('%03d', $idx).'] 	 external '. //2
						  Util::HID(Util::HID_ENAME, $hid).' '.($typ == DataStore::TYP_GROUP ? 'group' : 'record'). //2
						  ' ['.$xrid.($ngid ? ' -> '.$ngid : '').']', //2
						  '', $out.'</code>', FALSE, 'Msg'); //2
		} //2

		// allow external database handler to check every record
		foreach ($recid as $hid => $recs) //2
			$db->chkTrcReferences($hid, $recs, $this->_ctl[self::TRC_MAP]); //2

		// ------------------------------------------------------------------------------------------------------------------------------
		// process internal records

		$idx   = $org; //2
		$recid = []; //2

		while(++$idx < $this->_ctl[self::TRC_MAX]) { //2

			// skip log records and non-existing records
			if (!isset($this->_ctl[self::TRC_RECS][$idx]) || $this->_ctl[self::TRC_RECS][$idx][0] == Trace::LOG) //2
				continue; //2

			// HTTP received data?
			if ($this->_ctl[self::TRC_RECS][$idx][0] == Trace::RCV) //2
				break; //2

			// get handler
			$hid = $this->_ctl[self::TRC_RECS][$idx][1]; //2

			// only internal record actions (without attachments)
			if ($this->_ctl[self::TRC_RECS][$idx][0] != Trace::ADD || ($hid & (DataStore::ATTACHMENT|DataStore::EXT))) //2
				continue; //2

			$xml->loadXML($this->_ctl[self::TRC_RECS][$idx][3]); //2
			$gid = $xml->getVar('GUID'); //2

			if ($hid & DataStore::TASK) { //2

				if (($id = $xml->getVar(fldRelated::TAG)) && //2
					$id != ($ngid = $this->_ctl[self::TRC_MAP][DataStore::TASK|DataStore::EXT][$id])) { //2

					if (gettype($ngid) == 'integer') { //3
						$ngid = strval($ngid); //3
						Debug::Err($this->_ctl[self::TRC_MAP][DataStore::TASK|DataStore::EXT], //3
								   'Assignment for record "'.$id.'" missing!'); //3
					} //3

					$xml->setVal($ngid); //2

					Debug::Msg('['.$gid.'] Updating reference field <Related> from ['.$id.'] to ['.$ngid.']'); //3
				} //2
			} //2

			if ($hid & DataStore::DEVICE) { //2

				// change device name (for debugging purposes)
				if (strncmp($gid, Util::DBG_PREF, Util::DBG_PLEN)) //2
				    $gid = Util::DBG_PREF.$gid; //2

	   		   	$xml->updVar('GUID', $gid); //2

				// set owner to debug user
				$xml->updVar('LUID', $usr->getVar('LUID')); //2

	          	// be sure to delete existing record
	            $db->Query($hid, DataStore::DEL, $gid); //2

				// update suspended session
				if ($s = $xml->getVar('Suspended')) //2
					if (strncmp($s, Util::DBG_PREF, Util::DBG_PLEN)) //2
						$xml->setVal(Util::DBG_PREF.$s); //2

	            // disable saving of active device
				$dev = Device::getInstance(); //2
				$dev->updVar('GUID', ''); //2
	    	} //2

			// user record?
			if ($hid & DataStore::USER) { //2

				// update active device
				if (($d = $xml->getVar('ActiveDevice')) && strncmp($d, Util::DBG_PREF, Util::DBG_PLEN)) //2
					$xml->setVal(Util::DBG_PREF.$d); //2

				// update all alternate devices
				if ($xml->xpath('//DeviceId/.')) { //2
					while ($d = $xml->getItem()) //2
						if (strncmp($d, Util::DBG_PREF, Util::DBG_PLEN)) //2
							$xml->setVal(Util::DBG_PREF.$d); //2
				} //2

				// update attachment names
			    $xml->xpath('//Attachments/Name'); //2
			    while (($v = $xml->getItem()) !== NULL) { //2
			        if (substr($v, 0, Attachment::PLEN) == Attachment::PREF) { //2
			            list(, , $i) = explode(Attachment::SEP, $v); //2
			            $xml->setVal(Attachment::PREF.$usr->getVar('LUID').Attachment::SEP.$i); //2
			        } //2
			    } //2

			    // get debug user id
			    if (strpos($gid = $cnf->getVar(Config::DBG_USR), '@')) //2
			    	list($gid, ) = explode('@', $gid); //2

			    // update primary e-Mail
				$xml->updVar('EMailPrime', $gid.'@'.$cnf->getVar(Config::IMAP_HOST)); //2

			    // change userid to debug user
				$xml->updVar('GUID', $gid); //2
				$xml->updVar('LUID', $usr->getVar('LUID')); //2

				// be sure to delete existing record
	            $db->Query($hid, DataStore::DEL, $gid); //2

	            // force reload
	            $usr->updVar('GUID', ''); //2
			} //2

			if (!$db->Query($hid, DataStore::RGID, $gid)) { //2
				if (!($ngid = $db->Query($hid, DataStore::ADD, $xml))) { //2
					$gui->putMsg('+++ '.sprintf(_('Error adding record [%s] in internal data store %s'), $gid, //2
					             		   Util::HID(Util::HID_ENAME, $hid, TRUE)), Util::CSS_WARN); //2
				} //2

				// save processed record id
				$recid[$hid][] = $ngid; //2
			} elseif ($hid & DataStore::DATASTORES) //2
				// save record id
				$recid[$hid][] = $gid; //2

			$out = '<code style="'.Util::CSS_CODE.'">'.$xml->mkHTML(); //2
			$hdr = ''; //2
			$hdr = $gui->mkButton(_('Del'), _('Delete trace record'), //3
						  'document.getElementById(\'Action\').value=\''.$action.'\';'. //3
						  'document.getElementById(\'DelTraceRec\').value=\''.$idx.'\';', FALSE).'  '; //3
			$gui->putQBox($hdr.'<code style="'.Util::CSS_CODE.'">[R'.sprintf('%03d', $idx).'] '.($ngid ? 'Adding' : //3
						  'Skipping').' internal '.Util::HID(Util::HID_ENAME, $hid, TRUE).' record '. //3
						  '['.$gid.($ngid ? ' -> '.$ngid : ' already available').']', //3
						  '', $out.'</code>', FALSE, 'Msg'); //3
		} //2

		// allow external database handler to check every record
		foreach ($recid as $hid => $recs) //2
			$db->chkTrcReferences($hid, $recs, $this->_ctl[self::TRC_MAP]); //2

		return TRUE; //2
	} //2

	/**
	 *  Show HTTP send/received header and body
	 *
	 *	@param 	- Trace record #
	 *	@param  - TRUE= Show data records only
	 *  @param  - Time stamp
	 *  @param 	- HTTP data
	 */
	private function _showHTTP(int $idx, bool $show, string $tme, string $typ): void {

		$gui = guiHandler::getInstance();

		if ($show || $typ == HTTP::RCV_HEAD) {

			$out = '';
			foreach ($this->_ctl[self::T_HEADER] as $k => $v)
	        	$out .= '<code style="'.Util::CSS_CODE.'">'.XML::cnvStr($k.': '.$v).'</code><br />';
			$gui->putQBox('<code>'.$tme._('Header ').($typ == HTTP::RCV_HEAD ? _('received') : _('send')),
						  '', $out.'</code>', FALSE, 'Msg');

			if (empty($wrk = $this->_ctl[self::T_BODY]))
				$gui->putMsg('<code style="width:26px;display:inline-block"> </code><code>'.$tme._('Body is empty').'</code>');
			else {

				// reload XML data to get nice formatting
			    if (is_object($wrk) || substr($wrk, 0, 2) == '<?') {
			    	if (!is_object($wrk)) {
						$xml = new XML();
						$xml->loadXML(str_replace([ 'xmlns=',  'xmlns:', ], [ 'xml-ns=', 'xml-ns:', ], $wrk));
						$wrk = $xml;
			    	}
			    	$wrk = self::_comment($wrk); //2
			    	$wrk = $wrk->saveXML(TRUE, TRUE);
			    }

				// convert to array
				$body = explode("\n", str_replace("\r", '', $wrk));
				array_pop($body);

				$out = '';
			    foreach ($body as $rec) {
			        $c = strpos($rec, '<!--') !== FALSE ? Util::CSS_INFO : Util::CSS_CODE;
	    	        $out .= '<code style="'.$c.'">'.XML::cnvStr($rec).'</code><br />';
			    }
	            $gui->putQBox('<code>'.$tme._('Body'), '', $out.'</code>', FALSE, 'Msg');
	 		}
			return;
		}

		list($cnt, $arr) = Util::diffArray($this->_ctl[self::T_HEADER],
										   empty($this->_ctl[self::N_HEADER]) ? [] : $this->_ctl[self::N_HEADER]
										   , self::EXCLUDE //2
										   );
		if ($cnt)
        	$gui->putQBox('<code style="'.Util::CSS_ERR.'">'.$tme.'+++ '.
              			  sprintf(_('Header send (%d changes  "-" - stored in trace; "+" - newly created)'), $cnt / 2),
                          '',  $arr.'</code>', FALSE, 'Msg');
		else
        	$gui->putQBox('<code>'.$tme._('Header send (0 changes)'), '', $arr.'</code>', FALSE, 'Msg');

       	if (empty($this->_ctl[self::T_BODY]) && empty($this->_ctl[self::N_BODY])) {
			$gui->putMsg('<code style="width:26px;display:inline-block"> </code><code>'.$tme._('Body is empty').'</code>');
			return;
       	}

		// reload XML data to get nice formatting
		$bdy = [];
       	foreach ([ 'tbdy' => self::T_BODY, 'nbdy'=> self::N_BODY, ] as $k => $v) {
			$wrk = $this->_ctl[$v];
			if (is_null($wrk))
				$wrk = '';
			if (is_object($wrk) || substr($wrk, 0, 2) == '<?') {
				if (!is_object($wrk)) {
					$xml = new XML();
					$xml->loadXML(str_replace([ 'xmlns=',  'xmlns:', ], [ 'xml-ns=', 'xml-ns:', ], $wrk));
					$wrk = $xml;
				}
				$wrk = self::_comment($wrk); //2
	       		$wrk = $wrk->saveXML(TRUE, TRUE);
				$wrk = str_replace([ 'xml-ns=', 'xml-ns:' ], [ 'xmlns=', 'xmlns:' ], $wrk);
				// delete optional character set attribute
				$wrk = preg_replace('/(\sCHARSET)(=[\'"].*[\'"])/iU', '', $wrk);
				// remove DOCTYPE
				$wrk = preg_replace('/(.*)(<!.*">)(.*)\n/', '${1}${3}', $wrk);
			}

			// convert to array
	       	$bdy[$k] = explode("\n", str_replace("\r", '', strval($wrk)));
			array_pop($bdy[$k]);
       	}

		list($cnt, $arr) = Util::diffArray($bdy['tbdy'], $bdy['nbdy']
											, self::EXCLUDE //2
											);
        if ($cnt)
			$gui->putQBox('<code style="'.Util::CSS_ERR.'">'.$tme.'+++ '.
						  sprintf(_('Body send (%d changes  "-" - stored in trace; "+" - newly created)'), $cnt / 2),
                          '',  $arr.'</code>', FALSE, 'Msg');
        else
			$gui->putQBox('<code>'.$tme._('Body send (0 changes)'), '', $arr.'</code>', FALSE, 'Msg');

        $this->_ctl[self::N_BODY]   = NULL;
		$this->_ctl[self::N_HEADER] = [];
 	}

 	/**
 	 * 	Convert XML document
 	 *
 	 * 	@param 	- XML object to send
 	 */
 	private function _comment($xml) { //2

 		if (!is_object($xml)) //2
 			return $xml; //2

		$tags = [ //2
			    // Tag                   Path
				[ 'Autodiscover',		'Response/Action/Status'                              ], //2
				[ 'Sync', 				'Status'                                              ], //2
				[ 'Sync', 				'Collections/Collection/Status'                       ], //2
				[ 'Sync', 				'Collections/Collection/Responses/Add/Status'         ], //2
				[ 'Sync', 				'Collections/Collection/Responses/Change/Status'      ], //2
			    [ 'Sync', 				'Collections/Collection/Responses/Delete/Status'      ], //2
				[ 'Sync', 				'Collections/Collection/Responses/Fetch/Status'       ], //2
			    [ 'GetItemEstimate',	'Status'                                              ], //2
			    [ 'GetItemEstimate', 	'Response/Status'                                     ], //2
				[ 'FolderCreate', 		'Status'                                              ], //2
				[ 'FolderUpdate', 		'Status'                                              ], //2
				[ 'FolderSync', 		'Status'                                              ], //2
				[ 'Settings', 			'Status'                                              ], //2
				[ 'Settings', 			'Oof/Status'                                          ], //2
			    [ 'Settings', 			'Oof/Get/OofState'                                    ], //2
				[ 'Settings', 			'DeviceInformation/Status'                            ], //2
				[ 'Settings', 			'DevicePassword/Status'                               ], //2
				[ 'Settings', 			'UserInformation/Status'                              ], //2
				[ 'Settings', 			'RightsManagementInformation/Status'                  ], //2
				[ 'Provision', 		    'Status'                                              ], //2
				[ 'Provision', 		    'Policies/Policy/Status'                              ], //2
				[ 'Provision', 		    'DeviceInformation/Status'                            ], //2
				[ 'Provision', 		    'RemoteWipe/Status'                                   ], //2
				[ 'ValidateCert', 		'Status'                                              ], //2
				[ 'ValidateCert', 		'Certificate/Status'                                  ], //2
				[ 'Ping', 				'Status'                                              ], //2
				[ 'MoveItems', 		    'Response/Status'                                     ], //2
				[ 'SmartFormward', 	    'Status'                                              ], //2
				[ 'SmartReply', 		'Status'                                              ], //2
			    [ 'SendMail', 			'Status'                                              ], //2
				[ 'MeetingResponse', 	'Result/Status'                                       ], //2
				[ 'Search', 			'Status'                                              ], //2
				[ 'Search', 			'Response/Store/Status'                               ], //2
				[ 'Search', 			'Response/Result/Properties/Picture/Status'           ], //2
				[ 'ItemOperations', 	'Response/Fetch/Status'                               ], //2
			    [ 'ItemOperations', 	'Response/Move/Status'                                ], //2
				[ 'ItemOperations', 	'Response/EmptyFolderContent/Status'                  ], //2
				[ 'ItemOperations', 	'Status'                                              ], //2
				[ 'ResolveRecipients',  'Status'                                              ], //2
				[ 'ResolveRecipients',  'Response/Status'                                     ], //2
				[ 'ResolveRecipients',  'Response/Recipient/Availability/Status'              ], //2
				[ 'ResolveRecipients',  'Response/Recipient/Certificates/Status'              ], //2
				[ 'ResolveRecipients',  'Response/Recipient/Picture/Status'                   ], //2
			]; //2
		foreach ($tags as $t) { //2
			$xml->xpath('//'.$t[0].($t[1] ? '/'.$t[1] : '')); //2
			while (($v = $xml->getItem()) !== NULL) { //2
				$c = 'syncgw\activesync\mas'.$t[0]; //2
				$xml->addComment($c::status($t[1], $v)); //2
			} //2
		} //2
		$xml->xpath('//FolderSync/*/*/Type'); //2
		while (($v = $xml->getItem()) !== NULL) //2
			$xml->addComment(masFolderType::type($v)); //2
		$xml->xpath('//FolderCreate/Type'); //2
		while (($v = $xml->getItem()) !== NULL) //2
			$xml->addComment(masFolderType::type($v)); //2

		return $xml; //2
 	} //2

}

?>