#!/bin/sh
#
# Simple script to install SURFmap plugin.
#
# Copyright (C) 2013 INVEA-TECH a.s.
# Author(s): 	Rick Hofstede   <r.j.hofstede@utwente.nl>
#               Pavel Celeda    <celeda@invea-tech.com>
#
# LICENSE TERMS - 3-clause BSD license
#
# $Id$
#

SURFMAP_VER=3.2
SURFMAP_REL=SURFmap_v${SURFMAP_VER}.tar.gz
SURFMAP_TMP=SURFmap_tmp
GEO_DB=GeoLiteCity.dat.gz
GEOv6_DB=GeoLiteCityv6.dat.gz

err () {
	printf "ERROR: ${*}\n"
	exit 1
}

err_line () {
    echo "-----"
	printf "ERROR: ${*}\n"
	exit 1
}

echo "SURFmap installation script"
echo "---------------------------"

# Discover NfSen configuration
NFSEN_VARFILE=/tmp/nfsen-tmp.conf
if [ ! -n "$(ps axo command | grep [n]fsend | grep -v nfsend-comm)" ]; then
	err "NfSen - nfsend not running. Cannot detect nfsen.conf location!"
fi

NFSEN_LIBEXECDIR=$(cat $(ps axo command= | grep [n]fsend | grep -v nfsend-comm | awk '{print $NF}') | grep libexec | cut -d'"' -f2 | head -n 1)
NFSEN_CONF=$(cat ${NFSEN_LIBEXECDIR}/NfConf.pm | grep \/nfsen.conf | cut -d'"' -f2)

# Parse nfsen.conf file
cat ${NFSEN_CONF} | grep -v \# | egrep '\$BASEDIR|\$BINDIR|\$LIBEXECDIR|\$HTMLDIR|\$FRONTEND_PLUGINDIR|\$BACKEND_PLUGINDIR|\$WWWGROUP|\$WWWUSER|\$USER' | tr -d ';' | tr -d ' ' | cut -c2- | sed 's,/",",g' > ${NFSEN_VARFILE}
. ${NFSEN_VARFILE}
rm -rf ${NFSEN_VARFILE}

SURFMAP_CONF=${FRONTEND_PLUGINDIR}/SURFmap/config.php

# Check permissions to install SURFmap plugin - you must be ${USER} or root
if [ "$(id -u)" != "$(id -u ${USER})" ] && [ "$(id -u)" != "0" ]; then
	err "You do not have sufficient permissions to install SURFmap on this machine!"
fi

if [ "$(id -u)" = "$(id -u ${USER})" ]; then
	WWWUSER=${USER}		# we are installing as normal user
fi

# Check available PHP modules
PHP_CURL=$(php -m | grep 'curl' 2> /dev/null)
PHP_MBSTRING=$(php -m | grep 'mbstring' 2> /dev/null)
PHP_PDOSQLITE=$(php -m | grep 'pdo_sqlite$' 2> /dev/null) # The dollar-sign ($) makes sure that 'pdo_sqlite2' is not accepted

if [ "$PHP_CURL" != "curl" ]; then
    err "The PHP 'cURL' module is missing. Try to install the following packages (depending on your OS): \n\t* php5-curl (Ubuntu/Debian)\n\t* php-curl (RHEL/CentOS, using EPEL) \n\t* Include 'CURL' in the php5-extensions port (FreeBSD)\nDon't forget to restart your Web server after installing the package"
elif [ "$PHP_MBSTRING" != "mbstring" ]; then
    err "The PHP 'mbstring' module is missing. Try to install the following packages (depending on your OS): \n\t* php-mbstring (RHEL/CentOS, using EPEL) \n\t* Include 'MBSTRING' in the php5-extensions port (FreeBSD)\nDon't forget to restart your Web server after installing the package"
elif [ "$PHP_PDOSQLITE" != "pdo_sqlite" ]; then
    err "The PHP PDO SQLite v3 module is missing. Try to install the following packages (depending on your OS): \n\t* php5-sqlite (Ubuntu/Debian)\n\t* php-pdo (RHEL/CentOS, using EPEL) \n\t* Include 'PDO_SQLITE' in the php5-extensions port (FreeBSD)\nDon't forget to restart your Web server after installing the package"
fi

# Download files from Web
if [ $(uname) = "FreeBSD" ]; then
    RETRIEVE_TOOL="fetch"
else
    RETRIEVE_TOOL="wget"
fi

