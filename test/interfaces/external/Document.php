<?php

/*
 *  External database handler document test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\mime;

use syncgw\lib\Debug;
use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Device;
use syncgw\lib\User;
use syncgw\lib\Util;
use syncgw\lib\XML;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../../Functions.php');

Debug::$Conf['Script'] = 'Document';

Debug::$Conf['Exclude']['syncgw\lib\XML'] 					= 1;
Debug::$Conf['Exclude']['syncgw\lib\Config'] 				= 1;
Debug::$Conf['Exclude']['syncgw\lib\Device'] 				= 1;
Debug::$Conf['Exclude']['syncgw\lib\fldHandler:delTag'] 	= 1;

Debug::CleanDir('comp*.*');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
echo 'Call Parameter:<br /><br />';
echo 'T=  - Database handler to test<br />';
echo '<br />';

foreach (explode('&', $_SERVER['QUERY_STRING']) as $cmd) {
    list($c, $p) = explode('=', $cmd);
    switch (strtoupper($c)) {
    case 'T':
        switch ($p) {
        case '01':
        	process('myapp', DataStore::NOTE, 'plain', 'myapp-note/plain', 'DAV');
        	process('myapp', DataStore::NOTE, 'vnote', 'myapp-note/vnote', 'DAV');
        	process('myapp', DataStore::NOTE, 'asnote', 'myapp-note/asnote', 'MAS');
        	break;

        case '02':
        	process('roundcube', DataStore::CONTACT, 'vcard21', 'rc-contacts/vcard', 'DAV');
        	process('roundcube', DataStore::CONTACT, 'vcard30', 'rc-contacts/vcard', 'DAV');
        	process('roundcube', DataStore::CONTACT, 'vcard40', 'rc-contacts/vcard', 'DAV');
        	process('roundcube', DataStore::CONTACT, 'ascontact', 'rc-contacts/ascontact', 'MAS');
            break;

        case '03':
        	process('roundcube', DataStore::CALENDAR, 'vevent', 'rc-calendars/vevent', 'DAV');
   	    	process('roundcube', DataStore::CALENDAR, 'ascalendar', 'rc-calendars/ascalendar', 'MAS');
		    break;

        case '04':
        	process('roundcube', DataStore::TASK, 'vtodo', 'rc-tasks/vtodo', 'DAV');
        	process('roundcube', DataStore::TASK, 'astask', 'rc-tasks/astask', 'MAS');
		    break;

        case '05':
        	process('roundcube', DataStore::NOTE, 'plain', 'rc-note/plain', 'DAV');
        	process('roundcube', DataStore::NOTE, 'vnote', 'rc-note/vnote','DAV');
        	process('roundcube', DataStore::NOTE, 'asnote', 'rc-note/asnote','MAS');
    		break;

     	default:
          	msg('+++ Unknown parameter "'.$c.' = '.$p.'"');
          	exit;
        }
        break;

    default:
        msg('+++ Unknown parameter "'.$c.' = '.$p.'"');
        exit;
    }
}

function process($be, $hid, $idir, $odir, $devname) {

	$idir = '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'mimedata'.
			DIRECTORY_SEPARATOR.$idir.DIRECTORY_SEPARATOR;
	$odir = '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'appdata'.
			DIRECTORY_SEPARATOR.$odir.DIRECTORY_SEPARATOR;

	msg('Using back end handler "'.$be.'"');
	setDB($be, $hid);
	$cnf = Config::getInstance();
	$cnf->updVar(Config::ENABLED, $hid);
	$cnf->updVar(Config::HD, 'GUI');

	msg('Authorizing user "'.Debug::$Conf['ScriptUID'].'"');
	$cnf = Config::getInstance();
	$usr = User::getInstance();
	if (!$usr->Login(Debug::$Conf['ScriptUID'], Debug::$Conf['ScriptUPW'])) {
		msg('+++ Login failed!', Util::CSS_ERR);
	   	exit;
	}

	msg('Loading device "'.Debug::$Conf['ScriptUID'].'"');
	$dev = Device::getInstance();
	$dev->loadFile(Util::mkpath('source/dev_'.$devname.'.xml'));

	// be sure to enable document handler
	$cnf = Config::getInstance();
	$cnf->updVar(Config::ENABLED, $hid);

 	$start = 1;
 	for ($end=$start; file_exists($idir.sprintf('sgw%02d.xml', $end)); $end++)
  		;
	msg(($end-$start).' test files found in "'.$idir.'"');

	$db  = DB::getInstance();
	$int = new XML();

	// load request data
	for ($cnt=$start; $cnt < $end; $cnt++) {

		msg('Loading test file "'.($file = $idir.sprintf('in%02d.xml', $cnt)).'"');
		$int->loadFile($file);
		Debug::Msg($int, 'Input file');

		$dh = 'syncgw\\document\\Doc'.Util::HID(Util::HID_TAB, $hid, TRUE);
		$dh = $dh::getInstance();
		if (!$dh->import($int, DataStore::ADD)) {
			msg('+++ Error writing record', Util::CSS_WARN);
	        exit;
	 	}

	 	$xid = $dh->getVar('extID');
	 	msg('Reading new external record "'.$xid.'"');
	 	if(!($ext = $db->Query(DataStore::EXT|$hid, DataStore::RGID, $xid))) {
	        msg('+++ Error reading record "'.$xid.'"', Util::CSS_WARN);
	        exit;
	 	}
	 	Debug::Msg($ext, 'External record');

	 	msg('Deleting new external record "'.$xid.'"');
	 	if(!$db->Query(DataStore::EXT|$hid, DataStore::DEL, $xid)) {
	        msg('+++ Error deleting record "'.$xid.'"', Util::CSS_WARN);
	        exit;
	 	}

	 	$xml = new XML();
		$xml->loadXML('<!DOCTYPE xml><syncgw><GUID>1</GUID><LUID>2</LUID><SyncStat>'.DataStore::STAT_OK.'</SyncStat><Group>'.
				 ($hid != 4711 ? Util::HID(Util::HID_PREF, $hid).'00' : '').'</Group><Type>R</Type><Created>1388404736</Created>'.
	             '<LastMod>1388404740</LastMod><CRC/><extID/><extGroup/><Data/></syncgw>');
	 	$xml->getVar('Data');
	 	$ext->getChild('Data');
	 	while ($ext->getItem() !== NULL)
		 	$xml->append($ext, FALSE);
	 	$tag = '<UID>';
		comp($xml, TRUE, $odir.sprintf('sgw%02d.xml', $cnt), htmlentities('--- Differences in '.$tag.' were ok').'<br/><br/>');
	}
}

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>