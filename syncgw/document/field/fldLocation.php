<?php
declare(strict_types=1);

/*
 *  Location field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\activesync\masHandler;
use syncgw\lib\XML;

class fldLocation extends fldHandler {

	// module version number
	const VER = 7;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 				= 'Location';
	const SUB_TAG 			= 'DisplayName';

	const RFCC_PARM			= [
		// description see fldHandler:check()
	    'text'			 	=> [
		  'VALUE'			=> [ 1, 'text ' ],
		  'ALTREP'		   	=> [ 0 ],
		  'LANGUAGE'		=> [ 6 ],
		  '[ANY]'			=> [ 0 ],
		],
	];

    const ASC_SUB			= [
    	// <DisplayName> specifies the display name of an event's location
    	'DisplayName'												=> 'DisplayName',
    	// <Annotation> specifies a note about the location of an event
    	'Annotation'												=> 'fldComment',
    	// <City> specifies the city in which an event occurs
    	// <Country> specifies the country in which an event occurs
    	// <PostalCode> specifies the postal code for the address of the event's location
    	// <State> specifies the state or province in which an event occurs
    	// <Street> specifies the street address of the event's location
    	'Street,City,State,Country,PostalCode'						=> 'fldAddressOther',
		// <Accuracy> specifies the accuracy of the values of the <Latitude> element and the <Longitude> element
		// <Altitude> specifies the altitude of an event's location
    	// <AltitudeAccuracy> specifies the accuracy of the value of the <Altitude> element
    	// <Latitude> specifies the latitude of the event's location
    	// <Longitude> specifies the longitude of the event's location
		'Longitude,Latitude,Accuracy,Altitude,AltitudeAccuracy'		=> 'fldGeoPosition',
    	// <LocationUri> specifies the URI for the location of an event
    	'LocationUri'												=> 'fldURLOther',
    ];

   	/**
     * 	Singleton instance of object
     * 	@var fldLocation
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldLocation {
		if (!self::$_obj) {
            self::$_obj = new self();
			// clear tag deletion status
			unset(parent::$Deleted[self::TAG]);
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
		$xml->addVar('Opt', sprintf(_('&lt;%s&gt; field handler'), self::TAG));
		$xml->addVar('Ver', strval(self::VER));
	}

	/**
	 * 	Import field
	 *
	 *  @param  - MIME type
	 *  @param  - MIME version
	 *	@param  - External path
	 *  @param  - [[ 'T' => Tag; 'P' => [ Parm => Val ]; 'D' => Data ]] or external document
	 *  @param  - Internal path
	 * 	@param 	- Internal document
	 *  @return - TRUE = Ok; FALSE = Skipped
	 */
	public function import(string $typ, float $ver, string $xpath, $ext, string $ipath, XML &$int): bool {
		$rc    = FALSE;
		$ipath .= self::TAG;

		switch ($typ) {
		case 'text/calendar':
		case 'text/x-vcalendar':
			foreach ($ext as $rec) {
				if ($rec['T'] != $xpath)
					continue;
				// check parameter
				parent::check($rec, self::RFCC_PARM['text']);
				parent::delTag($int, $ipath);
				unset($rec['P']['VALUE']);
				$ip = $int->savePos();
				$int->addVar(self::TAG);
				$int->addVar(self::SUB_TAG, parent::rfc5545($rec['D']), FALSE, $rec['P']);
				$int->restorePos($ip);
				$rc = TRUE;
	  		}
			break;

		case 'application/activesync.mail+xml':
		case 'application/activesync.calendar+xml':
			if (!$ext->xpath($xpath, FALSE))
    	    	break;

    	    self::delTag($int, $ipath, $typ == 'application/activesync.calendar+xml' ? '2.5' : '');
    	    $ip = $int->savePos();

			$int->addVar(self::TAG);
			while (($val = $ext->getItem()) !== NULL) {

				// if locatoin value is available it must be < 16.0
				if (strlen($val)) {
					$int->addVar(self::SUB_TAG, $val);
					$rc = TRUE;
					break;
				}
				$xp = $ext->savePos();
				foreach (self::ASC_SUB as $key => $class) {
	    	    	if (substr($class, 0, 3) == 'fld') {
	    	    	    $class = 'syncgw\\document\\field\\'.$class;
	               		$field = $class::getInstance();
						if ($field->import($typ, $ver, (strpos($key, ',') === FALSE ? $key : ''), $ext, $ipath.'/', $int))
						    $rc = TRUE;
	    	    	} elseif ($val = $ext->getVar($key, FALSE)) {
  			        	$int->addVar($key, $val);
	   				    $rc = TRUE;
	    	    	}
	    	    	$ext->restorePos($xp);
	    	    }
    	   	}
			$int->restorePos($ip);
			if (!$rc)
			    $int->delVar(self::TAG, FALSE);

		default:
			break;
		}

		return $rc;
	}

	/**
	 * 	Export field
	 *
	 *  @param  - MIME type
	 *  @param  - MIME version
 	 *	@param  - Internal path
	 * 	@param 	- Internal document
	 *  @param  - External path
	 *  @param  - External document
	 *  @return - [[ 'T' => Tag; 'P' => [ Parm => Val ]; 'D' => Data ]] or FALSE=Not found
	 */
	public function export(string $typ, float $ver, string $ipath, XML &$int, string $xpath, ?XML $ext = NULL) {
		$rc   = FALSE;
		$cp	  = NULL;
		$tags = explode('/', $xpath);
		$tag  = array_pop($tags);

		if (!$int->xpath($ipath.self::TAG.'/'.self::SUB_TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'text/calendar':
		case 'text/x-vcalendar':
			$recs = [];
			while ($int->getItem() !== NULL) {
				$val = $int->getVar(self::SUB_TAG);
				$a = $int->getAttr();
				$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => parent::rfc5545($val, FALSE) ];
			}
			if (count($recs))
				$rc = $recs;
			break;

        case 'application/activesync.mail+xml':
			$cp = XML::AS_MAIL;

        case 'application/activesync.calendar+xml':
        	if (!$cp)
        		$cp = XML::AS_CALENDAR;

			$mas  = masHandler::getInstance();
			$ver  = $mas->callParm('BinVer');
			if ($ver < 16.0) {
				$ext->addVar($tag, $int->getItem(), FALSE, $ext->setCP($cp));
				$rc = TRUE;
				break;
			}

        	$xp = $ext->savePos();
        	$ext->addVar($tag, NULL, FALSE, $ext->setCP(XML::AS_BASE));

        	$int->xpath($ipath.self::TAG, FALSE);
        	while ($int->getItem() !== NULL) {
		        $ip = $int->savePos();
        		foreach (self::ASC_SUB as $key => $class) {
					if (substr($class, 0, 3) == 'fld') {
						$class = 'syncgw\\document\\field\\'.$class;
	               		$field = $class::getInstance();
	                    $field->export($typ, $ver, '', $int, $xpath, $ext);
					} elseif ($val = $int->getVar($key, FALSE))
						$ext->addVar($class, $val);
					$int->restorePos($ip);
	            }
	            $rc = TRUE;
       		}
       		$ext->restorePos($xp);
       		break;

		default:
			break;
		}

		return $rc;
	}

}

?>