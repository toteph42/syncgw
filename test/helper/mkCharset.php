<?php
declare(strict_types=1);

/**
 *  Create character set mapping table
 *
 *	@package	sync*gw
 *	@subpackage	Tools
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 *
 */

namespace test\helper;

use syncgw\lib\XML;

require_once('../../syncgw/lib/Loader.php');

if (!file_exists('character-sets.xml')) {
	echo 'Please download XML file from <a href="https://www.iana.org/assignments/character-sets/character-sets.xml">IANA</a>, save to helper directory and rerun script';
	exit;
}

if (!($buf = file_get_contents('character-sets.xml'))) {
    echo '+++ Error loading character set table';
    exit;
}

$in = new XML();
$in->loadXML(str_replace('xmlns', 'xml-ns', $buf));
unlink('character-sets.xml');

$out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
'<!--'."\n".
"\n".
'	Charset definitions'."\n".
"\n".
' 	@package	sync*gw'."\n".
' 	@subpackage	Core'."\n".
'	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved'."\n".
' 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE'."\n".
"\n".
'-->'."\n".
'<syncgw xmlns="https://github.com/toteph42/syncgw/downloads/schema/charset.xsd"'."\n".
'		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'."\n".
'		xsi:schemaLocation="https://github.com/toteph42/syncgw/downloads/schema/ https://github.com/toteph42/syncgw/downloads/schema/charset.xsd">'."\n";

$in->xpath('//record/.');
while ($in->getItem() !== NULL) {
	$p = $in->savePos();
	$out .= ' <Charset>'."\n".
			'  <Id>'.$in->getVar('value', false).'</Id>'."\n".
			'  <Cp></Cp>'."\n";

	$in->restorePos($p);
	$out .= '  <Name>'.strtolower($in->getVar('name', false)).'</Name>'."\n";
	$in->restorePos($p);
	$in->xpath('alias', FALSE);
	while ($v = $in->getItem())
		$out .= '  <Alias>'.strtolower($v).'</Alias>'."\n";
	$in->restorePos($p);
	$out .= ' </Charset>'."\n";
}
$out .= '</syncgw>';

header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: public');
header('Content-Description: File Transfer');
header('Content-Type: application-xml');
header('Content-Disposition: attachment; filename="charset.xml"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: '.strlen($out));
echo $out;
?>