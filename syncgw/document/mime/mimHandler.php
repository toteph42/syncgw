<?php
declare(strict_types=1);

/*
 * 	mim handler class
 *
 *	@package	sync*gw
 *	@subpackage	mim support
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\mime;

use syncgw\lib\Debug; //3
use syncgw\document\field\fldExceptions;
use syncgw\lib\DataStore;
use syncgw\lib\XML;

class mimHandler extends XML {

	// module version number
	const VER = 10;

	/**
	 *  Parents modul version
	 *  @var integer
	 */
	protected $_ver = 0;

	/**
	 *  Handler ID
	 *  @var int
	 */
	protected $_hid = 0;

	/**
	 *  mim types
	 *  @var array
	 */
	public $_mime = [];

	/**
	 *  Mapping table
	 */
	public $_map = [];

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Opt', _('MIME type base handler'));
		$xml->addVar('Ver', strval(self::VER));

		foreach ($this->_mime as $mime) {
			$xml->addVar('Opt', sprintf(_('MIME type handler "%s %s"'), $mime[0], $mime[1] ? sprintf('%.1F', $mime[1]) : ''));
			$xml->addVar('Ver', strval($this->_ver));
		}
	}

	/**
	 * 	Convert mim data to internal document
	 *
	 *	@param	- mim type
	 *  @param  - mim version
	 *  @param  - External document
	 * 	@param 	- Internal document
	 * 	@return	- TRUE = Ok; FALSE = We're not responsible
	 */
	public function import(string $typ, float $ver, XML &$ext, XML &$int): bool {

		// decode and swap data
		$mime  = mimRFC6868::getInstance();
	    $recs  = $mime->decode($ext->getVar('Data'));

        // remap path names
        $pref = '';
        $todo = FALSE;
        foreach ($recs as $k => $rec) {
            if ($rec['T'] == 'BEGIN') {
        		// check for task
        		if ($rec['D'] == 'VTODO')
        		    $todo = TRUE;
                $pref .= $rec['D'].'/';
            }
            // check for input mim version
            if ($rec['T'] == 'VERSION')
            	$ver = floatval($rec['D']);

            if ($rec['T'] == 'END') {
	       		$recs[$k]['T'] = $pref.$rec['T'];
        		$pref = explode('/', $pref);
        		array_pop($pref);
	       		array_pop($pref);
        		$pref = implode('/', $pref).'/';
       		} else
	       		$recs[$k]['T'] = $pref.$rec['T'];
        }

        // split up if we find more than one VEVENT (handling of reccurring events)
        if (strpos($typ, 'calendar') !== FALSE) {
        	$arecs = [];
        	$hd    = [];
        	$ft    = [];
        	$wrk   = [];
        	for ($i=$in=$cnt=0; isset($recs[$i]); $i++) {
        	    // second coparison is for vtodo
        		if ($recs[$i]['T'] == 'VCALENDAR/VEVENT/BEGIN' || ($todo && $recs[$i]['T'] == 'VCALENDAR/BEGIN'))
        			$in = 1;
        		if (!$in)
        			$hd[] = $recs[$i];
        		elseif ($in == 1)
        			$wrk[] = $recs[$i];
        		elseif ($in == 2)
        			$ft[] = $recs[$i];
        	    // second cmparison is for vtodo
        		if ($recs[$i]['T'] == 'VCALENDAR/VEVENT/END' || ($todo && $recs[$i]['T'] == 'VCALENDAR/END')) {
        			$in = 2;
        			$cnt++;
        			$arecs[] = array_merge($hd, $wrk, $ft);
        			$wrk = [];
        		}
        	}
        } else {
			$arecs = [ $recs ];
        	$cnt   = 1;
        }

        // swap data
	    $int->getVar('Data');
	    $rc = FALSE;
		foreach ($this->_map as $tag => $class)
		    if ($class->import($typ, $ver, $tag, $arecs[0], '', $int))
		    	$rc = TRUE;

		// any exceptions we need to handle?
		if ($cnt > 1) {
			$rc = TRUE;
			if ($int->getVar(fldExceptions::TAG) === NULL) {
				$int->getVar('Data');
  				$int->addVar(fldExceptions::TAG);
			}
			$p = $int->savePos();
			for ($n=1; $n < $cnt; $n++) {
				$xml = new XML();
				$xml->addVar('Data');
				foreach ($this->_map as $tag => $class)
				    $class->import($typ, $ver, $tag, $arecs[$n], '', $xml);
				$int->addVar(fldExceptions::SUB_TAG[0]);
				$xml->getChild('Data');
				while ($xml->getItem() !== NULL)
					$int->append($xml, FALSE);
			    $int->restorePos($p);
			}
		}

		return $rc;
	}

	/**
	 * 	Export to external document
	 *
	 *	@param	- Requested mim type
	 *  @param  - Requested mim version
	 * 	@param 	- Internal document
	 *  @param  - External document
	 * 	@return	- TRUE = Ok; FALSE = We're not responsible
	 */
	public function export(string $typ, float $ver, XML &$int, XML &$ext): bool {

		// delete only?
		if ($int->getVar('SyncStat') == DataStore::STAT_DEL)
			return TRUE;

		$int->getVar('Data'); //3
		Debug::Msg($int, 'Input document'); //3
		$int->getVar('syncgw');

		$mime = mimRFC6868::getInstance();

		// swap data
		$data = '';
		foreach ($this->_map as $path => $class) {

			if (Debug::$Conf['Script'] == 'MIME01' && ($class == 'fldCreated' || $class == 'fldLastMod')) //3
				continue; //3

            $ip = $int->savePos();

		    if (($recs = $class->export($typ, $ver, 'Data/', $int, $path)) !== FALSE) {
				foreach ($recs as $rec) {
					if (!$rec['T']) { //3
						Debug::Err($rec, 'Missing ['.$path.']'); //3
						break; //3
					} //3
		            if ($this->_hid & DataStore::CONTACT) {
                		// check special parameter
            			if ($ver != 4.0) {
            				// ensure string
            				$rec['D'] = strval($rec['D']);
    	         			if (($cs = mb_detect_encoding($rec['D'])) != 'ASCII' && $cs !== FALSE)
		            			$rec['P']['CHARSET'] = $cs;
    	               		if ($ver == 2.1) {
        	           			if (!isset($rec['P']['ENCODING'])) {
	          		          		for ($i=0, $m=strlen($rec['D']); $i < $m; $i++) {
		      	     		          	// "graphical" character
        			     	    	  	if (ord($rec['D'][$i]) > 127) {
	       			     	        		$rec['P']['ENCODING'] = 'QUOTED-PRINTABLE';
                                            break;
            			     			}
	             			     	}
    	           		     	}
              			      	foreach ($rec['P'] as $t => $v)
                		       	    $rec['P'][$t] = strval($v);
    	             		}
        		      	}
		            }
   			    	$data .= $mime->encode($rec);
 				}
		    }
		    $int->restorePos($ip);
		}

		// exception handling for calendar events
        if (strpos($typ, 'calendar') !== FALSE && $int->xpath('//'.fldExceptions::SUB_TAG[0])) {

        	while($int->getItem() !== NULL) {
        		$p = $int->savePos();
        		$wrk ='';
        		// EXDATE are already processed
        		if ($int->getVar(fldExceptions::SUB_TAG[1], FALSE) === NULL) {
	        		$int->restorePos($p);
        			$xml = new XML($int, FALSE);
        			$xml->getVar(fldExceptions::SUB_TAG[0]);
        			$xml->setName('Data');
			        $ip = $xml->savePos();
        			// swap data
    	   			foreach ($this->_map as $path => $class) {
					    $recs = $class->export($typ, $ver, '', $xml, $path);
					    $xml->restorePos($ip);
        			}
        		} else
	        		$int->restorePos($p);
        	}
        	$data = str_replace('END:VCALENDAR', $wrk.'END:VCALENDAR', $data);
       	}

		// add data
		$ext->addVar('Data', $data, TRUE);

		return TRUE;
	}

}
?>