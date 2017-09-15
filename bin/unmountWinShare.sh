#!/bin/sh

#
# un-mounts a Windows share
#

DIR=$1
sudo -u apache sudo umount $DIR 

