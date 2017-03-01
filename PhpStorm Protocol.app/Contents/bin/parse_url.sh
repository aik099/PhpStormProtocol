#!/bin/sh
URL="$1"
REGEX="^phpstorm://open\?(url=file://|file=)(.*)&line=(.*)$"

if [[ $URL =~ $REGEX ]]; then
	/usr/local/bin/pstorm "${BASH_REMATCH[2]}:${BASH_REMATCH[3]}"
fi
