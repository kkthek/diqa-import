#!/bin/sh
UNCPATH=$1
USER=$2
PW=$3
DOMAIN=$4

if [ ! -e /opt/freigabe ]; then
	sudo mkdir -p /opt/freigabe
fi

if [ !  -z  $DOMAIN  ]; then

	sudo -u apache sudo mount -t cifs $UNCPATH /opt/freigabe/ -o user=$USER,password=$PW,domain=$DOMAIN
else
	sudo -u apache sudo mount -t cifs $UNCPATH /opt/freigabe/ -o user=$USER,password=$PW
fi
