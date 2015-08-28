#!/bin/bash
if [ "$1" == "generate-docs" ];
then
    mkdir ./docs-build > /dev/null
    sphinx-build -b html "./docs" "./docs-build"

elif [ "$1" == "install" ];
then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /tmp/composer.phar
    chmod +x /tmp/composer.phar
    /tmp/composer.phar install
    /tmp/composer.phar update
else
    echo "Please specify a one of available options: generate-docs, install"
fi