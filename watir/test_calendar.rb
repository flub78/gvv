# encoding: utf-8
# GVV Watir test
#
# Test de la gestion des présences dans le calendrier Google
#
# Problème: c'est du Jquery et même Selenium n'arrive pas à enregistrer
# les séquences. Le test risque d'être partiel.
#
require './gvv_test.rb'
require File.dirname(__FILE__) + '/reset_database.rb'

class TestCalendar < GVVTest
  # --------------------------------------------------------------------------------
  # Run before every test
  # --------------------------------------------------------------------------------
  def setup
    super
    self.db_connect
    self.login('panoramix', 'password')
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
  # Test test
  # --------------------------------------------------------------------------------
  def test_calendar_crud
    
    description('basic calendar operations')
    
    # Selection de la case du jour
    # <td class="fc-day ui-widget-content fc-fri fc-past" data-date="2015-05-08"></td>
    # <td class="fc-day ui-widget-content fc-sat fc-today ui-state-highlight" data-date="2015-05-23"></td>

    # Création
    @b.goto @root_url
    today = @b.td(:class => 'fc-today')
    today.click

    # Attention il faut un _ dans :data_date
    # day = @b.td(:data_date => "2015-05-08")

    #    # <select name="mlogin" id="mlogin">
    #    <option value="" selected="selected"></option>
    #    <option value="abraracourcix">Chef Abraracourcix</option>
    #    <option value="bonemine">Chef Bonemine</option>
    #    <option value="goudurix">Chef Goudurix</option>
    #    <option value="panoramix">Druide Panoramix</option>
    #    <option value="asterix">Legaulois Astérix</option>
    #    <option value="obelix">Legaulois Obélix</option>
    #    </select>
    @b.select_list(:name => 'mlogin').select('Chef Goudurix')
    #
    #    <select name="role" id="role">
    #    <option value="" selected="selected"></option>
    #    <option value="Absent">absent</option>
    #    <option value="Inst">instructeur</option>
    #    <option value="Rem">remorqueur</option>
    #    <option value="Entretien">entretien</option>
    #    <option value="Elève">élève</option>
    #    <option value="Elève campagne">élève campagne</option>
    #    <option value="Solo">vol solo</option>
    #    <option value="Circuit">circuit solo</option>
    #    <option value="Simu">simulateur</option>
    #    <option value="Cours">cours théorique</option>
    #    </select>
    @b.select_list(:name => 'role').select('élève')

    #    <input name="commentaire" id="commentaire" value="" size="32" type="text">
    @b.text_field(:name => 'commentaire').set 'Répondez sur Facebook'

    #    <button aria-disabled="false" role="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" type="button"><span class="ui-button-text">Enregistrer</span></button>
    #    <button aria-disabled="false" role="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" type="button"><span class="ui-button-text">Abandon</span></button>
    @b.button(:type => 'button', :text => 'Enregistrer').click

    # quelques secondes pour la communication avec le serveur Google
    sleep(4)

    # Lecture
    #    <span class="fc-title">Chef Goudurix, élève</span>
    # Selection de l'événement qu'on vient de créer
    event = @b.span(:class => 'fc-title', :text => 'Chef Goudurix, élève')
    event.click
    
    # Update
    # On change la date et le commentaire
    # <input name="date_ajout" id="date_ajout" value="" size="12" class="datepicker hasDatepicker" title="JJ/MM/AAAA" type="text">
    event_date = @b.text_field(:name => 'date_ajout').value
    match = event_date.match(/(\d+)\/(\d+)\/(\d+)/)
    if (match)
      day = match[1].to_i
      month = match[2].to_i
      year = match[3].to_i
      time = Time.local(year, month, day) - 24 * 3600
      yesterday = time.strftime("%d/%m/%Y")
      puts "date = #{event_date}"
      puts "yesterday = #{yesterday}"
      
      @b.text_field(:name => 'date_ajout').set yesterday 
      @b.text_field(:name => 'commentaire').set 'Contactez moi sur Facebook'
      @b.button(:type => 'button', :text => 'Enregistrer').click
    end
     
    sleep(4)
    screenshot('scr_calendar.png')

    # Suppression
    # <button aria-disabled="false" role="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" type="button"><span class="ui-button-text">Supprimer</span></button>
    event = @b.span(:class => 'fc-title', :text => 'Chef Goudurix, élève')
    event.click
    @b.button(:type => 'button', :text => 'Supprimer').click
    
    # On vérifie la destruction
    event = @b.span(:class => 'fc-title', :text => 'Chef Goudurix, élève')
    begin
      event.click
      assert(false, "Présence non supprimée")     
    rescue Exception => e
      assert(true, "Présence supprimée")
    end

  end

end
