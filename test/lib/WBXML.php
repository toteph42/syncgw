<?php

/*
 *  WBXML decoder test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\WBXML;
use syncgw\lib\XML;
use syncgw\lib\Util;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'WBXML';

Debug::CleanDir('comp*.*');
Debug::CleanDir('WBXML*.*');

#Debug::$Conf['Exclude']['syncgw\lib\WBXML'] = 1;
#Debug::$Conf['Exclude']['syncgw\lib\WBXML:Decode'] = 1;

Debug::$Conf['Exclude']['syncgw\lib\Encoding'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML'] = 1;
#Debug::$Conf['Exclude']['syncgw\lib\DTD'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$start = 1;
$end   = 0;
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

    default:
        msg('+++ Unknown parameter "'.$c.' = '.$p.'"');
        exit;
    }
}

echo 'Call Parameter:<br /><br />';
echo 'S=  - Start test number (first to show) -> "'.$start.'"<br />';
echo 'E=  - End test number (last to show) -> "'.$end.'"<br />';
echo '<br />';

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$path = Util::mkpath('..').'/test/mimedata/wbxml/';
$wb   = WBXML::getInstance();

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
Debug::CleanDir('comp*.*');
Debug::CleanDir('WBXML*.wbxml');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
for ($cnt=$start; $cnt < $end; $cnt++) {

	$ifile = $path.'in'.sprintf('%02d', $cnt).'.wbxml';
	$ofile = $path.'out'.sprintf('%02d', $cnt).'.xml';

	if (!file_exists($ifile))
		break;

	if (!($buf = file_get_contents($ifile))) {
	 	msg('Can\'t open file '.$ifile);
	 	exit();
	}

	$o = new XML;
	$o->loadFile($ofile);
	msg(sprintf('%02d', $cnt).' Encoding XML document '.$ofile);
	$buf = $wb->Encode($o);
	if (!comp($buf, TRUE, $ifile, 'Compare WBXML')) {
		$len = 256;
		Debug::Warn($wrk = file_get_contents($ifile), 'new WBXML (0-'.$len.') of '.strlen($wrk).' bytes', 0, $len);
		Debug::Warn($buf, 'new WBXML (0-'.$len.') of '.strlen($buf).' bytes', 0, $len);
	}

	msg(sprintf('%02d', $cnt).' Decoding WBXML document');
	$xml = $wb->Decode($buf);
	$xml->getVar('SyncML');
	comp($xml, TRUE, $ofile, 'Compare XML');
}

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

?>