#!/usr/bin/python
# coding: utf8

# Script pour réaliser une sauvegarde périodique d'une base mysql
#    * typiquement lancé par un cron job
#    * garde un journal des sauvegarde
#   * les noms de sauvegarde incluent les dates et numéro de version de la base
#   * Efface les anciennes sauvegardes en en gardant
#
#     - une par jour pendant une semaine    7
#     - une par semaine pendant un mois        3
#     - une par mois pendant un an            11
#     - une par an pendant 10 ans            9
#
#    soit un total maximal de 30 fichiers    soit 15 Mo

import time
import os
import os.path
import glob
import calendar
import MySQLdb    # apt-get install python-mysqldb

# configuration
backup_dir = os.environ['HOME'] + '/workspace/gvv2/backups/'
logfile = backup_dir + 'logfile.txt'
database = 'ci3'
user = 'ci3'
password = 'ci3'
host = "localhost"
# fin de configuration

# Vérifie l'existance du répertoire et crée le s'il le faut
try:
    os.stat(backup_dir)
except:
    os.mkdir(backup_dir)

# version de la base de donnees
# A supprimer pour utilisation non OCdeIgniter
con = MySQLdb.connect(host=host, user=user, passwd=password, db=database)
con.query("SELECT * FROM migrations")
result = con.use_result()
migration=result.fetch_row()[0][0]
#print("migration="+str(migration) + '|')
con.close

current_time = time          
now = current_time.strftime("%H:%M:%S %d/%m/%Y")
backupfile = database + "_backup_" + current_time.strftime("%Y%m%d_%H%M%S")
backupfile += '_migration_' + str(migration)
backupfile += '.sql'

fullname = backup_dir + backupfile + ".gz"

# utilitaires

# Enregistrement dans le fichier journal
def log(logmsg):
    with open(logfile, 'a') as file:
        file.write(logmsg)    
        print (logmsg)
    
# sauvegarde la base

cmd = "mysqldump --host=" + host
cmd += " --user=" + user
cmd += " --password=" + password
cmd += " --default-character-set=utf8  --no-tablespaces " + database
cmd += " | gzip "
cmd += " > " + fullname
os.system(cmd)
print(cmd)

# Vérifie l'existence de la sauvegarde
if (os.path.getsize(fullname) > 100):
    log(now + ": Backup " + backupfile + " successful\n") 
else:
    log(now + ": Backup " + backupfile + " failed\n") 

# Ne garde que certaines sauvegarde pour sauver de la place
files = filter(os.path.isfile, glob.glob(backup_dir + database + "_backup*.gz"))
files = list(files)
files.sort(key=lambda x: os.path.getmtime(x),reverse=True)

day = 3600 * 24
week = 7 * day
month = 30 * day
year = 365 * day

age_previous = -10 * year

for file in files:
    age = current_time.time() - os.path.getmtime(file)

    if age < week:
        limit = day
    elif age < month:
        limit = week
    elif age < year:
        limit = month
    else:
        limit = year
    # print("age=", age, ", limit= ", limit, ", file=", file)
    
    since = age - age_previous
    if (since < limit * 0.95):
        msg = 'age=' + str(age)
        msg += " " + str(since) + " since previous"
        msg += " deleting " + file + "\n"
        log(msg)
        os.remove(file)
        
    age_previous = age
    