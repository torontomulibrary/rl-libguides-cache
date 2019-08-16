<?php
defined( 'ABSPATH' ) OR exit;
/*
 * Plugin Name: LibGuides Cache
 * Plugin URI: https://github.com/ryersonlibrary/rl_libguides_cache
 * Author: Ryerson University Library & Archives
 * Author URI: https://github.com/ryersonlibrary
 * Description: Periodically cache data from LibGuides to be used inside WordPress.
 * GitHub Plugin URI: https://github.com/ryersonlibrary/rl_libguides_cache
 * Version: 0.0.1
 */

// Include the LibGuides API client
require_once plugin_dir_path( __FILE__ ).'/lib/LibGuidesApiClient_v1_1.php';

// Include our custom settings page for the plugin
require_once plugin_dir_path( __FILE__ ).'/inc/rl-libguides-cache-settings.php';

// Include our shortcodes
require_once plugin_dir_path( __FILE__ ).'/inc/rl-libguides-cache-shortcodes.php';

// Include database helpers
require_once plugin_dir_path( __FILE__ ).'/inc/database-helpers.php';
require_once plugin_dir_path( __FILE__ ).'/inc/rl-libguides-cache-table-schema.php';

function rl_libguides_cache_activate() {
  global $wpdb, $rl_libguides_cache_db_version, $rl_libguides_cache_tables;

  rl_libguides_cache_create_table('accounts');
  rl_libguides_cache_create_table('relation_accounts_subjects');
  rl_libguides_cache_create_table('subjects');
  update_option( 'rl_libguides_cache-db_version', $rl_libguides_cache_db_version );

  if ( ! wp_next_scheduled( 'rl_libguides_cache_cron_hook' ) ) {
    wp_schedule_event( time(), 'hourly', 'rl_libguides_cache_cron_hook' );
  }

  /* Create transient data for activation notice */
  set_transient( 'rl_libguides_cache_activation_notice', true, 5 );
}

function rl_libguides_cache_deactivate() {
  $timestamp = wp_next_scheduled( 'rl_libguides_cache_cron_hook' );
  wp_unschedule_event( $timestamp, 'rl_libguides_cache_cron_hook' );

  rl_libguides_cache_uninstall();
}

function rl_libguides_cache_uninstall() {
  $timestamp = wp_next_scheduled( 'rl_libguides_cache_cron_hook' );
  wp_unschedule_event( $timestamp, 'rl_libguides_cache_cron_hook' );

  rl_libguides_cache_drop_table('accounts');
  rl_libguides_cache_drop_table('relation_accounts_subjects');
  rl_libguides_cache_drop_table('subjects');
  
  delete_option( 'rl_libguides_cache-db_version' );
  delete_option( 'rl_libguides_cache-site_id' );
  delete_option( 'rl_libguides_cache-api_key' );
}

register_activation_hook(   __FILE__, 'rl_libguides_cache_activate' );
register_deactivation_hook( __FILE__, 'rl_libguides_cache_deactivate' );
register_uninstall_hook(    __FILE__, 'rl_libguides_cache_uninstall' );



function rl_libguides_cache_get_api_client() {
  if ( !get_option('rl_libguides_cache-site_id') || !get_option('rl_libguides_cache-api_key') ) {
    return false;
  }

  $libguides_site_id = esc_attr( get_option( 'rl_libguides_cache-site_id' ) );
  $libguides_api_key = esc_attr( get_option( 'rl_libguides_cache-api_key' ) );
  $api_client = new LibGuidesApiClient_v1_1($libguides_site_id, $libguides_api_key);

  return $api_client;
}

function rl_libguides_cache_update_accounts() {
  if ( !$api_client = rl_libguides_cache_get_api_client() ) { return false; }

  $accounts = json_decode( $api_client->api_request('accounts', null, array(
    'expand' => 'profile'
  )));

  $columns = array(
    array( 'name' => 'id', 'placeholder' => '%d' ),
    array( 'name' => 'email', 'placeholder' => "%s" ),
    array( 'name' => 'phone_number', 'placeholder' => "%s" ),
    array( 'name' => 'first_name', 'placeholder' => "%s" ),
    array( 'name' => 'last_name', 'placeholder' => "%s" ),
    array( 'name' => 'nickname', 'placeholder' => "%s" ),
    array( 'name' => 'signature', 'placeholder' => "%s" ),
    array( 'name' => 'profile_image_url', 'placeholder' => "%s" ),
    array( 'name' => 'libguides_profile_url', 'placeholder' => "%s" )
  );

  $data = array();
  foreach ($accounts as $account) {
    $data[] = array(
      $account->id, 
      $account->email, 
      $account->profile->connect->phone,
      $account->first_name,
      $account->last_name,
      $account->nickname,
      $account->signature,
      $account->profile->image->url,
      $account->profile->url 
    );
  }

  return rl_libguides_cache_update_table('accounts', $columns, $data);
}

