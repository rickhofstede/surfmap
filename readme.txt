SURFmap -- A Network Monitoring Tool Based on the Google Maps API

Version: v2.2
Author: Rick Hofstede, University of Twente <r.j.hofstede@utwente.nl>

--

The purpose of this readme is to provide a quick start guide for installation and configuration of SURFmap for NfSen. More 
details and in-depth motivations of concepts etc., can be found in the SURFmap manual.

1) Introduction

SURFmap is a network monitoring tool based on the Google Maps API and is available as a plugin for NfSen. It adds a geographical 
dimension to network traffic by geolocating IP addresses of end hosts. For more details, the following resources are available:
	- [Website] http://surfmap.sf.net
	- [Mailing list] surfmap-discuss@lists.sourceforge.net

2) Installation instructions

SURFmap can be installed in a variety of ways (for notes on a version upgrade, please check 2.4):

2.1) Automated tar ball installation (latest stable, recommended)

- Create download directory:
	$ mkdir -p ~/surfmap
	$ cd ~/surfmap

- Download installation script
	$ wget http://sourceforge.net/projects/surfmap/files/install.sh/download
	$ chmod +x install.sh

- Install plugin
	$ ./install.sh
	$ sudo /data/nfsen/bin/nfsen reload (this path might differ, based your setup)

2.2) Manual tar ball installation (latest stable)

- Create download directory:
	$ mkdir -p ~/surfmap
	$ cd ~/surfmap

- Download tar ball from SourceForge repository:
	$ wget http://downloads.sourceforge.net/project/surfmap/source/SURFmap_v2.2.tar.gz

- Download MaxMind GeoLite City database:
	$ wget http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz

- Unpack installation packages:
	$ tar zxf SURFmap_v2.2.tar.gz --directory=/var/www/nfsen/plugins/ (this path might differ, based on your setup)
	$ gunzip -c GeoLiteCity.dat.gz > /var/www/nfsen/plugins/SURFmap/MaxMind/GeoLiteCity.dat

- Install backend and frontend plugin files:
	$ cd /var/www/nfsen/plugins/
	$ cp SURFmap/setup/backend/SURFmap.pm /data/nfsen/plugins/SURFmap.pm (the last path might differ, based on your setup)
	$ cp SURFmap/setup/frontend/SURFmap.php .

- Configure plugin (config.php):
	$ vi SURFmap/config.php
		$NFSEN_CONF="/data/nfsen/etc/nfsen.conf"; (this path might differ, based on your setup)

	-> Get geolocation information for your setup
		http://[your machine IP]/nfsen/plugins/SURFmap/setup/configurationchecker.php

	-> Update $MAP_CENTER, $INTERNAL_DOMAINS_COUNTRY, $INTERNAL_DOMAINS_REGION, $INTERNAL_DOMAINS_CITY in config.php,,

- Enable plugin:
	$ vim /data/nfsen/etc/nfsen.conf (this path might differ, based on your setup)
		[ 'live', 'SURFmap' ],

- Start plugin:
	$ sudo /etc/init.d/nfsen reload

2.3) SVN trunk installation (latest development version)
	$ wget http://svn.code.sf.net/p/surfmap/code/trunk/setup/scripts/install-svn-trunk.sh
	$ chmod +x install-svn-trunk.sh
	$ ./install-svn-trunk.sh

2.4) Upgrading existing installation

When upgrading your SURFmap installation to a newer version, keep in mind that the configuration file (config.php) is
not always compatible between the versions. It's there very important to update the settings in the configuration file
of the version you're upgrading to. Regarding the upgrade, you could use either of the installation methods discussed
above. In case you're using a method that's based on an installation script (i.e. 'automated tar ball installation' (2.1)
or 'SVN trunk installation' (2.3)) the scripts will automatically archive your existing SURFmap installation, including
the configuration file. If you're doing a manual installation/upgrade, keep in mind to archive your old installation
yourself.

3) Using SURFmap

In case it's the first time you run SURFmap after installation/upgrade, please restart your Web browser and clear its
cache (cookies, recent history, cache files, …). After that, you can open NfSen, navigate to the 'Plugins' tab and
choose 'SURFmap'. You should never call SURFmap directly by its URL, since it will not be able to communicate properly
with NfSen.

4) Support

For any questions or general technical support issues, please feel free to send an e-mail to <r.j.hofstede@utwente.nl>
or to join the SURFmap mailing list: surfmap-discuss@lists.sourceforge.net
