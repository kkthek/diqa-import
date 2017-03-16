#!/bin/sh

UNCPATH=$1
USER=$2
DOMAIN=$3
read -s -p "password: " PW
echo -e ""

if [ !  -z  $DOMAIN  ]; then
	sudo mount -t cifs $UNCPATH /opt/freigabe/ -o user=$USER,password=$PW,domain=$DOMAIN
else
	sudo mount -t cifs $UNCPATH /opt/freigabe/ -o user=$USER,password=$PW
fi