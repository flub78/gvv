<aside>

	<?php
	$menu = new menu();

	$CI = &get_instance();
	$url_club = $this->config->item('url_club');

	$submenu = array(
		array('label' => $this->lang->line("gvv_button_member"), 'url' => controller_url("calendar"), 'role' => ''),
		array('label' => $this->lang->line("gvv_button_club_admin"), 'url' => controller_url("welcome/ca"), 'role' => 'ca'),
		array('label' => $this->lang->line("gvv_button_accounter"), 'url' => controller_url("welcome/compta"), 'role' => 'tresorier'),
		array('label' => $this->lang->line("gvv_button_admin"), 'url' => controller_url("admin/page"), 'role' => 'admin'),
	);
	if (ENVIRONMENT == 'development') {
		$submenu[] = array('label' => "Licences", 'url' => site_url() . 'event/licences', 'role' => 'admin');
	}

	$menu_mode = array(
		'class' => 'menuheader',
		'submenu' => $submenu
	);

	$submenu = array(
		array('label' => $this->lang->line("gvv_button_help"), 'url' => "https://github.com/flub78/gvv/blob/main/README.md", 'role' => ''),
		array('label' => $this->lang->line("gvv_button_site"), 'url' => $url_club, 'role' => '')
	);

	if (ENVIRONMENT == 'development') {
		$submenu[] = array('label' => "Aide CodeIgniter", 'url' => base_url() . '/user_guide/', 'role' => 'admin');
		$submenu[] = array('label' => "pChart documentation", 'url' => "http://wiki.pchart.net/", 'role' => 'admin');
	}

	$menu_menu = array(
		'class' => 'menuheader',
		'submenu' => $submenu
	);

	echo '<div class="ui-widget">
	<div id="sidebar" class="ui-widget-content">
		<div class="element_sidebar">';
	$mode = $this->lang->line("gvv_label_mode");
	echo "<h3>$mode</h3>";
	echo $menu->html($menu_mode, 0, false, 'class="jbutton"');
	echo '</div>

		<div class="element_sidebar">';
	$menu_label = $this->lang->line("gvv_label_menu");
	echo "<h3>$menu_label</h3>";
	echo $menu->html($menu_menu, 0, false, 'class="jbutton"');
	echo '</div>
	</div>
</div>';
	?>

</aside>