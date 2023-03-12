<?php
declare(strict_types=1);

/*
 *  Attendee name field handler
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\activesync\masHandler;
use syncgw\lib\Debug; //3
use syncgw\lib\Util;
use syncgw\lib\XML;

class fldAttendee extends fldHandler {

	// module version number
	const VER = 7;

	// ----------------------------------------------------------------------------------------------------------------------------------------------------------
	const TAG 			 = 'Attendee';

	// Optional parameter 	CUTYPE=INDIVIDUAL		An individual
	//					 	CUTYPE=GROUP			Group of individuals
	//					 	CUTYPE=RESOURCE		   	Physical resource
	//					 	CUTYPE=ROOM			   	Room resource
	//					 	CUTYPE=UNKNOWN			Otherwise not known
	// Optional parameter 	PARTSTAT=NEEDS-ACTION	Needs action
	//					 	PARTSTAT=ACCEPTED		Accepted
	//					 	PARTSTAT=DECLINED		Declined
	//					 	PARTSTAT=TENTATIVE		Tentative accepted
	//					 	PARTSTAT=DELEGATED		Delegated
	// Optional parameter 	ROLE=CHAIR				Indicates chair of the calendar entity
	//					 	ROLE=REQ-PARTICIPANT	Indicates a participant whose participation is required
	//					 	ROLE=OPT-PARTICIPANT	Indicates a participant whose participation is optional
	//					 	ROLE=NON-PARTICIPANT	Indicates a participant who is copied for information purposes only
	// Optional parameter 	PSTART=					Specifies the start time of a new time proposal
	// Optional parameter 	PEND=					Specifies the end time of a new time proposal

	/*
	 attendee   = "ATTENDEE" attparam ":" cal-address CRLF

     attparam   = *(

                ; the following are optional,
                ; but MUST NOT occur more than once

                (";" cutypeparam) / (";"memberparam) /
                (";" roleparam) / (";" partstatparam) /
                (";" rsvpparam) / (";" deltoparam) /
                (";" delfromparam) / (";" sentbyparam) /
                (";"cnparam) / (";" dirparam) /
                (";" languageparam) /

                ; the following is optional,
                ; and MAY occur more than once

                (";" xparam)

                )
	 */
	const RFCC_PARM			= [
		// description see fldHandler:check()
	    'uri'			  	=> [
		  'CUTYPE'		   	=> [ 1, ' INDIVIDUAL GROUP RESOURCE ROOM UNKNOWN X- ' ],
		  'MEMBER'		   	=> [ 0 ],
		  'ROLE'			=> [ 1, ' CHAIR REQ-PARTICIPANT OPT-PARTICIPANT NON-PARTICIPANT X- ' ],
		  'CALENDAR'		=> [ 1, ' NEEDS-ACTION ACCEPTED DECLINED TENTATIVE DELEGATED X- ' ],
		  'TASK'			=> [ 1, ' NEEDS-ACTION ACCEPTED DECLINED TENTATIVE DELEGATED COMPLETED IN-PROCESS X- ' ],
		  'RSVP'			=> [ 1, ' TRUE FALSE ' ],
		  'DELEGATED-TO'	=> [ 3 ],
		  'DELEGATED-FROM'  => [ 3 ],
		  'SENT-BY'		  	=> [ 3 ],
		  'CN'			   	=> [ 0 ],
		  'DIR'			  	=> [ 0 ],
		  'LANGUAGE'		=> [ 6 ],
		  'EMAIL'			=> [ 3 ],
		  '[ANY]'			=> [ 0 ],
		]
	];

	const ASC_PARTSTAT	 	= [
			'0'				=> 'UNKNOWN',		  	// Response unknown
			'2'				=> 'TENTATIVE',		 	// Tentative
			'3'				=> 'ACCEPTED',		  	// Accepted
			'4'				=> 'DECLINED',		  	// Decline
			'5'				=> 'NEEDS-ACTION',	  	// Not responded
	];

   /**
     * 	Singleton instance of object
     * 	@var fldAttendee
     */
    static private $_obj = NULL;

    /**
	 *  Get class instance handler
	 *
	 *  @return - Class object
	 */
	public static function getInstance(): fldAttendee {

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
				// inject mailto?
				if (strpos($rec['D'], '@') !== FALSE && substr(strtolower($rec['D']), 0, 6) != 'mailto')
					$rec['D'] =  'mailto:'.$rec['D'];
				$p = parse_url($rec['D']);
				if (!isset($p['scheme'])) {
					Debug::Msg('['.$rec['T'].'] ['.$rec['D'].'] not "uri" - dropping record'); //3
					continue;
				}
				if (isset($rec['P']['STATUS'])) {
					$rec['P']['PARTSTAT'] = $rec['P']['STATUS'];
					unset($rec['P']['STATUS']);
					Debug::Msg('['.$rec['T'].'][STATUS] moved to [PARTSTAT]'); //3
				}
				if (isset($rec['P']['PARTSTAT']) && $rec['P']['PARTSTAT'] == 'NEEDS ACTION') {
					$rec['P']['PARTSTAT'] = 'NEEDS-ACTION';
					Debug::Msg('['.$rec['T'].'] parameter "NEEDS ACTION" converted to "NEEDS-ACTION"'); //3
				}
				if (isset($rec['P']['RSVP']) && strpos('TRUE FALSE ', $rec['P']['RSVP']) === FALSE) {
					$rec['P']['RSVP'] = str_replace([ 'NO', 'YES' ], [ 'FALSE', 'TRUE' ], $rec['P']['RSVP']);
					Debug::Msg('['.$rec['T'].'] [RSVP] content "YES/NO" replaced WITH "TRUE/FALSE"'); //3
				}
				$parms = self::RFCC_PARM['uri'];
				$parms['PARTSTAT'] = strpos($rec['T'], 'VTODO') ? $parms['TASK'] : $parms['CALENDAR'];
				unset($parms['TASK']);
				unset($parms['CALENDAR']);
				// check parameter
				parent::check($rec, $parms);
				parent::delTag($int, $ipath);
				unset($rec['P']['VALUE']);
				$int->addVar(self::TAG, $rec['D'], FALSE, $rec['P']);
				$rc = TRUE;
	  		}
			break;

		case 'application/activesync.calendar+xml':
	   		if ($ext->xpath($xpath.'/Attendee', FALSE))
				parent::delTag($int, $ipath);

			while ($ext->getItem() !== NULL) {
				$p 		   = $ext->savePos();
				$a		   = [];
				$a['RSVP'] = $ext->getVar('ResponseRequested') ? 'TRUE' : 'FALSE';
				$ext->restorePos($p);

				$email = $ext->getVar('Email', FALSE);
			   	$ext->restorePos($p);

				if ($name = $ext->getVar('Name', FALSE))
					$a['CN'] = $name;
				$ext->restorePos($p);

  		   		if ($v = $ext->getVar('AttendeeStatus', FALSE))
					$a['PARTSTAT'] = self::ASC_PARTSTAT[$v];
			  	$ext->restorePos($p);

   				// 1 Required
				// 2 Optional
				// 3 Resource
   				if (($v = $ext->getVar('AttendeeType', FALSE)) == '3') {
   					unset($a['CN']);
	   				$int->addVar(fldResource::TAG, $name, FALSE, $a);
	   				$ext->restorePos($p);
	   				continue;
				}
			  	$ext->restorePos($p);

				switch ($v) {
	   			// 1 Required
   				case 1:
					$a['CUTYPE'] = 'INDIVIDUAL';
		  		   	$a['ROLE']   = 'REQ-PARTICIPANT';
			 		break;

		   		// 2 Optional
			 	default:
				case 2:
					$a['CUTYPE'] = 'INDIVIDUAL';
		   			$a['ROLE']   = 'OPT-PARTICIPANT';
				 	break;
				}

  		   		if ($v = $ext->getVar('ProposedStartTime', FALSE))
					$a['PSTART'] = Util::unxTime($v);
			  	$ext->restorePos($p);

  		   		if ($v = $ext->getVar('ProposedEndTime', FALSE))
					$a['PEND'] = Util::unxTime($v);
			  	$ext->restorePos($p);

			   	$int->addVar(self::TAG, 'mailto:'.$email, FALSE, $a);
  		   		$rc = TRUE;
			}
			break;

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
		$tags = explode('/', $xpath);
		$tag  = array_pop($tags);

		if (!$int->xpath($ipath.self::TAG, FALSE))
			return $rc;

		switch ($typ) {
		case 'text/calendar':
		case 'text/x-vcalendar':
			$recs = [];
			while (($val = $int->getItem()) !== NULL) {
				$a = $int->getAttr();
				if (isset($a['RSVP']))
					$a['RSVP'] = $a['RSVP'] == TRUE ? 'YES' : 'NO';
				if (isset($a['PARTSTAT']))
					$a['PARTSTAT'] = strtoupper($a['PARTSTAT']);
				if ($typ != 'text/calendar')
					$val = str_replace('mailto:', 'MAILTO:', $val);
				$recs[] = [ 'T' => $tag, 'P' => $a, 'D' => $val ];
			}
			if (count($recs))
				$rc = $recs;
			break;

		case 'application/activesync.calendar+xml':
			$mas = masHandler::getInstance();
			$ver = $mas->callParm('BinVer');
			if (isset($tags[0]) && $tags[0] == fldExceptions::TAG && $ver < 14.0)
				break;

			foreach ([ self::TAG, fldResource::TAG ] as $t) {
				$ip = $int->savePos();

				$int->xpath($ipath.$t, FALSE);
				while (($val = $int->getItem()) !== NULL) {

					// get attributes
					$a = $int->getAttr();

					// check for <Attedees>
					$p = $ext->savePos();
					if ($ext->getVar($tag) === NULL) {
						$ext->restorePos($p);
						$ext->addVar($tag, NULL, FALSE, $ext->setCP(XML::AS_CALENDAR));
					}

				  	$ext->addVar('Attendee');

					$name = $email = '';
			  		// strip off mailto:
				  	if ($t == self::TAG)
						$email = substr($val, 7);
				  	else
				  		$name = $val;

					// 0 Response unknown
					// 2 Tentative
					// 3 Accept
					// 4 Decline
					// 5 Not responded
					$pstat = '0';
   					// 1 Required
					// 2 Optional
					// 3 Resource
					$ptyp  = $t == self::TAG ? '2' : '3';

					// check attributes
					foreach ($a as $k => $v) {
						switch($k) {
						case 'CN':
							$name = $v;
							break;

						case 'ROLE':
							switch ($v) {
							case 'REQ-PARTICIPANT':
								$ptyp = '1';
								break;

							case 'OPT-PARTICIPANT':
								$ptyp = '2';

							default:
								break;
							}
							break;

						case 'PARTSTAT':
							if (($v = array_search($v, self::ASC_PARTSTAT)) != '')
								$pstat = strval($v);
							break;

						case 'EMAIL':
							$email = $v;
							break;

						case 'CN':
							$name = $v;
							break;

						case 'PSTART':
							$ext->addVar('ProposedStartTime', gmdate(Util::UTC_TIME, intval($v)),
										 FALSE, $ext->setCP(XML::AS_MRESPONSE));
							break;

						case 'PEND':
							$ext->addVar('ProposedEndTime', gmdate(Util::UTC_TIME, intval($v)),
										 FALSE, $ext->setCP(XML::AS_MRESPONSE));
							break;

						default:
		   					break;
   						}
					}

					if ($ver > 2.5) {
						$ext->addVar('AttendeeStatus', $pstat, FALSE, $ext->setCP(XML::AS_CALENDAR));
			  			$ext->addVar('AttendeeType', $ptyp);
					}

					if ($name)
						$ext->addVar('Name', $name);

					if ($email)
					   	$ext->addVar('Email', $email);

					$rc = TRUE;
					$ext->restorePos($p);
				}
				$int->restorePos($ip);
			}
			break;

		default:
			break;
		}

		return $rc;
	}

}

?>