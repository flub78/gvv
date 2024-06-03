Modifications récentes:
    * plus de création de compte quand le membre est rattaché à un autre compte
    * meilleurs demande de confirmation de suppression pour comptes et membres
    * correction tickets non supprimés par la suppression des vols
    * Utiliser compte pilote pour facturation.
    * Facturation avion
    * Confirmation des évenements utilise un nombre
            * plan comptable aussi
    * Ajout de certificats impossible depuis la page certificats par pilote.
    * confirmation suppression users utilise un numéro
    * bug sur le changement de status d'un utilisateur (verification de l'existence indue)
    * simplifier l'affichage
        * des vols du pilote
        * des vols d'une machine
    * Table d'historique des vols
    * affichage des ventes incorrecte pour les années précédentes
    
Bugs:
    * Reprise du gel. Il faut geler les vols, achats et tickets.
    * Pas de demande de confirmation pour la suppression à partir des fiches ???
    * Bugs sur les affichages des diagrammes
    
Features:
  
    * Gestion des catégorie de dépenses
    * Facturation générique
            
Refactoring:

    * Réactiver deprecated et ancienne méthode pour tracer les éléments à nettoyer.
    
    * Plan comptable
        * deprecated, à migrer

    * migrer les dates dans la fiche pilote vers les évenements. 
      faire une procédure de migration.

    
Design:

    Type de ticket
        id  int
        name        varchar 32
        description varchar 64

    Gestion par section
        une seul section active ou toutes (pas de filtrage pour les admin)
        Par utilisateur liste des sections activable
        
        les avions / planeurs / vols / écritures sont affichés par section
        Les pilotes sont affichés s'ils appartiennent à la section active
        
        table des sections
        name        varchar 32
        description varchar 64

        table des appartenances
        id  int auto
        mlogin      varchar key users
        section     varchar key section
        
Support des applications mobiles
--------------------------------

    - On utilise Bootstrap et un design responsive
    

Accepter tous les pilotes en 2nd pilote (à la place de l'instructeur)
---------------------------------------------------------------------

    * champ vpinst
    * pour l'instant dans le formulaire:
        pilote_selector : tous les pilotes actifs
        inst_selector
        
        pilrem_selector & treuillard_selector
        
Internationalisation

    GVV supporte l'internationalisation des vues.
    
    * La langue de GVV est définie dans le fichier config/config.php
    $config['language'] = 'dutch';
    
    * La valeur spécifiée doit correspondre à un répertoire sous application/language ainsi qu'à un 
    fichier javascript sous assets/javascript. On y trouve déjà les fichiers french_lang.js et english_lang.js.
    
    * Pour ajouter une nouvelle langue par exemple 'dutch' il suffit de créer un répertoire
    application/language/dutch et un fichier assets/javascript/dutch_lang.js. Il est conseillé de copier
    les fichiers existant pour l'anglais ou le français et de traduire les chaines de caractères.
    
    Conseils pour internationaliser le programme
        
        * Il est conseiller de créer un fichier de langue par controleur. Si les chaines à traduire
        sont communes à plusieurs controleurs les mettre dans gvv_lang.php
        
        * repérer dans le source du programme les chaines de caractères affichées à l'écran. Normalement
        si le modèle MVC etait parfaimenent respecté, on ne devrait en trouver que dans les vues. Me contacter
        si vous trouvez des chaines à traduire ailleurs que dans les vues.
        
        * les remplacer par des appels à $this->lang->line("key") ou key correspond à une chaine de caractère
        à traduire présente dans le fichier gvv_lang.php ou le fichier de langue spécifique au controleur.
          
        4) il faut créer dans les fichiers spécifiques à chaque controleur des clé ayant la structure
        
        gvv_tablename_field_fieldname et gvv_tablename_short_field_fieldname
        tablename = nom de la table de base de données
        fieldname = nom du champ de la table.
        
        La version short est utilisée pour afficher les entetes de table
        La version longue est utilisée pour nommer les formulaires.
     
        dans les vues remplacer toutes les occurences de 
        $this->gvvmetadata->field_long_name("tablename", "fieldname") 
        par
        $this->lang->line("gvv_tablename_field_fieldname")
        
        et definir les entrées pour gvv_tablename_field_fieldname
        dans le fichier du controleur

Uploading

        <form action="file-echo2.php" method="post" enctype="multipart/form-data">
        
Export CSV et Pdf
-----------------

    L'export des données en CSV et Pdf mériterait un certain refactoring, pour l'instant
    la méthode a été plus moins recopié à chaque fois.
    
    Il faudrait:
    
    * pouvoir utiliser la même source de données quelque soit la sortie
        - page WEB
        - Excel
        - Pdf
        
    Pour l'instant il y a quelques points qui rendent cela impossible (des liens de navigation
    HTML sont parfois directement générés par le modêle).
    
    * Séparer les services de bas niveau
        - Génération d'un fichier CSV ou d'un document PDF
        - Ajout d'un tableau CSV ou PDF dans un fichier ou document existant
    
    * de la connaissance des MetaDonnées (Génération des titres et entêtes)
    
    * Des services de haut niveau qui combinent tout cela

    
        
Impression des tarifs
---------------------

    * Les tarifs doivent pouvoir être affichés/imprimés pour une date donnée.
    
    * Le plus simple est peut-être de leur mettre une date de fin, défaut: 31/12/2099
    migration.php
    015_tarifs_date_de_fin.php
    tarifs_lang
    GVVMetadata
    
    
    
    
    