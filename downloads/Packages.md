# [Available sync•gw software](https://github.com/toteph42/syncgw/releases) #

## Standard Edition (ED01) ##
Download this edition if you want to simply use **sync•gw**.

* Written in PHP - no binary CPU depended code.
* Support of **[XML](https://en.wikipedia.org/wiki/XML)** and 
**[WBXML](http://en.wikipedia.org/wiki/WBXML)** protocol.
* Support of **[WebDAV](https://en.wikipedia.org/wiki/WebDAV)** (**CalDAV** and **CardDAV**) protocol.
* Support of **[MicroSoft Exchange ActiveSync (EAS)](http://en.wikipedia.org/wiki/Exchange_ActiveSync)** protocol (2.5, 12.0, 12.1, 14.0, 14.1, 16.0, 16.1).
* Only a web server with PHP is required to run **sync•gw** (no additional software or tools required).
* Full internationalization support.
* Multi byte support (support for e.g. Japanese language).
* Support for time zones..
* Multiple level of logging supported
* Intelligent field assignment - calculated based on mix of configuration file and probability calculation.
* Programming documentation available (see **Developers Guide** in the [Downloads](https://github.com/toteph42/syncgw/downloads/blob/master/Downloads.md).
* Support for encrypted message exchange using SSL web server setting.
* Administrator browser interface with password protection.

The browser based administrator interface provides access to all internal data records. The following Plugins are included:

* Run-time environment and **sync•gw** status check.
* Administrator interface password management.
* **sync•gw** configuration.
* Select and establish / drop connection to data base.
* Log file viewer.
* Record explorer (including viewer of data records).
* Delete data record.
* Cleanup of session and trace data.
* Download data record (e.g. trace data for further analysis).

**sync•gw** setup is very easy. Download and unzip **sync•gw** software packages, define a administrator password, connect a data base handler and **sync•gw** is ready for your first synchronization.

A detailed description of available configuration option is available in our browser interface documentation available in our [download section](https://github.com/toteph42/syncgw/blob/master/downloads/Downloads.md)).

## Professional Edition (ED02) ##
If you want to provide end user support or deploy your interface handler the Professional Edition offers additional plugins for **sync•gw** browser interface.

* Show user statistics.
* View trace records.
* Debug (replay) trace file.
* Manually synchronize internal and external data records.
* Edit data records.
* Upload data record.

## Developer Edition ##
If you want to provide end user support or deploy your own interface handler you should download all project files.

The developer pack offers additional plugins for **sync•gw** browser interface.

* Export trace file.
* Force trace file creation.
* Switch between different interface handler.
* Truncate all data base tables and trace file directory.
* Create software packages.
* Create feature list table.

Additionally you will get many debug messages during trace file debugging and a whole bunch of test files. If
you have downloaded all project files, please open `https://[your localhost]/test/index.php` and you
will find links to all test scripts.

## Interface handler ##
**sync•gw** requires an interface handler to store and handle data during synchronization. The following interfaces are currently available:

### File interface  handler (BE01) ###
This data base handler is useful when your installation does not provide any MySQL data base handler.

### MySQL interface handler (BE02) ###
This data base handler is useful when you want to synchronize data between devices and you does not have any server application running.

### RoundCube (including MySQL) interface handler (BE03) ###
This data base handler includes the MySQL data base handler mentioned above. Additionally this interface handler synchronizes data (e.g. **contact** records) from RoundCube with **sync•gw** internal records. Your application users can always access most current data.

### MyApp interface handler (BE04) ###
If you require any another interface handler for which you're need a handler not listed here, you may either develop your own (for more information, please read our **Developer Guide** in our [download section](https://github.com/toteph42/syncgw/blob/master/downloads/Downloads.md).

### EXPERIMENTAL: Mail interface handler ###
The purpose of this interface handler is to communicate to an **IMAP** and a **SMTP** handler. It is an "on top" interface to all other data base handler. Currently this interface handler is in development and I hope to offer it as soon as possible. Please aware, this interface handler does not turn **sync•gw** into a full blown **Exchange server**. It does not support only **MicroSoft Exchange** protocol. But it is able to synchronize mails from your mail server to client devices. It is included in **Developer Edition** only.

## Protocoll handler ##
Protocoll handler were used to handle the comuunication with your device. 

### WebDav protocol handler ###
The included files enables support for [CalDav](http://en.wikipedia.org/wiki/CalDAV) and [CardDav](http://en.wikipedia.org/wiki/CardDAV) protocol. It is included in **Standard Edition** and **Professional Edition**.

### MicroSoft Exchange ActiveSync ###
The included files  enables support for [ActiveSync](http://en.wikipedia.org/wiki/Exchange_ActiveSync). 
It is included in package **Standard Edition** and **Professional Edition**.

### EXPERIMENTAL: Messaging Application Programming Interface (MAPI) over HTTP ##
If you want to connect your **[Outlook](https://en.wikipedia.org/wiki/Outlook)** installation to **sync•gw**,
then you need this ptocoll.

The included files enables support for [MAPI](https://en.wikipedia.org/wiki/MAPI). Technically the
communication between **Outlook** and your **sync•gw** installation works fine, bute unfortunately 
currently the results were poor. Due to the very poor documentation provided by **MicroSoft**, I was not able
to find out the meaning of many fields. It is included in **Developer Edition** only.

## Data store handler ##
Data store handlers are used to access data records in server application. **sync•gw** server uses these handler to convert internal data to supported format by client device.

### Contact (DS01) ###
### Calendar and task (DS02) ###
### Notes (DS03) ###
### Experimental: Mail ###
It is included in **Developer Edition** only.

[Go back](https://github.com/toteph42/syncgw/)
