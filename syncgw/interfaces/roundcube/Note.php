<?php
declare(strict_types=1);

/*
 * 	Notes handler class
 *
 *	@package	sync*gw
 *	@subpackage	RoundCube data base
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\interfaces\roundcube;

use syncgw\lib\Config; //3
use syncgw\lib\Debug; //3
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\ErrorHandler;
use syncgw\lib\Log;
use syncgw\lib\Util;
use syncgw\lib\XML;
use syncgw\document\field\fldSummary;
use syncgw\document\field\fldCategories;
use syncgw\document\field\fldBody;
use syncgw\document\field\fldGroupName;
use syncgw\document\field\fldAttribute;
use syncgw\document\field\fldMessageClass;
use syncgw\document\field\fldLastMod;

class Note {

	// module version number
	const VER 			= 19;

	const PLUGIN 		= [ 'ddnotes' => '1.0.2' ];

	const MAP       	= [
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
	// 	1 - Title
    //  2 - Body
    //  3 - Skip
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
		'title' 					=> [ 1, fldSummary::TAG,  		],
		'body'						=> [ 2, fldBody::TAG,	 			],
	//  'Body/Type'						// Handled by fldBody

    // some fields only included for syncDS() - not part of data record

    	'#grp_name'					=> [ 3, fldGroupName::TAG,	 	],
		'#grp_attr'					=> [ 3, fldAttribute::TAG,		],
		'#cats'						=> [ 3, fldCategories::TAG,		],
		'#msgtyp'					=> [ 3, fldMessageClass::TAG,		],
		'#lmod'						=> [ 3, fldLastMod::TAG,			],

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	];

	// supported file type extensions
	const EXT	    	= [
	    'text/plain'    => fldBody::TYP_TXT,
	    'text/html'    	=> fldBody::TYP_HTML,
	    'text/markdown' => fldBody::TYP_MD,
	];

	// default group id
	const GRP			= DataStore::TYP_GROUP.'0';

 	/**
	 * 	Record mapping table
	 * 	@var array
	 */
	private $_ids		= NULL;

	/**
	 * 	Synchronization preference
	 * 	@var string
	 */
	private $_pref;

	/**
	 *  Pointer to RoundCube main handler
	 *  @var Handler
	 */
	private $_hd;

    /**
     * 	Singleton instance of object
     * 	@var Note
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @param  - Pointer to handler class
	 *  @return - Class object
	 */
	public static function getInstance(Handler &$hd): Note {

		if (!self::$_obj) {

            self::$_obj = new self();

            self::$_obj->_hd = $hd;

			// check plugin version
			foreach (self::PLUGIN as $name => $ver) {
				$a = $hd->RCube->plugins->get_info($name);
		   		if (version_compare($ver, $a['version']) < 0)
	   				return self::$_obj;
			}
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

		$xml->addVar('Opt',sprintf(_('RoundCube %s handler'), Util::HID(Util::HID_ENAME, DataStore::NOTE)));
		$xml->addVar('Ver', strval(self::VER));

		// check plugin version
		foreach (self::PLUGIN as $name => $ver) {
			$i = $this->_hd->RCube->plugins->get_info($name);
			$a = $this->_hd->RCube->plugins->active_plugins;
			$xml->addVar('Opt', '<a href="https://plugins.roundcube.net/#/packages/dondominio/'.$name.'" target="_blank">'.$name.'</a> '.
					      ' plugin v'.$ver);
			if (!in_array($name, $a)) {
				ErrorHandler::resetReporting();
				$xml->addVar('Stat', sprintf(_('+++ ERROR: "%s" not active!'), $name));
			} elseif ($i['version'] != 'dev-master' && version_compare($ver, $i['version']) > 0) {
				ErrorHandler::resetReporting();
				$xml->addVar('Stat', sprintf(_('+++ ERROR: Require plugin version "%s" - "%s" found!'),
							  $ver, $i['version']));
			} else
				$xml->addVar('Stat', _('Implemented'));
		}
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

		// load records?
		if (is_null( $this->_ids))
			self::_loadRecs();

		$out = TRUE;
		$log = Log::getInstance();

		switch ($cmd) {
		case DataStore::GRPS:
			// build list of records
			$out = [];
			foreach ($this->_ids as $k => $v) {
				if (substr($k, 0, 1) == DataStore::TYP_GROUP)
					$out[$k] = substr($k, 0, 1);
			}

			if (Debug::$Conf['Script'] && Debug::$Conf['Script'] != 'Document') //3
				Debug::Msg($out, 'All group records'); //3
			break;

		case DataStore::RIDS:

			// build list of records
			$out = [];
			foreach ($this->_ids as $k => $v)
				if ($v[Handler::GROUP] == $parm)
					$out[$k] = substr($k, 0, 1);

			if (Debug::$Conf['Script'] && Debug::$Conf['Script'] != 'Document') //3
				Debug::Msg($out, 'All record ids in group "'.$parm.'"'); //3
			break;

		case DataStore::RGID:

			if (!is_string($parm) || !self::_chkLoad($parm) || !($out = self::_swap2int($parm))) {
				$log->Msg(Log::WARN, 20311, is_string($parm) ? (substr($parm, 0, 1) == DataStore::TYP_DATA ?
						  _('note record') : _('notes group')) : gettype($parm), $parm);
				return FALSE;
			}
			break;

		case DataStore::ADD:

			// if we have no group, we switch to default group
			if (!($gid = $parm->getVar('extGroup')) || !isset($this->_ids[$gid])) {

				// set default group
				foreach ($this->_ids as $rid => $val) {
					if (substr($rid, 0, 1) == DataStore::TYP_GROUP && ($val[Handler::ATTR] & fldAttribute::WRITE)) {
						$gid = $rid;
				       	break;
					}
				}
			    $parm->updVar('extGroup', $gid);
			}

			// no group found?
			if ($parm->getVar('Type') == DataStore::TYP_DATA && !isset($this->_ids[$gid])) {
				$log->Msg(Log::WARN, 20315, $gid, _('notes group'));
				return FALSE;
			}

			// add external record
			if (!($out = self::_add($parm))) {
				$log->Msg(Log::WARN, 20312, $parm->getVar('Type') == DataStore::TYP_DATA ?
						  _('note record') : _('notes group'));
				return FALSE;
			}
	   		break;

		case DataStore::UPD:

			$rid = $parm->getVar('extID');

			// be sure to check record is loaded
			if (!self::_chkLoad($rid)) {
				$log->Msg(Log::WARN, 20313, substr($rid, 0, 1) == DataStore::TYP_DATA ?
						  _('note record') : _('notes group'), $rid);
				$cnf = Config::getInstance(); //3
				if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE) //3
					Debug::Err('Update should work - please check if synchronization is turned on!'); //3
				return FALSE;
			}

			// does record exist?
			if (!isset($this->_ids[$rid]) ||
				// is record editable?
			   	!($this->_ids[$rid][Handler::ATTR] & fldAttribute::EDIT)
				// is group writable?^
				# || !($this->_ids[$this->_ids[$rid][Handler::GROUP]][Handler::ATTR] & fldAttribute::WRITE)
				) {
				$log->Msg(Log::WARN, 20315, _('notes group'), $rid);
				return FALSE;
			}

			// update external record
			if (!($out = self::_upd($parm))) {
				$log->Msg(Log::WARN, 20313, substr($rid, 0, 1) == DataStore::TYP_DATA ?
						  _('note record') : _('notes group'), $rid);
				return FALSE;
			}
			break;

		case DataStore::DEL:

			// be sure to check record is loaded
			if (!self::_chkLoad($parm)) {
				$log->Msg(Log::WARN, 20314, substr($parm, 0, 1) == DataStore::TYP_DATA ?
						  _('note record') : _('notes group'), $parm);
				return FALSE;
			}

			// does record exist?
			if (!isset($this->_ids[$parm])
				// is record a group and is it allowed to delete?
			    # substr($parm, 0, 1) == DataStore::TYP_GROUP && !($this->_ids[$parm][Handler::ATTR] & fldAttribute::DEL) ||
				// is record a data records and is it allowed to delete?
			   	# (substr($parm, 0, 1) == DataStore::TYP_DATA && !($this->_ids[$this->_ids[$parm][Handler::GROUP]][Handler::ATTR] & fldAttribute::WRITE))))
				) {
			    $log->Msg(Log::WARN, 20315, $parm, _('notes group'));
				return FALSE;
			}

			// delete  external record
			if (!($out = self::_del($parm))) {
				$log->Msg(Log::WARN, 20314, substr($parm, 0, 1) == DataStore::TYP_DATA ?
						  _('note record') : _('notes group'), $parm);
				return FALSE;
			}
			break;

		default:
			break;
		}

		return $out;
	}

	/**
	 * 	Get list of supported fields in external data base
	 *
	 * 	@param	- Handler ID
	 * 	@return	- [ field name]
	 */
	public function getflds(int $hid): array {

		$rc = [];
		foreach (self::MAP as $k => $v)
			$rc[] = $v[1];
		$k; // disable Eclipse warning

		return $rc;
	}

	/**
	 * 	Reload any cached record information in external data base
	 *
	 * 	@param	- Handler ID
	 * 	@return	- TRUE=Ok; FALSE=Error
	 */
	public function Refresh(int $hid): bool {

	    self::_loadRecs();

	    return TRUE;
	}

	/**
	 * 	Check trace record references
	 *
	 *	@param 	- Handler ID
	 * 	@param 	- External record array [ GUID ]
	 * 	@param 	- Mapping table [HID => [ GUID => NewGUID ] ]
	 */
	public function chkTrcReferences(int $hid, array $rids, array $maps): void {
	}

	/**
	 * 	(Re-) load existing external records
	 *
	 *  @param 	- NULL= root; else <GID> to load
 	 */
	private function _loadRecs(?string $grp = NULL): void {

	   	// get synchronization preferences
        $p = $this->_hd->RCube->user->get_prefs();
        $this->_pref = isset($p['syncgw']) ? $p['syncgw'] : '';
        Debug::Msg('Folder to synchronize "'.$this->_pref.'"'); //3

		// re-create list
		if (!$grp) {

			// no notes available
        	$this->_ids = [];
			$this->_ids[self::GRP] = [
						Handler::GROUP => '',
				        Handler::NAME  => _('Notes default group'),
						Handler::LOAD  => 1,
						Handler::ATTR  => fldAttribute::READ|fldAttribute::WRITE|fldAttribute::DEFAULT,
     		];

	    	// included in synchronization?
   			if (strpos($this->_pref, Handler::NOTES_FULL.'0'.';') === FALSE) {
	    		$log = Log::getInstance();
	    		$log->Msg(Log::ERR, 20350, _('notes group'), $this->_pref);
	    		$this->_ids = [];
	   			return;
   			}
		}

		$sql = 'SELECT * FROM `'.$this->_hd->RCube->config->get('db_table_lists', $this->_hd->RCube->db->table_name('ddnotes')).'`'.
			   ' WHERE user_id = ?';
		do {
	        $res = $this->_hd->RCube->db->query($sql, $this->_hd->RCube->user->ID);
		} while ($this->_hd->chkRetry(DataStore::NOTE, __LINE__));

	    if (!$this->_hd->Retry)
			return;

		$recs = [];
		do {
			while ($rec = $this->_hd->RCube->db->fetch_assoc($res))
				$recs[] = $rec;
		} while ($this->_hd->chkRetry(DataStore::NOTE, __LINE__));

		foreach ($recs as $rec)
			// supported?
			if (isset(self::EXT[$rec['mimetype']]))
				$this->_ids[DataStore::TYP_DATA.$rec['id']] = [
						Handler::GROUP => self::GRP,
				        Handler::NAME  => $rec['title'],
						Handler::ATTR  => fldAttribute::READ|fldAttribute::WRITE|fldAttribute::EDIT|fldAttribute::DEL,
	     		];

    	if (Debug::$Conf['Script'] == 'DBExt') { //3
        	$ids = $this->_ids; //3
        	foreach ($this->_ids as $id => $unused) //3
        		$ids[$id][Handler::ATTR] = fldAttribute::showAttr($ids[$id][Handler::ATTR]); //3
        	$unused; //3 disable Eclipse warning
        	Debug::Msg($ids, 'Record mapping table ('.count($this->_ids).')'); //3
    	} //3
	}

	/**
	 * 	Check record is loadeded
	 *
	 *  @param 	- Record id to load
	 *  @return - TRUE=Ok; FALSE=Error
 	 */
	private function _chkLoad(string $rid): bool {

		// any GUID given?
	    if (!$rid)
	    	return FALSE;

	    // alreay loaded?
		if (!isset($this->_ids[$rid])) {
			foreach ($this->_ids as $id => $parm) {
				if (substr($id, 0, 1) == DataStore::TYP_GROUP && !$parm[Handler::LOAD]) {

					// load group
					self::_loadRecs($id);

					// could we load record?
					if (isset($this->_ids[$rid]))
						return TRUE;
				}
			}
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * 	Get external record
	 *
	 *	@param	- External record ID
	 * 	@return - Internal document or NULL
	 */
	private function _swap2int(string $rid): ?XML {

		$db = DB::getInstance();

		if (substr($rid, 0, 1) == DataStore::TYP_GROUP) {

			$int = $db->mkDoc(DataStore::NOTE, [
						'Group' 			=> '',
						'Typ'   			=> DataStore::TYP_GROUP,
						'extID'				=> $rid,
						fldGroupName::TAG	=> $this->_ids[$rid][Handler::NAME],
						fldAttribute::TAG	=> $this->_ids[$rid][Handler::ATTR],
			]);

		} else {

			// load external record
			if (!($rec = self::_get($rid)))
				return NULL;

			// create XML object
			$int = $db->mkDoc(DataStore::NOTE, [
							'GID' 		=> '',
							'extID'		=> $rid,
							'extGroup'	=> self::GRP,
			]);

			foreach (self::MAP as $unused => $tag) {

				switch ($tag[0]) {
				// 	1 - Title
			    case 1:
			    	if ($rec['title'])
						$int->addVar($tag[1], $rec['title']);
					break;

				//  2 - Body
				case 2:
					$int->addVar($tag[1], $rec['body'], FALSE, [ 'X-TYP' => self::EXT[ $rec['typ'] ? $rec['typ'] : 'text/plain' ] ]);

				// 3 - Skip
				case 3:
					break;
				}
			}
			$unused; // disable Eclipse warning
		}

		// add missing field
		$int->addVar(fldMessageClass::TAG, 'IPM.StickyNote');

		if (Debug::$Conf['Script'] && Debug::$Conf['Script'] != 'Document') { //3
			$int->getVar('syncgw'); //3
            Debug::Msg($int, 'Internal record'); //3
		} //3

		return $int;
	}

	/**
	 * 	Swap internal to external record
	 *
	 *	@param 	- Internal document
	 * 	@return - External document
	 */
	private function _swap2ext(XML &$int): array {

		$rid = $int->getVar('extID');

		// output record
		$rec  = [
					'id' 		=> substr($rid, 1),
					'title'		=> NULL,
					'body'		=> NULL,
					'typ'		=> NULL,
		];

		$int->getVar('Data');
		$map = array_flip(self::EXT);

		// swap data
		foreach (self::MAP as $key => $tag) {

			$ip = $int->savePos();

			switch ($tag[0]) {
			// 	1 - Title
		    case 1:
				if ($val = $int->getVar($tag[1], FALSE))
					$rec[$key] = $val;
				break;

			//  2 - Body
			case 2:
				if ($val = $int->getVar($tag[1], FALSE)) {
					$rec[$key] = $val;
					if ($t = $int->getAttr('X-TYP'))
						$rec['typ'] = $map[ $t ];
					else
						$rec['typ'] = fldBody::TYP_TXT;
				}

			// 3 - Skip
			case 3:
				break;
			}

			$int->restorePos($ip);
		}

		if (Debug::$Conf['Script'] && Debug::$Conf['Script'] != 'Document') //3
	        Debug::Msg($rec, 'External record'); //3

		return $rec;
	}

	/**
	 *  Get record
	 *
	 *  @param  - Record Id
	 *  @return - External record or NULL on error
	 */
	private function _get(string $rid): ?array {

	    $sql    = 'SELECT * FROM `'.$this->_hd->RCube->config->get('db_table_lists', $this->_hd->RCube->db->table_name('ddnotes')).'`'.
			      ' WHERE id = ?';
	    do {
	    	if ($res = $this->_hd->RCube->db->query($sql, substr($rid, 1)))
	    		$r = $this->_hd->RCube->db->fetch_assoc($res);
		} while ($this->_hd->chkRetry(DataStore::NOTE, __LINE__));

   		if (!$this->_hd->Retry)
			return NULL;

		// output record
		$rec = [
					'id' 		=> substr($rid, 1),
					'title'		=> $r['title'],
					'body'		=> $r['content'] == 'NULL' ? '' : $r['content'],
					'typ'		=> $r['mimetype'],
		];

		if (Debug::$Conf['Script'] && Debug::$Conf['Script'] != 'Document') //3
            Debug::Msg($rec, 'External record'); //3

		return $rec;
	}

	/**
	 *  Add external record
	 *
	 *  @param  - XML record
	 *  @return - New record Id or NULL on error
	 */
	private function _add(XML &$int): ?string {

		// check for group
		if ($int->getVar('Type') == DataStore::TYP_GROUP)
			return NULL;

		// create external record
		$rec = self::_swap2ext($int);

	    $sql = 'INSERT INTO `'.$this->_hd->RCube->config->get('db_table_lists', $this->_hd->RCube->db->table_name('ddnotes')).'`'.
			   ' SET `user_id` = %s, `parent_id` = %d, `title` = "%s", '.
			   '     `mimetype` = "%s", `content`  = "%s", `file_size`  = %d';
		$sql = sprintf($sql, $this->_hd->RCube->user->ID, 0, $this->_hd->RCube->db->escape($rec['title'] ? $rec['title'] : ''), $rec['typ'],
        					$this->_hd->RCube->db->escape($rec['body']), strlen(strval($rec['body'])));
		do {
			if ($this->_hd->RCube->db->query($sql))
				$rid = $this->_hd->RCube->db->insert_id();
		} while ($this->_hd->chkRetry(DataStore::NOTE, __LINE__));

   		if (!$this->_hd->Retry)
    		return NULL;

		// add records to known list
		$this->_ids[$rid = DataStore::TYP_DATA.$rid] = [
				Handler::GROUP 	=> self::GRP,
				Handler::NAME  	=> $rec['title'],
				Handler::ATTR	=> fldAttribute::READ|fldAttribute::WRITE|fldAttribute::EDIT|fldAttribute::DEL,
		];

		$id = $this->_ids[$rid]; //3
        $id[Handler::ATTR] = fldAttribute::showAttr($id[Handler::ATTR]); //3
        if (Debug::$Conf['Script'] && Debug::$Conf['Script'] != 'Document') //3
			Debug::Msg($id, 'New mapping record "'.$rid.'" ('.count($this->_ids).')'); //3

		return $rid;
	}

	/**
	 *  Update external record
	 *
	 *  @param  - XML record
	 *  @param	- External record
	 *  @return - TRUE or FALSE on error
	 */
	private function _upd(XML &$int): bool {

		// get external record id
		$rid = $int->getVar('extID');

		// create external record
		$rec = self::_swap2ext($int);

	    $sql = 'UPDATE `'.$this->_hd->RCube->config->get('db_table_lists', $this->_hd->RCube->db->table_name('ddnotes')).'`'.
			   ' SET ``title` = "%s", `mimetype` = "%s", `content`  = "%s", `file_size`  = %d, `ts_updated` = "%s" '.
			   ' WHERE `id` = %d AND `user_id`= %d';
		$sql = sprintf($sql, $this->_hd->RCube->db->escape($rec['title']), $rec['typ'], $this->_hd->RCube->db->escape($rec['body']),
					   strlen($rec['body']), date("Y-m-d H:i:s"), substr($rid, 1), $this->_hd->RCube->user->ID);
		do {
			$this->_hd->RCube->db->query($sql);
		} while ($this->_hd->chkRetry(DataStore::NOTE, __LINE__));

   		if (!$this->_hd->Retry)
			return NULL;

		// add record to internal managament
		$this->_ids[$rid] = [
				Handler::GROUP => self::GRP,
				Handler::NAME  => $rec['title'],
		];

		return TRUE;
	}

	/**
	 * 	Delete external record
	 *
	 * 	@param 	- Record id
	 * 	@return - TRUE=Ok, FALSE=Error
	 */
	private function _del(string $rid): bool {

		$ids = [];

		// we ignore deletion of address books
		if (substr($rid, 0, 1) == DataStore::TYP_GROUP) {

			// but we will delete content of group!
			foreach ($this->_ids as $id => $v)
				if ($v[Handler::GROUP] == $rid)
					$ids[] = $id;

		} else
			$ids[] = $rid;

		// perform deletion

		foreach ($ids as $id) {

		    $sql = 'DELETE FROM `'.$this->_hd->RCube->config->get('db_table_lists', $this->_hd->RCube->db->table_name('ddnotes')).'`'.
				   ' WHERE `id` = %d AND `user_id`= %d';
			$sql = sprintf($sql, substr($id, 1), $this->_hd->RCube->user->ID);
			do {
				$this->_hd->RCube->db->query($sql);
			} while ($this->_hd->chkRetry(DataStore::NOTE, __LINE__));

   			if (!$this->_hd->Retry)
    			return FALSE;

    		unset($this->_ids[$id]);
		}

		return TRUE;
	}

}

?>