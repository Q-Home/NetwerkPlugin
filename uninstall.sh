#!/bin/bash

PLUGIN_DIR="${LBPDATA:-${LBHOMEDIR}/data/plugins}/network_plugin"
LOG_DIR="${LBPLOG:-${LBHOMEDIR}/log/plugins}/network_plugin"

if [ -d "$PLUGIN_DIR" ]; then
    rm -rf "$PLUGIN_DIR"
fi

mkdir -p "$LOG_DIR"
echo "Plugin uninstalled" >> "$LOG_DIR/uninstall.log"
