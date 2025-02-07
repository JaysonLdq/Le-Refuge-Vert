#!/usr/bin/sh
mariadb-dump leRefugeVert -uroot -psuperAdmin > /root/init.sql
echo "Sauvegarde terminée"