mysqldump --no-create-db=true -h <hostname> <username> > exported_dbs/backup.sql
php5.4-cli scripts/email_backup.php
