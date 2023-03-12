<?php

/*
 *  External handler query test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\interfaces;

use syncgw\lib\Debug;
use syncgw\document\field\fldGroupName;
use syncgw\document\field\fldFullName;
use syncgw\document\field\fldSummary;
use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Util;
use syncgw\document\field\fldAttribute;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../../Functions.php');

Debug::$Conf['Script'] = 'DBExt';

Debug::$Conf['Exclude']['syncgw\lib\Log:Caller'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:hasChild'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getVal'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:addVar'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getVar'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getName'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:getItem'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:updVar'] 	= 1;
Debug::$Conf['Exclude']['syncgw\lib\Config:getVar'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\User'] 			= 1;
Debug::$Conf['Exclude']['syncgw\lib\Device'] 		= 1;

Debug::CleanDir('comp*.*');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
// we store variables, so we need to start session
session_start();

const xxREAD      = 0x01;
const xxDUPLICATE = 0x02;
const xxUPDATE    = 0x04;
const xxCOMP      = 0x08;
const xxDELETE    = 0x10;
const xxGRPS 	  = 0x20;
const xxRIDS	  = 0x40;

$tests		 	  = [
	1 	=> 	[ 'myapp',			DataStore::NOTE,		xxREAD,			[ fldGroupName::TAG, fldSummary::TAG ],		],
	2	=> 	[ 'myapp',			DataStore::NOTE,		xxDUPLICATE,	[ fldGroupName::TAG, fldSummary::TAG ],		],
	3	=> 	[ 'myapp',			DataStore::NOTE,		xxUPDATE,		[ fldGroupName::TAG, fldSummary::TAG ],		],
	4	=> 	[ 'myapp',			DataStore::NOTE,		xxCOMP,			[ fldGroupName::TAG, fldSummary::TAG ],		],
	5	=> 	[ 'myapp',			DataStore::NOTE,		xxDELETE,		[ fldGroupName::TAG, fldSummary::TAG ],		],
	6	=> 	[ 'myapp',			DataStore::NOTE,		xxGRPS,			[ ],											],
	7	=> 	[ 'myapp',			DataStore::NOTE,		xxRIDS,			[ ],											],

	101 => 	[ 'roundcube',		DataStore::CONTACT,		xxREAD,			[ fldGroupName::TAG, fldFullName::TAG ],	],
	102 => 	[ 'roundcube',		DataStore::CONTACT,		xxDUPLICATE,	[ fldGroupName::TAG, fldFullName::TAG ],	],
	103 => 	[ 'roundcube',		DataStore::CONTACT,		xxUPDATE,		[ fldGroupName::TAG, fldFullName::TAG ],	],
	104 => 	[ 'roundcube',		DataStore::CONTACT,		xxCOMP,			[ fldGroupName::TAG, fldFullName::TAG ],	],
	105 => 	[ 'roundcube',		DataStore::CONTACT,		xxDELETE,		[ fldGroupName::TAG, fldFullName::TAG ],	],
	106 => 	[ 'roundcube',		DataStore::CONTACT,		xxGRPS,			[ ],											],
	107 => 	[ 'roundcube',		DataStore::CONTACT,		xxRIDS,			[ ],											],

	121 => 	[ 'roundcube',		DataStore::CALENDAR,	xxREAD,			[ fldGroupName::TAG, fldSummary::TAG ],		],
	122 => 	[ 'roundcube',		DataStore::CALENDAR,	xxDUPLICATE,	[ fldGroupName::TAG, fldSummary::TAG ],		],
	123 => 	[ 'roundcube',		DataStore::CALENDAR,	xxUPDATE,		[ fldGroupName::TAG, fldSummary::TAG ],		],
	124 => 	[ 'roundcube',		DataStore::CALENDAR,	xxCOMP,			[ fldGroupName::TAG, fldSummary::TAG ],		],
	125 => 	[ 'roundcube',		DataStore::CALENDAR,	xxDELETE,		[ fldGroupName::TAG, fldSummary::TAG ],		],
	126 => 	[ 'roundcube',		DataStore::CALENDAR,	xxGRPS,			[ ],											],
	127 => 	[ 'roundcube',		DataStore::CALENDAR,	xxRIDS,			[ ],											],

	131 => 	[ 'roundcube',		DataStore::TASK,		xxREAD,			[ fldGroupName::TAG, fldSummary::TAG ],		],
	132 => 	[ 'roundcube',		DataStore::TASK,		xxDUPLICATE,	[ fldGroupName::TAG, fldSummary::TAG ],		],
	133 => 	[ 'roundcube',		DataStore::TASK,		xxUPDATE,		[ fldGroupName::TAG, fldSummary::TAG ],		],
	134 => 	[ 'roundcube',		DataStore::TASK,		xxCOMP,			[ fldGroupName::TAG, fldSummary::TAG ],		],
	135 => 	[ 'roundcube',		DataStore::TASK,		xxDELETE,		[ fldGroupName::TAG, fldSummary::TAG ],		],
	136 => 	[ 'roundcube',		DataStore::TASK,		xxGRPS,			[ ],											],
	137 => 	[ 'roundcube',		DataStore::TASK,		xxRIDS,			[ ],											],

	141 => 	[ 'roundcube',		DataStore::NOTE,		xxREAD,			[ fldGroupName::TAG, fldSummary::TAG ],		],
	142 => 	[ 'roundcube',		DataStore::NOTE,		xxDUPLICATE,	[ fldGroupName::TAG, fldSummary::TAG ],		],
	143 => 	[ 'roundcube',		DataStore::NOTE,		xxUPDATE,		[ fldGroupName::TAG, fldSummary::TAG ],		],
	144 => 	[ 'roundcube',		DataStore::NOTE,		xxCOMP,			[ fldGroupName::TAG, fldSummary::TAG ],		],
	145 => 	[ 'roundcube',		DataStore::NOTE,		xxDELETE,		[ fldGroupName::TAG, fldSummary::TAG ],		],
	146 => 	[ 'roundcube',		DataStore::NOTE,		xxGRPS,			[ ],											],
	147 => 	[ 'roundcube',		DataStore::NOTE,		xxRIDS,			[ ],											],

	151 => 	[ 'mail',			DataStore::MAIL,		xxREAD,			[ fldGroupName::TAG, fldSummary::TAG ],		],
	152 => 	[ 'mail',			DataStore::MAIL,		xxDUPLICATE,	[ fldGroupName::TAG, fldSummary::TAG ],		],
	153 => 	[ 'mail',			DataStore::MAIL,		xxUPDATE,		[ fldGroupName::TAG, fldSummary::TAG ],		],
	154 => 	[ 'mail',			DataStore::MAIL,		xxCOMP,			[ fldGroupName::TAG, fldSummary::TAG ],		],
	155 => 	[ 'mail',			DataStore::MAIL,		xxDELETE,		[ fldGroupName::TAG, fldSummary::TAG ],		],
	156 => 	[ 'mail',			DataStore::MAIL,		xxGRPS,			[ ],											],
	157 => 	[ 'mail',			DataStore::MAIL,		xxRIDS,			[ ],											],
];

if (!strlen($_SERVER['QUERY_STRING'])) {
	msg('+++ Missing parameter', Util::CSS_ERR);
	exit;
}
$t = explode('&', $_SERVER['QUERY_STRING']);

if (!isset($tests[$t[0]])) {
	msg('+++ Test "'.$t[0].'" not found', Util::CSS_ERR);
	exit;
}

$tst  = $tests[$t[0]];
$be   = $tst[0];
$hid  = $tst[1]|DataStore::EXT;
$mod  = $tst[2];
$flds = $tst[3];

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('Testing back end handler "'.$be.'"');
setDB($be, $hid);

$cnf = Config::getInstance();
$cnf->updVar(Config::ENABLED, $hid);
$cnf->updVar(Config::HD, 'MAS');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('Authorizing user "'.Debug::$Conf['ScriptUID'].'"');
$db   = DB::getInstance();
$uid  = Debug::$Conf['ScriptUID'];
$host = '';
if (strpos(Debug::$Conf['ScriptUID'], '@'))
	list($uid, $host) = explode('@', Debug::$Conf['ScriptUID']);
if (!$db->Authorize($uid, $host, Debug::$Conf['ScriptUPW'])) {
	msg('+++ Login failed!', Util::CSS_ERR);
   	exit;
}

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
// check data store handler
if (!Util::HID(Util::HID_CNAME, $hid == (DataStore::CONTACT|DataStore::CALENDAR) ? DataStore::CONTACT : $hid)) {
	msg('+++ Cannot get class for handler "'.sprintf('0x04x', $hid).'" - may data store is not activated', Util::CSS_ERR);
   	exit;
}

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
// perform action

switch ($mod) {
case xxDUPLICATE:
	foreach ($_SESSION['DBExt-RecOld'] as $rid => $typ) {
		msg('Duplicating record "'.$rid.'"');
		if (!$xml = $db->Query($hid, DataStore::RGID, $rid)) {
			msg('+++ Failed to read record "'.$rid.'"', Util::CSS_ERR);
			Debug::Msg($_SESSION['DBExt-RecOld'], 'Existing records');
			Debug::Msg($_SESSION['DBExt-RecNew'], 'New records');
			exit;
		}
		foreach ($flds as $fld) {
			if (!$xml->xpath('//Data/'.$fld))
				continue;

			// skip default groups
			if ($xml->getVar('Type') == DataStore::TYP_GROUP &&
				$xml->getVar('Attributes') & fldAttribute::DEFAULT)
				continue;

			$t = $xml->getItem();
			msg('Change <'.$fld.'>'.$t.'</'.$fld.'>');
			$t = 'DUP-'.$t;
			msg('To     <'.$fld.'>'.$t.'</'.$fld.'>');
		   	$xml->setVal($t);

			if (($id = $db->Query($hid, DataStore::ADD, $xml)) === FALSE)
				msg('+++ Failed to add record - this may be ok', Util::CSS_CODE);
			else {
				msg('New record id is "'.$id.'"');
				$_SESSION['DBExt-RecNew'][$rid] = $id;
			}
		}
	}
	$typ; // disable Eclipse warning
	break;

case xxUPDATE:
	foreach ($_SESSION['DBExt-RecNew'] as $id => $rid) {
		msg('Updating record "'.$rid.'"');
		if (!$xml = $db->Query($hid, DataStore::RGID, $rid)) {
			msg('+++ Failed to read record "'.$rid.'"', Util::CSS_ERR);
			Debug::Msg($_SESSION['DBExt-RecOld'], 'Existing records');
			Debug::Msg($_SESSION['DBExt-RecNew'], 'New records');
			exit;
		}
		foreach ($flds as $fld) {
			if (!$xml->xpath('//Data/'.$fld))
				continue;
			$t = $xml->getItem();
		    msg('Change <'.$fld.'>'.$t.'</'.$fld.'>');
			$t = str_replace('DUP-','UPD-', $t);
			msg('To     <'.$fld.'>'.$t.'</'.$fld.'>');
		   	$xml->setVal($t);

			if ($db->Query($hid, DataStore::UPD, $xml) === FALSE) {
				msg('+++ Failed to update record "'.$rid.'"', Util::CSS_ERR);
			Debug::Msg($_SESSION['DBExt-RecOld'], 'Existing records');
			Debug::Msg($_SESSION['DBExt-RecNew'], 'New records');
				exit;
			}
		}
    	// external record id may have changed!!!
   	 	$_SESSION['DBExt-RecNew'][$id] = $xml->getVar('extID');
	}
    break;

case xxCOMP:
	foreach ($_SESSION['DBExt-RecNew'] as $id => $rid) {
		msg('Compare record "'.$rid.'"');
		if (!($xml = $db->Query($hid, DataStore::RGID, $rid))) {
			msg('+++ Failed to read record "'.$rid.'"', Util::CSS_ERR);
			Debug::Msg($_SESSION['DBExt-RecOld'], 'Existing records');
			Debug::Msg($_SESSION['DBExt-RecNew'], 'New records');
			exit;
		}
		$xml->getVar('Data');
		comp($xml, FALSE, $_SESSION['DBExt-RecComp'][$id]);
	}
	msg('+++ End of script');
	if (isset($_SESSION['DBExt-RecOld']))
		Debug::Msg($_SESSION['DBExt-RecOld'], 'Existing records');
	if (isset($_SESSION['DBExt-RecNew']))
		Debug::Msg($_SESSION['DBExt-RecNew'], 'New records');
	if (isset($_SESSION['DBExt-RecComp']))
		Debug::Msg($_SESSION['DBExt-RecComp'], 'Compare records');
		exit;

case xxDELETE:
	if (isset($_SESSION['DBExt-RecNew'])) {
		foreach ($_SESSION['DBExt-RecNew'] as $id => $rid) {
			msg('Deleting record "'.$rid.'"');
			if (!$db->Query($hid, DataStore::DEL, $rid)) {
				foreach($_SESSION['DBExt-RecNew'] as $k => $v)
					if ($rid == $v) {
						unset($_SESSION['DBExt-RecNew'][$k]);
						break;
					}
				msg('+++ Failed to delete record', Util::CSS_ERR);
				Debug::Msg($_SESSION['DBExt-RecOld'], 'Existing records');
				Debug::Msg($_SESSION['DBExt-RecNew'], 'New records');
				exit;
			}
			unlink($_SESSION['DBExt-RecComp'][$id]);
		}
	}
	unset($_SESSION['DBExt-RecOld']);
	unset($_SESSION['DBExt-RecComp']);
	unset($_SESSION['DBExt-RecNew']);
	break;

case xxGRPS:
	Debug::Msg($db->Query($hid, DataStore::GRPS), 'Group ids in datastore');
	msg('+++ End of script');
	exit;

case xxRIDS:
	Debug::Msg($db->getRIDS($hid), 'All record ids in datastore');
	msg('+++ End of script');
	exit;

default:
	break;
}

msg('Reading all records');
$ids = '|';

$rids = $db->getRIDS($hid);

if ($mod & xxREAD) {
	$_SESSION['DBExt-RecOld'] = $rids;
	unset($_SESSION['DBExt-RecNew']);
	$first = TRUE;
} else
	$first = FALSE;

foreach ($rids as $id => $typ) {
	$xml = $db->Query($hid, DataStore::RGID, $id);
##	Debug::Save('Rec%d.xml', $xml);
	$ids .= $id.'|';
	if ($first) {
		$rid = $xml->getVar('extID');
		$xml->getVar('Data');
		$_SESSION['DBExt-RecComp'][$id] = Debug::Save('DBExtComp'.$rid.'.xml', $xml, FALSE);
	}
	if ($be == 'myapp') {
		$xml->getVar('syncgw');
		Debug::Msg($xml, 'Record');
	}
}
msg('Available records "'.$ids.'"');

msg('+++ End of script');
if (isset($_SESSION['DBExt-RecOld']))
	Debug::Msg($_SESSION['DBExt-RecOld'], 'Existing records');
if (isset($_SESSION['DBExt-RecNew']))
	Debug::Msg($_SESSION['DBExt-RecNew'], 'New records');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

?>