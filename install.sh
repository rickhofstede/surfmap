#!/bin/sh
#
# Simple script to install SURFmap plugin.
#
# Copyright (C) 2012 INVEA-TECH a.s.
# Author(s): 	Pavel Celeda <celeda@invea-tech.com>
#				Rick Hofstede <r.j.hofstede@utwente.nl>
#
# LICENSE TERMS - 3-clause BSD license
#
# $Id: $
#

SURFMAP_VER=2.3
SURFMAP_REL=SURFmap_v${SURFMAP_VER}.tar.gz
SURFMAP_TMP=SURFmap_tmp
GEO_DB=GeoLiteCity.dat.gz

err () {
	echo "ERROR: ${*}"
	exit 1
}

echo "SURFmap installation script"
echo "---------------------------"

# Discover NfSen configuration
NFSEN_VARFILE=/tmp/nfsen-tmp.conf
if [ ! -n "$(ps axo command | grep [n]fsend | grep -v nfsend-comm)" ]; then
	err "NfSen - nfsend not running. Can not detect nfsen.conf location!"
fi

NFSEN_LIBEXECDIR=$(cat $(ps axo command= | grep [n]fsend | grep -v nfsend-comm | cut -d' ' -f3) | grep libexec | cut -d'"' -f2)
NFSEN_CONF=$(cat ${NFSEN_LIBEXECDIR}/NfConf.pm | grep \/nfsen.conf | cut -d'"' -f2)

# Parse nfsen.conf file
cat ${NFSEN_CONF} | grep -v \# | egrep '\$BASEDIR|\$BINDIR|\$HTMLDIR|\$FRONTEND_PLUGINDIR|\$BACKEND_PLUGINDIR|\$WWWGROUP|\$WWWUSER|\$USER' | tr -d ';' | tr -d ' ' | cut -c2- | sed 's,/",",g' > ${NFSEN_VARFILE}
. ${NFSEN_VARFILE}
rm -rf ${NFSEN_VARFILE}

SURFMAP_CONF=${FRONTEND_PLUGINDIR}/SURFmap/config.php

# Check permissions to install SURFmap plugin - you must be ${USER} or root
if [ "$(id -u)" != "$(id -u ${USER})" ] && [ "$(id -u)" != "0" ]; then
	err "You do not have sufficient permissions to install SURFmap on this server!"
fi

if [ "$(id -u)" = "$(id -u ${USER})" ]; then
	WWWUSER=${USER}		# we are installing as normal user
fi

# Download files from Web
if [ ! -f  ${SURFMAP_REL} ]; then
	echo "Downloading SURFmap plugin tar ball - http://surfmap.sf.net/"
	wget http://downloads.sourceforge.net/project/surfmap/source/${SURFMAP_REL}
fi

if [ ! -f  ${GEO_DB} ]; then
	echo "Downloading MaxMind GeoLite City database - http://geolite.maxmind.com"
	wget http://geolite.maxmind.com/download/geoip/database/${GEO_DB}
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

# Unpack GeoLocation database
echo "Installing MaxMind GeoLite City database to ${FRONTEND_PLUGINDIR}/SURFmap/MaxMind"
gunzip -c ${GEO_DB} > ${FRONTEND_PLUGINDIR}/SURFmap/MaxMind/$(basename ${GEO_DB} .gz)

# Deleting temporary files
rm -rf ${SURFMAP_TMP}
rm -rf ${GEO_DB}

# Set permissions - owner and group
echo "Setting plugin files permissions - user \"${USER}\" and group \"${WWWGROUP}\""
chown -R ${USER}:${WWWGROUP} ${FRONTEND_PLUGINDIR}/SURFmap*
chown -R ${USER}:${WWWGROUP} ${BACKEND_PLUGINDIR}/SURFmap*

# Update plugin configuration file - config.php. We use ',' as sed delimiter instead of escaping all '/' to '\/'.
echo "Updating plugin configuration file ${SURFMAP_CONF}"
sed -i "s,$(grep NFSEN_CONF ${SURFMAP_CONF} | cut -d'"' -f2),${NFSEN_CONF},g" ${SURFMAP_CONF}

# Get my location information
cd ${FRONTEND_PLUGINDIR}/SURFmap/setup

i=0
while true; do
	MY_LOC=$(php configurationchecker.php | grep configdata | cut -d'>' -f2 | cut -d'<' -f1)
	echo "Geocoding plugin location - ${MY_LOC}"

	i=$(( i + 1 ))		# check 5 times before giving up
	if [ ${i} = 5 ] || [ "${MY_LOC}" != "(UNKNOWN),(UNKNOWN),(UNKNOWN),(UNKNOWN),(UNKNOWN)" ]; then
		break
	fi
done

cd - > /dev/null

# Fill my location in plugin configuration file
if [ "${MY_LOC}" != "(UNKNOWN),(UNKNOWN),(UNKNOWN),(UNKNOWN),(UNKNOWN)" ]; then
	OLDENTRY=$(grep INTERNAL_DOMAINS_COUNTRY ${SURFMAP_CONF} | cut -d'"' -f2)
	sed -i "s/${OLDENTRY}/$(echo ${MY_LOC} | cut -d',' -f1)/g" ${SURFMAP_CONF}

	OLDENTRY=$(grep INTERNAL_DOMAINS_REGION ${SURFMAP_CONF} | cut -d'"' -f2)
	sed -i "s/${OLDENTRY}/$(echo ${MY_LOC} | cut -d',' -f2)/g" ${SURFMAP_CONF}

	OLDENTRY=$(grep INTERNAL_DOMAINS_CITY ${SURFMAP_CONF} | cut -d'"' -f2)
	sed -i "s/${OLDENTRY}/$(echo ${MY_LOC} | cut -d',' -f3)/g" ${SURFMAP_CONF}

	OLDENTRY=$(grep MAP_CENTER ${SURFMAP_CONF} | cut -d'"' -f2)
	sed -i "s/${OLDENTRY}/$(echo ${MY_LOC} | cut -d',' -f4-)/g" ${SURFMAP_CONF}
fi

# Enable plugin
echo "Updating NfSen configuration file ${NFSEN_CONF}"
sed -i "/SURFmap/d" ${NFSEN_CONF}

OLDENTRY=$(grep \@plugins ${NFSEN_CONF})
sed -i "s/${OLDENTRY}/${OLDENTRY}\n    \[ 'live', 'SURFmap' ],/g" ${NFSEN_CONF}

echo ""

# Restart/reload NfSen
echo "Please restart/reload NfSen to finish installation (e.g. sudo ${BINDIR}/nfsen reload)"

