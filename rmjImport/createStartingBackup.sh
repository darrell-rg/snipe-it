#!/bin/bash
set -x #echo on

#make sure you run this with source: or the paths in the zip file will be messed up

cd /var/www/html/snipeit/rmjImport

zip -u snipe-it-dev2-backup-23-08-21-12-16-19.zip /var/www/html/snipeit/.env
zip -u snipe-it-dev2-backup-23-08-21-12-16-19.zip db-dumps/mysql-snipeit.sql
zip -d snipe-it-dev2-backup-23-08-21-12-16-19.zip /var/www/html/snipeit/storage/oauth-public.key
zipinfo snipe-it-dev2-backup-23-08-21-12-16-19.zip




php ../artisan snipeit:restore /var/www/html/snipeit/rmjImport/snipe-it-dev2-backup-23-08-21-12-16-19.zip
# revert snipe-it-dev2-backup-23-08-21-12-16-19.zip
git checkout -- snipe-it-dev2-backup-23-08-21-12-16-19.zip
# sudo usermod -a -G www-data darrell
# sudo usermod -a -G snipeitapp darrell
# cd /var/www/html/snipeit/