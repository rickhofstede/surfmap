#!/bin/sh
#
# Script for installing SURFmap from Git master repository.
#
# Author(s): 	Rick Hofstede   <r.j.hofstede@alumnus.utwente.nl>
#
# LICENSE TERMS - 3-clause BSD license
#

err () {
    printf "ERROR: ${*}\n"
    exit 1
}

echo "SURFmap Git installation script"
echo "-------------------------------"

if [ ! "$(which git)" ]; then
	err "Git is not installed on your system. Install it first, or download the latest stable version of SURFmap from https://github.com/rickhofstede/surfmap/releases"
fi

echo "Removing previous Git clone"
rm -rf SURFmap SURFmap_v*.tar.gz install.sh

echo "Cloning Git repository"
git clone https://github.com/rickhofstede/surfmap.git SURFmap

if [ ! -f SURFmap/install.sh ]; then
    err "Something went wrong while cloning SURFmap!"
fi

echo "Updating installation script for Git installation"
cp SURFmap/install.sh .

SURFMAP_VER=$(cat SURFmap/frontend/SURFmap/version.php | grep -m1 \$version | awk '{print $3}' |  cut -d"\"" -f2)
sed -i.tmp "s/SURFMAP_VER=.*/SURFMAP_VER=${SURFMAP_VER}/g" install.sh

echo "Creating Git master tar ball"
tar czf SURFmap_v${SURFMAP_VER}.tar.gz SURFmap

echo "Launching installation script ..."
./install.sh
