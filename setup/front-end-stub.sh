#!/bin/bash

if [ ${#THEME_DIR} != 0 ] && [ ! -f /vagrant/www/Gruntfile ]; then

  cl "Cloning front end project into $THEME_DIR"

  # Clone the frontend project
  mkdir -p /tmp/frontend
  ssh-agent bash -c 'ssh-add /root/.ssh/id_sls; git clone ssh://sls@slsapp.com:1234/monkii/monkii-frontend.git /tmp/frontend'
  #git clone ssh://sls@slsapp.com:1234/monkii/monkii-frontend.git /tmp/frontend

  if [ -f /tmp/frontend/Gruntfile.js ]; then

    # Move these parts to the project root
    cp --no-preserve=mode /tmp/frontend/Gruntfile.js /vagrant/
    cp --no-preserve=mode /tmp/frontend/project.json /vagrant/
    cp --no-preserve=mode /tmp/frontend/package.json /vagrant/

    # Move this to webroot
    cp --no-preserve=mode /tmp/frontend/humans.txt "/var/www/$VAGRANT_HOSTNAME/"

    # Move the rest to the theme directory
    if [ ! -d "$THEME_DIR" ]; then
      mkdir -p "$THEME_DIR"
    fi
    cp -R --no-preserve=mode /tmp/frontend/* "$THEME_DIR/"

    # Cleanup
    rm -rf /tmp/frontend

  else

    cl "Error: Could not clone the frontend project" -e

  fi

fi
