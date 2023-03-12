<?php

/*
 *  XML class test
 *
 *	@package	sync*gw
 *	@subpackage	Test scripts
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/Toteph42/syncgw/blob/master/LICENSE
 */

namespace test\scripts\lib;

use syncgw\lib\Debug;
use syncgw\lib\Util;
use syncgw\lib\XML;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once('../Functions.php');

Debug::$Conf['Script'] = 'XML';

Debug::$Conf['Exclude']['syncgw\lib\Config'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:hasChild'] = 1;
Debug::$Conf['Exclude']['syncgw\lib\XML:saveXML'] = 1;

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
$xml = new XML();

$xml->addVar('Record');
Debug::Msg($xml, '<Record> added');

$s = 'Unterä&amp;-Ordner<&\'ö"ß>sync&bull;gw';
$xml->addVar('Data', $s);
Debug::Msg($xml, '<Record> added');
if ($xml->getVar('Data') != $s) {
	msg('+++ Inconsistent data', Util::CSS_ERR);
	exit;
}

$xml->addVar('Empty', '');
$xml->addVar('SubRec', 'This is a short text, with some commas ,,,;;;.
# comment
This <Pitty>tag</Pitty> should survive.
$%äöü)([]=^\'"1!
&nbsp;&auml;#&tag
	tab1	tab2 --END');
$xml->getVar('Record');
Debug::Msg($xml, '<Empty>, <Subrec>Content<Subrec> added');

$xml->addVar('Record1');
$xml->getVar('Record');
Debug::Msg($xml, 'Full record');

$xml->getVar('SubRec');
$xml->addComment('This is a comment - note the ugly position');
$xml->getVar('Record');
Debug::Msg($xml, 'Full record with comment at "SubRec"');

$xml->delVar('SubRec');
$xml->getVar('Record');
Debug::Msg($xml, 'Full record');

$xml->addVar('SubRec', 'Content');
$xml->getVar('Record');
Debug::Msg($xml, 'Full record');

$xml->getVar('SubRec');
$xml->setVal('Replaced content');
$xml->getVar('Record');
Debug::Msg($xml, 'Full record');

$x = $xml->updVar('SubRec', 'NEW Replaced content');
$xml->getVar('Record');
Debug::Msg('Old Value = "'.$x.'"');
Debug::Msg($xml, 'Full record');

$xml->addVar('NewSubRec', 'Should be sub record of "Record"');
$xml->getVar('Record');
Debug::Msg($xml, 'Full record');

$xml->setName('Otto');
$xml->getVar('Record');
Debug::Msg($xml, 'Full record');

msg('hasChild()');
Debug::$Conf['Exclude']['syncgw\lib\XML:hasChild'] = 0;
$xml->hasChild();
Debug::$Conf['Exclude']['syncgw\lib\XML:hasChild'] = 1;
$xml->getVar('SubRec', TRUE);
$xml->hasChild();

msg('updObj() - object updated '.$xml->updObj(FALSE).' times (should be 10)');

msg('loadFile()');
$xml->loadFile(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'syncgw'.
			   DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR.'dev_MAS.xml');
Debug::Msg($xml, 'Loaded record from file');

msg('xpath() - should find: 6');
Debug::Msg('Found value is: '.$xml->xpath('//DataStore'));

msg('getVar("Model")');
Debug::Msg('Found value is: '.$xml->getVar('Model', TRUE));

msg('setAttr("Attribut", "Value")');
$xml->setAttr([ 'Attribut' => 'Value' ]);
msg('getAttr()');
$xml->getAttr();
$xml->getVar('Device');
Debug::Msg($xml, 'Full record');

msg('Number of childrens: of "Device": '.$xml->getChild('Device'));
show($xml);

msg('Getting Calendar datastore');
$xml->xvalue('//DataStore/HandlerID', 'DataStore::CALENDAR');
$xml->getItem();
msg('Calling XML2Array()');
Debug::Msg($xml, 'Input document');
$arr = $xml->XML2Array();
Debug::Msg($arr, 'Output');
$x = new XML();
$x->loadXML('<syncgw/>');
$x->getVar('syncgw');
$x->Array2XML($arr);
$x->getVar('syncgw');
Debug::Msg($x, 'Output');

$dup = new XML();
$dup->loadXML('<syncgw><Tag A="1" B="2" C="3">1</Tag></syncgw>');
Debug::Msg($dup, 'Document to insert');
$xml->loadXML('<syncgw><Dummy/></syncgw>');
Debug::Msg($xml, 'Destination document');
$xml->getVar('Tag');
$xml->getVar('Dummy');
$xml->append($dup, TRUE, TRUE);
$xml->setTop();
Debug::Msg($xml, 'Append whole docuement as first child node');
$dup->getVar('Tag');
$xml->loadXML('<syncgw><Dummy/></syncgw>');
$xml->getVar('Dummy');
$xml->append($dup, FALSE, FALSE);
$xml->setTop();
Debug::Msg($xml, 'Append from current position <Dummy> as child node');

$xml->loadXML('<syncgw><Tag A="1" B="2" C="3">1</Tag></syncgw>');
$xml->getVar('Tag');
$xml->dupVar(3);
$xml->setTop();
Debug::Msg($xml, 'Document with 3 duplcated = 5 <Tag>');

# -------------------------------------------------------------------------------------------------------------------------------------------------------------------
msg('+++ End of script');

function show(XML &$xml): void {
	while ($xml->getItem() !== NULL) {
		if ($xml->hasChild()) {
			Debug::Msg(''.$xml->getName().'="";');
			$save = $xml->savePos();
			$xml->getChild($xml->getName());
			show($xml);
			$xml->restorePos($save);
		} else {
			Debug::Msg('['.$xml->getName().'] = "'.$xml->getVal().'"');
		}
	}
}

?>