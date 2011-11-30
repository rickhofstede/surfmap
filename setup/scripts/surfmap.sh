#!/bin/sh
#
# Simple script to install SURFmap plugin.
#
# Copyright (C) 2011 INVEA-TECH a.s.
# Author(s): Pavel CELEDA <celeda@invea-tech.com>
#
# LICENSE TERMS - 3-clause BSD license
#
# $Id:$
#

SURFMAP_REL=SURFmap_v2.2.tar.gz
GEO_DB=GeoLiteCity.dat.gz

err () {
	echo "ERROR : ${*}"
	exit 1
}

echo "SURFmap installation script"
echo "---------------------------"

# discover NfSen configuration
NFSEN_VARFILE=/tmp/nfsen-tmp.conf
if [ ! -n "$(ps axo command | grep [n]fsend | grep -v nfsend-comm)" ]; then
	err "NfSen - nfsend not running. Can not detect nfsen.conf location!"
fi

NFSEN_LIBEXECDIR=$(cat $(ps axo command= | grep [n]fsend | grep -v nfsend-comm | cut -d' ' -f3) | grep libexec | cut -d'"' -f2)
NFSEN_CONF=$(cat ${NFSEN_LIBEXECDIR}/NfConf.pm | grep \/nfsen.conf | cut -d'"' -f2)

# parse nfsen.conf file
cat ${NFSEN_CONF} | grep -v \# | egrep '\$BASEDIR|\$BINDIR|\$HTMLDIR|\$FRONTEND_PLUGINDIR|\$BACKEND_PLUGINDIR' | tr -d ';' | tr -d ' ' | cut -c2- > ${NFSEN_VARFILE}
. ${NFSEN_VARFILE}
rm -rf ${NFSEN_VARFILE}

SURFMAP_CONF=${FRONTEND_PLUGINDIR}/SURFmap/config.php

# download files from Internet
if [ ! -f  ${SURFMAP_REL} ]; then
	echo "Downloading SURFmap plugin tar ball - http://surfmap.sf.net/"
	wget http://downloads.sourceforge.net/project/surfmap/source/${SURFMAP_REL}
fi

if [ ! -f  ${GEO_DB} ]; then
	echo "Downloading MaxMind Geo Database - http://geolite.maxmind.com"
	wget http://geolite.maxmind.com/download/geoip/database/${GEO_DB}
fi

# backup old SURFmap installation
if [ -d ${FRONTEND_PLUGINDIR}/SURFmap ]; then
	SURFMAP_BACKUPDIR=${FRONTEND_PLUGINDIR}/SURFmap-$(date +%s)
	echo "Backuping old SURFmap installation to ${SURFMAP_BACKUPDIR}"
	mv ${FRONTEND_PLUGINDIR}/SURFmap ${SURFMAP_BACKUPDIR}
fi

# unpack SURFmap plugin
echo "Installing SURFmap plugin to ${FRONTEND_PLUGINDIR}/SURFmap"
tar zxfp ${SURFMAP_REL} --directory=${FRONTEND_PLUGINDIR}

# unpack GeoLocation database
echo "Installing MaxMind Geo Database to ${FRONTEND_PLUGINDIR}/SURFmap/MaxMind"
gunzip -c ${GEO_DB} > ${FRONTEND_PLUGINDIR}/SURFmap/MaxMind/$(basename ${GEO_DB} .gz)

# update config.php. We use ',' as sed delimiter instead of escaping all '/' to '\/'.
echo "Updating plugin configuration file ${SURFMAP_CONF}"
sed -i "s,$(grep NFSEN_CONF ${SURFMAP_CONF} | cut -d'"' -f2),${NFSEN_CONF},g" ${SURFMAP_CONF}

# get my location information
echo -n "Geocoding plugin location - "
cd ${FRONTEND_PLUGINDIR}/SURFmap/setup
MY_LOC=$(php configurationchecker.php | grep configdata | cut -d'>' -f2 | cut -d'<' -f1)
echo "${MY_LOC}"

while [ "${MY_LOC}" == "(Unknown),(Unknown),(Unknown),," ]; do
	MY_LOC=$(php configurationchecker.php | grep configdata | cut -d'>' -f2 | cut -d'<' -f1)
	echo "Geocoding plugin location - ${MY_LOC}"
done

cd - > /dev/null

# fill my location in plugin configuration file
OLDENTRY=$(grep INTERNAL_DOMAINS_COUNTRY ${SURFMAP_CONF} | cut -d'"' -f2)
sed -i "s/${OLDENTRY}/$(echo ${MY_LOC} | cut -d',' -f1)/g" ${SURFMAP_CONF}

OLDENTRY=$(grep INTERNAL_DOMAINS_REGION ${SURFMAP_CONF} | cut -d'"' -f2)
sed -i "s/${OLDENTRY}/$(echo ${MY_LOC} | cut -d',' -f2)/g" ${SURFMAP_CONF}

OLDENTRY=$(grep INTERNAL_DOMAINS_CITY ${SURFMAP_CONF} | cut -d'"' -f2)
sed -i "s/${OLDENTRY}/$(echo ${MY_LOC} | cut -d',' -f3)/g" ${SURFMAP_CONF}

OLDENTRY=$(grep MAP_CENTER ${SURFMAP_CONF} | cut -d'"' -f2)
sed -i "s/${OLDENTRY}/$(echo ${MY_LOC} | cut -d',' -f4-)/g" ${SURFMAP_CONF}

# install backend and frontend plugin files
echo "Installing backend and frontend plugin files - SURFmap.pm, SURFmap.php"
cp ${FRONTEND_PLUGINDIR}/SURFmap/setup/backend/SURFmap.pm ${BACKEND_PLUGINDIR}
cp ${FRONTEND_PLUGINDIR}/SURFmap/setup/frontend/SURFmap.php ${FRONTEND_PLUGINDIR}

# enable plugin
echo "Updating NfSen configuration file ${NFSEN_CONF}"
sed -i "/SURFmap/d" ${NFSEN_CONF}

OLDENTRY=$(grep \@plugins ${NFSEN_CONF})
sed -i "s/${OLDENTRY}/${OLDENTRY}\n    \[ 'live', 'SURFmap' ],/g" ${NFSEN_CONF}

# restart/reload NfSen
echo "Please restart/reload NfSen to finish installation e.g. sudo ${BINDIR}/nfsen reload"

