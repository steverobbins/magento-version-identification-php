#!/bin/bash

DEST=release/magento-EE-1.14.2.3
URL=https://www.bensherman.com
USE=md5/magento-EE-1.14.2.0

cat $USE | awk '{print $2}' | while read FILE; do
    mkdir -p "$DEST/$FILE"
    rm -rf "$DEST/$FILE"
    echo "Downloading $FILE"
    curl -ksSL -o "$DEST/$FILE" "$URL/$FILE"
done
