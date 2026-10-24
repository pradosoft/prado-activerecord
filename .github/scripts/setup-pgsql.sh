#!/usr/bin/env bash
set -euo pipefail

# Create the login role and a database owned by it. The database uses the C locale:
# the ordering tests expect a binary sort, in which '+GX Service' comes before every
# letter, and the glibc en_US collation ignores punctuation at the first level.
PGPASSWORD=postgres psql -h 127.0.0.1 -U postgres \
  -c "CREATE ROLE prado_unitest WITH LOGIN PASSWORD 'prado_unitest';"
PGPASSWORD=postgres createdb -h 127.0.0.1 -U postgres -O prado_unitest --locale=C --template=template0 prado_unitest

# PostgreSQL 15+ no longer grants CREATE on the public schema to non-owners.
PGPASSWORD=postgres psql -h 127.0.0.1 -U postgres -d prado_unitest \
  -c "ALTER SCHEMA public OWNER TO prado_unitest;"

# Load the schema as prado_unitest so the objects are owned by the test user.
PGPASSWORD=prado_unitest psql -h 127.0.0.1 -U prado_unitest -d prado_unitest \
  -v ON_ERROR_STOP=1 -q -f ./tests/unit/Data/ActiveRecord/scripts/pgsql/database.sql
