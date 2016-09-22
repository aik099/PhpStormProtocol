#!/bin/sh
URL="$1"
REGEX="^pstorm://open\?url=file://(.*)&line=(.*)$"

if [[ $URL =~ $REGEX ]]; then
	/usr/local/bin/pstorm "${BASH_REMATCH[1]}:${BASH_REMATCH[2]}"
fi
