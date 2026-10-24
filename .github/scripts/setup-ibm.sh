#!/usr/bin/env bash
set -euo pipefail

CONTAINER="$1"

docker cp ./tests/unit/Data/ActiveRecord/scripts/ibm/database.sql "$CONTAINER":/tmp/initdb.sql

# Run the schema script, then verify it loaded by querying a known table.
# DROP statements on a fresh database raise SQL0204N (42704, undefined name);
# those are expected, so only the final verification query decides success.
docker exec "$CONTAINER" su - db2inst1 -c "
  db2 connect to pradount
  db2 -td\; -f /tmp/initdb.sql || true
  db2 'SELECT COUNT(*) FROM departments'
"