if [ ! -f  ${SURFMAP_REL} ]; then
	echo "Downloading SURFmap plugin tar ball - http://surfmap.sf.net/"
	${RETRIEVE_TOOL} http://downloads.sourceforge.net/project/surfmap/source/${SURFMAP_REL}
fi

if [ ! -f  ${GEO_DB} ]; then
	echo "Downloading MaxMind GeoLite City database - http://geolite.maxmind.com"
	${RETRIEVE_TOOL} http://geolite.maxmind.com/download/geoip/database/${GEO_DB}
fi

if [ ! -f  ${GEOv6_DB} ]; then
	echo "Downloading MaxMind GeoLite City (IPv6) database - http://geolite.maxmind.com"
	${RETRIEVE_TOOL} http://geolite.maxmind.com/download/geoip/database/GeoLiteCityv6-beta/${GEOv6_DB}
fi

# Backup old SURFmap installation
if [ -d ${FRONTEND_PLUGINDIR}/SURFmap ]; then
	SURFMAP_BACKUPDIR=${FRONTEND_PLUGINDIR}/SURFmap-$(date +%s)
	echo "Backuping old SURFmap installation to ${SURFMAP_BACKUPDIR}"
	mv ${FRONTEND_PLUGINDIR}/SURFmap ${SURFMAP_BACKUPDIR}
fi

# Unpack SURFmap
echo "Unpacking files"
tar zxf ${SURFMAP_REL} --directory=.
mv SURFmap ${SURFMAP_TMP}

