#!/bin/sh
#
# Simple script to install SURFmap plugin.
#
# $Id:$
#

SNAPSHOT=SURFmap_20111024.zip
GEO_DB=GeoLiteCity.dat.gz
NFSEN_DIR=/data/nfsen
SURFMAP_DIR=/var/www/shtml/nfsen
SURFMAP_CFG=${SURFMAP_DIR}/SURFmap/config.php
MYLOC_CFG=/tmp/mylocation.txt
MYIP=`curl -s http://www.whatismyip.org || echo 195.113.224.158`

stripsed () {
	str="${1//\//\\/}"		# replace sed special characters
	str="${str/[/\[}"		# replace sed special characters
	str="${str/]/\]}"		# replace sed special characters
}

# download files from Internet
if [ ! -f  ${SNAPSHOT} ]; then
	echo "Downloading SURFmap - http://surfmap.sf.net/"
	wget http://downloads.sourceforge.net/project/surfmap/source/${SNAPSHOT}
fi

if [ ! -f  ${GEO_DB} ]; then
	echo "Downloading Geo Database - http://geolite.maxmind.com"
	wget http://geolite.maxmind.com/download/geoip/database/${GEO_DB}
fi

# remove old SURFmap version
echo "Removing old SURFmap installation from ${SURFMAP_DIR}"
rm -rf ${SURFMAP_DIR}/SURFmap

# unzip SURFmap plugin
echo "Installing new SURFmap plugin to ${SURFMAP_DIR}"
unzip -q -X ${SNAPSHOT} -d /tmp/
mv /tmp/SURFmap_* ${SURFMAP_DIR}/SURFmap

# gunzip GeoLocation database
echo "Installing MaxMing GeoDatabase to ${SURFMAP_DIR}/MaxMind"
gunzip -c ${GEO_DB} > ${SURFMAP_DIR}/SURFmap/MaxMind/`basename ${GEO_DB} .gz`

# get my location
echo "GeoLocating public IP address ${MYIP}"
php -f mylocation.php ${SURFMAP_DIR}/SURFmap/MaxMind ${MYIP} > ${MYLOC_CFG}
. ${MYLOC_CFG}

# fill my location in SURFmap configuration file
OLDENTRY=`grep MAP_CENTER ${SURFMAP_CFG} | cut -d\" -f2`
sed -i "s/${OLDENTRY}/${MAP_CENTER}/g" ${SURFMAP_CFG}

OLDENTRY=`grep INTERNAL_DOMAINS_COUNTRY ${SURFMAP_CFG} | cut -d\" -f2`
sed -i "s/${OLDENTRY}/${INTERNAL_DOMAINS_COUNTRY}/g" ${SURFMAP_CFG}

OLDENTRY=`grep INTERNAL_DOMAINS_REGION ${SURFMAP_CFG} | cut -d\" -f2`
sed -i "s/${OLDENTRY}/${INTERNAL_DOMAINS_REGION}/g" ${SURFMAP_CFG}

OLDENTRY=`grep INTERNAL_DOMAINS_CITY ${SURFMAP_CFG} | cut -d\" -f2`
sed -i "s/${OLDENTRY}/${INTERNAL_DOMAINS_CITY}/g" ${SURFMAP_CFG}

# set missing parameters in SURFmap configuration file
stripsed `grep COMMSOCKET ${SURFMAP_CFG} | cut -d\" -f2`
OLDENTRY=${str}
stripsed ${NFSEN_DIR}/var/run/nfsen.comm	
NEWENTRY=${str}
sed -i "s/${OLDENTRY}/${NEWENTRY}/g" ${SURFMAP_CFG}

sed -i "s/router_name/p3000/g" ${SURFMAP_CFG}

OLDENTRY=`grep -m1 GEOLOCATION_DB ${SURFMAP_CFG} | cut -d\" -f2`
sed -i "s/${OLDENTRY}/MaxMind/g" ${SURFMAP_CFG}

stripsed `grep MAXMIND_PATH ${SURFMAP_CFG} | cut -d\" -f2`
OLDENTRY=${str}
stripsed ${SURFMAP_DIR}/SURFmap/MaxMind/`basename ${GEO_DB} .gz`
NEWENTRY=${str}
sed -i "s/${OLDENTRY}/${NEWENTRY}/g" ${SURFMAP_CFG}

echo "Non-proceeded tasks backend/frontend configuration, nfsen restart"

rm -rf ${MYLOC_CFG}

