#!/bin/bash

if ! [ -n "${DIRECTORIES[files_dir]}" ]; then
  cl "No files directory is set in the project.ini so skipping creating mount on Bubbles"

else

  # Create folder to mount to
  if [ ! -d "/mnt/project-data" ]; then
    mkdir /mnt/project-data
  fi

  # Mount bubbles
  if ! mount | grep bubbles > /dev/null; then
      cl "Mounting Bubbles"
      sudo mount bubbles:/var/data/project-data /mnt/project-data
  fi

  # Create project folder
  if [ ! -d "/mnt/project-data/${PROJECT[name]}" ]; then
    cl "Creating project folder structure on Bubbles"
    su - vagrant -c "mkdir /mnt/project-data/${PROJECT[name]}"
    su - vagrant -c "mkdir /mnt/project-data/${PROJECT[name]}/sqldump/"
    su - vagrant -c "mkdir /mnt/project-data/${PROJECT[name]}/files/"
  fi

  # Make sure files directory and symlink exists
  BASE_PATH=${DIRECTORIES[files_dir]%/*}
  #BASE_PATH=${FILES_DIR%/*}
  if [ ! -d "$BASE_PATH" ]; then
    mkdir -p "$BASE_PATH"
  fi

  # If there is a directory where we want our symlink then remove it
  if [ -d "${DIRECTORIES[files_dir]}" ] && [ ! -L "${DIRECTORIES[files_dir]}" ]; then

    cl "Files directory already exists, moving contents to mount location"

    # Copy any files already there to the mounted location
    su - vagrant -c "cp -R ${DIRECTORIES[files_dir]}/. /mnt/project-data/${PROJECT[name]}/files"

    chmod 755 "$BASE_PATH"
    rm -rf "${DIRECTORIES[files_dir]}"

  fi

  # Make the symlink
  if [ ! -L "${DIRECTORIES[files_dir]}" ]; then
    ln -s "/mnt/project-data/${PROJECT[name]}/files" "${DIRECTORIES[files_dir]}"
    # This is needed to allow Drupal to write to the files directory
    chmod 777 "${DIRECTORIES[files_dir]}"
  fi

fi