# Install backend and frontend plugin files
echo "Installing SURFmap ${SURFMAP_VER} to ${FRONTEND_PLUGINDIR}/SURFmap"
cp -r ./${SURFMAP_TMP}/backend/* ${BACKEND_PLUGINDIR}
cp -r ./${SURFMAP_TMP}/frontend/* ${FRONTEND_PLUGINDIR}

# Unpack geoLocation databases
MAXMIND_PATH=${FRONTEND_PLUGINDIR}/SURFmap/lib/MaxMind
echo "Installing MaxMind GeoLite City database to ${MAXMIND_PATH}"
gunzip -c ${GEO_DB} > ${MAXMIND_PATH}/$(basename ${GEO_DB} .gz)
if [ $? != 0 ]; then
    err_line "The MaxMind GeoLite City database has not been downloaded successfully. You may have been graylisted by MaxMind because of subsequent download retries. Please try again later"
fi

echo "Installing MaxMind GeoLite City (IPv6) database to ${MAXMIND_PATH}"
gunzip -c ${GEOv6_DB} > ${MAXMIND_PATH}/$(basename ${GEOv6_DB} .gz)
if [ $? != 0 ]; then
    err_line "The MaxMind GeoLite City (IPv6) database has not been downloaded successfully. You may have been graylisted by MaxMind because of subsequent download retries. Please try again later"
fi

# Deleting temporary files
rm -rf ${SURFMAP_TMP}
rm -rf ${GEO_DB}
rm -rf ${GEOv6_DB}

# Set permissions - owner and group
echo "Setting plugin file permissions - user \"${USER}\" and group \"${WWWGROUP}\""
chown -R ${USER}:${WWWGROUP} ${FRONTEND_PLUGINDIR}/SURFmap*
chown -R ${USER}:${WWWGROUP} ${BACKEND_PLUGINDIR}/SURFmap*
chmod -R g+w ${FRONTEND_PLUGINDIR}/SURFmap/db

# Update plugin configuration file - config.php. We use ',' as sed delimiter instead of escaping all '/' to '\/'.
echo "Updating plugin configuration file ${SURFMAP_CONF}"
OLD_NFSEN_CONF=$(grep nfsen_config ${SURFMAP_CONF} | awk '{ START=index($0,"="); LENGTH=length($0)-START; print substr($0,START,LENGTH) }' | cut -d"\"" -f2)
sed -i.tmp "s,${OLD_NFSEN_CONF},${NFSEN_CONF},g" ${SURFMAP_CONF}

# Check for proxy and update config.php accordingly
PROXY=$(env | grep -i http_proxy | awk '{ START=index($0,"=")+1; print substr($0,START) }')
if [ "$PROXY" != "" ]; then
    echo "HTTP proxy detected"
    sed -i.tmp "s,config\['use_proxy'\] = 0,config\['use_proxy'\] = 1,g" ${SURFMAP_CONF}
    
    PROXY_IP_PORT=$(echo ${PROXY} | awk '{ FROM=index($0,"//")+2; print substr($0,FROM) }')
    PROXY_IP=$(echo ${PROXY_IP_PORT} | awk '{ TO=index($0,":")-1; print substr($0,0,TO) }')
    PROXY_PORT=$(echo ${PROXY_IP_PORT} | awk '{ FROM=index($0,":")+1; print substr($0,FROM) }')
    
    OLD_PROXY_IP=$(grep "$config\['proxy_ip'\]" ${SURFMAP_CONF} | cut -d'"' -f2)
    OLD_PROXY_PORT=$(grep "$config\['proxy_port'\]" ${SURFMAP_CONF} | awk '{ FROM=index($0,"=")+2; TO=index($0,";"); print substr($0,FROM,TO-FROM) }')
    
    sed -i.tmp "s,${OLD_PROXY_IP},${PROXY_IP},g" ${SURFMAP_CONF}
    sed -i.tmp "s,${OLD_PROXY_PORT},${PROXY_PORT},g" ${SURFMAP_CONF}
fi

# Get my location information
cd ${FRONTEND_PLUGINDIR}/SURFmap/setup
MY_LOC=$(php retrievelocation.php | grep config_data | cut -d'>' -f2 | cut -d'<' -f1)
echo "Geocoding plugin location - ${MY_LOC}"

cd - > /dev/null

# Fill my location in plugin configuration file
if [ "${MY_LOC}" != "(UNKNOWN),(UNKNOWN),(UNKNOWN),(UNKNOWN),(UNKNOWN)" ]; then
	OLDENTRY=$(sed -e "/$config\['internal_domains'\] = array/,/);/!d" ${SURFMAP_CONF} | grep '=>' | cut -d'"' -f6)
	sed -i.tmp "s/${OLDENTRY}/$(echo ${MY_LOC} | cut -d',' -f1)/g" ${SURFMAP_CONF}

	OLDENTRY=$(sed -e "/$config\['internal_domains'\] = array/,/);/!d" ${SURFMAP_CONF} | grep '=>' | cut -d'"' -f10)
	NEWENTRY=$(echo ${MY_LOC} | cut -d',' -f2)
	if [ "${NEWENTRY}" = "(UNKNOWN)" ]; then
		NEWENTRY=""
	fi
	sed -i.tmp "s/${OLDENTRY}/${NEWENTRY}/g" ${SURFMAP_CONF}

	OLDENTRY=$(sed -e "/$config\['internal_domains'\] = array/,/);/!d" ${SURFMAP_CONF} | grep '=>' | cut -d'"' -f14)
	NEWENTRY=$(echo ${MY_LOC} | cut -d',' -f3)
	if [ "${NEWENTRY}" = "(UNKNOWN)" ]; then
		NEWENTRY=""
	fi
	sed -i.tmp "s/${OLDENTRY}/${NEWENTRY}/g" ${SURFMAP_CONF}

	OLDENTRY=$(grep "$config\['map_center'\]" ${SURFMAP_CONF} | cut -d'"' -f2)
	NEWENTRY=$(echo ${MY_LOC} | cut -d',' -f4,5)
	if [ "${NEWENTRY}" != "(UNKNOWN),(UNKNOWN)" ]; then
		sed -i.tmp "s/${OLDENTRY}/${NEWENTRY}/g" ${SURFMAP_CONF}
	fi
fi

# Enable plugin
echo "Updating NfSen configuration file ${NFSEN_CONF}"

OLDENTRY=$(grep \@plugins ${NFSEN_CONF})

# Check whether we are running Linux of BSD (BSD sed does not support inserting new lines (\n))
if [ $(uname) = "Linux" ]; then
    # Linux
    sed -i.tmp "/SURFmap/d" ${NFSEN_CONF}
    sed -i.tmp "s/${OLDENTRY}/${OLDENTRY}\n    \[ 'live', 'SURFmap' \],/g" ${NFSEN_CONF}
else
    # Something else (we assume *BSD)
    if grep "SURFmap" ${NFSEN_CONF} > /dev/null; then
        echo "Found 'SURFmap' in ${NFSEN_CONF}, assuming it is already configured"
    else
        sed -i.tmp "s/${OLDENTRY}/${OLDENTRY}\ \[ 'live', 'SURFmap' \],/g" ${NFSEN_CONF}
    fi
fi

echo "-----"
echo "Please restart/reload NfSen to finish installation (e.g., sudo ${BINDIR}/nfsen reload)"
