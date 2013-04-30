#!/bin/sh
#
# Simple script to install SURFmap plugin from SVN.
#
# Copyright (C) 2011 INVEA-TECH a.s.
# Author(s): Pavel CELEDA <celeda@invea-tech.com>
#
# LICENSE TERMS - 3-clause BSD license
#
# $Id: install-svn-trunk.sh 101 2011-12-06 12:50:57Z rickhofstede $
#

echo "SURFmap SVN installation script"
echo "-------------------------------"

echo "Removing previous SVN trunk snapshot"
rm -rf SURFmap SURFmap_v*.tar.gz install.sh

echo "Exporting SVN trunk snapshot"
svn export svn://svn.code.sf.net/p/surfmap/code/trunk SURFmap

echo "Updating install script for SVN install"
cp SURFmap/setup/scripts/install.sh .
SURFMAP_VER=$(cat SURFmap/index.php | grep -m1 \$version | sed 's/.*"v//; s/ .*//')
sed -i "s/SURFMAP_VER=.*/SURFMAP_VER=${SURFMAP_VER}/g" install.sh

echo "Creating SURFmap SVN tar ball"
tar -czf SURFmap_v${SURFMAP_VER}.tar.gz SURFmap

echo "Launching installation script ..."
./install.sh

