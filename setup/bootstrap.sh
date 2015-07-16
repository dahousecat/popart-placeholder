#!/bin/bash

cl() {
	BLUE='\033[1;34m'
	RED='\033[0;31m'
	GREEN='\033[0;32m'
	if [[ $* == *-e* ]]; then # e is for error
		COLOUR=$RED
	elif [[ $* == *-s* ]]; then # s is for sucsess
		COLOUR=$GREEN
	else
		COLOUR=$BLUE
	fi
	NC='\033[0m' # No Color
	echo -e "${COLOUR}$1${NC}"
}


##################################
#        Set up variables        #
##################################

. "/vagrant/setup/load-variables.sh"


##################################
#          Provisioning          #
##################################

# Don't ask for anything
export DEBIAN_FRONTEND=noninteractive

# Set MySQL root password
debconf-set-selections <<< "mysql-server-5.5 mysql-server/root_password password ${DB[pass]}"
debconf-set-selections <<< "mysql-server-5.5 mysql-server/root_password_again password ${DB[pass]}"


# Don't update more than one per day
if [ -f /var/log/vagrant_provision_last_update_time.log ]; then
	LAST_UPDATED=`cat /var/log/vagrant_provision_last_update_time.log`
	DAY_AGO=`date +%s --date='-1 day'`
else
	LAST_UPDATED=""
fi

if [ -z "$LAST_UPDATED" ] || [ "$LAST_UPDATED" -lt "$DAY_AGO" ]; then

	# Install packages
	cl "Install / update packages"
	apt-get update

	apt-get install -q -f -y --force-yes -o Dpkg::Options::='--force-confdef' -o Dpkg::Options::='--force-confold' mysql-server-5.5 php5-mysql libsqlite3-dev apache2 php5 libapache2-mod-php5 php5-dev build-essential php-pear ruby1.9.1-dev php5-mcrypt php5-curl git php5-gd imagemagick unzip php5-xdebug postfix mailutils git
	apt-get -y remove puppet chef chef-zero puppet-common

	# Make log file so save last updated time
	date +%s > /var/log/vagrant_provision_last_update_time.log

else
	cl "Updates last ran less than a day ago so skipping"
fi

# Set timezone
echo "Australia/Melbourne" | tee /etc/timezone
dpkg-reconfigure --frontend noninteractive tzdata


# Link repository webroot to server webroot
if [ ! -h "/var/www/${VAGRANT[hostname]}" ]; then
  ln -fs "/vagrant/www" "/var/www/${VAGRANT[hostname]}"
fi


# Make sure symlinks to import and export database scripts exist
if [ ! -h "/usr/local/bin/load-db" ]; then
  ln -s /vagrant/scripts/load-db /usr/local/bin/load-db
fi
if [ ! -h "/usr/local/bin/save-db" ]; then
  ln -s /vagrant/scripts/save-db /usr/local/bin/save-db
fi


# Setup database
if ! mysql -u root -e 'use ${DB[name]}'; then
	cl "Setting up database"
	echo "DROP DATABASE IF EXISTS test" | mysql -uroot -p${DB[root_pass]}
	echo "CREATE DATABASE ${DB[name]};" | mysql -uroot -p${DB[root_pass]}
	# This line will create the user if they do not already exist
	echo "GRANT ALL ON ${DB[name]}.* TO '${DB[user]}'@'${DB[host]}' identified by '${DB[pass]}';" | mysql -uroot -p${DB[root_pass]}
	echo "FLUSH PRIVILEGES" | mysql -uroot -p${DB[root_pass]}

	# If there is a dump on bubbles then import it now
  su - vagrant -c "load-db -y"

fi


# CMS specific provisioning
# TODO: Check if provision script for cms exists and then just dynamically include it
if [ "${PROJECT[cms]}" == "drupal" ]; then
	. /vagrant/setup/bootstrap.drupal.sh
elif [ "${PROJECT[cms]}" == "laravel" ]; then
	. /vagrant/setup/bootstrap.laravel.sh
elif [ "${PROJECT[cms]}" == "wordpress" ]; then
	. /vagrant/setup/bootstrap.wordpress.sh
else
  . /vagrant/setup/bootstrap.php.sh
fi


# Set up mount on Bubbles
. /vagrant/setup/project-data.sh



# Setup apache
echo "ServerName localhost" >> /etc/apache2/apache2.conf
a2enmod rewrite

sed -e "s/site-name.local/${VAGRANT[hostname]}/g" /vagrant/setup/files/host.conf >/etc/apache2/sites-available/host.conf
cp /vagrant/setup/files/xdebug.ini /etc/apache2/mods-available/xdebug.ini

# Create .my.cnf
cp /vagrant/setup/files/my.cnf /home/vagrant/.my.cnf

# SSH config
cp /vagrant/setup/files/ssh/* /root/.ssh/
chmod 400 /root/.ssh/id_sls
ssh-keyscan -H slsapp.com >> ~/.ssh/known_hosts

a2ensite host
a2dissite 000-default

# Configure PHP
sed -i '/display_errors = Off/c display_errors = On' /etc/php5/apache2/php.ini
sed -i '/error_reporting = E_ALL & ~E_DEPRECATED/c error_reporting = E_ALL | E_STRICT' /etc/php5/apache2/php.ini
sed -i '/html_errors = Off/c html_errors = On' /etc/php5/apache2/php.ini

# Configure postfix
if [ -f /etc/postfix/main.cf ]; then
	sed -i '/relayhost =/c relayhost = devrelay.in.monkii.com' /etc/postfix/main.cf
	service postfix restart
fi

# Make sure things are up and running as they should be
service apache2 restart

# Import frontend stub if theme dir is set and there is no Grunt file
. /vagrant/setup/front-end-stub.sh



cl " "
cl " w  c(..)o   ("
cl "  \__(-)    __)"
cl "      /\   ("
cl "   w_/(_)___)"
cl "      /|"
cl "      | \ "
cl "     m  m"
cl " "


cl 'Add the following line to yours hosts file:\n'
cl "${VAGRANT[ip]} ${VAGRANT[hostname]}"
