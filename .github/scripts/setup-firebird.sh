#!/usr/bin/env bash
set -euo pipefail

CONTAINER="$1"

docker cp ./tests/unit/Data/ActiveRecord/scripts/firebird/database.sql "$CONTAINER":/tmp/initdb_firebird.sql

# Run the schema script, then verify it loaded by querying a known table.
# DROP statements on a fresh database raise SQLCODE -607 (object does not exist);
# those are expected, so only the final verification query decides success.
docker exec "$CONTAINER" bash -c "
  ISQL=\$(which isql-fb 2>/dev/null || which isql 2>/dev/null)
  if [ -z \"\$ISQL\" ]; then echo 'isql binary not found'; exit 1; fi
  DB=localhost:/var/lib/firebird/data/prado_unitest.fdb
  \"\$ISQL\" \"\$DB\" -user SYSDBA -password masterkey -i /tmp/initdb_firebird.sql 2>&1 || true
  VERIFY=\$(echo 'SELECT COUNT(*) FROM departments;' | \"\$ISQL\" \"\$DB\" -user SYSDBA -password masterkey 2>&1)
  printf '%s\n' \"\$VERIFY\"
  echo \"\$VERIFY\" | grep -q 'Statement failed' && exit 1
  exit 0
"
