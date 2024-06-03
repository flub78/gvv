# encoding: utf-8
# GVV Watir test
# Create minimum data to prepare others tests
#
# Normally it is not recommended for tests to be dependent on each others.
# However several GVV features like billing and accounting can only
# run when some data already exist. Before to bill anything, you must have
# some pilotes, some gliders, some prices already define.
#
# This module make sure that this minimal environment exists.
#
require './gvv_test.rb'

class TestData < GVVTest
  # --------------------------------------------------------------------------------
  # Run before every test
  # --------------------------------------------------------------------------------
  def setup
    super
    self.login('testadmin', 'password')
    self.db_connect
  end

  # --------------------------------------------------------------------------------
  # Executed after each test
  # --------------------------------------------------------------------------------
  def teardown
    self.db_disconnect
    self.logout()
    super
  end

  # --------------------------------------------------------------------------------
  # Creates accounts
  # 706 Ventes d'heures de vol planeur
  # 706 Heures de vol ULM
  # 706 Vente de remorques
  # 706 Vols d'initiation
  # 756 Cotisations
  # 606 Essence + Huile
  # 606 Frais de bureaux
  # 616 Assurance planeur
  # 615 Entretien 
  # --------------------------------------------------------------------------------
  def create_accounts
    action = 'comptes/create'
    table = 'comptes'
    success = []
    comptes = [
      ["Vols d'intiation","706 Prestations de services","Vols d'initiation"],
      ["Heures de vol + remorqués","706 Prestations de services","Heures de vol et remorqués"],
      ["Fonds associatifs","102 Fonds associatif (sans droit de reprise)","Fonds associatifs"],
      ["Essence + Huile","606 Achats non stockés de matières et fournitures","Essence + Huile"],
      ["Subventions D.R.D.J.S","74 Subventions d'exploitation","Subventions  D.R.D.J.S au titre d u C.N.D.S"],
      ["Frais remorqueur","615 Entretien et réparations","Frais remorqueur (suivi de nav, OSAC et entretien)"],
      ["Subvention Conseil Général","74 Subventions d'exploitation","Subventions conseil général"],
      ["Bourses FFVV","754 Retour des Fédérations (bourses).","Bourses FFVV"],
      ["C.D.V.V.S","74 Subventions d'exploitation","Aides comité départemental"],
      ["Encaissement licences FFVV","753 Assurances licences FFVV.","Licences assurances de la F.F.V.V"],
      ["Librairie aéro","75 Autres produits de gestion courante","Manuels de vo à voile, carnets de vol"],
      ["Intérêts","76 Produits financiers","Interêts de livrets d'épargne"],
      ["Convoyages, transport","625 Déplacement, missions et reception","Convoyages, transports, déplacements"],
      ["Ventes diverses, T-shirts","708 Produit des activités annexes","Ventes diverses, maillots"],
      ["Frais de bureau","606 Achats non stockés de matières et fournitures","Frais de bureau"],
      ["Cotisations","756 Cotisations","Cotisation des membres"],
      ["Banque vol à voile","512 Banque","Banque compte courant"],
      ["C.R.V.V.P","74 Subventions d'exploitation","Comité régional de vol à voile Picard"],
      ["Frais d'entretien planeurs","615 Entretien et réparations","Entretien planeur"],
      ["Assurances planeur","616 Assurances","Assurance Casse et RC"],
      ["G-NAV planeurs","615 Entretien et réparations","G-NAV planeurs"],
      ["Entretien remorques","615 Entretien et réparations","Frais associés aux remorques"],
      ["Parachutes","615 Entretien et réparations","Entretien parachutes"],
      ["Frais d'atelier","615 Entretien et réparations","Frais d'atelier, documentation, véhicule de piste"],
      ["Licences F.F.V.V","628 Divers, cotisations","Licences et cotisation FFVV"],
      ["Communications","626 Frais postaux et télécommunications","Téléphone, Internet, timbres"],
      ["Librairie aéronautique, carnets de vol","607 Achats de marchandises","Manuels de l'élève pilote, carnets de vol"],
      ["Cotisation F.F.V.V","628 Divers, cotisations","Cotisation club à la FFVV"],
      ["Cotisation C.R.V.V.P","628 Divers, cotisations","Cotisation au comité régional de vol à voile"],
      ["Assurances Remorqueur","616 Assurances","Assurance Casse et RC"],
      ["Report à nouveau créditeur","110 Report à nouveau (solde créditeur)",],
      ["Report à nouveau débiteur","119 Report à nouveau (solde débiteur)",],
      ["CRVVP - Subventions","441 Etat - Subventions","CRVVP - Subventions"],
      ["Frais repas","623 Publicité, Publications, Relations publiques","Frais repas"],
      ["Recettes repas","708 Produit des activités annexes","Recettes repas"],
      ["Remorqueur F-BLIT","215 Matériel","Immobilisation Remorqueur F-BLIT"],
      ["Heures de vol et lancements","611 Sous-traitance générale","Achat d'heures de vols ou de lancements à d'autres clubs"],
      ["Achats radios, instruments","606 Achats non stockés de matières et fournitures","Achat matériel, équipement"],
      ["Ask21 F-CJRG","215 Matériel","Immobilisation Ask21 F-CJRG"],
      ["Twin F-CFYD","215 Matériel","Immobilisation Twin F-CFYD"],
      ["WA30 F-CDUC","215 Matériel","Immobilisation WA30 F-CDUC"],
      ["C101 F-CGNP","215 Matériel","Immobilisation C101 F-CGNP"],
      ["Caisse","512 Banque","Caisse"],
      ["PIWI F-CICA","215 Matériel","Immobilisation planeur PIWI"],
      ["C101 F-CGHF","215 Matériel","Immobilisation C101 F-CGHF"],
      ["Remorques, outillage","215 Matériel","Immobilisation remorques et outillage"],
      ["Parachutes","215 Matériel","Immobilisation parachutes"],
      ["C101 F-CGBR","215 Matériel","Immobilisation C101 F-CGBR"],
      ["Redistributions Bourses F.F.V.V","657 Subventions versées par l’association","Redistribution des Bourses F.F.V.V"],
      ["Opale Aero Services","401 Fournisseurs","Compte fournisseur Opale"],
      ["Remorqueur Dynamic F-JUFA","215 Matériel","Immobilisation Dynamic"],
      ["Convoyage","774 Autres produits exceptionnels","Facturation aux pilotes de frais de convoyage"],
      ["Valorisation d'immobilisations","781 Reprises sur amortissements et provisions","Réévaluation à la hausse d'immobilisation - Améliorations - Hausse du marché"],
      ["Immobilisation Janus F-CFAJ","215 Matériel","Janus F-CFAJ"],
      ["Dotation aux amortissements et dépréciations","68 Dotation aux Amortissements","Dotation aux amortissements et dépréciations de matériel"],
      ["Subvention C.N.D.S","74 Subventions d'exploitation","Centre National pour le Développement du Sport"],
      ["Vol moteur","46 Débiteurs divers et créditeur divers","Opérations avec le vol moteur"],
      ["Résultat de l’exercice (excédent)","120 Résultat de l’exercice (excédent)","Résultat de l’exercice (excédent)"],
      ["Résultat de l’exercice (déficit)","129 Résultat de l’exercice (déficit)","Résultat de l’exercice (déficit)"],
      ["Ventes de planeurs et avions","775 Produits des cessions d’éléments d’actif","Ventes de planeurs et avions"],
      ["Heures ULM","706 Prestations de services","Heures de vol ULM"],
      ["Remorqués","706 Prestations de services","Vente de remorqués"],
      ["Heures planeurs","706 Prestations de services","Ventes d'heures de vol planeur"],
      ["Amortissements Remorqueur","281 Amortissement des immobilisations corporelles","Amortissements Remorqueur"],
      ["Ammortissement planeurs","281 Amortissement des immobilisations corporelles","Amortissement et provisions pour remplacements"],
      ["Créances irrécouvrables","654 Pertes sur créances irrécouvrables","Créances irrécouvrables, factures qui ne seront jamais payées"],
      ["Frais financiers","66 Charges financières","Frais cartes bleues, agios, intérêts"],
      ["Dons divers","74 Subventions d'exploitation","Dons, pourboires"],
      ["Divers, Cotisations club","628 Divers, cotisations","Reversements divers"],
      ["C101 F-CGBC","215 Matériel","Immobilisation C101 F-CGBR"]]

      comptes.each do |elt|
        values = [
              {name: 'nom',   value: elt[0], type: 'text_field'},
              {name: 'codec', value: elt[1], type: 'select'},
              {name: 'desc',  value: elt[2], type: 'text_field'}]
        self.fill_form(table, action, values, success, 1)
      end
  end

  # --------------------------------------------------------------------------------
  # Creates prices
  # --------------------------------------------------------------------------------
  def create_prices
    
    action = 'tarifs/create'
    table = 'tarifs'
    success = []
    tarifs = [
      ["Gratuit", "Non facturé", 0.00, "(706) Heures planeurs", false, 0, ""],
      ["Forfait heure", "Forfait heures de vol", 500.00, "(706) Heures planeurs", true, 0, ""],
      ["Heure planeur", "Heure planeur", 20.00, "(706) Heures planeurs", true, 0, ""],
      ["Déjeuné", "Déjeuné", 10.00, "(708) Recettes repas", false, 0, ""],
      ["Diner", "Diner", 12.00, "(708) Recettes repas", false, 0, ""],
      ["Treuillé", "Treuillé", 8.00, "(706) Remorqués", true, 0, ""],
      ["Treuillé par 10", "Treuillé par 10", 70.00, "(706) Remorqués", true, 10, "treuillé"]
    ]

    tarifs.each do |elt|
      values = [
            {name: 'reference',   value: elt[0], type: 'text_field'},
            {name: 'description', value: elt[1], type: 'text_field'},
            {name: 'prix', value: elt[2], type: 'text_field'},
            {name: 'compte', value: elt[3], type: 'select'},
            {name: 'public', value: "1", type: elt[4] ? 'checkbox' : 'checkbox-clear'},
            {name: 'nb_tickets',   value: elt[5], type: 'text_field'},
            {name: 'type_ticket',   value: elt[6], type: 'select'}
            ]
      self.fill_form(table, action, values, success, 1)
    end
   
  end

  # --------------------------------------------------------------------------------
  # Creates gliders
  # --------------------------------------------------------------------------------
  def create_gliders
  end

  # --------------------------------------------------------------------------------
  # Creates airplanes
  # --------------------------------------------------------------------------------
  def create_airplanes
  end

  # --------------------------------------------------------------------------------
  # Creates pilots
  # --------------------------------------------------------------------------------
  def create_pilots
  end

  # --------------------------------------------------------------------------------
  # Check access to unknow members
  # --------------------------------------------------------------------------------
  def test_generate_data
    description('it is possible to create an initial data set', 'GVV is initialized')
    create_accounts()
    create_prices()
    create_gliders()
    create_airplanes()
    create_pilots()
  end

end
