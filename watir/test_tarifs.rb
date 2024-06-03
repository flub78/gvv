# encoding: utf-8
# GVV Watir test
# Test price list
#
require './gvv_test.rb'
require File.dirname(__FILE__) + '/reset_database.rb'


class TestTarifs < GVVTest
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
  # Creates prices
  # --------------------------------------------------------------------------------
  def create_prices
    
    action = 'tarifs/create'
    table = 'tarifs'
    success = []
      
    @initial_count = self.table_count(@db, 'tarifs')
    
    # Les références sont prefixées pour éviter les collisions avec des données réelles
      
    tarifs = [
      ["Test_Gratuit", "Non facturé", 0.00, "(706) Heures planeurs", false, 0, ""],
      ["Test_Forfait heure", "Forfait heures de vol", 500.00, "(706) Heures planeurs", true, 0, ""],
      ["Test_Heure planeur", "Heure planeur", 20.00, "(706) Heures planeurs", true, 0, ""],
#      ["Test_Déjeuné", "Déjeuné", 10.00, "(708) Recettes repas", false, 0, ""],
#      ["Test_Diner", "Diner", 12.00, "(708) Recettes repas", false, 0, ""],
#      ["Test_Treuillé", "Treuillé", 8.00, "(706) Remorqués", true, 0, ""],
      ["Test_Treuillé par 10", "Treuillé par 10", 70.00, "(706) Remorqués", true, 10, "treuillé"]
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
    
    @new_count = self.table_count(@db, 'tarifs')
    check(@new_count - @initial_count == tarifs.count, "#{tarifs.count} tarifs created")

  end

  # --------------------------------------------------------------------------------
  # look for a row mathing a pattern in a datatable
  # --------------------------------------------------------------------------------
  def look_for(url, pattern, page=1)
    @b.goto(@root_url + url)
    
    # #DataTables_Table_0_next
    current_page = 1
    while (current_page < page)
      @b.link(:id => 'DataTables_Table_0_next').click
      current_page += 1
    end
          
    self.screenshot 'scr_looking_for.png'

    # Recherche de la table, supposant qu'il n'y ai qu'une datatable
    cells = @b.table(:class => "datatable").tds
  
    row = nil
    
    cells.each_slice(10) do |slice|
      reference = slice[0].text
      description = slice[1].text
      date = slice[2].text
      fin = slice[3].text
      prix = slice[4].text
    
      delete_button = slice[8].a

      if (reference.match(pattern))
        row = slice
        break 
      end # if
    end

    return row    
  end
  
  # --------------------------------------------------------------------------------
  # Clone one test entry
  # --------------------------------------------------------------------------------
  def clone

    row = look_for('tarifs/page', /^Test_Forfait\s*heure/, 3)
        
    clone_button = row[9].a
    
    previous_count = @new_count
    clone_button.click
    
    @new_count = self.table_count(@db, 'tarifs')
    check(@new_count == previous_count + 1, "One price has been cloned")
     
  end

  # --------------------------------------------------------------------------------
  # Filter
  # --------------------------------------------------------------------------------
  def filter
    
    # pour l'instant impossible d'accéder au text "Affichage de l'élément x sur y
    # ni d'ouvrir les fieldset avec WATIR
    
    @b.goto(@root_url + 'tarifs/page')
#    datatable = @b.table(:class => "datatable")
#    
#    puts datatable.html
    
    puts "############################################################"
    fieldset = @b.fieldset(:class => "filtre").legend
    
    puts fieldset.html
    fieldset.click
    
  end
  
  # --------------------------------------------------------------------------------
  # Creates prices
  #
  # Détruit les prix générés pour le test.
  #
  # Cela implique l'interaction avec un tableau en utilisant WATIR.
  # Chaque ligne contient des chaines, et des balises <a/>
  # Il va falloir aller cliquer sur la seconde balise des lignes identifiés comme contenant
  # des valeurs de test.
  #
  # Navigation structure:
  # <table class="datatable">
  #   <thead>
  #     <tr>
  #       <th></th> *
  #     </th>
  #   </thead>
  #   <tr>
  #     <td> string </td> *
  #     <td> <a></a> </td> *
  #   </tr>   
  # --------------------------------------------------------------------------------
  def delete_prices
    
    existing_test_entries = true
    
    while (existing_test_entries)
      @b.goto(@root_url + 'tarifs/page')
    
      # goto last page
      @b.link(:id => 'DataTables_Table_0_last').click
            
      # return true for confirm to simulate clicking OK
      @b.execute_script("window.confirm = function() {return true}")
      
      existing_test_entries = false  
      
      # Recherche de la table, supposant qu'il n'y ai qu'une datatable
      cells = @b.table(:class => "datatable").tds
    
      cells.each_slice(10) do |slice|
        reference = slice[0].text
        description = slice[1].text
        date = slice[2].text
        fin = slice[3].text
        prix = slice[4].text
      
        delete_button = slice[8].a

        if (reference.match(/^Test_/))
          # puts "reference=#{reference}, description=#{description}, date=#{date}, fin=#{fin}, prix=#{prix}"
          # puts delete_button.html
          delete_button.click
          existing_test_entries = true
          break 
        end # if
      
      end # each_slice
    end # existing_test_entries

    @new_count = self.table_count(@db, 'tarifs')
    check(@new_count == @initial_count, "All test prices have been deleted")
    
  end

  # --------------------------------------------------------------------------------
  # Check access to unknow members
  # --------------------------------------------------------------------------------
  def test_generate_data
    description('basic prices mechanism', '', 'initial test data set is loaded')
    create_prices()
    clone()
    filter()
    delete_prices()
  end

end
