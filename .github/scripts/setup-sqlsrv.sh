#!/usr/bin/env bash
set -euo pipefail

SQLCMD=/opt/mssql-tools18/bin/sqlcmd

curl -sSL -O https://packages.microsoft.com/config/ubuntu/24.04/packages-microsoft-prod.deb
sudo dpkg -i packages-microsoft-prod.deb
sudo apt-get update -qq
sudo ACCEPT_EULA=Y apt-get install -y mssql-tools18 unixodbc-dev

"$SQLCMD" -C -U sa -P Prado_Unitest1 -Q "CREATE DATABASE prado_unitest"
"$SQLCMD" -C -U sa -P Prado_Unitest1 -Q "CREATE LOGIN prado_unitest WITH PASSWORD='prado_unitest', CHECK_POLICY=OFF"
"$SQLCMD" -C -U sa -P Prado_Unitest1 -d prado_unitest \
  -Q "CREATE USER prado_unitest FOR LOGIN prado_unitest; ALTER ROLE db_owner ADD MEMBER prado_unitest;"
"$SQLCMD" -C -U sa -P Prado_Unitest1 -d prado_unitest -b \
  -i ./tests/unit/Data/ActiveRecord/scripts/sqlsrv/database.sql
