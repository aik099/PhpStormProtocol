#!/bin/sh
URL="$1"
URL=$(echo "$URL" | sed "s#%2F#/#g")
REGEX="^pstorm://open/\?url=file://(.*)&line=(.*)$"

if [[ $URL =~ $REGEX ]]; then
	/usr/local/bin/pstorm "${BASH_REMATCH[1]}:${BASH_REMATCH[2]}"
fi
