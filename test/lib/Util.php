<?php

/*
 *  Utility class test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\DataStore;
use syncgw\lib\Trace;
use syncgw\lib\Util;

// disable call to Encoding->setLang()

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'Util';

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('unfoldStr()');
$s = '1;2;3;4;5;6;';
Debug::Msg($s, 'Original');
Debug::Msg($a = Util::unfoldStr($s, ';'), 'Unfold');

Msg('foldStr()');
Debug::Msg(Util::foldStr($a, ';'), 'Fold');

msg('unfoldStr()');
Debug::Msg($a = Util::unfoldStr($s, ';', 10), 'Unfold');

Msg('foldStr()');
Debug::Msg(Util::foldStr($a, ';'), 'Fold');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

msg('HID()');
Debug::Msg(Util::HID(Util::HID_TAB, DataStore::ALL, TRUE));

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

msg('mkPath(): "'.Util::mkPath('source').'"');
msg('getTmpFile(): "'.Util::getTmpFile().'"');
$s = '\'k39d44löä1##+.bin';
msg('normFileName(): "'.$s.'": "'.Util::normFileName($s).'"');

msg('getFileExt()');
foreach ([ 'application/gpx+xml', 'application/inkml+xml' ] as $mime)
    msg('File extenson for "'.$mime.'" is "'.Util::getFileExt($mime).'"');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

msg('unxTime()');
$s = 'Thu Dec 20 08:15:39 2018 CET';
Debug::Msg($s.' = "'.(Util::unxTime($s)).'"');

msg('utcTime()');
Debug::Msg('UTC = "'.Util::utcTime($s).'"');

msg('cnvDuration()');
Debug::Msg('12938 Sek: "'.Util::cnvDuration(FALSE, 12938).'"');

msg('getTZName()');
Debug::Msg('Default time zone = "'.date_default_timezone_get().'"');
Debug::Msg('UTC time zone offset = "'.($off = date('Z')).'"');
Debug::Msg('Time zone name based on offset= "'.Util::getTZName($off, $off*2).'"');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

msg('Sleep()');
Util::Sleep();

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

msg('Hash()');
$s = 'hello world from Frankfurt';
Debug::Msg('Hash for "'.$s.'" is: '.Util::Hash($s));

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

msg('diffArray()');
$a = [
    '[Response] => HTTP/1.1 200 OK',
    '[Cache-Control] => private',
    '[Accept-Charset] => UTF-8',
    '[Connection] => Keep-Alive',
    '[Content-Type] => application/vnd.ms-sync.wbxml; charset=UTF-8',
    '[Content-Length] => 449',
    '[MS-Server-ActiveSync] => 7.07.09',
    '[Date] => Sun, 14 Jan 2018 18:28:28 GMT',
];
$b = [
    '[Response] => HTTP/1.1 200 OK',
    '[Cache-Control] => private',
    '[Accept-Charset] => UTF-8',
    '[Connection] => Keep-Alive',
    '[Content-Type] => application/vnd.ms-sync.wbxml; charset=UTF-8',
    '[Content-Length] => 415',
    '[MS-Server-ActiveSync] => 7.07.09',
    '[Date] => Wed, 09 Jan 2019 22:42:53 GMT',
];
Debug::Msg(Util::diffArray($a, $b), 'Comparison');

msg('isbinary()');
$s = '93äößd9k2_:;';
Debug::Msg('Checking: "'.$s.'" : '.Util::isBinary($s) ? 'Yes' : 'No');

msg('cnvImg()');
$s = Util::mkPath('source').DIRECTORY_SEPARATOR.'TooBig.png';
$d = file_get_contents($s);
$d = Util::cnvImg($d, 'jpg');
$d['newdata'] = Trace::BIN_DATA;
Debug::Msg($d, 'Converting "'.$s.'" to ".jpg"');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------

msg('+++ End of script');

?>