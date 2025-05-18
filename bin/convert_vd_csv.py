<invoke name="artifacts">
<parameter name="command">update</parameter>
<parameter name="id">csv_converter</parameter>
<parameter name="old_str"># Fonction principale

def process_csv():
    rows_processed = 0
    
    with open(input_file, 'r', encoding='utf-8') as infile, \
         open(output_file, 'w', newline='', encoding='utf-8') as outfile:
        
        # Lire le CSV d'entrée
        reader = csv.DictReader(infile)
        
        # Définir les colonnes du CSV de sortie
        fieldnames = [
            'id', 'date_vente', 'club', 'product', 'saisie_par', 'beneficiaire',
            'de_la_part', 'occasion', 'paiement', 'participation', 'beneficiaire_email',
            'beneficiaire_tel', 'urgence', 'date_planning', 'time_planning',
            'date_vol', 'time_vol', 'pilote', 'airplane_immat', 'cancelled',
            'nb_personnes', 'prix'
        ]
        
        writer = csv.DictWriter(outfile, fieldnames=fieldnames)
        writer.writeheader()
        
        # Traiter chaque ligne
        for row in reader:
            try:
                # Créer une nouvelle ligne pour le CSV de sortie
                new_row = {}
                
                # ID - Enlever le préfixe (ex: 240066 -> 40066)
                if 'n°' in row and row['n°']:
                    new_row['id'] = row['n°'].strip().replace('n°', '')
                else:
                    new_row['id'] = ''
                
                # Date de vente
                if 'Date de vente' in row:
                    new_row['date_vente'] = convert_date(row['Date de vente'])
                else:
                    new_row['date_vente'] = ''
                
                # Club (0 pour ULM, 1 pour avion)
                if 'Type de vol' in row and row['Type de vol']:
                    new_row['club'] = '0' if 'ulm' in row['Type de vol'].lower() else '1'
                else:
                    new_row['club'] = '1'  # Par défaut
                
                # Product (Type de vol)
                if 'Type de vol' in row:
                    new_row['product'] = standardize_product(row['Type de vol'])
                else:
                    new_row['product'] = ''
                
                # Saisie par (opérateur)
                if 'opérateur' in row:
                    new_row['saisie_par'] = clean_value(row['opérateur'])
                else:
                    new_row['saisie_par'] = 'IMPORT'  # Valeur par défaut
                
                # Bénéficiaire
                if 'Bénéficiaire' in row:
                    new_row['beneficiaire'] = clean_value(row['Bénéficiaire'])
                else:
                    new_row['beneficiaire'] = ''
                
                # De la part (non présent dans le CSV)
                new_row['de_la_part'] = ''
                
                # Occasion (déduire des notes ou du contexte)
                if 'Prix' in row and row['Prix'] and 'offert' in row['Prix'].lower():
                    new_row['occasion'] = 'Offert'
                else:
                    new_row['occasion'] = ''
                
                # Paiement (mode de règlement)
                if 'mode règle,' in row:
                    payment = clean_value(row['mode règle,'])
                    if payment.lower() == 'ch':
                        new_row['paiement'] = 'Chèque'
                    elif payment.lower() == 'esp':
                        new_row['paiement'] = 'Espèces'
                    elif payment.lower() == 'cb':
                        new_row['paiement'] = 'Carte bancaire'
                    elif payment.lower() == 'vi':
                        new_row['paiement'] = 'Virement'
                    else:
                        new_row['paiement'] = payment
                else:
                    new_row['paiement'] = ''
                
                # Participation (non présent dans le CSV)
                new_row['participation'] = ''
                
                # Email du bénéficiaire (non présent dans le CSV)
                new_row['beneficiaire_email'] = ''
                
                # Téléphone du bénéficiaire
                if 'N° à contacter' in row:
                    new_row['beneficiaire_tel'] = clean_value(row['N° à contacter'])
                else:
                    new_row['beneficiaire_tel'] = ''
                
                # Urgence (non présent dans le CSV)
                new_row['urgence'] = ''
                
                # Date de planning (utiliser la date du vol par défaut)
                if 'Date du vol' in row:
                    new_row['date_planning'] = convert_date(row['Date du vol'])
                else:
                    new_row['date_planning'] = ''
                
                # Heure de planning (non présent dans le CSV)
                new_row['time_planning'] = ''
                
                # Date du vol
                if 'Date du vol' in row:
                    new_row['date_vol'] = convert_date(row['Date du vol'])
                else:
                    new_row['date_vol'] = ''
                
                # Heure du vol (non présent dans le CSV)
                new_row['time_vol'] = ''
                
                # Pilote
                if 'Pilote' in row:
                    new_row['pilote'] = clean_value(row['Pilote'])
                else:
                    new_row['pilote'] = ''
                
                # Immatriculation de l'appareil
                if 'Appareil' in row:
                    new_row['airplane_immat'] = clean_value(row['Appareil'])
                else:
                    new_row['airplane_immat'] = ''
                
                # Annulé
                new_row['cancelled'] = is_cancelled(row)
                
                # Nombre de personnes
                if 'Nb personnes' in row:
                    new_row['nb_personnes'] = clean_value(row['Nb personnes'])
                else:
                    new_row['nb_personnes'] = ''
                
                # Prix
                if 'Prix' in row:
                    new_row['prix'] = convert_price(row['Prix'])
                else:
                    new_row['prix'] = ''
                
                # Écrire la ligne dans le fichier de sortie
                writer.writerow(new_row)
                rows_processed += 1
                
            except Exception as e:
                print(f"Erreur lors du traitement de la ligne: {row}")
                print(f"Détail de l'erreur: {e}")
    
    print(f"Conversion terminée. {rows_processed} lignes traitées.")
    print(f"Fichier de sortie créé: {output_file}")
    
    </parameter>
