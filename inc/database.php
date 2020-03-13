<?php
global $rylib_lg_cache_wpdb, $rylib_lg_cache_tables;

$rylib_lg_cache_wpdb = new Rylib_WPDB_Interface('rylib_lg_cache');

// Our table schema
$rylib_lg_cache_tables = array(
  // https://lgapi-ca.libapps.com/1.1/accounts
  // Stores account data from LibGuides API
  'accounts' => array(
    'columns' => array(
      'id' => array( 
        'type' => 'mediumint(9)', 
        'not_null' => true
      ),
      'email' => array( 'type' => 'tinytext' ),
      'phone_number' => array( 'type' => 'tinytext' ),
      'first_name' => array( 'type' => 'tinytext' ),
      'last_name' => array( 'type' => 'tinytext' ),
      'nickname' => array( 'type' => 'tinytext' ),
      'signature' => array( 'type' => 'tinytext' ),
      'profile_image_url' => array( 'type' => 'tinytext' ),
      'libguides_profile_url' => array( 'type' => 'tinytext' ),
    ),
    'primary_key' => 'id'
  ),
  // https://lgapi-ca.libapps.com/1.1/subjects
  // Stores subject data from LibGuides API
  'subjects' => array(
    'columns' => array(
      'id' => array( 
        'type' => 'mediumint(9)', 
        'not_null' => true
      ),
      'name' => array( 'type' => 'tinytext' ),
      'slug_id' => array( 'type' => 'tinytext' ),
      'slug' => array( 'type' => 'tinytext' ),
    ),
    'primary_key' => 'id'
  ),
  // https://lgapi-ca.libapps.com/1.1/assets?asset_types=10
  // Stores database data from LibGuides API 
  'databases' => array(
    'columns' => array(
      'id' => array( 
        'type' => 'mediumint(9)', 
        'not_null' => true
      ),
      'name' => array( 'type' => 'tinytext' ),
      'description' => array( 'type' => 'text' ),
      'url' => array( 'type' => 'tinytext' ),
    ),
    'primary_key' => 'id'
  ),
  // Relational table to link accounts (subject librarians) and subjects
  'relation_accounts_subjects' => array(
    'columns' => array(
      'id' => array(
        'type' => "mediumint(9)",
        'not_null' => true,
        'auto_increment' => true,
      ),
      'account_id' => array(
        'type' => "mediumint(9)",
        'not_null' => true
      ),
      'subject_id' => array(
        'type' => "mediumint(9)",
        'not_null' => true
      )
    ),
    'primary_key' => 'id',
  ),  
  // Relational table to link databases to subjects
  'relation_databases_subjects' => array(
    'columns' => array(
      'id' => array(
        'type' => "mediumint(9)",
        'not_null' => true,
        'auto_increment' => true,
      ),
      'database_id' => array(
        'type' => "mediumint(9)",
        'not_null' => true
      ),
      'subject_id' => array(
        'type' => "mediumint(9)",
        'not_null' => true
      )
    ),
    'primary_key' => 'id',
  ),  
);

function rylib_lg_cache_create_tables() {
  global $rylib_lg_cache_wpdb, $rylib_lg_cache_tables;

  foreach ( $rylib_lg_cache_tables as $tbl_id => $tbl_props ) {
    $rylib_lg_cache_wpdb->create_table( $tbl_id, $tbl_props['columns'], $tbl_props['primary_key']);
  }
}

function rylib_lg_cache_drop_tables() {
  global $rylib_lg_cache_wpdb, $rylib_lg_cache_tables;
  foreach ( $rylib_lg_cache_tables as $tbl_id => $tbl_props ) {
    $rylib_lg_cache_wpdb->drop_table( $tbl_id );
  }
}
