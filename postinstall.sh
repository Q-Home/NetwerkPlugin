#!/bin/bash

#INSTALL ARP_SCAN !!!!!!!!!!!!!!!!

















# Shell script which is executed by bash *AFTER* complete installation is done
# (but *BEFORE* postupdate). Use with caution and remember, that all systems may
# be different!
#
# Exit code must be 0 if executed successfull.
# Exit code 1 gives a warning but continues installation.
# Exit code 2 cancels installation.
#
# Will be executed as user "loxberry".
#
# You can use all vars from /etc/environment in this script.
#
# We add 5 additional arguments when executing this script:
# command <TEMPFOLDER> <NAME> <FOLDER> <VERSION> <BASEFOLDER>
#
# For logging, print to STDOUT. You can use the following tags for showing
# different colorized information during plugin installation:
#
# <OK> This was ok!"
# <INFO> This is just for your information."
# <WARNING> This is a warning!"
# <ERROR> This is an error!"
# <FAIL> This is a fail!"

# To use important variables from command line use the following code:
COMMAND=$0    # Zero argument is shell command
PTEMPDIR=$1   # First argument is temp folder during install
PSHNAME=$2    # Second argument is Plugin-Name for scipts etc.
PDIR=$3       # Third argument is Plugin installation folder
PVERSION=$4   # Forth argument is Plugin version
#LBHOMEDIR=$5 # Comes from /etc/environment now. Fifth argument is
              # Base folder of LoxBerry
PTEMPPATH=$6  # Sixth argument is full temp path during install (see also $1)

# Combine them with /etc/environment
PHTMLAUTH=$LBHOMEDIR/webfrontend/htmlauth/plugins/$PDIR
PHTML=$LBPHTML/$PDIR
PTEMPL=$LBPTEMPL/$PDIR
PDATA=$LBPDATA/$PDIR
PLOGS=$LBPLOG/$PDIR # Note! This is stored on a Ramdisk now!
PCONFIG=$LBPCONFIG/$PDIR
PSBIN=$LBPSBIN/$PDIR
PBIN=$LBPBIN/$PDIR

# your code goes here

PLUGIN_DIR="${LBPBIN:-${LBHOMEDIR}/bin/plugins}/$PDIR"
PHP_MQTT_DIR="$PLUGIN_DIR/phpMQTT"
INSTALL_SCRIPT="$PLUGIN_DIR/install.sh"
SCANNER_SCRIPT="$PLUGIN_DIR/scanner.php"

if ! command -v arp-scan >/dev/null 2>&1; then
    if command -v apt-get >/dev/null 2>&1; then
        echo "<INFO> Installing arp-scan..."
        sudo apt-get update -qq
        sudo apt-get install -y arp-scan
    else
        echo "<WARNING> apt-get not available, please install arp-scan manually."
    fi
fi

if [ ! -d "$PLUGIN_DIR" ]; then
    echo "<ERROR> Plugin directory not found: $PLUGIN_DIR"
    exit 1
fi

if [ ! -d "$PHP_MQTT_DIR" ]; then
    echo "<INFO> phpMQTT not found, downloading..."
    git clone https://github.com/bluerhinos/phpMQTT.git "$PHP_MQTT_DIR"
else
    echo "<INFO> phpMQTT is already installed."
fi

chmod -R 755 "$PHP_MQTT_DIR"
chmod 644 "$PHP_MQTT_DIR/phpMQTT.php"

if [ -f "$SCANNER_SCRIPT" ]; then
    chmod +x "$SCANNER_SCRIPT"
fi

if [ -f "$INSTALL_SCRIPT" ]; then
    chmod +x "$INSTALL_SCRIPT"
fi

echo "<OK> Post-install completed successfully."
exit 0