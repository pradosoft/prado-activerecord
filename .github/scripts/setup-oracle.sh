#!/usr/bin/env bash
set -euo pipefail

CONTAINER="$1"

docker cp ./tests/unit/Data/ActiveRecord/scripts/oracle/database.sql "$CONTAINER":/tmp/initdb_oracle.sql
docker exec -u oracle "$CONTAINER" \
  bash -c 'source ~/.bash_profile && sqlplus -S prado_unitest/prado_unitest@//localhost/FREEPDB1 @/tmp/initdb_oracle.sql'
