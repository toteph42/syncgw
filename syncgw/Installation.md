# Installation guide for sync•gw #

1. Select [sync•gwpackages](https://github.com/toteph42/syncgw/blob/master/downloads/Packages.md) you want to install.
2. Go to [release page](https://github.com/toteph42/syncgw/releases) and download your **sync•gw** packages. 
3. Unpack files in a temporary directory. Depending on the **sync•gw** packages you downloaded, warning messages 
may pop up you are attempting to override some files during unpacking. You can safely ignore this 
message - the message appears because some software packages may contain same source files.
4. Copy all files to your (RoundCube) application root directory.
5. On Linux systems you need to set the appropriate file permissions. Please change 
to application root directory and enter `chmod -R 0755 ./syncgw`. Please ensure, the owner of the files and directories are properly set. 
6. If your web server user is e.g. `www-data`, then you may execute `chown -R www-data ./syncgw` in application root directory to ensure user access is granted.
7. Perform steps described in chapter "Data base connection Installation".
8. Finally open **sync•gw** browser interface (`https://[your Roundcube installation directory]/sync.php`and select "Check Status" from menu.

# Upgrade sync•gw installtion #

1. Go to [download page](https://github.com/toteph42/syncgw/releases) and download your 
**sync•gw** installation packages. If you known which files you want to download (see [List of packages](https://github.com/toteph42/syncgw/blob/master/downloads/Packages.md), then you can use the link `https://github.com/toteph42/syncgw/releases/latest/download/[package name].zip` (replace `[package name]` with the name of the package (e.g. `ED01`).
2. Unpack files in a temporary directory. Depending on the software you downloaded, a warning 
message may appear you are attempting to override some files. You can safely ignore this message -
the message appears because some software packages may contain same source files.
3. Switch to application root directory and copy file `syncgw/config.ini.php` to `[temporary directory]/syncgw/config.ini.php`.
4. Delete sub directory `syncgw` in your installation directory.
5. Copy all files from temporary directory to your application root directory.
6. Set file permissions (see above).
7. Finally open **sync•gw** browser interface. Please note, loading may take longer time, due to 
automatic upgrade performed. 
8. **Please note, until you performed step 7) synchronization is disabled.**

# Data base connection installation #

## File connection handler ##
1. Download and unpack all **sync•gw** files and upload all files to your web server directory.
2. Start **sync•gw** by typing into your browser's URL bar `http://[your-domain.tld]/[path to application directory]/sync.php`.
3. Select "Configure **sync•gw**", check settings and click on "Save".

## MySQL data base connection handler ##
1. Download and unpack all **sync•gw** files and upload all files to your web server directory.
2. You need at least a MySQL database to store data. If you don't want to use an existing database, 
please create a new database. Write down the database name the user name and password to access the 
database.
	
   To create a database and a user, please use the following SQL commands. Replace `[NAME]` with the name 
of the database you want to use, `[USER]` with the desired MySQL user name and `[PASSWORD]` with data 
base administrator password.
    
   To create a database use the first command. To create a user and grants the required access rights
use the second command.
    
    ```    
    CREATE DATABASE [NAME];
    GRANT ALL PRIVILEGES ON [NAME].* TO [USER]@localhost IDENTIFIED BY '[PASSWORD]';
    ```

3. Start **sync•gw** by typing into your browser's URL bar `http://[your-domain.tld]/[path to application directory]/sync.php`.
4. Select "Configure **sync•gw**", check settings and click on "Save".

## RoundCube data base connection handler ##
1. Download and unpack all **sync•gw** files and upload all files to your web server directory.
2. Start **sync•gw** by typing into your browser's URL bar `http://[your-domain.tld]/[path to RoundCube root directory]/sync.php`.
3. Select "Configure **sync•gw**", check settings and click on "Save".
4. Download and install **[syncgw_rc plugin](https://plugins.roundcube.net/#/packages/toteph42/syncgw-rc)**
RoundCube plugin and enable all datastores you want to synchronize in "Settings" menu.
5. If you want to use **calendars**, please install this **[calendar plugin](https://plugins.roundcube.net/#/packages/toteph42/calendar)**. This package is a fork of the original **kolab/calendar** plugin. Unfortunately I found no way to report bugs to the maintainer. Therefore I created 
this fork and fixed the bugs myself.
6. If you want to use **todos** then you need to install **[tasklist plugin](https://plugins.roundcube.net/#/packages/kolab/tasklist)**.
7. If you want to use **notes**, then please install **[ddnotes plugin](https://plugins.roundcube.net/#/packages/dondominio/ddnotes)**.

## MyApp data base connection handler
1. Download and unpack all **sync•gw** files and upload all files to your web server directory.
2. Start **sync•gw** by typing into your browser's URL bar `http://[your-domain.tld]/[path to RoundCube root directory]/sync.php`.
3. Select "Configure **sync•gw**", check settings and click on "Save".

[Go back](https://github.com/toteph42/syncgw/)
