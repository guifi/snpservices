#!/bin/bash

ALIVE=$(ps aux | grep -i 'check_blacklist.php' | grep -v grep)

if [[ -z "$ALIVE" ]]; then
  echo "check_blacklist.php stoped! starting it!"
  cd /usr/share/snpservices/common; /usr/bin/php /usr/share/snpservices/common/check_blacklist.php &>/dev/null &
fi
