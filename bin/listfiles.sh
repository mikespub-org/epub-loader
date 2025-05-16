#!/bin/bash
#
# List files for rclone remote library
#
# ```sh
# $ cp .env.sample .env
# $ # adapt env variables
# $ sh bin/listfiles.sh
# ```
#
if [ -z "${RCLONE_REMOTE}" ]; then
  . ./.env
fi
if [ -z "${RCLONE_REMOTE}" ]; then
  echo "Missing env variable RCLONE_REMOTE in .env file"
else
  CACHE_DIR="cache/rclone"
  if [ ! -d "${CACHE_DIR}/${RCLONE_REMOTE}" ]; then
    mkdir -p "${CACHE_DIR}/${RCLONE_REMOTE}"
  fi
  echo "Updating list of remotes for rclone"
  rclone listremotes --json | jq . > "${CACHE_DIR}/remotes.json"
  echo "Updating list of files for ${RCLONE_REMOTE}:\"${RCLONE_ROOT}\""
  rclone lsjson -R --fast-list $RCLONE_REMOTE:"${RCLONE_ROOT}" | jq . > "${CACHE_DIR}/${RCLONE_REMOTE}/getfiles.json"
  echo "Done"
fi
