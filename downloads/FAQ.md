
# Client device #
<details>
<summary><h3>Why does my phone number does not arrive on client device?</h3></summary>
<p>Some information may not be synchronized due to internal device limitations not covered by back end handler. In some back ends you may enter "abc" as telephone number. If you try to synchronize this piece of information to cell phone, telephone number will not be synchronized, because numbers strings are only allowed to contain the digits 0 to 9 and the special characters "+ ()#".</p>
</details>

<details>
<summary><h3>I've attached a picture to my contact on server. Why does it not synchronize to client device?</h3></summary>
<p>All images are stored in common PNG graphic format in <strong>sync•gw</strong>. As soon as you connect your client device to <strong>sync•gw</strong> and this device is capable of receiving or sending images, the image is converted to the supported graphic format (the information about the supported graphics formats are exchanged during synchronization initialization).<br />
During exchange of device information, some client devices raises the "Supporting pictures" flag, 
but does include which graphic formats is supported. <strong>sync•gw</strong> assumes as default the <strong>JPEG</strong> graphic format. If that format is not supported by client device, picture may not been shown.</p>
</details>

<details>
<summary><h3>My server do not accept my credentials. What can I do?</h3></summary>
<p>If your server is configured to run as <strong>FAST-CGI</strong> then Apache do not provide your credentials automatically to PHP. Please goto to <a href="https://github.com/toteph42/syncgw/blob/master/downloads/Downloads.md">download section</a> and install the file <code>.htaccess</code> in root directory of your internet server.</p>
</details>

# Client configuration #

<details>
<summary><h3>CardDAV: How do I have to configure my <em>Android device</em> for synchronizing?</h3></summary>
<p>Please use the following Android CardDav description as starting point. This documentation should help you figuring out how to configure your device.<br />
<ul><li>Select <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/webdav-1.png" target="_blank">CardDav-Sync</a></li>
<li>Enter <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/carddav-1.png" target="_blank">URL, user id and password</a>. For more information about which URL to use, please check out our data store definitions</li>
<li>Select <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/carddav-2.png" target="_blank">address book</a> to sync</li>
<li>Check <strong>Account Name</strong> and click on <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/carddav-3.png" target="_blank">address bookFinish</a>. For synchronization in both directions please don't forget to un-check check box</li>
</ul></p>
</details>

<details>
<summary><h3>CardDAV: How do I have to configure my iPhone device for synchronizing?</h3></summary>
<p>Please use the following <em>iPhone CardDav</em> description as starting point. This documentation should help you figuring out how to configure your device.<br />
<ul>
<li>In <strong>Settings</strong> open <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/ip01.png" target="_blank">Accounts &amp; Passwords</a></li>
<li>Select <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/ip02.png" target="_blank">Add Account</a></li>
<li>Select <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/ip03.png" target="_blank">Other</a></li>
<li>Select <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/ip04.png" target="_blank">Add CardDAV Account</a></li>
<li>Insert server name (e.g. <code>[your-domain]</code>), your user name (e.g. <strong>test@xx.com</strong>), your password and a description. Then click on <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/ip05.png" target="_blank">Next</a>. Please note, it might happen your iPhone claims the server certificate does not match. This might happen if you use <strong>Let's Encrypt</strong> certificates. In this case, please accept certificate shown.</li>
</ul>
</p>
</details>

<details>
<summary><h3>ActiveSync: How can I configure my device for synchroniziation?</h3></summary>
<p>Please use the this description for an <em>Android device</em> as starting point. This documentation should help you figuring out how to configure your device.<br />
<ul>
<li>Select <strong>Settings</strong> and scroll down to <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/pic01.png" target="_blank">Accounts</a></li>
<li>Select <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/pic02.png" target="_blank">Microsoft Exchange ActiveSync</a></li>
<li>Enter <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/pic03.png" target="_blank">E-Mail address and Password</a> and click on <strong>Manual Setup</strong></li>
<li>Change in field <strong>Domain\username</strong> your user name to your e-Mail address. Add to <strong>Exchange server</strong> the <code>/sync.php</code> script name. If your server does not have an valid SSL certificate available, de-select <strong>Use secure connection</strong> and click on <a href="https://github.com/toteph42/syncgw/blob/master/downloads/FAQ/pic04.png" target="_blank">Sign in</a></li>
</ul></p>
</details>

[Go back](https://github.com/toteph42/syncgw/)
