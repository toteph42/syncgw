<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head><style>body {font: 16px Arial;}</style></head>
<body>
<table cellpadding="3" cellspacing="2" border="1">
 <colgroup><col width="300"><col width="1000"></colgroup>

 <tr><td colspan="2"><h3>ActiveSync &lt;Option&gt; test</h3></td></tr>
 <tr>
  <td><input type="checkbox" id="asOption01"> <a target="_blank" href="AsOption.php?1">activesync/MAS-Handler.php</a></td>
  <td>&lt;Sync&gt;&lt;Option&gt; test</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="asOption02"> <a target="_blank" href="AsOption.php?2">activesync/MAS-Handler.php</a></td>
  <td>&lt;Find&gt;&lt;Option&gt; test</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="asOption03"> <a target="_blank" href="AsOption.php?3">activesync/MAS-Handler.php</a></td>
  <td>&lt;GetItemEstimate&gt;&lt;Option&gt; test</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="asOption04"> <a target="_blank" href="AsOption.php?4">activesync/MAS-Handler.php</a></td>
  <td>&lt;ItemOperations&gt;&lt;Option&gt; test</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="asOption05"> <a target="_blank" href="AsOption.php?5">activesync/MAS-Handler.php</a></td>
  <td>&lt;ResolveRecipients&gt;&lt;Option&gt; test</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="asOption06"> <a target="_blank" href="AsOption.php?6">activesync/MAS-Handler.php</a></td>
  <td>&lt;Search&gt;&lt;Option&gt; test</td>
 </tr>

 <tr><td colspan="2"><h3>ActiveSync tests</h3></td></tr>
 <tr>
  <td><input type="checkbox" id="asCVI07"> <a target="_blank" href="cvIndex.php">interfaces/mail/Handler.php</a></td>
  <td>Decode / create &lt;ConversationIndex&gt;</td>
 </tr>
 <tr><td colspan="2" style="line-height:5px;">&nbsp;</td></tr>
 <tr><td colspan="2"><h3>Connection tests</h3></td></tr>
 <tr><td></td><td>&diams; All trace files were located in <strong>test/trace</strong> directory<br />
  &diams; <a target="_blank" href="ConnetionTest.docx">ConnetionTest.docx</a>.</td></tr>
 <tr>
  <td><input type="checkbox" id="810">Trace 810</td>
  <td>Exchange ActiveSync with Autodiscover (Microsoft RCA)</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="811">Trace 811</td>
  <td>Exchange ActiveSync without Autodiscover (Microsoft RCA)</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="812">Trace 812</td>
  <td>Exchange ActiveSync Outlook Autodiscover (Microsoft RCA - responseschema/2006)</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="813">Trace 813</td>
  <td>Exchange ActiveSync Outlook Autodiscover (Microsoft RCA - responseschema/2006a)</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="814">Trace 814</td>
  <td>Win 10 Mail Autodiscover (responseschema/2006a)</td>
 </tr>

 <tr><td colspan="2"><h3>AsContact</h3></td></tr>
 <tr><td></td><td>&diams; All trace files were located in <strong>test/trace</strong> directory<br />
 &diams; <a target="_blank" href="Emulator configuration.docx">Emulator configuration.docx</a><br />
 &diams; Change back end connection in GUI to <strong>roundcube</strong> and enable <strong>Contact</strong> data store only.<br />
 &diams; Created with "Nine.apk" on Android emulator AVD31.<br />
 &diams; Optionally load SQL <strong>test/helper/rc-reset-user.sql</strong>.<br />
 &diams; Load SQL <strong>test/helper/rc-reset-data.sql</strong>.<br />
 &diams; Call <strong>Truncate ALL sync•gw tables</strong> in GUI.<br />
 &diams; Disable <strong>Only contacts with phone numbers</strong> during testing.</td></tr>
 <tr>
  <td><input type="checkbox" id="720">Trace 720</td>
  <td>Send contacts to device<br/>&hookrightarrow; check for 3 contacts on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="721">Trace 721</td>
  <td>Modify "FirstName#1" to "FirstName#1X" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="722">Trace 722</td>
  <td>Modiy "FirstName#1X" to "FirstName#1" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="723">Trace 723</td>
  <td>Create new contact "Device" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="724">Trace 724</td>
  <td>Modify "Device" to "DeviceXX" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="725">Trace 725</td>
  <td>Create new contact "Server" on server <br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="726">Trace 726</td>
  <td>Delete "Server" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="727">Trace 727</td>
  <td>Delete "DeviceXX" on server<br/>&hookrightarrow; check on client</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="728">Trace 728</td>
  <td>Send 1 contact to device from one additional account<br/>&hookrightarrow; check on device</td>
 </tr>

 <tr><td colspan="2"><h3>AsCalendar</h3></td></tr>
 <tr><td></td><td>&diams; All trace files were located in <strong>test/trace</strong> directory<br />
 &diams; <a target="_blank" href="Emulator configuration.docx">Emulator configuration.docx</a><br />
 &diams; Change back end connection in GUI to <strong>roundcube</strong> and enable <strong>Calendar</strong> data store only<br />
 &diams; Created with "Nine.apk" on Android emulator AVD31.<br />
 &diams; Load SQL <strong>test/helper/rc-reset-data.sql</strong> (check date which is not allowed to be too much in the past).<br />
 &diams; Disable <strong>Birthday calendar</strong> for synchronization.</td></tr>
 <tr>
  <td><input type="checkbox" id="740">Trace 740</td>
  <td>Send 4 events to device<br/>&hookrightarrow; check for 4 events on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="741">Trace 741</td>
  <td>Modify "Event #4" to "Event #4X" event on device<br/>&hookrightarrow; check on sever (big attachment will <strong>NOT</strong> disappear - thank's to ghosting)</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="742">Trace 742</td>
  <td>Modify "Event #4X" to "Event #4" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="743">Trace 743</td>
  <td>Create new event "Device" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="744">Trace 744</td>
  <td>Modify "Device" to "DeviceXX" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="745">Trace 745</td>
  <td>Create new event "Server" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="746">Trace 746</td>
  <td>Delete event "Server" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="747">Trace 747</td>
  <td>Delete event "DeviceXX" on server<br/>&hookrightarrow; check on client</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="748">Trace 748</td>
  <td>Download small attachment from "Event #3" on device <br/>&hookrightarrow; check on device for updated picture</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="749">Trace 749</td>
  <td>Download big attachment from "Event #4" to device<br/>&hookrightarrow; check on device for updated picture</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="750">Trace 750</td>
  <td>Change "Event #2 - start" to ""Event #2X - start" on device (all events)<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="751">Trace 751</td>
  <td>Change "Event #2X - start" to Event #2 - start" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="752">Trace 752</td>
  <td>Get out-of-office-status<br/>&diams; Click on <strong>Automatic replies</strong> in account settings.<br/>&hookrightarrow; check for XML response</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="753">Trace 753</td>
  <td>Save out of office status<br/>&diams; Click on <strong>Automatic replies</strong> in account settings.<br/>&hookrightarrow; check for updated User object on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="754">Trace 754</td>
  <td>Send 1 event to device from different account<br/>&hookrightarrow; check 5 events on device</td>
 </tr>

 <tr><td colspan="2"><h3>AsTask</h3></td></tr>
 <tr><td></td><td>&diams; All trace files were located in <strong>test/trace</strong> directory<br />
 &diams; <a target="_blank" href="Emulator configuration.docx">Emulator configuration.docx</a><br />
 &diams; Change back end connection in GUI to <strong>roundcube</strong> and enable <strong>Task</strong> data store only<br />
 &diams; Created with "Nine.apk" on Android emulator AVD31.<br />
 &diams; Load SQL <strong>test/helper/rc-reset-data.sql</strong>.</td></tr>
 <tr>
  <td><input type="checkbox" id="760">Trace 760</td>
  <td>Send 3 task to device<br/>&hookrightarrow; check for 4 tasks on device (1 is done)</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="761">Trace 761</td>
  <td>Modify "Task #2" to "Task #2X" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="762">Trace 762</td>
  <td>Modify "Task #2X" to "Task #2" on server to<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="763">Trace 763</td>
  <td>Create new task "Device" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="764">Trace 764</td>
  <td>Modify "Device" to "DeviceXX" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="765">Trace 765</td>
  <td>Create new task "Server" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="766">Trace 766</td>
  <td>Delete "Server" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="767">Trace 767</td>
  <td>Delete task "DeviceXX" on server<br/>&hookrightarrow; check device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="768">Trace 768</td>
  <td>Send 1 task to device from different account<br/>&hookrightarrow; check on device</td>
 </tr>

 <tr><td colspan="2"><h3>AsNote</h3></td></tr>
 <tr><td></td><td>&diams; All trace files were located in <strong>test/trace</strong> directory<br />
 &diams; <a target="_blank" href="Emulator configuration.docx">Emulator configuration.docx</a><br />
 &diams; Change back end connection in GUI to <strong>roundcube</strong> and enable <strong>Notes</strong> data store only.<br />
 &diams; Created with "Nine.apk" on Android emulator AVD31.<br />
 &diams; Copy <strong>test/helper/notes</strong> directory to <strong>/www/roundcube/notes</strong> directory.</td></tr>
 <tr>
  <td><input type="checkbox" id="780">Trace 780</td>
  <td>Send 3 notes to device<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="781">Trace 781</td>
  <td>Modify note "Text #1" to "Text #1XX" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="782">Trace 782</td>
  <td>Modify note "Text #1XX" to "Text #1" on device<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="783">Trace 783</td>
  <td>Create new note "Device" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="784">Trace 784</td>
  <td>Modify "Device" to "DeviceXX" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="785">Trace 785</td>
  <td>Create new "Server" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="786">Trace 786</td>
  <td>Delete "Server" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="787">Trace 787</td>
  <td>Delete "DevixeXX" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="788">Trace 788</td>
  <td>Send 1 note to device from different account<br/>&hookrightarrow; check on device</td>
 </tr>

 <tr><td colspan="2"><h3>EXPERIMENTAL: AsMail</h3></td></tr>
 <tr><td></td><td>&diams; All trace files were located in <strong>test/trace</strong> directory<br />
 &diams; <a target="_blank" href="Emulator configuration.docx">Emulator configuration.docx</a><br />
 &diams; Change back end connection in GUI to <strong>mail</strong> and enable <strong>Mail</strong>, <strong>Calendar</strong> and <strong>Contact</strong> data stores.<br />
 &diams; Created with "Nine.apk" on Android emulator AVD30.<br />
 &diams; Start XAMPP Mercury mail.<br />
 &diams; Execute <a target="_blank" href="./helper/LoadMails.php">test/helper/LoadMails.php</a>.<br /></td></tr>
 <tr>
  <td><input type="checkbox" id="I800">Trace 800</td>
  <td>Send mails to device<br/>&hookrightarrow; check for 4 mails on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="I801">Trace 801</td>
  <td>Show mail with inline attachments on device<br/>&hookrightarrow; check for "Inline attachment"</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="I802">Trace 802</td>
  <td>Download file attachment from mail "Inline attachment" to device<br/>&hookrightarrow; check for downloaded picture on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="I803">Trace 803</td>
  <td>Search Mail<br/>&hookrightarrow; check for "First"</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="I804">Trace 804</td>
  <td>Send mail<br/>&hookrightarrow; check if mail arrives</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="I805">Trace 805</td>
  <td>Resolve receipient<br/>&hookrightarrow; check XML</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="I806">Trace 806</td>
  <td>&lt;Itemoperation&gt;&lt;Fetch&gt; attachment with <b>MS-ASAcceptMultiPart</b><br/>&hookrightarrow; check XML</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="I807">Trace 807</td>
  <td>&lt;Search&gt; GAL created with Win10 Mail program</td>
 </tr>

 </table>
 <p>--- END --</p>
 </body>
</html>
