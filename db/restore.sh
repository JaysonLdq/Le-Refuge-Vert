#!/usr/bin/sh

mariadb leRefugeVert -uroot -psuperAdmin < /root/init.sql
echo "Restauration terminée"
