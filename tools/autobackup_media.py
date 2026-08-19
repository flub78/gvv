#!/usr/bin/python
# coding: utf8

# Script pour réaliser une sauvegarde périodique des fichiers média (uploads/)
#    * typiquement lancé par un cron job
#    * garde un journal des sauvegardes
#    * les noms de sauvegarde incluent la date
#    * efface les anciennes sauvegardes en en gardant un nombre limité
#
# Miroir de autobackup.py, mais pour le répertoire uploads/ au lieu de la base.

import time
import os
import os.path
import glob
import subprocess

# configuration
_script_dir = os.path.dirname(os.path.abspath(__file__))
backup_dir = os.environ.get('BACKUP_DIR', os.path.join(_script_dir, '..', 'backups') + os.sep)
uploads_dir = os.environ.get('UPLOADS_DIR', os.path.join(_script_dir, '..', 'uploads'))
prefix = os.environ.get('MEDIA_BACKUP_PREFIX', 'media')

# fin de configuration
logfile = backup_dir + 'logfile.txt'

# Vérifie l'existance du répertoire et crée le s'il le faut
os.makedirs(backup_dir, exist_ok=True)

current_time = time
now = current_time.strftime("%H:%M:%S %d/%m/%Y")
backup_basename = prefix + "_backup_" + current_time.strftime("%Y%m%d_%H%M%S")

archive_name = backup_basename + ".tar.gz"
archive_path = backup_dir + archive_name

# utilitaires

# Enregistrement dans le fichier journal
def log(logmsg):
    with open(logfile, 'a') as file:
        file.write(logmsg)
        print(logmsg)

# Vérifie qu'il y a quelque chose à sauvegarder (hors restore/)
has_content = False
if os.path.isdir(uploads_dir):
    for entry in os.listdir(uploads_dir):
        if entry != 'restore':
            has_content = True
            break

if not has_content:
    log(now + ": Media backup skipped, uploads directory empty or missing\n")
else:
    # sauvegarde des médias, mêmes exclusions que admin.php::backup_media()
    cmd = [
        "tar",
        "--exclude=restore",
        "--exclude=attachments_backup",
        "--exclude=*.tmp",
        "--exclude=*.bak",
        "-czf", archive_path,
        "-C", uploads_dir,
        "."
    ]

    print(" ".join(cmd))
    return_code = subprocess.call(cmd)

    # Vérifie l'existence et la validité de la sauvegarde
    if return_code == 0 and os.path.getsize(archive_path) > 100:
        log(now + ": Media backup " + backup_basename + " successful\n")
    else:
        log(now + ": Media backup " + backup_basename + " failed\n")

# Ne garde que certaines sauvegardes pour sauver de la place
# On utilise la liste des sauvegardes triées par date de création
files = filter(os.path.isfile, glob.glob(backup_dir + prefix + "*.tar.gz"))
files = list(files)
files.sort(key=lambda x: os.path.getmtime(x), reverse=True)

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

    since = age - age_previous
    if since < limit * 0.95:
        msg = current_time.strftime("%H:%M:%S %d/%m/%Y")
        msg += ' age=' + str(age)
        msg += " " + str(since) + " since previous"
        msg += " deleting " + file + "\n"
        log(msg)
        os.remove(file)

    age_previous = age
