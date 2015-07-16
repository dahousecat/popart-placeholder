#!/bin/bash

cl "Running Laravel specific provisioning"

cd /tmp

# Composer
curl -sS https://getcomposer.org/installer | php
chmod +x composer.phar
mv composer.phar /usr/local/bin/composer

# Download the Laravel installer
composer global require "laravel/installer=~1.1"

cd /vagrant/www

laravel new "${PROJECT[name]}"
