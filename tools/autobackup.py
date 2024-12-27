#!/usr/bin/python
# coding: utf8

# Script pour réaliser une sauvegarde périodique d'une base mysql
#    * typiquement lancé par un cron job
#    * garde un journal des sauvegarde
#   * les noms de sauvegarde incluent les dates et numéro de version de la base
#   * Efface les anciennes sauvegardes en en gardant un nombre limité


import time
import os
import os.path
import glob
import calendar
import MySQLdb    # apt-get install python-mysqldb

# configuration
backup_dir = os.environ.get('BACKUP_DIR', os.environ['HOME'] + '/workspace/gvv2/backups/')
database = os.environ.get('DB_NAME', 'ci3')
user = os.environ.get('DB_USER', 'ci3')
password = os.environ.get('DB_PASSWORD', 'ci3')
host = os.environ.get('DB_HOST', 'localhost')

# fin de configuration
logfile = backup_dir + 'logfile.txt'

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
backup_basename = database + "_backup_" + current_time.strftime("%Y%m%d_%H%M%S")
backup_basename += '_migration_' + str(migration)

zipname = backup_dir + backup_basename + ".zip"
backup_script = backup_dir + backup_basename + '.sql'

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
cmd += " > "  + backup_script

print(cmd)
os.system(cmd)

cmd = "zip " + zipname + " " + backup_script
print(cmd)
os.system(cmd)

# Supprime les fichiers temporaires
os.remove(backup_script)

# Vérifie l'existence de la sauvegarde
if (os.path.getsize(zipname) > 100):
    log(now + ": Backup " + backup_basename + " successful\n") 
else:
    log(now + ": Backup " + backup_basename + " failed\n") 

# Ne garde que certaines sauvegarde pour sauver de la place
# On utilise la liste des sauvegardes triées par date de création
files = filter(os.path.isfile, glob.glob(backup_dir + database + "*.zip"))
files = list(files)
files.sort(key=lambda x: os.path.getmtime(x),reverse=True)

day = 3600 * 24
week = 7 * day
month = 30 * day
year = 365 * day

age_previous = -10 * year

for file in files:
    # On regarde l'age du fichier
    age = current_time.time() - os.path.getmtime(file)
    # print("file=", file, "age=", age)

    # On garde le plus récent 
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
    