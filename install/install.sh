# Execute certaines commandes après obtention des sources
#
# export BASE_URL='http://localhost/gvv_dev/'
# export GVV_DIR='/home/frederic/workspace/gvv2'
# ===================================================================
# Variables à configurer

if [ ! -n "${BASE_URL+1}" ]; then
	export BASE_URL="http://localhost/jenkins_bb/"
fi

export BASE_URL_PATTERN='http://localhost/gvv/'

# User ID of the WEB server
export WEB_SERVER_ID=""

# Directory where GVV has been fetched
if [ ! -n "${GVV_DIR+1}" ]; then
	export GVV_DIR="/var/www/html/jenkins_bb"
fi

# Not changes required below this line
# ===================================================================
# Configure BASE_URL

export CONFIG_FILE="$GVV_DIR/application/config/config.php"
echo "GVV configuration"
echo "    BASE_URL=$BASE_URL"
echo "    CONFIG_FILE=$CONFIG_FILE"

# Vérifie les droits d'écriture
#sed s|$BASE_URL_PATTERN|$BASE_URL| $CONFIG_FILE > $CONFIG_FILE
sed -i s#http://localhost/gvv#http://localhost/jenkins_bb# $CONFIG_FILE 

# Nettoyage des répertoires

# Vérification des droits
chmod a+w $GVV_DIR/application/config/club.php
chmod a+w $GVV_DIR/application/config/facturation.php
chmod a+w $GVV_DIR/application/config/club.php

chmod 777 $GVV_DIR/assets/images
chmod 777 $GVV_DIR/uploads
chmod 777 $GVV_DIR/application/logs
