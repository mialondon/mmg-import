<?php
/*
 * Functions for the mmg plugin
 * 
 * File information:
 * Contains functions to get the objects, print them to the screen, run the turns, save data, etc
 * 
 */

/*
 * Notes on import adaptations:
 * 
 * Powerhouse data doesn't have an 'interpretative' date, but instead uses date_earliest and date_latest
 * The differences in approach between earliest-latest date ranges and single
 * Ditto qualified vs simple (interprerative) dates and places - interpretative is a method we
 * use at the Science Museum to present a public-facing 'summarised' data and/or place while
 * keeping the more complicated, qualified (i.e. relating to specific relationships with 
 * e.g. types of people, places, etc) data intact for specialist use
 * 
 * You'll need to set your own values for $db_account, $db_password, $db_host and $db_name
 * 
 * Call this via the browser
 * 
 * Import written for: http://museumgam.es, November 2010
 *  
 */


/*
 * Copyright (C) 2010 Mia Ridge
 */


/*function mmgImportCheckForParams() {
  global $wp_query;
  if (isset($wp_query->query_vars['term'])) {
    // sanitise the input ###
    $term = $wp_query->query_vars['term'];
  } else {
    unset($term); // possibly pointless
  }
  
  return $term; 

}*/

function mmgImportPrintSearchBox() {
  ?><p>Some explanatory text ###</p>
  <form method="post" action="">
    
  <label class="" for="title">Enter a search term:</label>
	<input name="search_term" size="30" tabindex="1" value="" id="search_term" autocomplete="on" type="text">
          <input type="submit" name="search" value="search" />

          </form>

  <?php
}

/*
 * Will need to vary depending on the target API
 * Inputs: $terms is the search terms given in the box, $mode is display or import
 * Needs updating to use $wpdb methods
 */
function mmgImportGetAPISearchResults($terms, $mode) {
  
  global $wpdb;
  
  $api_provider;
  $type;

  $mmg_import_options = get_option('mmg_import_settings_values');
  $mmg_import_api_url = $mmg_import_options['mmg_import_api_url']; 
  $mmg_import_api_key = $mmg_import_options['mmg_import_api_key'];
  
  switch ($mmg_import_api_url) {
      case 'http://api.powerhousemuseum.com/api/':
          $type = 'xml';
          $api_provider = 'Powerhouse Museum';
          $url = $mmg_import_api_url . 'v1/item/xml/?api_key=' . $mmg_import_api_key; // basic URL
          $url .= '&num_multimedia_gte=0&description='.$terms;
          break;
      case 'http://culturegrid.org.uk/index/select/':
          $type = 'json';
          $api_provider = 'Culture Grid';
          $url = $mmg_import_api_url . '?q=' . $terms . '+-dcmi.type:Interactive*&version=2.2&start=0&rows=50&wt=json'; 
          break;
  }

echo "<br />Loading list file... <br />";

  echo $url;

  $ch = curl_init(); // relies on curl
  curl_setopt($ch, CURLOPT_URL, $url); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $output = curl_exec($ch); 
  curl_close($ch);

  if (!empty($output)) { // ### doesn't work
    switch ($type) {
      case 'xml':
        $data = simplexml_load_string($output);
        break;
      case 'json':
        $data = json_decode($output,true);
        break;
    }
  }

  if (!empty($data)) {
  
    if ($mode == 'display') {
    
      echo '<ul>';
      foreach($data->entry as $e){
        echo '<li><a href="' . $e->link[0]['href'] . 
             '">'.$e->title.'</a></li>';
      }
      echo '</ul>';
      
      //echo '<pre>';
      //print_r($data);
      //echo '</pre>';
    
    } else {
  
    // set up database stuff specific to the db table for this API (if it's not using the general object table) object_table
    // $db_objecttable = 'wp_mmg_objects_powerhouse';
    DEFINE("db_objecttable",table_prefix.'objects');
    DEFINE("db_objecttablefields",'name, accession_number, institution, data_source_url, source_display_url, description, date_earliest, date_latest, interpretative_date, interpretative_place, image_url, subject_group');
    
    // process the file
    
    if( sizeof($data) > 0 ){

      $i;
    
      echo 'Loading records into database...';    
      switch ($mmg_import_api_url) {
          case 'http://api.powerhousemuseum.com/api/':
            $i = mmgDoPowerhouseImport($data,$terms);
            break;
          case 'http://culturegrid.org.uk/index/select/':
            $i = mmgDoCultureGridImportJSON($data,$terms);
            break;
      }
      //echo "Building SQL string... ";
  
      echo '<p>'.$i.' objects loaded.</p>';
        
    }
  }
  } else {
    echo '<br />No results found for that search term. Try again!';
  }
}

