#!/bin/bash

CONFIG_FILE="/vagrant/project.ini"

# Parse the ini using Python as it has a built in parser for this
eval $(cat $CONFIG_FILE  | /vagrant/setup/ini2arr.py)

# TODO: Find a way to loop over all directories

# Make sure directories don't have trailing slashes
DIRECTORIES[files_dir]=${DIRECTORIES[files_dir]%/}
DIRECTORIES[theme_dir]=${DIRECTORIES[theme_dir]%/}

# Make directory paths absolute by concatanating with webroot dir
if [ -n "${DIRECTORIES[files_dir]}" ]; then
  DIRECTORIES[files_dir]="/var/www/${VAGRANT[hostname]}/${DIRECTORIES[files_dir]}"
fi
if [ -n "${DIRECTORIES[theme_dir]}" ]; then
  DIRECTORIES[theme_dir]="/var/www/${VAGRANT[hostname]}/${DIRECTORIES[theme_dir]}"
fi

# Hardcoded variables - these are not editable in project.ini
declare -p ADMIN >/dev/null 2>&1 || declare -A ADMIN
ADMIN[username]=admin
ADMIN[password]=password
ADMIN[email]=felix@monkii.com

declare -p DB >/dev/null 2>&1 || declare -A DB
DB[name]=${PROJECT[name]}
DB[user]="${PROJECT[name]}_user"
DB[pass]=password
DB[root_pass]=password
DB[host]=localhost
DB[dump_dir]="/mnt/project-data/${PROJECT[name]}/sqldump"
