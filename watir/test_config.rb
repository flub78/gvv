# GVV Watir test
# Test de la configuration club
#
require './gvv_test.rb'

class TestConfig < GVVTest
  
  def test_change_config

    self.login('testadmin', 'password')
     
    # Change la configuration du club
    self.goto  @root_url + 'config', "fct_config_before_change.png" 
    check(@b.text.include?("Configuration club"), "Titre configuration")
    
    initial_values = Hash.new
    
    values = {
      'sigle_club' => 'Le super club de vol a voile',
      'nom_club' => 'The Club',
      'code_club' =>  '1000000'}
    
        
    values.each do |key, val|
      tf = @b.text_field(:name => key)
      initial_values[key] = tf.value
      tf.set val
    end
    @b.select_list(:name => 'palette').select 'eggplant'
    
    # Validation des modifs
    @b.button(:value => 'Valider').click
    check(!@b.text.include?("error writing"), "Writing succesful")
    check(@b.text.include?("Configuration modifi"), "Configuration change 1 ACK")

    # force le rafraichissement de la config
    self.logout()
    self.login('testadmin', 'password')

    # back to configuration
    self.goto(@root_url + 'config', "fct_config_after_change.png")
    @b.wait_until {@b.text.include? "Peignot"}  
    # Vérification des modifs
    values.each do |key, val|
      tf = @b.text_field(:name => key)
      check(tf.value == values[key], "#{key} config value: current=#{tf.value}, expected=#{values[key]}")
      tf.set initial_values[key]      # back to previous value
    end
    color = @b.select_list(:name => 'palette').value
    check(color == 'eggplant', 'Palette changed')
    
    # Création d'un message du jour
    mod = "Message du jour " + Time.now.to_s
    @b.textarea(:name => 'mod').set mod
    
    # Validation de la restauration et mod
    @b.button(:value => 'Valider').click
    check(!@b.text.include?("error writing"), "Writing succesful")
    check(@b.text.include?("Configuration modifi"), "Configuration change 2 ACK")
    
    # Check message du jour
    # puts @b.text
    
    # force le rafraichissement de la config
    self.logout()
    self.login('testadmin', 'password')
    
    @b.goto  @root_url + 'calendar'
    @b.wait_until {@b.text.include? "Peignot"}  
    check(@b.text.include?(mod), "Message du jour")

    if (@b.text.include?(mod))
      @b.checkbox(:id => 'no_mod').set  # checkbox selection
      @b.button(:id => 'close_mod_dialog').click  
    end
    
    # Effacement du message du jour qui a tendance à perturber d'autres tests
    self.goto(@root_url + 'config', "fct_config_before_restore.png")
    @b.wait_until {@b.text.include? "Peignot"}
    @b.textarea(:name => 'mod').set ""
    @b.button(:value => 'Valider').click
    self.logout()


  end

end
