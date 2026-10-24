#!/usr/bin/env bash
set -euo pipefail

# Installs Oracle Instant Client and exports ORACLE_HOME / LD_LIBRARY_PATH into
# the GitHub Actions environment so that the subsequent setup-php step can build
# the pdo_oci and oci8 extensions against the correct libraries.

sudo apt-get install -y libaio1t64 unzip 2>/dev/null || sudo apt-get install -y libaio1 unzip

wget -q -O /tmp/ic.zip \
  "https://download.oracle.com/otn_software/linux/instantclient/193000/instantclient-basiclite-linux.x64-19.3.0.0.0dbru.zip"
sudo unzip -q /tmp/ic.zip -d /opt/oracle
echo "/opt/oracle/instantclient_19_3" | sudo tee /etc/ld.so.conf.d/oracle.conf
sudo ldconfig

echo "ORACLE_HOME=/opt/oracle/instantclient_19_3" >> "$GITHUB_ENV"
echo "LD_LIBRARY_PATH=/opt/oracle/instantclient_19_3" >> "$GITHUB_ENV"
