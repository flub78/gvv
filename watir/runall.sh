#
# Run all the GVV Watir tests
#
# On teste déja:
#
#    l'installation du programme
#    la migration de la base
#    l'accès à toutes les vues sans erreurs après l'installation
#    la création d'éléments dans les tables de base
#       - cas nominaux et cas d'erreurs
#    La modification de la configuration du club
#    Les tests unitaires CIUnit
#    La création de comptes, tarifs, machines et pilotes
#    La restauration de sauvegarde
#    La création de vols
#    La facturation d'Abbeville et de Troyes
#    Le passage d'écritures
#    Le changement de mot de passe
#    Le calendrier des présences
#    Les droits et limitations par type d'usagé
#    Internationalisation
#
# TODO: 
#    Bilan, Résultat, balance
#    Statistiques
#    Les filtres
#    Formation
#    Emails
#    Exports and PDF generation
#    Chargement de photos
# ----------------------------------------------------------------------------------------

export VERBOSE="-v"
rm screenshots/*.png

# Installation
ruby test_install.rb $VERBOSE

# Migration (must be done early)
# Normally no migration should be required after an installation from scratch
ruby test_migration.rb $VERBOSE

# Access to all views
ruby test_all_views.rb $VERBOSE

# CRUD on most tables
ruby test_crud.rb $VERBOSE

# Check configuration
ruby test_config.rb $VERBOSE

# Run CI Unit tests
ruby test_ciunit.rb $VERBOSE

# Create data, create enough data, account, prices, gliders
# to be able to test high level features
ruby test_data.rb $VERBOSE

# Backup / Restore
ruby test_restore.rb $VERBOSE
ruby test_migration.rb $VERBOSE

# =====================================================================================================
# From this point all tests should be able to run from the reference database

ruby test_tarifs.rb $VERBOSE

# Add flights
ruby test_flights.rb $VERBOSE

# Facturation
ruby test_facturation.rb $VERBOSE

# Accounting
ruby test_compta.rb $VERBOSE

# Password
ruby test_password.rb $VERBOSE

# Droits
ruby test_droits.rb $VERBOSE

# Calendar
ruby test_calendar.rb $VERBOSE

# Internationalisation
ruby test_international.rb $VERBOSE

