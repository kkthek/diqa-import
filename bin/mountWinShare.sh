#!/bin/sh

#
# Mounts a Windows share
#

DIQA_IMPORT_PATH=/opt/DIQA/freigabe
UNCPATH=$1
USER=$2
PW=$3
DOMAIN=$4

if [ ! -e $DIQA_IMPORT_PATH ]; then
	sudo mkdir -p $DIQA_IMPORT_PATH
fi

if [ !  -z  $DOMAIN  ]; then

	sudo -u apache sudo mount -t cifs $UNCPATH $DIQA_IMPORT_PATH -o user=$USER,password=$PW,domain=$DOMAIN
else
	sudo -u apache sudo mount -t cifs $UNCPATH $DIQA_IMPORT_PATH -o user=$USER,password=$PW
fi
