Je vais vous aider à importer le fichier CSV dans la base de données ayant le schéma défini dans le fichier SQL.

Après analyse des deux fichiers, plusieurs modifications sont nécessaires pour aligner les données du CSV avec la structure de la table `vols_decouverte`.

## Problèmes identifiés dans le CSV

1. **Format de date** : Les dates dans le CSV sont au format JJ/MM/AA, mais SQL attend un format YYYY-MM-DD
2. **Colonnes manquantes** : Plusieurs colonnes présentes dans la table SQL ne figurent pas dans le CSV
3. **Colonnes superflues** : Le CSV contient des colonnes qui ne correspondent pas directement au schéma
4. **Données incohérentes** : Certaines valeurs sont mal formatées ou contiennent des caractères problématiques
5. **Gestion des valeurs nulles** : De nombreuses cellules vides doivent être gérées correctement

## Modifications requises

Voici les modifications nécessaires pour rendre le CSV compatible avec le schéma SQL :

1. **Renommer et réorganiser les colonnes** pour correspondre au schéma SQL :
   - `n°` → `id`
   - `Date de vente` → `date_vente` (reformatée en YYYY-MM-DD)
   - `Type de vol` → `product`
   - `Prix` → `prix`
   - `mode règle` → `paiement`
   - `Bénéficiaire` → `beneficiaire`
   - `N° à contacter` → `beneficiaire_tel`
   - `Nb personnes` → `nb_personnes`
   - `Date du vol` → `date_vol` (reformatée en YYYY-MM-DD)
   - `Appareil` → `airplane_immat`
   - `Pilote` → `pilote`

2. **Ajouter les colonnes manquantes** avec des valeurs par défaut ou NULL :
   - `club` (boolean) - déterminer selon la nature du vol
   - `saisie_par` - utiliser la valeur de la colonne `opérateur` quand disponible
   - `de_la_part` - NULL si non disponible
   - `occasion` - NULL si non disponible
   - `participation` - NULL si non disponible
   - `beneficiaire_email` - NULL si non disponible
   - `urgence` - NULL si non disponible
   - `date_planning` - NULL si non disponible
   - `time_planning` - NULL si non disponible
   - `time_vol` - NULL si non disponible
   - `cancelled` - Déduire de la présence de mentions "Annulé"

3. **Nettoyer les données** :
   - Standardiser les types de vol (bs, BS, BS2, FAL, etc.)
   - Éliminer les espaces supplémentaires et les caractères spéciaux
   - Gérer les valeurs nulles (représentées par "X", "?", ou espaces vides)
   - Convertir les prix dans le format décimal approprié

4. **Traiter les cas spéciaux** :
   - Gérer les bons annulés (marquer cancelled = 1)
   - Traiter les vols offerts (prix = 0 avec indication dans occasion)

Voici un script SQL qui pourrait être utilisé pour préparer l'importation après avoir nettoyé le CSV :

```antml:function_calls
<invoke name="artifacts">
<parameter name="command">create</parameter>
<parameter name="id">import_script</parameter>
<parameter name="type">application/vnd.ant.code</parameter>
<parameter name="language">sql</parameter>
<parameter name="title">Script d'importation CSV vers SQL</parameter>
<parameter name="content">-- Supposons que les données CSV ont été importées dans une table temporaire 'temp_vols'
-- Voici un script pour transférer les données vers la table finale

-- Créer une table temporaire pour importer les données brutes du CSV
CREATE TEMPORARY TABLE temp_vols (
  numero VARCHAR(10),
  operateur VARCHAR(32),
  date_vente VARCHAR(20),
  type_vol VARCHAR(32),
  prix VARCHAR(20),
  mode_reglement VARCHAR(32),
  beneficiaire VARCHAR(64),
  contact VARCHAR(64),
  nb_personnes VARCHAR(5),
  date_vol VARCHAR(20),
  appareil VARCHAR(10),
  pilote VARCHAR(64),
  notes VARCHAR(255)
);

-- LOAD DATA INFILE '/chemin/vers/VD_2025.csv'
-- INTO TABLE temp_vols
-- FIELDS TERMINATED BY ',' ENCLOSED BY '"'
-- LINES TERMINATED BY '\n'
-- IGNORE 1 LINES;

-- Insérer les données dans la table finale avec les transformations nécessaires
INSERT INTO vols_decouverte (
  id,
  date_vente,
  club,
  product,
  saisie_par,
  beneficiaire,
  de_la_part,
  occasion,
  paiement,
  beneficiaire_tel,
  nb_personnes,
  date_vol,
  pilote,
  airplane_immat,
  cancelled,
  prix
)
SELECT 
  SUBSTRING(numero, 3) AS id, -- Supprimer le préfixe (ex: 240066 -> 40066)
  STR_TO_DATE(date_vente, '%d/%m/%y') AS date_vente,
  CASE 
    WHEN LOWER(type_vol) LIKE '%ulm%' THEN 0
    ELSE 1
  END AS club,
  TRIM(UPPER(type_vol)) AS product,
  COALESCE(NULLIF(TRIM(operateur), ''), 'IMPORT') AS saisie_par,
  NULLIF(TRIM(beneficiaire), '') AS beneficiaire,
  NULL AS de_la_part,
  CASE 
    WHEN LOWER(prix) LIKE '%offert%' OR LOWER(beneficiaire) LIKE '%offert%' THEN 'Offert'
    ELSE NULL
  END AS occasion,
  CASE 
    WHEN LOWER(mode_reglement) = 'ch' THEN 'Chèque'
    WHEN LOWER(mode_reglement) = 'esp' THEN 'Espèces'
    WHEN LOWER(mode_reglement) = 'cb' THEN 'Carte bancaire'
    WHEN LOWER(mode_reglement) = 'vi' THEN 'Virement'
    ELSE mode_reglement
  END AS paiement,
  NULLIF(contact, '') AS beneficiaire_tel,
  NULLIF(TRIM(REPLACE(nb_personnes, ',', '.')), '') AS nb_personnes,
  CASE 
    WHEN date_vol = '' OR date_vol IS NULL THEN NULL
    ELSE STR_TO_DATE(date_vol, '%d/%m/%y')
  END AS date_vol,
  NULLIF(pilote, '') AS pilote,
  NULLIF(appareil, '') AS airplane_immat,
  CASE
    WHEN LOWER(contact) LIKE '%annulé%' OR LOWER(notes) LIKE '%annulé%' OR beneficiaire LIKE '%annulé%' THEN 1
    ELSE 0
  END AS cancelled,
  CASE
    WHEN prix REGEXP '^[0-9]+$' THEN CAST(prix AS DECIMAL(14,2))
    WHEN LOWER(prix) LIKE '%offert%' THEN 0.00
    ELSE NULL
  END AS prix
FROM temp_vols;
</parameter>
</invoke>