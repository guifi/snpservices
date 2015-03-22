#!/bin/bash
# ping to be executed in paralel, if online, leaves the output at blacklist.ok
ping -c 5  $1 -q -W 4 > /dev/null 2>&1
if [ $? -eq 0 ] then 
  echo "$1" >> /tmp/blacklist.ok 
fi
