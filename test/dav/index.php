<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head><style>body {font: 16px Arial;}</style></head>
<body>
<table cellpadding="3" cellspacing="2" border="1">
 <colgroup><col width="300"><col width="1000"></colgroup>
 <tr><td colspan=2><h3>DavContact</h3></td></tr>
 <tr><td></td><td>&diams; All trace files were located in <strong>test/trace</strong> directory<br />
 &diams; <a target="_blank" href="Emulator configuration.docx">Emulator configuration.docx</a><br />
 &diams; Change back end connection in GUI to <strong>roundcube</strong> and enable <strong>Contact</strong> data store only<br />
 &diams; Created with "DAVx.apk" on Android emulator AVD31.<br />
 &diams; Optionally load SQL <strong>test/helper/rc-reset-user.sql</strong>.<br />
 &diams; Modify and load SQL <strong>test/helper/rc-reset-data.sql</strong>.<br />
 &diams; Disable <strong>Only contacts with phone numbers</strong> during testing.</td></tr>
 <tr>
  <td><input type="checkbox" id="600">Trace 600</td>
 <td>Send contacts to device<br/>&hookrightarrow; check for 3 contacts on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="601">Trace 601</td>
  <td>Modify "FirstName#1" to "FirstName#1X" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="602">Trace 602</td>
  <td>Modiy "FirstName#1X" to "FirstName#1" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="603">Trace 603</td>
  <td>Create new contact "Device" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="604">Trace 604</td>
  <td>Modify "Device" to "DeviceXX" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="605">Trace 605</td>
  <td>Create new contact "Server" on server <br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="606">Trace 606</td>
  <td>Delete "Server" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="607">Trace 607</td>
  <td>Delete "DeviceXX" on server<br/>&hookrightarrow; check on client</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="608">Trace 608</td>
  <td>Send 1 contact to device from one additional account<br/>&hookrightarrow; check on device</td>
 </tr>

 <tr><td colspan=2><h3>DavCalendar</h3></td></tr>
 <tr><td></td><td>&diams; All trace files were located in <strong>test/trace</strong> directory<br />
 &diams; <a target="_blank" href="Emulator configuration.docx">Emulator configuration.docx</a><br />
 &diams; Change back end connection in GUI to <strong>roundcube</strong> and enable <strong>Calendar</strong> data store only<br />
 &diams; Created with "DAVx.apk" on Android emulator AVD31.<br />
 &diams; Load SQL <strong>test/helper/rc-reset-data.sql.</strong>.<br />
 &diams; Disable <strong>Birthday calendar</strong> for synchronization.<br />
 &diams; Select "Calendar#1" and "Calendar#2" for synchronization in DAVX.<br />
 &diams; <strong>You may need to change start date of events, since Android only sync. 3 month in the past.</strong></td></tr>
 <tr>
  <td><input type="checkbox" id="620">Trace 620</td>
  <td>Send 4 events to device<br/>&hookrightarrow; check for 4 events on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="621">Trace 621</td>
  <td>Modify "Event #4" to "Event #4X" event on device<br/>&hookrightarrow; check on sever (big attachment will disappear)</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="622">Trace 622</td>
  <td>Modify "Event #4X" to "Event #4" on server<br/>&hookrightarrow; check eon device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="623">Trace 623</td>
  <td>Create new event "Device" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="624">Trace 624</td>
  <td>Modify "Device" to "DeviceXX" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="625">Trace 625</td>
  <td>Create new event "Server" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="626">Trace 626</td>
  <td>Delete event "Server" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="627">Trace 627</td>
  <td>Delete event "DeviceXX" on server<br/>&hookrightarrow; check on client</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="628">Trace 628</td>
  <td>Send 1 event to device from different account<br/>&hookrightarrow; check 5 events on device</td>
 </tr>

 <tr><td colspan="2"><h3>DavTask</h3></td></tr>
 <tr><td></td><td>&diams; All trace files were located in <strong>test/trace</strong> directory<br />
 &diams; <a target="_blank" href="Emulator configuration.docx">Emulator configuration.docx</a><br />
 &diams; Change back end connection in GUI to <strong>roundcube</strong> and enable <strong>Task</strong> data store only<br />
 &diams; Created with "DAVx.apk" and "OpenTask.apk" on Android emulator AVD31.<br />
 &diams; Load SQL <strong>test/helper/rc-reset-data.sql-</strong>.<br />
 &diams; Select "Task list #1" and "Task list #2" for synchronization in DAVX.<br />
 &diams; If you want to gather trace data, set <code>TaskDAV = "FORCE"</code> in <code>config.ini.php</code>.</td></tr>
 <tr>
  <td><input type="checkbox" id="640">Trace 640</td>
  <td>Send 3 task to device<br/>&hookrightarrow; check for 4 tasks on device (1 is done)</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="641">Trace 641</td>
  <td>Modify "Task #2" to "Task #2X" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="642">Trace 642</td>
  <td>Modify "Task #2X" to "Task #2" on server to<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="643">Trace 643</td>
  <td>Create new task "Device" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="644">Trace 644</td>
  <td>Modify "Device" to "DeviceXX" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="645">Trace 645</td>
  <td>Create new task "Server" on server<br/>&hookrightarrow; check on device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="646">Trace 646</td>
  <td>Delete "Server" on device<br/>&hookrightarrow; check on server</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="647">Trace 647</td>
  <td>Delete task "DeviceXX" on server<br/>&hookrightarrow; check device</td>
 </tr>
 <tr>
  <td><input type="checkbox" id="648">Trace 648</td>
  <td>Send 1 task to device from different account<br/>&hookrightarrow; check on device</td>
 </tr>
 </table>
 <p>--- END --</p>
 </body>
</html>
