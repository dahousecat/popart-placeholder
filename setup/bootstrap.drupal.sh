#!/bin/bash

# Drupal specific variables

# Site
siteLocale="au"

cl "Running Drupal specific provisioning"

# Drush
if ! which drush 2>/dev/null; then
	cl "Installing Drush"
	wget -q -P /tmp https://github.com/drush-ops/drush/archive/6.6.0.tar.gz
	if [ ! -d "/usr/local/drush" ]; then
	  mkdir /usr/local/drush
	fi
	tar -xf /tmp/6.6.0.tar.gz -C /usr/local/drush --strip-components=1
	rm /tmp/6.6.0.tar.gz
	ln -s /usr/local/drush/drush /usr/bin/drush
	chmod 777 /usr/local/drush/lib
	chmod 777 /home/vagrant/.drush/cache/usage

  # drushrc
  cat > /home/vagrant/.drush/drushrc.php << EOF
<?php
// Put downloaded modules in the contrib folder
$command_specific['dl']['destination'] = 'sites/all/modules/contrib';
EOF

fi

# Install Drupal
if [ ! -f "/var/www/${VAGRANT[hostname]}/includes/bootstrap.inc" ]; then
  # If bootstrap is not there then it's safe to say Drupal is not installed

  cl "Downloading Drupal"
  drush dl drupal-7.x --destination=/vagrant --drupal-project-rename=drupal

  # Move drupal to webroot
  cp -R /vagrant/drupal/. /vagrant/www

  rm -rf /vagrant/drupal

  # Install Drupal
  cl "Installing Drupal"
  cd "/vagrant/www"
  drush si -y standard --site-name="${PROJECT[name]}" --site-mail="${ADMIN[email]}" --db-url="mysql://${DB[user]}:${DB[pass]}@${DB[host]}/${DB[name]}" --locale="$siteLocale";

  # Setting password seperatly as setting in si doesn't seem to work
  drush upwd --password="${ADMIN[password]}" "${ADMIN[username]}"
  cl "Admin password set to ${ADMIN[password]}"
  drush sqlq "UPDATE users SET mail='${ADMIN[email]}' WHERE uid=1"

  # Install some modules
  drush -y dl devel admin_menu
  drush -y en devel admin_menu

  # Disable some modules
  drush -y dis toolbar overlay

  # Disable user pictures
  Drush vset -y user_pictures 0;

  # Allow only admins to register
  drush vset -y user_register 0;

fi

# If there is no settings.php make one with default values
if [ ! -f "/vagrant/www/sites/default/settings.php" ]; then

  # Make sure default.settings.php exists
  if [ ! -f "/vagrant/www/sites/default/default.settings.php" ]; then
    cl "Attmpt to auto create setting.php failed as default.settings.php is missing. Go and download a copy from http://cgit.drupalcode.org/drupal/plain/sites/default/default.settings.php?h=7.x" -e
  else

    cl "settings.php does not exist. Creating file with default values."

    cp /vagrant/www/sites/default/default.settings.php /vagrant/www/sites/default/settings.php

    cat <<EOT >> /vagrant/www/sites/default/settings.php

$databases['default']['default'] = array(
  'driver' => 'mysql',
  'database' => '${DB[name]}',
  'username' => '${DB[user]}',
  'password' => '${DB[pass]}',
  'host' => '${DB[host]}',
  'prefix' => '',
);
EOT

    chmod 444 /vagrant/www/sites/default/settings.php

  fi

fi