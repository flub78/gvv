# Ce script mais à jour GVV
#
# Pour mettre le script à jour
# svn export --force http://subversion.developpez.com/projets/gvv/trunk/gvv/update_gvv.sh

if [ $# -eq 0 ]
then
	export REVISION=""
else
	export REVISION="-r $1"
fi

svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/assets
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/themes
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/system
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/install
# svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/tools/autobackup.py

cd application
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/controllers
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/libraries
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/helpers
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/models
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/views
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/migrations
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/third_party
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/language
cd config
# svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/config/program.php
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/config/autoload.php
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/config/hooks.php
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/config/mimes.php
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/config/routes.php
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/config/migration.php
svn export $REVISION --force http://subversion.developpez.com/projets/gvv/trunk/gvv/application/config/constants.php

cd ../..
touch installed.txt
svn info http://subversion.developpez.com/projets/gvv/trunk/gvv/ >> installed.txt
if [ $# -ne 0 ]
then
echo "last update REVISION=$REVISION" >> installed.txt
echo "" >> installed.txt
fi
