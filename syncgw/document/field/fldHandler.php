<?php
declare(strict_types=1);

/*
 * 	fld handler class
 *
 *	@package	sync*gw
 *	@subpackage	Tag handling
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace syncgw\document\field;

use syncgw\lib\Debug; //3
use syncgw\lib\Device;
use syncgw\lib\Server;
use syncgw\lib\XML;
use syncgw\activesync\masHandler;

class fldHandler {

	// module version number
	const VER = 6;

	static protected $Deleted = [];

    /**
	 * 	Collect information about class
	 *
	 * 	@param 	- Object to store information
     *	@param 	- TRUE = Provide status information only (if available)
	 */
	public function getInfo(XML &$xml, bool $status): void {

		$xml->addVar('Name', _('fld handler'));
		$xml->addVar('Ver', strval(self::VER));

		$srv = Server::getInstance();
		$srv->getSupInfo($xml, $status, 'document\\field');
	}

	/**
	 * 	Delete existing tag from record
	 *
	 * 	@param 	- Internal document
	 *  @param  - Path to delete
	 *  @param  - Check ActiveSync Ghosted parameter = Version
	 *  @param  - Force deletion
	 */
	protected function delTag(XML &$int, string $path, string $minver = '', bool $force = FALSE): void {

		if ((!$force && isset(self::$Deleted[$path])) || $int->getName() == $path)
			return;

		self::$Deleted[$path] = TRUE;
		$ip = $int->savePos();

		if ($minver) {
			$mas	= masHandler::getInstance();
			$actver = $mas->callParm('BinVer');

			// In protocol version 16.0 and 16.1, Calendar class elements are ghosted by default and
			// CLIENTS SHOULD NOT send unchanged elements in Sync command requests.
			// Ghosted elements are not sent to the server. Instead of deleting these excluded properties,
			// the server preserves their previous value.
			if ($actver >= 16.0) {
				$int->xpath('/syncgw/Data/'.$path);
				while ($int->getItem() !== NULL)
					$int->delVar(NULL, FALSE);
				$int->restorePos($ip);
				return;
			}

			// load supported ghosting tags on client
			$dev = Device::getInstance();
			// is tag ghosted?
			if (strpos($dev->getVar('ClientManaged'), $path.';') === FALSE || $actver > $minver) {
				$int->xpath('/syncgw/Data/'.$path);
				while ($int->getItem() !== NULL)
					$int->delVar(NULL, FALSE);
				$int->restorePos($ip);
				return;
			}
			$int->getVar('Data');
		} else {
			$int->xpath('/syncgw/Data/'.$path);
			while ($int->getItem() !== NULL)
				$int->delVar(NULL, FALSE);
			$int->restorePos($ip);
		}
	}

	/**
	 * 	Clean parameter
	 *
	 * 	@param 	- Record to check [ 'T' => Tag; 'P' => [ Parm => Val ]; 'D' => Data ]
	 *  @param  - [ [ typ, values ] ]
	 *             0 -Nothing to check
	 *             1 - Check constant
	 *             2 - Check range value
	 *             3 - Check e-mail
	 *             4 - Check text
	 *             5 - Check uri
	 *             6 - Check language
	 *             7 - Check mediatype
	 *             8 - Check format type
	 */
	protected function check(array &$rec, array $parms): void {

		foreach ($rec['P'] as $tag => $val) {

			if (Debug::$Conf['Script'] == 'MIME01' || Debug::$Conf['Script'] == 'MIME02') //3
				Debug::Msg('['.$rec['T'].'] ['.$tag.'] checking "'.$val.'"'); //3

			// check [ANY] parameter
			if (substr($tag, 0, 2) == 'X-' && isset($parms['[ANY]'])) {
				if (Debug::$Conf['Script'] == 'MIME01' || Debug::$Conf['Script'] == 'MIME02') //3
					Debug::Msg('['.$rec['T'].'] ['.$tag.'] allowed'); //3
				continue;
			}

			// is paramneter supported?
			if (!isset($parms[$tag])) {
				Debug::Msg('['.$rec['T'].'] ['.$tag.'] unknown - dropping parm '); //3
				unset($rec['P'][$tag]);
				continue;
			}

			// now we check in detail
			switch ($parms[$tag][0]) {
			// 0	 Nothing to check
	        // 6     @todo Check language (LANGUAGE)
	        // 7     @todo Check mediatype (MEDIATYPE)
	        // 8     @todo Check format type (FMTTYPE)
			case 0:
			case 6:
			case 7:
			case 8:
				if (Debug::$Conf['Script'] == 'MIME01' || Debug::$Conf['Script'] == 'MIME02') //3
					Debug::Msg('['.$rec['T'].'] ['.$tag.'] any value allowed'); //3
				break;

			// 1	 Check constant
			case 1:
				if (Debug::$Conf['Script'] == 'MIME01' || Debug::$Conf['Script'] == 'MIME02') //3
					Debug::Msg('['.$rec['T'].'] ['.$tag.'] found - checking constant "'.$parms[$tag][1].'"'); //3
	   			// [ANY] contant allowed?
	   			$xtag = stripos($parms[$tag][1], 'x-');
	   			$out  = '';
				// walk through constants
				foreach (explode(',', $val) as $t) {
					// normalize parameter
					$c = strtoupper($t);
   					if (substr($c, 0, 2) == 'X-' && $xtag)
   						$out .= $t.',';
					elseif (stripos($parms[$tag][1], $c.' ') !== FALSE)
		   				$out .= $t.',';
				}
   				if (!$out) {
   					if ($val) {
   						Debug::Msg('['.$rec['T'].'] ['.$tag.'] no constants matches "'.$val.'" - dropping parm'); //3
						unset($rec['P'][$tag]);
   					}
   				} else {
					if ($val != substr($out, 0, -1)) //3
						Debug::Msg('['.$rec['T'].'] ['.$tag.'] value changed from "'.$val.'" to "'.substr($out, 0, -1).'"'); //3
					$rec['P'][$tag] = strtolower(substr($out, 0, -1));
				}
   				break;

			// 2	 Check range value
			case 2:
				if (Debug::$Conf['Script'] == 'MIME01' || Debug::$Conf['Script'] == 'MIME02') //3
					Debug::Msg('['.$rec['T'].'] ['.$tag.'] - "'.$val.'" range check "'.$parms[$tag][1].'"'); //3
				list($l, $h) = explode('-', $parms[$tag][1]);
				if ($val < $l || $val > $h) {
					Debug::Msg('['.$rec['T'].'] ['.$tag.'] "'.$val.'" out of range - dropping parm'); //3
					unset($rec['P'][$tag]);
				}
				break;

			// 3	 Check e-mail
			case 3:
				if (Debug::$Conf['Script'] == 'MIME01' || Debug::$Conf['Script'] == 'MIME02') //3
					Debug::Msg('['.$rec['T'].'] ['.$tag.'] - e-mail check'); //3
				if (!preg_match('|[^0-9<][A-z0-9_]+([.][A-z0-9_]+)*[@][A-z0-9_]+([.][A-z0-9_]+)*[.][A-z]{2,4}|i', $val)) {
					Debug::Msg('['.$rec['T'].'] ['.$tag.'] "'.$val.'" invalid e-mail - dropping parm'); //3
					unset($rec['P'][$tag]);
				}
				break;

    		// 4     Convert text
			case 4:
			    $rec['P'][$tag] = str_replace([ "\;", "\,", "\\\\" ], [ ";", ",", "\\" ], $rec['P'][$tag]);
			    break;

	        // 5     Check uri
			case 5:
			    $p = parse_url($rec['P'][$tag]);
			    if (!isset($p['scheme'])) {
					Debug::Msg('['.$rec['T'].'] ['.$tag.'] "'.$val.'" invalid URI - dropping parm'); //3
					unset($rec['P'][$tag]);
				}
				break;

			default:
				Debug::Err('['.$rec['T'].'] ['.$tag.'] - parm not found'); //3
				break;
			}
		}
	}

	/**
	 *  Sanitize value
	 *
	 *  @param  - Value
	 *  @param  - TRUE=In; FALSE=Out
	 *  @return - Sanitized value
	 */
	protected function rfc6350(string $val, bool $mod = TRUE): string {
		if ($mod)
		    return str_replace([ "\;", "\,", "\\\\", '\n' ], [ ";", ",", "\\", "\n" ], $val);
		else
		    return str_replace([ "\\", ";", ",", "\n" ], [ "\\\\", "\;", "\,", '\n' ], $val);
	}

	/**
	 *  Sanitize value
	 *
	 *  @param  - Value
	 *  @param  - TRUE=In; FALSE=Out
	 *  @return - Sanitized value
	 */
	protected function rfc5545(string $val, bool $mod = TRUE): string {
		if ($mod)
		    return str_replace([ "\;", "\,", "\\\\", '\n', '\N' ], [ ";", ",", "\\", "\n", "\n" ], $val);
		else
		    return str_replace([ "\\", ";", ",", "\n" ], [ "\\\\", "\;", "\,", '\n' ], $val);
	}

	/**
	 *  Match parameter
	 *
	 * 	@param 	- Record to check [ 'T' => Tag; 'P' => [ Parm => Val ]; 'D' => Data ]
	 *  @param  - Parameter to check Tag => [ Match, [ Parm => Val ] ]
	 *  @return - Internal tag name or NULL
	 */
	protected function match(array &$rec, array $parms): ?string {

		foreach ($parms as $tag => $parm) {
			$match = 0;
			foreach ($parm[1] as $t => $v) {
				if (!isset($rec['P'][$t]))
					continue;
				$rec['P'][$t] = strtolower($rec['P'][$t]);
				foreach (explode(',', $v) as $v) {
					if (stripos($rec['P'][$t], $v) !== FALSE)
						$match++;
					if (substr($v, 0, 2) == 'x-' && stripos($rec['P'][$t], substr($v, 2)) !== FALSE)
						$match++;
				}
			}
			if ($match >= $parm[0]) {
				if (isset($rec['P'][$t]) && $rec['P'][$t]) {
					$p = array_flip(explode(',', $rec['P'][$t]));
					// remove matched tag from parameter
					foreach ($parm[1] as $t => $v) {
						foreach (explode(',', $v) as $v1) {
							unset($p[$v1]);
							unset($p['x-'.$v1]);
							if (substr($v1, 0, 2) == 'x-')
								unset($p[substr($v1, 2)]);
						}
					}
					if (!count($p))
						unset($rec['P'][$t]);
					else
						$rec['P'][$t] = implode(',', array_flip($p));
				}
				return $tag;
			}
		}

		return NULL;
	}


}

?>