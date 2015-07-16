#!/bin/bash

cl "Running Wordpress specific provisioning"

cd /tmp

# Composer
if [ ! -f "/usr/local/bin/composer" ]; then
  cl "Installing composer"
  curl -sS https://getcomposer.org/installer | php
  chmod +x composer.phar
  mv composer.phar /usr/local/bin/composer
fi

# WP CLI
if [ ! -f "/usr/local/bin/wp" ]; then
  cl "Installing WP CLI"
  curl -sS -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  chmod +x wp-cli.phar
  mv wp-cli.phar /usr/local/bin/wp
fi

# Install wordpress
if [ ! -f "/var/www/${VAGRANT[hostname]}/wp-config.php" ]; then

  # Download Wordpress
  cl "Downloading Wordpress"
  su - vagrant -c "wp core download --path=/var/www/${VAGRANT[hostname]}"

  #cd "/var/www/${VAGRANT[hostname]"

  # Create config file
  cl "Creating wp-config.php"
  su - vagrant -c "wp core config --dbname=${DB[name]} --dbuser=${DB[user]} --dbpass=${DB[pass]} --dbhost=${DB[host]} --path=/var/www/${VAGRANT[hostname]}"

  # Install Wordpress
  cl "Installing Wordpress"
  su - vagrant -c "wp core install --url=http://${VAGRANT[hostname]} --title='${PROJECT[name]}' --admin_user=${ADMIN[username]} --admin_email=${ADMIN[email]} --admin_password=${ADMIN[password]} --path=/var/www/${VAGRANT[hostname]}"

fi
