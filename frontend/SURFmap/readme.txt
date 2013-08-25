SURFmap -- A Network Monitoring Tool Based on the Google Maps API

Version:    3.1.1
Author:     Rick Hofstede, University of Twente <r.j.hofstede@utwente.nl>

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
check 2.5; for installation verification, check 2.6):

2.1) Requirements & dependencies

- Linux or *BSD system, having the following installed:
    * NfSen
    * PHP 5.2.4 or newer
    * PHP cURL module
    * PHP mbstring module
    * PHP PDO SQLite3 module

- INVEA-TECH's FlowMon Probe (version >= 5.0) (http://www.invea-tech.com/products-and-services/flowmon/flowmon-probes)
- INVEA-TECH's FlowMon Collector (version >= 5.0) (http://www.invea-tech.com/products-and-services/flowmon/flowmon-collectors)

2.2) Automated tar ball installation (latest stable, recommended)

- Download installation script:
    $ wget http://sourceforge.net/projects/surfmap/files/install.sh/download
    $ chmod +x install.sh

- Install plugin:
    $ ./install.sh
    $ sudo /data/nfsen/bin/nfsen reload (path might differ, depending on your setup)

2.3) Manual tar ball installation (latest stable)

- Download tar ball from SourceForge repository:
    $ wget http://downloads.sourceforge.net/project/surfmap/source/SURFmap_v3.1.tar.gz

- Download MaxMind GeoLite City database:
    $ wget http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz
    
- Download MaxMind GeoLite City (IPv6) database:
    $ wget http://geolite.maxmind.com/download/geoip/database/GeoLiteCityv6-beta/GeoLiteCityv6.dat.gz

- Unpack installation package:
    $ tar zxf SURFmap_v3.1.tar.gz --directory=.
    
- Install plugin files:
    $ cp -r SURFmap/frontend/* /var/www/nfsen/plugins/
        (path might differ, depending on your setup)
    $ cp -r SURFmap/backend/* /data/nfsen/plugins/
        (path might differ, depending on your setup)
    $ gunzip -c GeoLiteCity.dat.gz > /var/www/nfsen/plugins/SURFmap/lib/MaxMind/GeoLiteCity.dat
        (path might differ, depending on your setup)
    $ gunzip -c GeoLiteCityv6.dat.gz > /var/www/nfsen/plugins/SURFmap/lib/MaxMind/GeoLiteCityv6.dat
        (path might differ, depending on your setup)

- Configure plugin (config.php):
    $ vi /var/www/nfsen/plugins/SURFmap/config.php
        $config['nfsen_config']="/data/nfsen/etc/nfsen.conf"; (path might differ, depending on your setup)

    -> Get geolocation information for your setup
        http://[your machine IP]/nfsen/plugins/SURFmap/setup/retrievelocation.php

    -> Update $config['map_center] and $config['internal_domains'] in config.php

- Enable plugin:
    $ vi /data/nfsen/etc/nfsen.conf (path might differ, depending on your setup)
        [ 'live', 'SURFmap' ],

- Check file and directory permissions:
    - The backend directory (e.g. /data/nfsen/plugins/SURFmap) should (recursively) be owned by the user configured as $USER and group $WWWGROUP in nfsen.conf
    - The frontend directory (e.g. /var/www/nfsen/plugins/SURFmap) should (recursively) be owned by the group $WWWGROUP in nfsen.conf

- Start plugin:
    $ sudo /etc/init.d/nfsen reload

2.4) SVN trunk installation (latest development version)
    $ wget http://svn.code.sf.net/p/surfmap/code/trunk/setup/scripts/install-svn-trunk.sh
    $ chmod +x install-svn-trunk.sh
    $ ./install-svn-trunk.sh

2.5) Upgrading existing installation

When upgrading your SURFmap installation to a newer version, keep in mind that the 
configuration file (config.php) is not always compatible between the versions. It's 
therefore very important to update the settings in the configuration file of the 
version you're upgrading to. Regarding the upgrade, you could use either of the 
installation methods discussed above. In case you're using a method that's based 
on an installation script (i.e. 'automated tar ball installation' (2.2) or 'SVN trunk 
installation' (2.4)) the scripts will automatically archive your existing SURFmap 
installation, including the configuration file. If you're doing a manual 
installation/upgrade, keep in mind to archive your old installation yourself.

Besides backing up the configuration file, you can save the contents of the GeoCoder 
cache database. In most cases, this will considerably speed up the flow record 
visualization after upgrading.

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
