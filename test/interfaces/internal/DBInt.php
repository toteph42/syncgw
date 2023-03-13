<?php

/*
 *  Internal data base handler test (with groups)
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 */

namespace test\interfaces;

use syncgw\lib\Debug;
use syncgw\lib\DB;
use syncgw\lib\DataStore;
use syncgw\lib\Util;
use syncgw\lib\XML;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../../Functions.php');

Debug::$Conf['Exclude']['syncgw\lib\XML'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\Config:getVar'] = 1;

Debug::$Conf['Script'] = 'DBInt';

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
setDB($_SERVER['QUERY_STRING']);
$db  = DB::getInstance();
$xml = new XML();

if ($_SERVER['QUERY_STRING'] == 'mysql') {
	msg('DataStore::CONTACT <GUID> creation');
	Debug::Msg('New GUID = "'.$db->mkGUID(DataStore::CONTACT, FALSE).'"');
}

msg('Creating document N131 in root');
$xml->loadXML('
<syncgw>
	<GUID>N131</GUID>
	<LUID>L131</LUID>
	<SyncStat>'.DataStore::STAT_OK.'</SyncStat>
	<Group/>
	<Type>'.DataStore::TYP_DATA.'</Type>
	<Created>'.time().'</Created>
	<LastMod>'.time().'</LastMod>
	<CRC/>
	<extID/>
	<extGroup/>
	<Data>
		<Subject/>
		<Body>Document #131</Body>
	</Data>
</syncgw>');
if (!$db->Query(DataStore::NOTE, DataStore::ADD, $xml)) {
	msg('Failed!', Util::CSS_ERR);
	exit;
}
msg('Should show 1 documents');
showall();

msg('Creating group G02');
$xml->loadXML('
<syncgw>
	<GUID>G02</GUID>
	<LUID>L140</LUID>
	<SyncStat>'.DataStore::STAT_OK.'</SyncStat>
	<Group/>
	<Type>'.DataStore::TYP_GROUP.'</Type>
	<Created>'.time().'</Created>
	<LastMod>'.time().'</LastMod>
	<CRC/>
	<extID/>
	<extGroup/>
	<Data>
		<Folder>
			<name>Junga</name>
			<X-DISPLAYNAME>Junga</X-DISPLAYNAME>
			<X-DESCRIPTION>sync*gw Junga Note folder</X-DESCRIPTION>
		</Folder>
	</Data>
</syncgw>');
if (!$db->Query(DataStore::NOTE, DataStore::ADD, $xml)) {
	msg('Failed!', Util::CSS_ERR);
	exit;
}
msg('Should show 2 documents');
showall();

msg('Creating group G03 in G02');
$xml->loadXML('
<syncgw>
	<GUID>G03</GUID>
	<LUID>L141</LUID>
	<SyncStat>'.DataStore::STAT_OK.'</SyncStat>
	<Group>G02</Group>
	<Type>'.DataStore::TYP_GROUP.'</Type>
	<Created>'.time().'</Created>
	<LastMod>'.time().'</LastMod>
	<CRC/>
	<extID/>
	<extGroup/>
	<Data>
		<Folder>
			<name>myNote</name>
			<X-DISPLAYNAME>myNote</X-DISPLAYNAME>
			<X-DESCRIPTION>sync*gw nested Notes folder</X-DESCRIPTION>
		</Folder>
	</Data>
</syncgw>');
if (!$db->Query(DataStore::NOTE, DataStore::ADD, $xml)) {
	msg('Failed!', Util::CSS_ERR);
	exit;
}
msg('Should show 3 documents');
showall();

msg('Creating document N132 in G02');
$xml->loadXML('
<syncgw>
	<GUID>N132</GUID>
	<LUID>L132</LUID>
	<SyncStat>'.DataStore::STAT_OK.'</SyncStat>
	<Group>G02</Group>
	<Type>'.DataStore::TYP_DATA.'</Type>
	<Created>'.time().'</Created>
	<LastMod>'.time().'</LastMod>
	<CRC/>
	<extID/>
	<extGroup/>
	<Data>
		<Subject>Unknown</Subject>
		<Body>Document #132</Body>
	</Data>
</syncgw>');
if (!$db->Query(DataStore::NOTE, DataStore::ADD, $xml)) {
	msg('Failed!', Util::CSS_ERR);
	exit;
}
msg('Should show 4 documents');
showall();

msg('Changing <Subject> on document N132');
$xml->updVar('Subject', 'New SUMMARY');
if (!$db->Query(DataStore::NOTE, DataStore::UPD, $xml)) {
	msg('Failed!', Util::CSS_ERR);
	exit;
}
showall();

msg('Changing <SyncStat> on document N132 to "'.DataStore::STAT_REP.'"');
if (!$db->setSyncStat(DataStore::NOTE, $xml, DataStore::STAT_REP)) {
	msg('Failed!', Util::CSS_ERR);
	exit;
}
showall();

msg('Selecting documents in root with <SyncStat> != "'.DataStore::STAT_OK.'"');
if ($ids = $db->Query(DataStore::NOTE, DataStore::RNOK, '')) {
	msg('Failed!', Util::CSS_ERR);
	var_dump($ids);
	exit;
}
if (!count($ids))
	msg('OK');
else {
	msg('Invalid', Util::CSS_ERR);
	Debug::Msg($ids);
	exit;
}

msg('Selecting documents with <SyncStat> != "'.DataStore::STAT_OK.'" in G02');
if (!($ids = $db->Query(DataStore::NOTE, DataStore::RNOK, 'G02'))) {
	msg('Failed!', Util::CSS_ERR);
	exit;
}
if (count($ids) == 1)
	msg('OK');
else {
	msg('Invalid', Util::CSS_ERR);
	Debug::Msg($ids);
	exit;
}

msg('Deleting document N132');
if (!$db->Query(DataStore::NOTE, DataStore::DEL, 'N132')) {
	msg('Failed!', Util::CSS_ERR);
	exit;
}
msg('Should show 3 documents');
showall();

msg('Recreating document N132 in G03');
$xml->loadXML('
<syncgw>
	<GUID>N132</GUID>
	<LUID>L132</LUID>
	<SyncStat>'.DataStore::STAT_OK.'</SyncStat>
	<Group>G03</Group>
	<Type>'.DataStore::TYP_DATA.'</Type>
	<Created>'.time().'</Created>
	<LastMod>'.time().'</LastMod>
	<CRC/>
	<extID/>
	<extGroup/>
	<Data>
		<Subject>Unknown</Subject>
		<Body>Document #132 - recreated</Body>
	</Data>
</syncgw>');
if (!$db->Query(DataStore::NOTE, DataStore::ADD, $xml)) {
	msg('Failed!', Util::CSS_ERR);
	exit;
}
msg('Should show 4 documents');
showall();

msg('Creating new GUID "'.$db->mkGUID(DataStore::NOTE, FALSE).'" - should be N133');

msg('Deleting all record in group G03');
if (!$db->Query(DataStore::NOTE, DataStore::DEL, 'G03')) {
	msg('Failed!', Util::CSS_ERR);
	exit;
}
msg('Should show 2 documents');
showall();

msg('Delete remaining records');
foreach($db->Query(DataStore::NOTE, DataStore::RIDS) as $gid => $ids) {
	if (!$db->Query(DataStore::NOTE, DataStore::DEL, $gid)) {
		msg('Failed deleting "'.$gid.'"!', Util::CSS_ERR);
		exit;
	}
}

msg('Should show NO documents');
showall();

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

function showall(): void {

	$db = DB::getInstance();

	$r = $db->Query(DataStore::NOTE, DataStore::GRPS);
	$r += $db->Query(DataStore::NOTE, DataStore::RIDS);
	foreach ($r as $grp => $typ)
		if ($typ == DataStore::TYP_GROUP)
			$r += $db->Query(DataStore::NOTE, DataStore::RIDS, $grp);

	if (!is_array($r)) {
		msg('+++ No records found!', Util::CSS_WARN);
	   	exit;
	}

	Debug::Msg($r, 'Records found');

	foreach ($r as $id => $typ) {
		$xml = $db->Query(DataStore::NOTE, DataStore::RGID, $id);
		if ($typ == DataStore::TYP_GROUP)
			Debug::Msg($xml, 'Group "'.$id.'"');
		else
			Debug::Msg($xml, 'Record "'.$id.'"');
	}
}


?>