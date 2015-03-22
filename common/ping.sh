#!/bin/sh
 PING="/bin/ping"
 ADDR=$1
 DATA=`$PING -c5 -i 0.2 $ADDR -q -W 4`
 LOSS=`echo $DATA | awk '{print $18 }' | tr -d %`
 ERRORS=`echo $DATA | awk '{print $19 }' | tr -d %`
 if [ $ERRORS = "errors," ]
 then
               LOSS=`echo $DATA | awk '{print $20 }' | tr -d %`
 fi
 echo $LOSS
 if [ $LOSS = 100 ];
 then
    echo 0
    echo ",$ADDR," >> /tmp/blacklist.snmp
 else
#   echo $DATA | awk -F/ '{print $5 }' | cut -f 1 -d '.'
    echo $DATA | awk -F/ '{print $5 }'
 fi
