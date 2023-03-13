<?php
declare(strict_types=1);

/**
 *
 *  Create MIME file extension mapping table
 *
 *	@package	sync*gw
 *	@subpackage	Tools
 *	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
 * 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
 *
 */

namespace test\helper;

if (!($buf = @file_get_contents('http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types'))) {
    echo '+++ Error loading MIME type table from apache.org';
    exit;
}

$wrk = [];
$arr = [];
foreach (explode("\n", $buf) as $line) {
    if (isset($line[0]) && $line[0] !== '#' && preg_match_all('#([^\s]+)#', $line, $wrk) && isset($wrk[1]) && ($c = count($wrk[1])) > 1)
        for ($i = 1; $i < $c; $i++)
            if (!isset($arr[$wrk[1][0]]))
                $arr[$wrk[1][0]] = $wrk[1][$i];
}

$out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
'<!--'."\n\n".
'    MIME file extension mapping table'."\n\n".
' 	@package	sync*gw'."\n".
' 	@subpackage	Core'."\n".
'	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved'."\n".
' 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE'."\n".
'-->'."\n".
'<MIME xmlns="https://github.com/toteph42/syncgw/downloads/schema/syncgw.xsd"'."\n".
'		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'."\n".
'		xsi:schemaLocation="https://github.com/toteph42/syncgw/downloads/schema/ https://github.com/toteph42/syncgw/downloads/schema/syncgw.xsd">'."\n";

foreach ($arr as $k => $v) {
    $out .= '  <Application>'."\n".
            '    <Name>'.$k.'</Name>'."\n".
            '    <Ext>'.$v.'</Ext>'."\n".
            '  </Application>'."\n";
}

$out .= '</MIME>';

header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: public');
header('Content-Description: File Transfer');
header('Content-Type: application-xml');
header('Content-Disposition: attachment; filename="mime_types.xml"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: '.strlen($out));
echo $out;
?>