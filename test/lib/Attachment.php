<?php

/*
 *  Attachment handler class test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\Attachment;
use syncgw\lib\Config;
use syncgw\lib\DB;
use syncgw\lib\DataStore;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'Attachment';

Debug::$Conf['Exclude']['syncgw\lib\XML:hasChild'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:saveXML'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\Config:getVar'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\Attachment:getVar'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
setDB();

$instr = 'Hello world, how are you today!';

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$cnf = Config::getInstance();
msg('Set database record size to "6"');
$cnf->updVar(Config::DB_RSIZE, 6);

$att = Attachment::getInstance();
msg('Write attachment');
$gid = $att->create($instr);
msg('Reading attachment "'.$gid.'"');
$f = $att->read($gid);
msg('Attachment data "'.$f.'" with " '.$att->getVar('Size').' bytes');
msg('Dumping data store');
show();

msg('Deleting attachment record "'.$gid.'"');
$db = DB::getInstance();
$db->Query(DataStore::ATTACHMENT, DataStore::DEL, $gid);
show();

msg('Rewrite attachment');
msg('Set database record size to "1440"');
$cnf->updVar(Config::DB_RSIZE, 1400);
$att = Attachment::getInstance();
$att->create($instr);
$f = $att->read($gid);
msg('Attachment data "'.$f.'" with "'.$att->getVar('Size').'" bytes');
msg('Dumping data store');
show($att);

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

function show(): void {
 	$db = DB::getInstance();
 	foreach ($db->Query(DataStore::ATTACHMENT, DataStore::RIDS) as $gid => $typ) {
 	    if ($typ == DataStore::TYP_DATA)
 	        continue;
 	    $xml = $db->Query(DataStore::ATTACHMENT, DataStore::RGID, $gid);
 	 	$xml->getVar('syncgw');
 	 	Debug::Msg($xml, 'Group record');
 	 	foreach ($db->Query(DataStore::ATTACHMENT, DataStore::RIDS, $gid) as $id => $unused) {
 	 		$xml = $db->Query(DataStore::ATTACHMENT, DataStore::RGID, $id);
 	 		$xml->getVar('syncgw');
 	 		Debug::Msg($xml, 'Sub record');
 	 	}
 	 	// remove $unused warning
 	 	$unused = 1; $unused++;
 	}
}

?>