function mmgDoPowerhouseImport($data, $terms) {
  $i;
  foreach ($data->items->item as $museumobject) {
    $object_name = $museumobject->title;
    $accession_number = $museumobject->registration_number;
    $data_source_url = 'http://api.powerhousemuseum.com'.$museumobject->item_uri;
    $source_display_url = $museumobject->permanent_url;
    // don't expect date or place to work immediately cos there are lots of repeated items
    $date_earliest = $museumobject->provenance->item->date_earliest;
    $date_latest = $museumobject->provenance->item->date_latest; 
    $interpretative_place = ''; // $museumobject->provenance->item->place;
    $description = $museumobject->description;
    $image_url = $museumobject->thumbnail->url;
              
    mmgInsertObject($object_name,$accession_number,$api_provider, $data_source_url, $source_display_url, $description, $date_earliest, $date_latest, $interpretative_place, '', $image_url, $terms);

    return $i;
  }
}

function mmgDoCultureGridImportJSON($data,$terms) {
  $i;
  // For each doc element in the returned JSON
  foreach($data[response][docs] as $doc) {
    // Extract the title element
    // echo "Processing $i " . $doc['dc.title'][0] . '<br/>';
    $acc_no = $doc['otherDcIdentifier'][0];
    if ( empty ( $acc_no ) )
      $acc_no = $doc['dc.identifier'];
    $publisher = $doc['dc.publisher'];
    if ( empty ( $publisher ) )
      $publisher = $doc['authority_name'];

    if ( !empty($doc['cached_thumbnail']) ) {
      mmgInsertObject($doc['dc.title'][0], 
                      $acc_no,
                      $publisher,  // Institutional Provider
                      'http://culturegrid.org.uk/dpp',  // Data source URL
                      $doc['dc.identifier'],  // Source display URL
                      $doc['dc.description'][0],  // Description
                      "",  // Date_earliest
                      "",  // Data_latest
                      $doc['dcterms.temporal'][0],  // interpretative_date
                      $doc['dcterms.spatial'][0],  // interpretative_place
                      'http://culturegrid.org.uk' . $doc['cached_thumbnail'],  // image url
                      $terms); // terms
      $i++;
    }
  }
  return $i;
}

function mmgInsertObject($object_name,$accession_number,$api_provider, $data_source_url, $source_display_url, $description, $date_earliest, $date_latest, $interpretative_date, $interpretative_place, $image_url, $terms) {
  global $wpdb;
  if (!empty($image_url)) {

    $sqlresult = $wpdb->query( $wpdb->prepare( "
      INSERT IGNORE INTO ". db_objecttable . " 
      (".db_objecttablefields.")
      VALUES ( %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )" ,
      array( $object_name,$accession_number,$api_provider, $data_source_url, $source_display_url, $description, $date_earliest, $date_latest, $interpretative_place, $interpretative_place, $image_url, $terms ) ) ); 

      if ( $sqlresult == 0 ) {
        echo 'OK <br/>';
      }
      else { 
        // echo 'Error : ' . mysql_errno() . ' ' . mysql_error() . $object_name . '<br/>';        
        // echo $object_name . '-' . $accession_number . '-' . $api_provider . '-' .  $data_source_url. '-' .  $source_display_url . '-' .  $description . '-de:' .  $date_earliest . '-dl:' .  $date_latest. '-id:' .  $interpretative_date. '-ip:' .  $interpretative_place . '-' .  $image_url . '-' . $terms . '.<br/>';
      }

  }
  else {
    echo 'Item has no image....';
  }
}

function mmgDoCultureGridImportXML($data) {

  $i;

  // For each doc element in the returned JSON
  foreach ($data->result->doc as $museumobject) {
    foreach ($museumobject->str as $property) {
      $title = getSingleValue($museumobject,"/result[$i]/str[name='dc.title']");
      echo "Title : $title <br/>";
    }
    $object_name = $museumobject->title;
  }
}

function getSingleValue($document,$xpath) {
  $result;
  $nodelist = $document->xpath($xpath);
  echo "nl: $nodelist <br/>";
  if ( count($nodelist) > 0 ) {
    $result = $nodelist[0];
  }
  else {
    $result = "";
  }
  return $result;
}

?>