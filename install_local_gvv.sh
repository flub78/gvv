#
# Ce script copy GVV depuis un workspace vers un répèrtoire d'installation
# Typiquement /var/www/html
#
export WORKSPACE=/home/frederic/zend-eclipse-php/workspace/gvv2
export DEST=/var/www
export ROOT=$DEST/gvv2

cp -r $WORKSPACE $DEST
cp -r $WORKSPACE/application/third_party/pChart/fonts $DEST

chmod +w $ROOT/application/config/club.php
chmod +w $ROOT/application/config/facturation.php
chmod +wx $ROOT/assets/images
chmod +wx $ROOT/uploads