<parameter name="new_str"># Fonction principale

def process_csv():
    rows_processed = 0
    
    with open(input_file, 'r', encoding='utf-8') as infile, \
         open(output_file, 'w', newline='', encoding='utf-8') as outfile:
        
        # Lire le CSV d'entrée
        reader = csv.DictReader(infile)
        
        # Définir les colonnes du CSV de sortie selon le schéma SQL
        fieldnames = [
            'id', 'date_vente', 'club', 'product', 'saisie_par', 'beneficiaire',
            'de_la_part', 'occasion', 'paiement', 'participation', 'beneficiaire_email',
            'beneficiaire_tel', 'urgence', 'date_planning', 'time_planning',
            'date_vol', 'time_vol', 'pilote', 'airplane_immat', 'cancelled',
            'nb_personnes', 'prix'
        ]
        
        writer = csv.DictWriter(outfile, fieldnames=fieldnames)
        writer.writeheader()
        
        # Traiter chaque ligne
        for row in reader:
            try:
                # Créer une nouvelle ligne pour le CSV de sortie
                new_row = {}
                
                # Traiter les colonnes qui existent dans le CSV source
                for source_col, target_col in COLUMN_MAPPING.items():
                    if source_col in row:
                        if source_col == 'n°':
                            # Traitement spécial pour l'ID
                            new_row[target_col] = row[source_col].strip().replace('n°', '')
                        elif source_col == 'Date de vente' or source_col == 'Date du vol':
                            # Convertir les dates
                            new_row[target_col] = convert_date(row[source_col])
                        elif source_col == 'Type de vol':
                            # Standardiser le type de vol
                            new_row[target_col] = standardize_product(row[source_col])
                        elif source_col == 'Prix':
                            # Convertir le prix
                            new_row[target_col] = convert_price(row[source_col])
                        elif source_col == 'mode règle,':
                            # Convertir le mode de paiement
                            payment = clean_value(row[source_col])
                            if payment.lower() == 'ch':
                                new_row[target_col] = 'Chèque'
                            elif payment.lower() == 'esp':
                                new_row[target_col] = 'Espèces'
                            elif payment.lower() == 'cb':
                                new_row[target_col] = 'Carte bancaire'
                            elif payment.lower() == 'vi':
                                new_row[target_col] = 'Virement'
                            else:
                                new_row[target_col] = payment
                        else:
                            # Nettoyage général pour les autres colonnes
                            new_row[target_col] = clean_value(row[source_col])
                    else:
                        # Colonnes manquantes dans le CSV mais présentes dans le mapping
                        if target_col == 'saisie_par':
                            new_row[target_col] = 'IMPORT'  # Valeur par défaut
                        else:
                            new_row[target_col] = ''
                
                # Ajouter les colonnes manquantes avec les valeurs calculées
                for target_col, value_func in MISSING_COLUMNS.items():
                    if target_col not in new_row:  # Éviter de remplacer les colonnes déjà traitées
                        new_row[target_col] = value_func(row)
                
                # Écrire la ligne dans le fichier de sortie
                writer.writerow(new_row)
                rows_processed += 1
                
            except Exception as e:
                print(f"Erreur lors du traitement de la ligne: {row}")
                print(f"Détail de l'erreur: {e}")
    
    print(f"Conversion terminée. {rows_processed} lignes traitées.")
    print(f"Fichier de sortie créé: {output_file}")</parameter>
</invoke>