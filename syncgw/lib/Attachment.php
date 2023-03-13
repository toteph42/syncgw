<?php
declare(strict_types=1);

/*
 * 	Attachment handling functions class
 *
 *	@package	sync*gw
 *	@subpackage	Core
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

/**
 * 	Variables used in attachment object:
 *
 *------------------------------------------------------------------------------------------------------------------
 *  <GUID/>				Global Unique Identified: user ID.Attachment::SEP.attachment name
 *  <Group/>			Record group: ''
 *  <Type/>				Record type: DataStore::TYP_GROUP
 *  <Data>
 *   <MIME>             MIME type of data
 *   <Encoding>			IMAP encoding type
 *   <Size>             Size of attachment
 *   <Record>           Attachment data
 *  </Data>
 *------------------------------------------------------------------------------------------------------------------
 *  <GUID/>				Global Unique Identified: Attachment GUID.Attachment::SEP.sub record counter
 *  <Group/>			Record group: Attachment record ID
 *  <Type/>				Record type: DataStore::TYP_DATA
 *  <Data>
 *   <MIME>             MIME type of data
 *   <Encoding>			IMAP encoding type
 *   <Size>             Size of attachment
 *   <Record>           Attachment data
 *  </Data>
 *
 **/

namespace syncgw\lib;

class Attachment extends XML {

	// module version number
	const VER 		= 9;

	const SEP  		=  '-';					// attachment name seperator
	const PREF 		= 'sgw'.self::SEP;		// name prefix
	const PLEN 		= 4;					// length of prefix
    const SIZE 		= 1000000;				// max. attachment chunk size for database (1 MB)

   	/**
     * Debug helper
     * @var string
     */
    public $_gid = NULL; //2

    /**
     * 	Singleton instance of object
     * 	@var Attachment
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): Attachment {

		if (!self::$_obj) {
            self::$_obj = new self();

	  		// set messages 11401-11400
	   		$log = Log::getInstance();
	   		$log->setMsg([
	   		        11401 => _('Error creating attachment record [%s]'),
	   		        11402 => 11401,
	   				11403 => 11401,
	   		        11404 => _('Error reading attachment record [%s]'),
	   		]);
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

		$xml->addVar('Name', _('Attachment handler'));
		$xml->addVar('Ver', strval(self::VER));

		if ($status) {
			$cnf = Config::getInstance();
			$xml->addVar('Opt', _('SabreDAV max. attachment size'));
			$xml->addVar('Stat', strval($cnf->getVar(Config::MAXOBJSIZE)));
		}
	}

	/**
	 * 	Get variable
	 *
	 * 	@param	- Name of variable
	 * 	@param 	- TRUE = Search whole document (default); FALSE = Search from current position
	 * 	@return	- Variable content or NULL = Not found
	 */
	public function getVar(string $name, bool $top = TRUE): ?string {

		$val = NULL;

        $cnf = Config::getInstance();
		$len = intval(parent::getVar('Size'));

        // check limited attachment size for DAV
      	if (($cnf->getVar(Config::HD)) == 'DAV' &&
      	    !($cnf->getVar(Config::HACK) & Config::HACK_SIZE) &&
            $len > $cnf->getVar(Config::MAXOBJSIZE)) {
      	    $fnam = Util::mkPath('source/TooBig.png'); //3
            Debug::Warn('Attachment data for ['.parent::getVar('GUID').'] replaced with "'.$fnam.'"'); //3
            $len  = 3466;
       	    $mime = 'image/png';
  	    } else
    		$mime = parent::getVar('MIME');

		switch ($name) {
	    case 'MIME':
	        $val = $mime;
    		Debug::Msg('['.$name.'] = "'.$val.'"'); //3
	        break;

	    case 'Size':
	        $val = strval($len);
    		Debug::Msg('['.$name.'] = "'.$val.'"'); //3
	        break;

	    default:
	        $val = parent::getVar($name, $top);
	        break;
		}

		return $val;
	}

	/**
	 *  Create attachment record
	 *
	 *  @param  - Binary data
	 *  @param  - Optional mime type
	 *  @param  - Optional imap encoding type
	 *  @return - Attachment name or NULL
	 */
	function create(string $bin, ?string $mime = NULL, int $enc = ENCBINARY): ?string {

	    $db  = DB::getInstance();
        $cnf = Config::getInstance();
        $max = $cnf->getVar(Config::DB_RSIZE);

        list($gid, $rc) = self::_load(TRUE, $bin, $mime, $enc);

        // does record already exist?
        if (!$rc)
        	return $gid;

        // create encoded string
        $data = base64_encode($bin);
        $len  = strlen($data);

        if ($len < $max) {
        	parent::updVar('Record', $data);
        	$data = '';
        } else
			parent::updVar('Record', substr($data, 0, $max));

  		if ($db->Query(DataStore::ATTACHMENT, DataStore::UPD, $this) === FALSE) {
			$log = Log::getInstance();
  			$log->Msg(Log::WARN, 11401, $gid);
  			return NULL;
  		}

  		// already saved?
  		if (!$data) {
	        Debug::Msg('Attachment GUID "'.$gid.'" MIME type "'.parent::getVar('MIME').'" with '.$len.' bytes written'); //3
  			return $gid;
  		}

        // create swap record
        $xml = new XML($this);
        $xml->updVar('Type', DataStore::TYP_DATA);
        $xml->updVar('Group', $gid);

        // save record
        for ($pos=$max, $cnt=0; $pos < $len; $cnt++, $pos+=$max) {
            $l = $len - $pos > $max ? $max : $len - $pos;
            $xml->updVar('GUID', $gid.self::SEP.$cnt);
            $xml->updVar('Record', substr($data, $pos, $l));
	   		if (($rc = $db->Query(DataStore::ATTACHMENT, DataStore::ADD, $xml)) === FALSE) {
	   			$log = Log::getInstance();
      			$log->Msg(Log::WARN, 11402, $gid.self::SEP.$cnt);
      			$gid = NULL;
      			break;
   			}
        }

	    Debug::Msg('Attachment GUID "'.$gid.'" MIME type "'.parent::getVar('MIME').'" with '.$len.' bytes written'); //3

        return $gid;
	}

