<?php
declare(strict_types=1);

/*
 * 	Contact handler class
 *
 *	@package	sync*gw
 *	@subpackage	RoundCube data base
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\interfaces\roundcube;

use syncgw\lib\Debug; //3
use syncgw\lib\Attachment;
use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Encoding;
use syncgw\lib\Log;
use syncgw\lib\Trace; //3
use syncgw\lib\Util;
use syncgw\lib\XML;
use rcube_contacts;
use syncgw\document\field\fldFullName;
use syncgw\document\field\fldFirstName;
use syncgw\document\field\fldLastName;
use syncgw\document\field\fldMiddleName;
use syncgw\document\field\fldNickName;
use syncgw\document\field\fldOrganization;
use syncgw\document\field\fldPrefix;
use syncgw\document\field\fldSuffix;
use syncgw\document\field\fldHomePhone;
use syncgw\document\field\fldHomePhone2;
use syncgw\document\field\fldBusinessPhone;
use syncgw\document\field\fldBusinessPhone2;
use syncgw\document\field\fldMobilePhone;
use syncgw\document\field\fldCompanyPhone;
use syncgw\document\field\fldHomeFax;
use syncgw\document\field\fldBusinessFax;
use syncgw\document\field\fldCarPhone;
use syncgw\document\field\fldPager;
use syncgw\document\field\fldVideoPhone;
use syncgw\document\field\fldAssistantPhone;
use syncgw\document\field\fldUmCallerID;
use syncgw\document\field\fldBirthday;
use syncgw\document\field\fldURLHome;
use syncgw\document\field\fldURLWork;
use syncgw\document\field\fldURLBlog;
use syncgw\document\field\fldURLProfile;
use syncgw\document\field\fldURLOther;
use syncgw\document\field\fldBody;
use syncgw\document\field\fldMailHome;
use syncgw\document\field\fldMailWork;
use syncgw\document\field\fldMailOther;
use syncgw\document\field\fldAddressHome;
use syncgw\document\field\fldAddressBusiness;
use syncgw\document\field\fldAddressOther;
use syncgw\document\field\fldTitle;
use syncgw\document\field\fldDepartment;
use syncgw\document\field\fldGender;
use syncgw\document\field\fldMaiden;
use syncgw\document\field\fldAnniversary;
use syncgw\document\field\fldAssistant;
use syncgw\document\field\fldManagerName;
use syncgw\document\field\fldSpouse;
use syncgw\document\field\fldIMjabber;
use syncgw\document\field\fldIMicq;
use syncgw\document\field\fldIMmsn;
use syncgw\document\field\fldIMaim;
use syncgw\document\field\fldIMyahoo;
use syncgw\document\field\fldIMskype;
use syncgw\document\field\fldPhoto;
use syncgw\document\field\fldCategories;
use syncgw\document\field\fldGroupName;
use syncgw\document\field\fldAttribute;

class Contact {

	// module version number
	const VER   		 = 28;

    const MAP 			 = [
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
    // 	0 - String
    //	1 - Array
    //  2 - Address
    //  3 - Date array
    //  4 - Gender
    //	5 - Photo
    //  6 - Skip
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
    	'name'						=> [ 0, fldFullName::TAG, 		],
        'firstname'                 => [ 0, fldFirstName::TAG, 		],
        'surname'                   => [ 0, fldLastName::TAG, 		],
        'middlename'                => [ 0, fldMiddleName::TAG, 		],
        'nickname'                  => [ 0, fldNickName::TAG, 		],
	    'organization'              => [ 0, fldOrganization::TAG, 	],
	    'prefix'                    => [ 0, fldPrefix::TAG,			],
        'suffix'                    => [ 0, fldSuffix::TAG,			],
        'phone:home'				=> [ 1, fldHomePhone::TAG, 		],
	    'phone:home2'				=> [ 1, fldHomePhone2::TAG, 		],
    	'phone:work' 				=> [ 1, fldBusinessPhone::TAG, 	],
	    'phone:work2'          		=> [ 1, fldBusinessPhone2::TAG, 	],
	    'phone:mobile'             	=> [ 1, fldMobilePhone::TAG,	 	],
        'phone:main'				=> [ 1, fldCompanyPhone::TAG, 	],
    	'phone:homefax'             => [ 1, fldHomeFax::TAG, 			],
    	'phone:workfax'             => [ 1, fldBusinessFax::TAG, 		],
        'phone:car'            		=> [ 1, fldCarPhone::TAG, 		],
    	'phone:pager'               => [ 1, fldPager::TAG, 			],
		'phone:video'				=> [ 1, fldVideoPhone::TAG, 		],
	    'phone:assistant'          	=> [ 1, fldAssistantPhone::TAG, 	],
	    'phone:other'          		=> [ 1, fldUmCallerID::TAG, 		],
        'birthday'                  => [ 3, fldBirthday::TAG, 		],
	    'website:homepage'			=> [ 1, fldURLHome::TAG,	 		],
    	'website:work'				=> [ 1, fldURLWork::TAG,			],
        'website:blog'				=> [ 1, fldURLBlog::TAG,	 		],
    	'website:profile'			=> [ 1, fldURLProfile::TAG,		],
    	'website:other'				=> [ 1, fldURLOther::TAG, 		],
    	'notes'						=> [ 1, fldBody::TAG, 			],
    	'email:home'                => [ 1, fldMailHome::TAG, 		],
	    'email:work'                => [ 1, fldMailWork::TAG, 		],
	    'email:other'               => [ 1, fldMailOther::TAG, 		],
	    'address:home'				=> [ 2, fldAddressHome::TAG,	 	],
	    'address:work'				=> [ 2, fldAddressBusiness::TAG, 	],
	    'address:other'				=> [ 2, fldAddressOther::TAG, 	],
	    'jobtitle'                  => [ 1, fldTitle::TAG, 			],
    	'department'                => [ 1, fldDepartment::TAG, 		],
    	'gender'    				=> [ 4, fldGender::TAG, 			],
        'maidenname'				=> [ 1, fldMaiden::TAG, 			],
    	'anniversary'				=> [ 3, fldAnniversary::TAG,		],
	    'assistant'                 => [ 1, fldAssistant::TAG, 		],
	    'manager'                   => [ 1, fldManagerName::TAG,	 	],
	    'spouse'                    => [ 1, fldSpouse::TAG, 			],
	    'im:jabber'                 => [ 1, fldIMjabber::TAG, 		],
	    'im:icq'                    => [ 1, fldIMicq::TAG, 			],
	    'im:msn'                    => [ 1, fldIMmsn::TAG, 			],
        'im:aim'                    => [ 1, fldIMaim::TAG, 			],
        'im:yahoo'                  => [ 1, fldIMyahoo::TAG,	 		],
        'im:skype'                  => [ 1, fldIMskype::TAG,	 		],
    	'photo'                     => [ 5, fldPhoto::TAG, 			],
	//	'contact_id'														// Ignored
	//  'changed'															// Ignored
	// 	'del'																// Ignored
	//	'vcard'																// Ignored
	// 	'words'																// Ignored
	//	'user_id'															// Ignored
	// 	'contactgroup_id'													// Ignored
	// 	'created'															// Ignored

    // some fields only included for syncDS() - not part of data record

    	'#category'                 => [ 6, fldCategories::TAG, 		],
    	'#grp_name'					=> [ 6, fldGroupName::TAG,	 	],
		'#grp_attr'					=> [ 6, fldAttribute::TAG,		],

    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
    ];
    const ADDR 			 = [
    //	'PostOffice'				=> '',
    //	'ExtendedAddress'			=> '',
    	'Street'					=> 'street',
    	'City'						=> 'locality',
    	'Region'					=> 'region',
    	'PostalCode'				=> 'zipcode',
    	'Country'					=> 'country',
    ];

	const C_NEW 		= 1;							// new category
	const C_UPD 		= 2;							// update category
	const C_DEL 		= 3;							// delete category

	/**
	 * 	Record mapping table
	 * 	@var array
	 */
	private $_ids;

	/**
	 * 	Category mapping table
	 * 	@var array
	 */
	private $_cats;

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
     * 	@var Contact
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @param  - Pointer to handler class
	 *  @return - Class object
	 */
	public static function getInstance(Handler &$hd): Contact {

	   	if (!self::$_obj)
            self::$_obj = new self();

        self::$_obj->_hd = $hd;

		return self::$_obj;
	}

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Opt',sprintf(_('RoundCube %s handler'), Util::HID(Util::HID_ENAME, DataStore::CONTACT)));
		$xml->addVar('Ver', strval(self::VER));
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
		if (is_null($this->_ids))
			self::_loadRecs();

		$out = TRUE;
		$log = Log::getInstance();

		switch ($cmd) {
		case DataStore::GRPS:
			// build list of records
			$out = [];
			foreach ($this->_ids as $k => $v) {
				if (substr($k, 0, 1) == DataStore::TYP_GROUP) {
					// merge adress books?
					if (strpos($this->_pref, Handler::ABOOK_MERGE.';') !== FALSE &&
					   ($v[Handler::ATTR] & fldAttribute::DEFAULT)) {
						$out[$k] = substr($k, 0, 1);
						break;
					} else
						$out[$k] = substr($k, 0, 1);
				}
			}

			if (Debug::$Conf['Script']) //3
				Debug::Msg($out, 'All group records'); //3
			break;

		case DataStore::RIDS:

			// find base group?
			if ($parm == '') {
				foreach ($this->_ids as $rid => $val) {
					if (!$val[Handler::GROUP]) {
						$parm = $rid;
						break;
					}
				}
			}

			// check group
			if (!isset($this->_ids[$parm])) {
				$log->Msg(Log::WARN, 20317, $parm, _('adress book'));
				return FALSE;
			}

			// late load group?
			if (substr($parm, 0, 1) == DataStore::TYP_GROUP && !$this->_ids[$parm][Handler::LOAD])
				self::_loadRecs($parm);

			// load all groups?
			if (strpos($this->_pref, Handler::ABOOK_MERGE.';') !== FALSE) {
				foreach ($this->_ids as $rid => $val)
					if (!$val[Handler::GROUP])
						self::_loadRecs($rid);
			}

			// build list of records
			$out = [];
			foreach ($this->_ids as $k => $v) {
				if (!$v[Handler::GROUP] && strpos($this->_pref, Handler::ABOOK_MERGE.';') !== FALSE) {
					if (!($v[Handler::ATTR] & fldAttribute::DEFAULT))
						continue;
				}
				if (strpos($this->_pref, Handler::ABOOK_MERGE.';') !== FALSE || $v[Handler::GROUP] == $parm)
					$out[$k] = substr($k, 0, 1);
			}

			if (Debug::$Conf['Script']) //3
				Debug::Msg($out, 'All record ids in group "'.$parm.'"'); //3
			break;

		case DataStore::RGID:

			if (!is_string($parm) || !self::_chkLoad($parm) || !($out = self::_swap2int($parm))) {
				$log->Msg(Log::WARN, 20311, is_string($parm) ?
						  (substr($parm, 0, 1) == DataStore::TYP_DATA ? _('contact record') :
						  _('address book')) : gettype($parm), $parm);
				return FALSE;
			}
			break;

		case DataStore::ADD:

			// adding default group?
			if ($parm->getVar(fldAttribute::TAG) & fldAttribute::DEFAULT) {

				// not possible, so we fake success
				$out = FALSE;
				foreach ($this->_ids as $gid => $t)
					if ($t[Handler::ATTR] & fldAttribute::DEFAULT) {
						$out = $gid;
						break;
					}

				// update defalt group
				if ($out) {
					$parm->updVar('extID', $out);
					self::_upd($parm);
				}
				break;
			}

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
				$log->Msg(Log::WARN, 20317, $gid, _('address book'));
				return FALSE;
			}

			// add external record
			if (!($out = self::_add($parm)) || !self::_chkCat($out, $parm, self::C_NEW)) {
				$log->Msg(Log::WARN, 20312, $parm->getVar('Type') == DataStore::TYP_DATA ?
						  _('contact record') : _('address book'));
				return FALSE;
			}

			// special check for contacts without phone numbers
			if (strpos($this->_pref, Handler::ABOOK_SMALL.substr($this->_ids[$out][Handler::GROUP], 1).';') !== FALSE) {

				// create external record
				$rec = self::_swap2ext($parm);
				if (strpos(implode('|', array_keys($rec)), 'phone:') === FALSE) {
					// delete record
					self::_del($out);
					$log->Msg(Log::WARN, 20312, $parm->getVar('Type') == DataStore::TYP_DATA ?
							  _('contact record') : _('address book'));
					return FALSE;
				}
			}
			break;

		case DataStore::UPD:

			$rid = $parm->getVar('extID');

			// be sure to check record is loaded
			if (!self::_chkLoad($rid)) {
				$log->Msg(Log::WARN, 20313, substr($rid, 0, 1) == DataStore::TYP_DATA ?
						  _('contact record') : _('address book'), $rid);
				$cnf = Config::getInstance(); //3
				if ($cnf->getVar(Config::DBG_LEVEL) == Config::DBG_TRACE) //3
					Debug::Err('Update should work - please check if synchronization is turned on!'); //3
				return FALSE;
			}

			// get group ID
			$gid = substr($rid, 0, 1) == DataStore::TYP_GROUP ? $rid : $this->_ids[$rid][Handler::GROUP];

			// does record exist?
			if (!isset($this->_ids[$rid]) ||
				// is record editable?
			   	!($this->_ids[$rid][Handler::ATTR] & fldAttribute::EDIT) ||
				// is group writable?^
				!($this->_ids[$gid][Handler::ATTR] & fldAttribute::WRITE)) {
			   	$log->Msg(Log::WARN, 20315, $rid, _('address book'));
				return FALSE;
			}

	    	// special check for contacts without phone numbers
			if (strpos($this->_pref, Handler::ABOOK_SMALL.substr($this->_ids[$rid][Handler::GROUP], 1).';') !== FALSE) {

				// create external record
				$rec = self::_swap2ext($parm);

				if (strpos(implode('|', array_keys($rec)), 'phone:') === FALSE) {
					$log->Msg(Log::WARN, 20313, substr($rid, 0, 1) == DataStore::TYP_DATA ?
							  _('contact record') : _('address book'), $rid);
					return FALSE;
				}
			}

			// update external record
			if (!($out = self::_upd($parm)) || !self::_chkCat($rid, $parm, self::C_UPD)) {
				$log->Msg(Log::WARN, 20313, substr($rid, 0, 1) == DataStore::TYP_DATA ?
						  _('contact record') : _('address book'), $rid);
				return FALSE;
			}
			break;

    	case DataStore::DEL:

			// be sure to check record is loaded
			if (!self::_chkLoad($parm)) {
				$log->Msg(Log::WARN, 20314, substr($parm, 0, 1) == DataStore::TYP_DATA ?
						  _('contact record') : _('address book'), $parm);
				return FALSE;
			}

			// does record exist?
			if (!isset($this->_ids[$parm]) ||
				// is record a group and is it allowed to delete?
			    (substr($parm, 0, 1) == DataStore::TYP_GROUP && !($this->_ids[$parm][Handler::ATTR] & fldAttribute::DEL) ||
				// is record a data records and is it allowed to delete?
			   	(substr($parm, 0, 1) == DataStore::TYP_DATA && !($this->_ids[$this->_ids[$parm][Handler::GROUP]][Handler::ATTR] & fldAttribute::WRITE)))) {
				$log->Msg(Log::WARN, 20315, $parm, _('adresss book'));
				return FALSE;
			}

			// delete external record
			if (!self::_chkCat($parm, NULL, self::C_DEL) || !($out = self::_del($parm))) {
				$log->Msg(Log::WARN, 20314, substr($parm, 0, 1) == DataStore::TYP_DATA ?
						  _('contact record') : _('address book'), $parm);
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
	 * 	@return	- [ field name ]
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

   		// set new task to do
   		$this->_hd->RCube->plugins->init($this->_hd->RCube, 'addressbook');

        // re-load plugins
       	$this->_hd->RCube->plugins->load_plugins($this->_hd->RCube->config->get('plugins'));

		// re-create list
		if (!$grp) {
			$this->_ids  = [];
        	$this->_cats = [];

 	      	// get list of all address books
        	do {
        		$ad = $this->_hd->RCube->get_address_sources();
			} while ($this->_hd->chkRetry(DataStore::CONTACT, __LINE__));

			if (!$this->_hd->Retry) {
				$log = Log::getInstance();
       	        $log->Msg(Log::WARN, 20311, _('list of adress books'), $grp);
                return;
			}

			if (Debug::$Conf['Script'] == 'DBExt') //3
    	    	Debug::Msg($ad, 'Available address books'); //3

		} else
			$ad = [[
				'id' => substr($grp, 1),
			]];

		//  load language settings
        $lang = $this->_hd->RCube->user->get_prefs();
        if (isset($lang['language']))
        	$lang = $lang['language'];
        else
        	$lang = $this->_hd->RCube->config->get('language', 'en_US');
       	$lang = $this->_hd->RCube->read_localization_file(RCUBE_LOCALIZATION_DIR.$lang.DIRECTORY_SEPARATOR.'labels.inc');

		// process alll address books
    	foreach ($ad as $a) {

    		// root group?
    		if (!$grp) {
	    		if (Debug::$Conf['Script'] == 'DBExt') //3
		       		Debug::Msg($a, 'get_address_sources() output'); //3

	    		// included in synchronization?
	    		if (strpos($this->_pref, Handler::ABOOK_FULL.$a['id'].';') === FALSE &&
				    strpos($this->_pref, Handler::ABOOK_SMALL.$a['id'].';') === FALSE)
	    			continue;

	    		// translate message?
	    		if (!empty($lang)) {
	    			switch ($a['id']) {
	    			case \rcube_addressbook::TYPE_CONTACT:
						$a['name'] = $lang['personaladrbook'];
						break;

	    			case \rcube_addressbook::TYPE_RECIPIENT:
						$a['name'] = $lang['collectedrecipients'];
						break;

	    			case \rcube_addressbook::TYPE_TRUSTED_SENDER:
	    				$a['name'] = $lang['trustedsenders'];

	    			default:
	    				break;
	    			}
	    		}

	    		$this->_ids[$rid = DataStore::TYP_GROUP.$a['id']] = [
				     	Handler::GROUP	=> '',
	    			    Handler::NAME	=> $a['name'],
	           			Handler::ATTR	=> fldAttribute::READ | (!$a['id'] ? fldAttribute::DEFAULT : 0x00),
	           			Handler::LOAD	=> 0,
		      	];

	           	// read only?
	           	if (!$a['readonly'])
			     	$this->_ids[$rid][Handler::ATTR] |= fldAttribute::WRITE;

	           	// check for global address book
			    if ($a['id'] == 'global')
			     	$this->_ids[$rid][Handler::ATTR] |= fldAttribute::GAL;

	           	continue;
    		}

    		$this->_ids[$grp][Handler::LOAD] |= 1;

    		// get address book
    		do {
    			$ab = $this->_hd->RCube->get_address_book($a['id'], FALSE);
			} while ($this->_hd->chkRetry(DataStore::CONTACT, __LINE__));

			if (!$this->_hd->Retry) {
           		$log = Log::getInstance();
                $log->Msg(Log::WARN, 20311, _('adress book'), $grp);
                continue;
		    }

		    // max # of records to catch
	       	$ab->page_size = 999999999;
           	$gids = $ab->list_groups(NULL, 0);
	       	if (Debug::$Conf['Script'] == 'DBExt') //3
				Debug::Msg($gids, 'list_groups() from addressbook "'.$a['id'].'" output'); //3

    		$gids[] = [ 'contactgroup_id' => 0, 'del' => 0, 'name' => '' ];

	       	// walk trough available groups
			// program/lib/Roundcube/rcube_contacts.php
    		foreach ($gids as $c) {

	       		// soft deleted?
			    if ($c['del'])
    				continue;

	       		// set group ID
   				$ab->reset();
	       		$ab->set_group($c['contactgroup_id']);
			    $r = $ab->list_records(NULL, 0, TRUE);

    			// save category information
	       		$this->_cats[$cid = $a['id'].'#'.$c['contactgroup_id']] = [
			    			 Handler::GROUP => $grp,
    				         Handler::NAME  => $c['name'],
	       					 Handler::REFS  => $r->count,
	       		];

    			// load record information
	       		foreach ($r->records as $v) {

			    	if (Debug::$Conf['Script'] == 'DBExt') { //3
    					$x = $v; //3
	       				$x['photo'] = isset($x['photo']) ? Trace::BIN_DATA : ''; //3
			     		$x['vcard'] = '>>> DELETED <<<'; //3

				       	if (Debug::$Conf['Script'] == 'DBExt') //3
                        	Debug::Msg($x, 'list_record('.$c['contactgroup_id'].') output'); //3
    				} //3

    				// only records with phone numbers?
            		if (strpos($this->_pref, Handler::ABOOK_SMALL.$a['id'].';') !== FALSE &&
    			       (!isset($v['vcard']) || strpos($v['vcard'], 'TEL;') === FALSE))
	              		continue;

	              	// 'contact_id' == $ab->primary_key
    				$rid = DataStore::TYP_DATA.$v['contact_id'];

	       			// add record to category AND addressbook root
			     	if (isset($this->_ids[$rid]))
    					$this->_ids[$rid][Handler::CID] = $this->_ids[$rid][Handler::CID].';'.$cid;
    				else
			     		$this->_ids[$rid] = [
				    			Handler::GROUP => $grp,
					       		Handler::CID   => ';'.$cid,
			       				Handler::ATTR  => fldAttribute::READ|fldAttribute::WRITE|fldAttribute::EDIT|fldAttribute::DEL,
			     		];
	       		}
    		}
    	}

    	if (!count($this->_ids)) {
    		$log = Log::getInstance();
    		$log->Msg(Log::ERR, 20350, _('address book'), $this->_pref);
    	}

    	if (Debug::$Conf['Script'] == 'DBExt') { //3
        	Debug::Msg($this->_cats, 'Category mapping table ('.count($this->_cats).')'); //3
        	$ids = $this->_ids; //3
        	foreach ($this->_ids as $id => $v) //3
        		$ids[$id][Handler::ATTR] = fldAttribute::showAttr($ids[$id][Handler::ATTR]); //3
        	Debug::Msg($ids, 'Record mapping table ('.count($this->_ids).')'); //3
    	} //3
	}

	/**
	 * 	Check if record is loadeded
	 *
	 *  @param 	- Record id
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

		// if not data record it must be group
		if (substr($rid, 0, 1) == DataStore::TYP_GROUP) {

			$int = $db->mkDoc(DataStore::CONTACT, [
						'GID'  				=> '',
						'Typ'  				=> DataStore::TYP_GROUP,
						'extID'				=> $rid,
						'extGroup'			=> '',
						fldGroupName::TAG => $this->_ids[$rid][Handler::NAME],
						fldAttribute::TAG	=> $this->_ids[$rid][Handler::ATTR],
			]);

			if (Debug::$Conf['Script']) { //3
				$int->updVar(fldAttribute::TAG, fldAttribute::showAttr($this->_ids[$rid][Handler::ATTR])); //3
				$int->getVar('syncgw'); //3
	            Debug::Msg($int, 'Internal record'); //3
	            $int->updVar(fldAttribute::TAG, strval($this->_ids[$rid][Handler::ATTR])); //3
			} //3

			return $int;
		}

		// load external record
		if (!($rec = self::_get($rid)))
			return NULL;

		// create new data record
		$att = Attachment::getInstance();
		$int = $db->mkDoc(DataStore::CONTACT, [
					'GID' 		=> '',
					'extID' 	=> $rid,
					'extGroup'	=> $this->_ids[$rid][Handler::GROUP],
		]);

		// swap data
		$int->getVar('Data');
		foreach (self::MAP as $key => $tag) {

			// empty field?
			if (!isset($rec[$key]))
			    continue;

			if (!intval($val = $rec[$key]) && (is_string($val) && !strlen($val)))
				continue;

		    switch ($tag[0]) {
		    // 	0 - String
			case 0:
				$int->addVar($tag[1], $val);
				break;

			//	1 - Array
		    case 1:
		    	foreach ($val as $val)
		    		$int->addVar($tag[1], $val, FALSE, $tag[1] == fldBody::TAG ? [ 'X-TYP' => fldBody::TYP_TXT ] : []);
		    	break;

			//  2 - Address
		    case 2:
				foreach ($val as $val) {
					$ip = $int->savePos();
					$int->addVar($tag[1]);
					foreach (self::ADDR as $k => $v) {
						if ($val[$v])
							$int->addVar($k, $val[$v]);
					}
					$int->restorePos($ip);
				}
				break;

			//  3 - Date array
		    case 3:
				foreach ($val as $val)
					$int->addVar($tag[1], Util::unxTime($val, 'UTC'), FALSE, [ 'VALUE' => 'date' ]);
				break;

			//  4 - Gender
		    case 4:
				// male/female only
				foreach ($val as $val)
					$int->addVar($tag[1], substr($val, 0, 1) == 'm' ? 'M' : 'F');
				break;

			//  5 - Photo
			case 5:
				$int->addVar($tag[1], $att->create($val));

			default:
				break;
			}
		}

		// don't forget categories
   		foreach (explode(';', $this->_ids[$rid][Handler::CID]) as $g) {
         	if (isset($this->_cats[$g][Handler::NAME]) && $this->_cats[$g][Handler::NAME])
         		$int->addVar(fldCategories::TAG, $this->_cats[$g][Handler::NAME]);
   		}

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

		$att = Attachment::getInstance();

		// output record
		$rec = [
					'created'	=> $int->getVar('Created'),
					'changed'	=> $int->getVar('LastMod'),
		];

		// build record
		if ($rid = $int->getVar('extID')) {
			// set addressbook
			if (isset($this->_ids[$rid][Handler::GROUP]))
	            // 'contact_id' == $ab->primary_key
				$rec['contact_id'] = intval(substr($this->_ids[$rid][Handler::GROUP], 1));
		}

        // disable attachment size check for WebDAV
        $cnf  = Config::getInstance();
		$hack = $cnf->getVar(Config::HACK);
		$cnf->updVar(Config::HACK, $hack | Config::HACK_SIZE);

		$int->getVar('Data');

		// swap data
		foreach (self::MAP as $key => $tag) {

			$ip = $int->savePos();

		    switch ($tag[0]) {
			// 	0 - String
		    case 0:
				if ($val = $int->getVar($tag[1], FALSE))
					$rec[$key] = Encoding::cnvStr($val, FALSE);
				break;

			//	1 - Array
		    case 1:
				$int->xpath($tag[1], FALSE);
				$a = [];
				while (($val = $int->getItem()) !== NULL)
					$a[] = Encoding::cnvStr($val, FALSE);
				$rec[$key] = $a;
				break;

			//  2 - Address
		    case 2:
				$int->xpath($tag[1], FALSE);
				$n = 0;
				while ($int->getItem() !== NULL) {
					if (!isset($rec[$key]))
						$rec[$key] = [];
					foreach (self::ADDR as $k => $v) {
						$p = $int->savePos();
						$int->xpath($k, FALSE);
						while (($val = $int->getItem()) !== NULL)
	                		$rec[$key][$n][$v] = Encoding::cnvStr($val, FALSE);
						$int->restorePos($p);
					}
					$n++;
				}
				break;

			//  3 - Date
		    case 3:
				if ($val = $int->getVar($tag[1], FALSE))
					$rec[$key] = gmdate(Util::STD_DATE, intval($val));
				break;

			//  4 - Gender
		    case 4:
				if ($val = $int->getVar($tag[1], FALSE))
					$rec[$key] = $val == 'M' ? 'male' : 'female';
				break;

			//  5 - Photo
		    case 5:
		    	if ($val = $int->getVar($tag[1], FALSE)) {
		    		if ($int->getAttr('VALUE') == 'uri')
		    			$rec[$key] = $val;
		    		else
						$rec[$key] = $att->read($val);
		    	}

			default:
		    	break;
		    }

			$int->restorePos($ip);
		}

		// enable attachment size check
		$cnf->updVar(Config::HACK, $hack);

		if (Debug::$Conf['Script'] && Debug::$Conf['Script'] != 'Document') { //3
			$xr = $rec; //3
			$xr['photo'] = isset($xr['photo']) ? Trace::BIN_DATA : ''; //3
            Debug::Msg($xr, 'External record'); //3
		} //3

		return $rec;
	}

	/**
	 * 	Get data base handler
	 *
	 *  @param  - Record Id
	 * 	@return - Data base driver
	 */
	private function _getHandler(string $rid): rcube_contacts {

	   	// get address book handler
		if ($this->_ids[$rid][Handler::GROUP])
			do {
		        $ab = $this->_hd->RCube->get_address_book(substr($this->_ids[$rid][Handler::GROUP], 1));
			} while ($this->_hd->chkRetry(DataStore::CONTACT, __LINE__));
		else
			do {
				$ab = $this->_hd->RCube->get_address_book(0);
			} while ($this->_hd->chkRetry(DataStore::CONTACT, __LINE__));

		return $ab;
	}

	/**
	 *  Get external record
	 *
	 *  @param  - Record Id
	 *  @return - External record or NULL on error
	 */
	private function _get(string $rid): ?array {

	   	// get data base handler
		$dbh = self::_getHandler($rid);

		// get contact
		do {
			$rec = $dbh->get_record(intval(intval(substr($rid, 1))), TRUE);
		} while ($this->_hd->chkRetry(DataStore::CONTACT, __LINE__));

		return $rec;
	}

	/**
	 *  Add external record
	 *
	 *  @param  - XML record
	 *  @return - New record Id or NULL on error
	 */
	private function _add(XML &$int): ?string {

	       // we don't support adding of group records
	    if ($int->getVar('Type') == DataStore::TYP_GROUP)
	    	return NULL;

		// create external record
		$rec = self::_swap2ext($int);

		// get data base handler
		$dbh = self::_getHandler($gid = $int->getVar('extGroup'));

		// add record
		do {
			$rid = $dbh->insert($rec);
		} while ($this->_hd->chkRetry(DataStore::CONTACT, __LINE__));

		if (!$this->_hd->Retry)
			return NULL;

		// add record to internal managament
		$rid = DataStore::TYP_DATA.$rid;
  	    if ($a = $int->getVar(fldAttribute::TAG))
   	    	$a = intval($a);
    	else
   	    	$a = fldAttribute::READ|fldAttribute::WRITE|fldAttribute::EDIT|fldAttribute::DEL;
		$this->_ids[$rid] = [
				Handler::GROUP 	=> $gid,
				Handler::CID   	=> '',
			 	Handler::ATTR	=> $a,
		];

		// save record id
		$int->updVar('extID', $rid);

		$id = $this->_ids[$rid]; //3
        $id[Handler::ATTR] = fldAttribute::showAttr($id[Handler::ATTR]); //3
        if (Debug::$Conf['Script'] != 'Document') //3
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

		// get record id
		$rid = $int->getVar('extID');

	    // get data base handler
		$dbh = self::_getHandler($rid);

		do {
			$rc = $dbh->update(intval(substr($rid, 1)), self::_swap2ext($int));
		} while ($this->_hd->chkRetry(DataStore::CONTACT, __LINE__));

		return $rc;
	}

	/**
	 * 	Delete external record
	 *
	 * 	@param 	- Record id
	 * 	@return - TRUE=Ok, FALSE=Error
	 */
	private function _del(string $rid): bool {

	   	// we ignore deletion of address books
		if (substr($rid, 0, 1) == DataStore::TYP_GROUP) {

			// but we will delete content of group!
			foreach ($this->_ids as $id => $v)
				if ($v[Handler::GROUP] == $rid)
					self::Query(DataStore::CONTACT|DataStore::EXT, DataStore::DEL, $id);

		    return TRUE;
		}

	    // get data base handler
		$dbh = self::_getHandler($rid);

       	// is address book read-only?
		do {
			$dbh->delete([ intval(substr($rid, 1)) ]);
		} while ($this->_hd->chkRetry(DataStore::CONTACT, __LINE__));

		if (!$this->_hd->Retry)
			return FALSE;

       	// remove record from list
		unset($this->_ids[$rid]);

		return TRUE;
	}

	/**
	 * 	Check categories
	 * 	Warning: We cannot provide updates on folder!
	 *
	 *	@param 	- Record ID
	 *	@param 	- Document
	 *	@param	- self::C_NEW=New; self::C_UPD=Update; self::C_DEL=Delete
	 *	$return - TRUE=Ok; FALSE=Error
	 */
	private function _chkCat(string $rid, XML $xml = NULL, int $mod): bool {

    	if (Debug::$Conf['Script'] != 'Document') //3
			Debug::Msg($this->_cats, 'New category list'); //3
    	if (Debug::$Conf['Script'] != 'Document') //3
			Debug::Msg($this->_ids, 'New record list'); //3

		// contact group does not support categories
		if (substr($rid, 0, 1) == DataStore::TYP_GROUP)
			return TRUE;

		// get addressbook
		$dbh = self::_getHandler($rid);

		$iscat  = [];
		$delcat = [];

		// address book record id
        $aid = substr($this->_ids[$rid][Handler::GROUP], 1);

		// get all category from record
		if ($mod != self::C_DEL) {

			$xml->xpath('//Data/'.fldCategories::TAG);
			while (($v = $xml->getItem()) !== NULL)
			    $iscat[] = $v;

			if (Debug::$Conf['Script'] && count($iscat) && Debug::$Conf['Script'] != 'Document') //3
				Debug::Msg($iscat, 'Found categories for "'.$rid.'"'); //3
		}

		// check existing references
		if ($mod != self::C_NEW) {
			foreach (Util::unfoldStr($this->_ids[$rid][Handler::CID], ';') as $cid) {

				// root group?
				if ($cid == $aid.'#0' || !$cid)
					continue;

				// group not in use?
				if (($idx = array_search($this->_cats[$cid][Handler::NAME], $iscat)) === FALSE) {

					// adjust category reference
					if (!--$this->_cats[$cid][Handler::REFS])
						$delcat[] = $cid;
				} else
    				unset($iscat[$idx]);
			}
			if (Debug::$Conf['Script'] && count($delcat) && Debug::$Conf['Script'] != 'Document') //3
                Debug::Msg($delcat, 'Category references to delete for "'.$rid.'"'); //3
		}

		// check all assigned group names
		foreach ($iscat as $nam) {

			$cat = '';
			foreach ($this->_cats as $k => $v) {

				// is group available?
				if ($v[Handler::NAME] == $nam) {
					$cat = $k;
					break;
				}
			}

			// remove assignment?
			if ($mod == self::C_DEL && $cat) {
				if (!--$this->_cats[$cid][Handler::REFS])
					$delcat[] = $cat;
				continue;
			}

			// create new category?
			if (!$cat && $nam) {
				if (Debug::$Conf['Script'] && Debug::$Conf['Script'] != 'Document') //3
                    Debug::Msg('Adding new category "'.$nam.'" for "'.$rid.'"'); //3

                do {
                	$c = $dbh->create_group($nam);
				} while ($this->_hd->chkRetry(DataStore::CONTACT, __LINE__));

				if (!$this->_hd->Retry) {
				    $log = Log::getInstance();
					$log->Msg(Log::WARN, 20312, $nam, _('adress book'));
					return FALSE;
				}

				// save category information
				$this->_cats[$cat = $aid.'#'.$c['id']] = [
							Handler::NAME => $nam,
                           	Handler::REFS => 0,
			    ];
			}

			// assign group?
			if (Debug::$Conf['Script'] && Debug::$Conf['Script'] != 'Document') //3
                Debug::Msg('Creating reference to "'.$nam.'" for "'.$rid.'"'); //3
            $this->_ids[$rid][Handler::CID] .= ';'.$cat;

			// add new record to group
            do {
            	$dbh->add_to_group(substr($cat, strrpos($cat, '#') + 1), substr($rid, 1));
			} while ($this->_hd->chkRetry(DataStore::CONTACT, __LINE__));

			if (!$this->_hd->Retry)
				return FALSE;

			// adjust category reference
			$this->_cats[$cat][Handler::REFS]++;
		}

		// anything to delete?
		if (Debug::$Conf['Script'] && count($delcat) && Debug::$Conf['Script'] != 'Document') //3
			Debug::Msg($delcat, 'Removing category reference for "'.$rid.'"'); //3

		foreach ($delcat as $cat) {

		    // do not delete default category
		    if ($cat == $aid.'#0')
			    continue;

		    // remove from group
			do {
			   	$dbh->remove_from_group(substr($cat, strrpos($cat, '#') + 1), substr($rid, 1));
			} while ($this->_hd->chkRetry(DataStore::CONTACT, __LINE__));

			if (!$this->_hd->Retry)
				return FALSE;

			// no more refences?
			if (Debug::$Conf['Script'] && Debug::$Conf['Script'] != 'Document') //3
                Debug::Msg('Deleting obsolete category "'.$this->_cats[$cat][Handler::NAME].'"'); //3

            // delete group
            do {
                $dbh->delete_group(substr($cat, strrpos($cat, '#') + 1));
			} while ($this->_hd->chkRetry(DataStore::CONTACT, __LINE__));

			if (!$this->_hd->Retry) {
			    $log = Log::getInstance();
				$log->Msg(Log::WARN, 20314, $this->_cats[$cat][Handler::NAME], _('adress book'));
				return FALSE;
			}

			// remove reference
			unset($this->_cats[$cat]);
		}

		if (Debug::$Conf['Script'] == 'DBExt') { //3
            Debug::Msg($this->_cats, 'New category list'); //3
    		Debug::Msg($this->_ids, 'New record list'); //3
		} //3

		return TRUE;
	}

}

?>