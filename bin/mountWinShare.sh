#!/bin/sh

#
# Mounts a Windows share
#

DIR=$1
DIQA_IMPORT_PATH=/opt/DIQA/$DIR
UNCPATH=$2
USER=$3
PW=$4
DOMAIN=$5

if [ ! -e $DIQA_IMPORT_PATH ]; then
	sudo mkdir -p $DIQA_IMPORT_PATH
fi

if [ !  -z  $DOMAIN  ]; then

	sudo -u apache sudo mount -t cifs "$UNCPATH" $DIQA_IMPORT_PATH -o user=$USER,password=$PW,domain=$DOMAIN
else
	sudo -u apache sudo mount -t cifs "$UNCPATH" $DIQA_IMPORT_PATH -o user=$USER,password=$PW
fi
