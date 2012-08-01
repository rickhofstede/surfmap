#!/bin/sh
#
# Simple script to install SURFmap plugin from SVN.
#
# Copyright (C) 2012 INVEA-TECH a.s.
# Author(s): 	Pavel Celeda <celeda@invea-tech.com>
#				Rick Hofstede <r.j.hofstede@utwente.nl>
#
# LICENSE TERMS - 3-clause BSD license
#
# $Id$
#

echo "SURFmap SVN installation script"
echo "-------------------------------"

echo "Removing previous SVN trunk snapshot"
rm -rf SURFmap SURFmap_v*.tar.gz install.sh

if [ ! "$(which svn)" ]; then
	echo "Subversion (SVN) is not installed on your system. Install it first, or download the latest stable version of SURFmap from http://surfmap.sf.net"
	exit 1
fi

echo "Exporting SVN trunk snapshot"
svn export svn://svn.code.sf.net/p/surfmap/code/trunk SURFmap

echo "Updating install script for SVN install"
cp SURFmap/install.sh .
SURFMAP_VER=$(cat SURFmap/frontend/SURFmap/index.php | grep -m1 \$version | sed 's/.*"v//; s/ .*//')
sed -i "s/SURFMAP_VER=.*/SURFMAP_VER=${SURFMAP_VER}/g" install.sh

echo "Creating SURFmap SVN tar ball"
tar -czf SURFmap_v${SURFMAP_VER}.tar.gz SURFmap

echo "Launching installation script ..."
./install.sh