function rl_libguides_cache_update_relation_accounts_subjects() {
  if ( !$api_client = rl_libguides_cache_get_api_client() ) { return false; }

  $accounts = json_decode( $api_client->api_request('accounts', null, array(
    'expand' => 'subjects'
  )));

  $columns = array(
    array( 'name' => 'account_id', 'placeholder' => '%d' ),
    array( 'name' => 'subject_id', 'placeholder' => '%d' )
  );

  $data = array();
  foreach ($accounts as $account) {
    if ( !isset($account->subjects ) ) { continue; }
    foreach ($account->subjects as $subject) {
      $data[] = array( $account->id, $subject->id );
    }
  }

  rl_libguides_cache_truncate_table('relation_accounts_subjects');
  rl_libguides_cache_update_table('relation_accounts_subjects', $columns, $data);
}

function rl_libguides_cache_update_subjects() {
  if ( !$api_client = rl_libguides_cache_get_api_client() ) { return false; }

  $subjects = json_decode( $api_client->api_request('subjects') );

  $columns = array(
    array( 'name' => 'id', 'placeholder' => '%d' ),
    array( 'name' => 'name', 'placeholder' => "%s" )
  );

  $data = array();
  foreach ($subjects as $subject) {
    $data[] = array( $subject->id, $subject->name );
  }

  return rl_libguides_cache_update_table('subjects', $columns, $data);
}

/* Returns an array of subjects and librarians associated with the subject 
  array(
    array(
      'name' => 'Accounting',
      'librarians' => array(
        WP_OBJECT(containing librarian information), ...
      )
    ), ...
  )
*/
function rl_libguides_cache_get_subjects_list() {
  global $wpdb;
  
  $relation_accounts_subjects_table = $wpdb->prefix . "libguides_cache_relation_accounts_subjects"; 
  $sql = "SELECT account_id, subject_id FROM $relation_accounts_subjects_table;";
  $results = $wpdb->get_results($sql);

  $subjects_list = array();
  foreach ($results as $result) {
    $subject_id = $result->subject_id;
    $account_id = $result->account_id;

    // check if subject is in our list and if it doesn't exist add it.
    if ( !array_key_exists($subject_id, $subjects_list) ) {
      // Get the human readable name of the subject from the database
      $subjects_table = $wpdb->prefix . "libguides_cache_subjects";
      $subject_lookup_sql = "SELECT name FROM $subjects_table WHERE id=%d;";
      $subject_name = $wpdb->get_row( $wpdb->prepare($subject_lookup_sql, $subject_id) )->name;
      $subjects_list[$subject_id] = array('name' => $subject_name, 'librarians' => array());
    }

    // Get the librarian profile associated with the account_id in the result object
    $accounts_table = $wpdb->prefix . "libguides_cache_accounts";
    $account_lookup_sql = "SELECT * FROM $accounts_table WHERE id=%d;";
    $account = $wpdb->get_row( $wpdb->prepare($account_lookup_sql, $account_id) );

    // Add the librarian profile to the subject list
    $subjects_list[$subject_id]['librarians'][] = $account;
  }

  // SORT the list by subject name a-z
  $subject_names = array_column($subjects_list, 'name');
  array_multisort($subject_names, SORT_ASC, $subjects_list);

  return $subjects_list;
}

/* Schedule WP Cron to execute our update functions periodically */
add_action( 'rl_libguides_cache_cron_hook', 'rl_libguides_cache_cron_exec' );
function rl_libguides_cache_cron_exec() {
  rl_libguides_cache_update_subjects();
  rl_libguides_cache_update_accounts();
  rl_libguides_cache_update_relation_accounts_subjects();
}

/* Add admin notice */
add_action( 'admin_notices', 'rl_libguides_cache_activation_notice' );

/**
 * Admin Notice on Activation.
 * @since 0.0.1
 */
function rl_libguides_cache_activation_notice() {
  global $rl_libguides_cache_db_version;
  /* Check transient, if available display notice */
  if( get_transient( 'rl_libguides_cache_activation_notice' ) ){
    ?>
    <div class="updated notice is-dismissible">
      <p>Don't forget to set up the site_id and api keys in settings!</p>
    </div>
    <?php
    /* Delete transient, only display this notice once. */
    delete_transient( 'rl_libguides_cache_activation_notice' );
  }
}
