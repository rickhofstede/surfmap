SURFmap -- A Network Monitoring Tool Based on the Google Maps API

Version: v2.4
Author: Rick Hofstede, University of Twente <r.j.hofstede@utwente.nl>

--

The purpose of this readme is to provide a quick start guide for installation and 
configuration of SURFmap for NfSen. More details and in-depth motivations of concepts 
etc., can be found in the SURFmap manual.

1) Introduction

SURFmap is a network monitoring tool based on the Google Maps API and is available 
as a plugin for NfSen. It adds a geographical dimension to network traffic by geolocating 
IP addresses of end hosts. For more details, the following resources are available:
	- [Website] http://surfmap.sf.net
	- [Mailing list] surfmap-discuss@lists.sourceforge.net

2) Installation instructions

SURFmap can be installed in a variety of ways (for notes on a version upgrade, 
check 2.4; for installation verification, check 2.5):

2.1) Automated tar ball installation (latest stable, recommended)

- Download installation script:
	$ wget http://downloads.sourceforge.net/project/surfmap/install.sh
	$ chmod +x install.sh

- Install plugin:
	$ ./install.sh
	$ sudo /data/nfsen/bin/nfsen reload (this path might differ, depending on your setup)

2.2) Manual tar ball installation (latest stable)

- Download tar ball from SourceForge repository:
	$ wget http://downloads.sourceforge.net/project/surfmap/source/SURFmap_v2.3.tar.gz

- Download MaxMind GeoLite City database:
	$ wget http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz

- Unpack installation package to temporary directory:
	$ tar zxf SURFmap_v2.3.tar.gz --directory=.
	$ mv SURFmap SURFmap_tmp

- Install plugin files:
	$ cp -r ~/SURFmap_tmp/backend/* /data/nfsen/plugins/ (last path might differ, depending 
		on your setup)
	$ cp -r ~/SURFmap_tmp/frontend/* /data/nfsen/plugins/ (last path might differ, depending
		on your setup)
	$ gunzip -c GeoLiteCity.dat.gz > /var/www/nfsen/plugins/SURFmap/MaxMind/GeoLiteCity.dat
		(path might differ, depending on your setup)

- Configure plugin (config.php):
	$ cd /var/www/nfsen/plugins/SURFmap (this path might differ, depending on your setup)
	$ vi config.php
		$NFSEN_CONF="/data/nfsen/etc/nfsen.conf"; (path might differ, depending on your
			setup)

	-> Get geolocation information for your setup
		http://[your machine IP]/nfsen/plugins/SURFmap/setup/configurationchecker.php

	-> Update $MAP_CENTER and $INTERNAL_DOMAINS in config.php

- Enable plugin:
	$ vi /data/nfsen/etc/nfsen.conf (path might differ, depending on your setup)
		[ 'live', 'SURFmap' ],

- Start plugin:
	$ sudo /data/nfsen/bin/nfsen reload

2.3) SVN trunk installation (latest development version)
	$ wget http://svn.code.sf.net/p/surfmap/code/trunk/install-svn-trunk.sh
	$ chmod +x install-svn-trunk.sh
	$ ./install-svn-trunk.sh

2.4) Upgrading existing installation

When upgrading your SURFmap installation to a newer version, keep in mind that the 
configuration file (config.php) is not always compatible between the versions. It's 
therefore very important to update the settings in the configuration file of the 
version you're upgrading to. Regarding the upgrade, you could use either of the 
installation methods discussed above. In case you're using a method that's based 
on an installation script (i.e. 'automated tar ball installation' (2.1) or 'SVN trunk 
installation' (2.3)) the scripts will automatically archive your existing SURFmap 
installation, including the configuration file. If you're doing a manual 
installation/upgrade, keep in mind to archive your old installation yourself.

Besides backing up the configuration file, you can save the contents of the GeoCoder 
cache database. In most cases, this will considerably speed up the flow record 
visualization after upgrading.

2.5) Installation verification

In order to verify whether SURFmap was configured properly depending on your particular 
system setup, a "Configuration Checker" has been included in the package. It can be 
found at the following location:

http://[your machine IP]/nfsen/plugins/SURFmap/setup/configurationchecker.php

3) Using SURFmap

In case it's the first time you run SURFmap after installation/upgrade, please restart 
your Web browser and clear its cache (cookies, recent history, cache files, …).
After that, you can open NfSen, navigate to the 'Plugins' tab and choose 'SURFmap'. 
You should never call SURFmap directly by its URL, since it will not be able to 
communicate properly with NfSen.

4) Support

For any questions or general technical support issues, please feel free to send an 
e-mail to <r.j.hofstede@utwente.nl> or to join the SURFmap mailing list:
surfmap-discuss@lists.sourceforge.net
