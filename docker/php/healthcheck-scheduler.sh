#!/bin/sh
# Healthcheck for scheduler - checks if artisan schedule:run is running via /proc

for pid in $(ls /proc 2>/dev/null | grep -E '^[0-9]+$'); do
    if [ -f /proc/$pid/cmdline ]; then
        if cat /proc/$pid/cmdline 2>/dev/null | tr '\0' ' ' | grep -q "artisan schedule:run"; then
            exit 0
        fi
    fi
done
exit 1