	/**
	 * 	Read attachment data
	 *
	 *	@param  - Attachment record id
	 *  @return - Binary data or NULL = Error
	 */
	function read(string $gid): ?string {

        $db  = DB::getInstance();
        $cnf = Config::getInstance();
      	$max = $cnf->getVar(Config::DB_RSIZE);

  		// load attachment record
  		list($gid, $rc) = self::_load(FALSE, $gid);

  		// new record?
  		if ($rc)
  			return NULL;

  		$len = intval(parent::getVar('Size'));

    	// check limited attachment size for DAV
      	if (($cnf->getVar(Config::HD)) == 'DAV' &&
      	    !($cnf->getVar(Config::HACK) & Config::HACK_SIZE) &&
            $len > $cnf->getVar(Config::MAXOBJSIZE)) {
      	    $fnam = Util::mkPath('source/TooBig.png');
      	    $data = file_get_contents($fnam);
      	    Debug::Warn('Attachment data for ['.$gid.'] replaced with "'.$fnam.'"'); //3
		    return strval($data);
      	}

      	$data = parent::getVar('Record');

	    // do we need to load sub records?
      	if ($len > $max) {
		    $xml = new XML();
		    foreach ($db->Query(DataStore::ATTACHMENT, DataStore::RIDS, $gid) as $id => $unused) {
		        if (!($xml = $db->Query(DataStore::ATTACHMENT, DataStore::RGID, $id))) {
	        		$log = Log::getInstance();
		            $log->Msg(Log::WARN, 11404, $id);
	           	    return NULL;
		        }
		        $data .= $xml->getVar('Record');
		    }
			$unused; // disable Eclipse warning
      	}

  		Debug::Msg('Reading attachment record "'.$gid.'" '.$len.' bytes'); //3

		return base64_decode($data);
	}

	/**
	 * 	Load / create new attachment record
	 *
	 *  @param  - TRUE=Create reqord if required
	 *  @param  - Attachment record ID or binary data
	 *  @param  - Optional mime type or NULL
	 *  @param  - Optional imap encoding type
	 *  @return - [ Attachment record ID, TRUE = New; FALSE = Existing record ]
	 */
	private function _load(bool $mod, string $bin, ?string $mime = NULL, int $enc = ENCBINARY): array {

		$db = DB::getInstance();
		$rc = TRUE;

	    // check, if binary data is a valid GUID
		if (!$this->_gid) { //2
		    if (substr($bin, 0, self::PLEN) == self::PREF)
		    	$gid = $bin;
	    	else
            	$gid = self::PREF.Util::Hash($bin);
		} else { //2
			$gid = $this->_gid; //2
			$this->_gid = NULL; //2
		} //2

		// first try to load record
        if ($xml = $db->Query(DataStore::ATTACHMENT, DataStore::RGID, $gid)) {
            parent::loadXML($xml->saveXML());
            $rc = FALSE;
        } elseif ($mod) {

	        // compile MIME type?
    	    if (!$mime) {
	            $fnam = Util::getTmpFile();
    	        if (file_put_contents($fnam, $bin))
   	    		    $mime = mime_content_type($fnam);
	        }
	   	    parent::loadXML(
	      			'<syncgw>'.
	   			      '<GUID>'.$gid.'</GUID>'.
		   			  '<LUID/>'.
	   			  	  '<SyncStat>'.DataStore::STAT_OK.'</SyncStat>'.
	   			  	  '<Group/>'.
	   			  	  '<Type>'.DataStore::TYP_GROUP.'</Type>'.
		  			  '<LastMod>'.time().'</LastMod>'.
	   		  		  '<Created>'.time().'</Created>'.
	   	    		  '<CRC/>'.
	   	    		  '<extID/>'.
	   	    		  '<extGroup/>'.
	   	    		  '<Data>'.
			  		    '<MIME>'.$mime.'</MIME>'.
			  		    '<Encoding>'.strval($enc).'</Encoding>'.
	   	    			'<Size>'.strval(strlen($bin)).'</Size>'.
	 			        '<Record/>'.
	   				  '</Data>'.
		      		'</syncgw>');

	   		// save group record
  			if ($db->Query(DataStore::ATTACHMENT, DataStore::ADD, $this) === FALSE) {
	            $log = Log::getInstance();
  			    $log->Msg(Log::WARN, 11403, $gid);
  			}
        }

        return [ $gid, $rc ];
	}

}

?>