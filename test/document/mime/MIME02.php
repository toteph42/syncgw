<?php

/*
 *  MIME fld handler test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\mime;

use syncgw\activesync\MASHandler;
use syncgw\lib\Debug;
use syncgw\lib\Config;
use syncgw\lib\DataStore;
use syncgw\lib\Util;
use syncgw\lib\XML;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../../Functions.php');

Debug::$Conf['Script'] = 'MIME02';

Debug::$Conf['Exclude']['syncgw\lib\Config'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\Device'] = 1;

Debug::CleanDir('comp*.*');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$start = 1;
$end   = 0;
$odir  = '';
foreach (explode('&', $_SERVER['QUERY_STRING']) as $cmd) {
    list($c, $p) = explode('=', $cmd);
    switch (strtoupper($c)) {
    case 'S':
        $start = $p;
        $end   = $p + 1;
        break;

    case 'E':
        $end = $p;
        break;

    case 'T':
        switch ($p) {
        case '01':
            $hid   = DataStore::NOTE;
            $class = 'mimPlain';
            $idir  = 'plain';
            $mtyp  = 'text/plain';
            $mver  = 1.1;
            $opt   = NULL;
            break;

        case '02':
            $hid   = DataStore::NOTE;
            $class = 'mimvNote';
            $idir  = 'vnote';
            $mtyp  = 'text/x-vnote';
            $mver  = 1.1;
            $opt   = 'Note';
            break;

        case '04':
            $hid   = DataStore::NOTE;
            $class = 'mimAsNote';
            $idir  = 'asnote';
            $mtyp  = 'application/activesync.note+xml';
            $mver  = 1.0;
            $opt   = 'Note';
            break;

        case '10':
            $hid   = DataStore::CONTACT;
            $class = 'mimvCard';
            $idir  = 'vcard21';
            $opt   = 'Contact';
            $mtyp  = 'text/x-vcard';
            $mver  = 2.1;
            break;

        case '11':
            $hid   = DataStore::CONTACT;
            $class = 'mimvCard';
            $idir  = 'vcard30';
            $opt   = 'Contact';
            $mtyp  = 'text/vcard';
            $mver  = 3.0;
            break;

        case '12':
            $hid   = DataStore::CONTACT;
            $class = 'mimvCard';
            $idir  = 'vcard40';
            $opt   = 'Contact';
  	        $mtyp  = 'text/vcard';
            $mver  = 4.0;
            break;

        case '14':
            $hid   = DataStore::CONTACT;
            $class = 'mimAsContact';
            $idir  = 'ascontact';
            $mtyp  = 'application/activesync.contact+xml';
            $mver  = 1.0;
            $opt   = 'Contact';
            break;

        case '15':
            $hid   = DataStore::CONTACT;
            $class = 'mimAsGAL';
            $idir  = 'asgal';
            $mtyp  = 'application/activesync.gal+xml';
            $mver  = 1.0;
            $opt   = 'Contact';
            break;

        case '20':
            $hid   = DataStore::CALENDAR;
            $class = 'mimvCal';
            $idir  = 'vevent';
            $odir  = 'vcal10';
            $mtyp  = 'text/x-vcalendar';
            $mver  = 1.0;
            $opt   = 'Calendar';
            break;

        case '21':
            $hid   = DataStore::CALENDAR;
            $class = 'mimvCal';
            $idir  = 'vevent';
            $odir  = 'vcal20';
            $mtyp  = 'text/calendar';
            $mver  = 2.0;
            $opt   = 'Calendar';
            break;

        case '23':
            $hid   = DataStore::CALENDAR;
            $class = 'mimAsCalendar';
            $idir  = 'asCalendar';
            $mtyp  = 'application/activesync.calendar+xml';
            $mver  = 1.0;
            $opt   = 'Calendar';
            break;

        case '30':
            $hid   = DataStore::TASK;
            $class = 'mimvTask';
            $idir  = 'vtodo';
            $odir  = 'vcal10';
            $mtyp  = 'text/x-vcalendar';
            $mver  = '1.0';
            $opt   = 'Calendar';
            break;

        case '31':
            $hid   = DataStore::TASK;
            $class = 'mimvTask';
            $idir  = 'vtodo';
            $odir  = 'vcal20';
            $mtyp  = 'text/calendar';
            $mver  = 2.0;
            $opt   = 'Calendar';
            break;

        case '33':
            $hid   = DataStore::TASK;
            $class = 'mimAsTask';
            $idir  = 'astask';
            $mtyp  = 'application/activesync.task+xml';
            $mver  = 1.0;
            $opt   = 'Calendar';
            break;

        case '60':
            $hid   = DataStore::MAIL;
            $class = 'mimAsMail';
            $idir  = 'asmail';
            $mtyp  = 'application/activesync.mail+xml';
            $mver  = 1.0;
            $opt   = 'Mail';
            break;

        case '61':
            $hid   = DataStore::DOCLIB;
            $class = 'mimAsDocLib';
            $idir  = 'asDocLib';
            $mtyp  = 'application/activesync.doclib+xml';
            $mver  = 1.0;
            $opt   = 'Mail';
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

echo 'Call Parameter:<br /><br />';
echo 'T=  - MIME typ to test<br />';
echo 'S=  - Start test number (first to show) -> "'.$start.'"<br />';
echo 'E=  - End test number (last to show) -> "'.$end.'"<br />';
echo '<br />';

msg('Starting server');
setDB('file');

$idir = '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'mimedata'.DIRECTORY_SEPARATOR.$idir.DIRECTORY_SEPARATOR;
if (!$odir)
	$odir = $idir;
else
	$odir = $idir.$odir.DIRECTORY_SEPARATOR;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$mas = MASHandler::getInstance();
// load <Options>
if ($opt) {
	msg('Set <Body> options');
	$xml = new XML();
	$xml->loadXML('<Sync><Collection><Options><Class>'.$opt.'</Class>'.
			  '<BodyPreference><Type>1</Type><TruncationSize>51201</TruncationSize></BodyPreference>'.
			  '<BodyPreference><Type>2</Type><TruncationSize>51202</TruncationSize></BodyPreference>'.
			  '<BodyPreference><Type>3</Type><TruncationSize>51203</TruncationSize></BodyPreference>'.
			  '</Options></Collection></Sync>');
	$mas->loadOptions('Sync', $xml);
}

// we need to emulate setting of supported Active-Sync version
$mas->setCallParm('BinVer', floatval(MASHandler::MSVER));

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('Loading MIME type "'.$class.'"');
// be sure to enable MIME handler
$cnf = Config::getInstance();
$cnf->updVar(Config::ENABLED, $hid);
$mh = 'syncgw\\document\\mime\\'.$class;
$mh = $mh::getInstance();

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
if (!$start)
 	$start = 1;
if (!$end)
 	for ($end=$start; file_exists($idir.sprintf('in%02d.xml', $end)); $end++)
  		;
msg(($end-$start).' test files found in "'.$idir.'"');

// load request data
for ($cnt=$start; $cnt < $end; $cnt++) {

	$int = new XML();
	$int->loadXML('<!DOCTYPE xml><syncgw><GUID>1</GUID><LUID>2</LUID><SyncStat>'.DataStore::STAT_OK.'</SyncStat><Group>'.
				 ($hid != 4711 ? Util::HID(Util::HID_PREF, $hid).'00' : '').'</Group><Type>R</Type><Created>1388404736</Created>'.
	             '<LastMod>1388404740</LastMod><CRC/><extID/><extGroup/><Data/></syncgw>');

 	msg('Loading test file "'.$idir.sprintf('in%02d.xml', $cnt).'"');
 	$ext = new XML();
 	$ext->loadFile($idir.sprintf('in%02d.xml', $cnt));
    Debug::Msg($ext);

	if ($mh->import($mtyp, $mver, $ext, $int) === FALSE) {
	    msg('+++ Error importing document', Util::CSS_ERR);
	    exit;
    }
 	msg('Imported document');
 	$int->setTop();
 	Debug::Msg($int);
	comp($int, TRUE, $idir.sprintf('sgw%02d.xml', $cnt));

	msg('Creating output document from parsed document (no file load)');
 	$ext = new XML();
 	$ext->setDocType('xml', '', '');
	if ($mh->export($mtyp, $mver, $int, $ext) === FALSE) {
		msg('+++ No matching MIME handler found', Util::CSS_ERR);
		exit;
	}

	comp($ext, TRUE, $odir.sprintf('out%02d.xml', $cnt));
}

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>