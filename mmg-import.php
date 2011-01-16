<?php
/*
Plugin Name: mmg import
Plugin URI: http://www.museumgam.es/
Description: mmg-import allows users to search for terms in museum APIs and import objects for use in museum metadata games (with the mmg plugin). 
Version: 0.1
Author: Mia Ridge
Author URI: http://openobjects.org.uk
License: GPL2

*/

/*
Copyright (C) 2010 Mia Ridge

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

// ### Add as plugin config setting so it's generalisable. Also db name, not just table names
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'www.museumgames.org.uk' || $_SERVER['HTTP_HOST'] == 'museumgames.org.uk') {
  define("table_prefix", "wp_mmg_");
} elseif ($_SERVER['HTTP_HOST'] == 'www.museumgam.es' || $_SERVER['HTTP_HOST'] == 'museumgam.es')  {
  define("table_prefix", "wplive_mmg_");
}

/////////// set up activation and deactivation stuff
register_activation_hook(__FILE__,'mmg_import_install');

function mmg_import_install() {
  // do stuff when installed
  global $wp_version;
  if (version_compare($wp_version, "3", "<")) {
    deactivate_plugins(basename(__FILE__)); // deactivate plugin
    wp_die("This plugin requires WordPress Version 3 or higher.");
    // also requires mmg for the tables, or should test for existence of mmg main plugin
    // also relies on curl so check for it 
  } else {
    // create the tables if mmg main plugin hasn't already been installed
 }
}


register_deactivation_hook(__FILE__,'mmg_import_uninstall');

function mmg_import_uninstall() {
  // do stuff
  // maybe call export thingy too?  
  // presumably delete settings from db?
}


/////////// set up option storing stuff
// create array of options
$mmg_import_options_arr=array(
  "mmg_import_api_key"=>'', 
  "mmg_import_api_url"=>'',
  );

// store them
update_option('mmg_import_plugin_options',$mmg_import_options_arr); 

// get them
$mmg_import_options_arr = get_option('mmg_import_plugin_options');

// use them
$mmg_import_api_key = $mmg_import_options_arr["mmg_import_api_key"];
$mmg_import_api_url = $mmg_import_options_arr["mmg_import_api_url"];
// end option array setup

// required in WP 3 but not earlier?
add_action('admin_menu', 'mmg_import_plugin_menu');

/////////// set up stuff for admin options pages
// add submenu item to existing WP menu
function mmg_import_plugin_menu() {
add_options_page('MMG Import settings page', 'MMG Import settings', 'manage_options', __FILE__, 'mmg_import_settings_page');
}

// call register settings function before admin pages rendered
add_action('admin_init', 'mmg_import_register_settings');

function mmg_import_register_settings() {
  // register settings - array, not individual
  register_setting('mmg-import-settings-group', 'mmg_import_settings_values');
}

// write out the plugin options form. Form field name must match option name.
// add other options here as necessary.
function mmg_import_settings_page() {
  
  if (!current_user_can('manage_options'))  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }

  ?>
  <div>
  <h2><?php _e('mmg import plugin options', 'mmg-import-plugin') ?></h2>
  <form method="post" action="options.php">
  <?php settings_fields('mmg-import-settings-group'); ?>
  <?php _e('API key (as provided by API publisher, if required)','mmg-import-plugin') ?> 
  
  <?php mmg_import_api_key(); ?>
  
  <?php _e('API search URL','mmg-import-plugin') ?> 
  
  <?php mmg_import_api_url(); ?>
  
  <p class="submit"><input type="submit" class="button-primary" value=<?php _e('Save changes', 'mmg-import-plugin') ?> /></p>
  </form>
  </div>
  <?php
}

// get options from array and display as fields
function mmg_import_api_key() {
  // load options array
  $mmg_import_options = get_option('mmg_import_settings_values');
  
  $mmg_import_api_key = $mmg_import_options['mmg_import_api_key'];
  
  // display form field
  echo '<input type="text" name="mmg_import_settings_values[mmg_import_api_key]" 
  value="'.esc_attr($mmg_import_api_key).'" />';
}

function mmg_import_api_url() {
  // load options array
  $mmg_import_options = get_option('mmg_import_settings_values');
  
  $mmg_import_api_url = $mmg_import_options['mmg_import_api_url'];
  
  // display form field
  echo '<input type="text" name="mmg_import_settings_values[mmg_import_api_url]" 
  value="'.esc_attr($mmg_import_api_url).'" />';
}

/*
 * set up shortcode Sample: [mmg_import]
 */
function mmgImportShortCode($atts, $content=null) {
  
  if(@is_file(ABSPATH.'/wp-content/plugins/mmg-import/mmg_import_functions.php')) {
      include_once(ABSPATH.'/wp-content/plugins/mmg-import/mmg_import_functions.php'); 
  }
  
  //$term = mmgImportCheckForParams();
  
  $terms = stripslashes($_POST['search_term']);
  
  if(!empty($terms)) {
    // process - deal with search, display results and import into db
    echo '<p>Searching for ' .$terms . ' now...</p>';
    mmgImportGetAPISearchResults($terms,'import');
  } else {
    // display search box and instructions
    mmgImportPrintSearchBox();
  }

}

// Add the shortcode
add_shortcode('mmg_import', 'mmgImportShortCode');

/* adding a filter for object ID and gamecode so players can return via a link 
function parameter_searchterm($oVars) {
    $oVars[] = "term"; 
    return $oVars;
}

// hook add_query_vars function into query_vars
add_filter('query_vars', 'parameter_searchterm');*/

?>