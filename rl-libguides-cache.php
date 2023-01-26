<?php
defined( 'ABSPATH' ) OR exit;
/*
 * Plugin Name: LibGuides Cache
 * Plugin URI: https://github.com/ryersonlibrary/rl-libguides-cache
 * Author: Ryerson University Library & Archives
 * Author URI: https://github.com/ryersonlibrary
 * Description: Periodically cache data from LibGuides to be used inside WordPress. Provides the [subject_librarians_list] and [databases_by_subject_dropdown] shortcodes.
 * GitHub Plugin URI: https://github.com/ryersonlibrary/rl-libguides-cache
 * Version: 0.1.7
 */

 // Include our custom settings page for the plugin
require_once plugin_dir_path( __FILE__ ).'/inc/rylib-lg-cache-settings.php';

// Include database
require_once plugin_dir_path( __FILE__ ).'inc/rylib-lg-cache-db.php';

// Include functions
require_once plugin_dir_path( __FILE__ ).'/inc/rylib-lg-cache-functions.php';

// Include shortcodes
require_once plugin_dir_path( __FILE__ ).'/inc/rylib-lg-cache-shortcodes.php';

function rl_libguides_cache_activate() {
  global $rylib_lg_cache_wpdb;
  $rylib_lg_cache_wpdb->create_tables_from_schema();
  
  /* WP Cron hook to update the cache periodically */
  if ( ! wp_next_scheduled( 'rylib_lg_cache_cron' ) ) {
    wp_schedule_event( time(), 'hourly', 'rylib_lg_cache_cron' );
  }

  /* Create transient data for activation notice */
  set_transient( 'rylib_lg_cache_activation_notice', true, 5 );
}

function rl_libguides_cache_deactivate() {
  // Only uninstall the cron hook on deactivation
  $timestamp = wp_next_scheduled( 'rylib_lg_cache_cron' );
  wp_unschedule_event( $timestamp, 'rylib_lg_cache_cron' );
}

function rl_libguides_cache_uninstall() {
  // Do everything from the deactivate hook and then some more
  rl_libguides_cache_deactivate();

  global $rylib_lg_cache_wpdb;
  $rylib_lg_cache_wpdb->drop_tables_from_schema();

  delete_option( 'rl_libguides_cache-site_id' );
  delete_option( 'rl_libguides_cache-api_key' );
}

register_activation_hook(   __FILE__, 'rl_libguides_cache_activate' );
register_deactivation_hook( __FILE__, 'rl_libguides_cache_deactivate' );
register_uninstall_hook(    __FILE__, 'rl_libguides_cache_uninstall' );

/* WP Cron hook to update the cache periodically */
add_action( 'rylib_lg_cache_cron', 'rylib_lg_cache_refresh' );
function rylib_lg_cache_refresh() {
  rylib_lg_cache_fetch_accounts_data();
  rylib_lg_cache_fetch_subjects_data();
  rylib_lg_cache_fetch_relation_accounts_subjects_data();
  rylib_lg_cache_fetch_databases_data();
}

/* wp_ajax rylib_lg_cache_refresh action */
add_action( 'wp_ajax_rylib_lg_cache_refresh', 'rylib_lg_ajax_cache_refresh' );
function rylib_lg_ajax_cache_refresh() {
  rylib_lg_cache_refresh();
  echo 'LibGuides cache refreshed!';
  wp_die();
}

/* Add admin notice */
add_action( 'admin_notices', 'rylib_lg_cache_activation_notice' );

/**
 * Admin Notice on Activation.
 * @since 0.0.1
 */
function rylib_lg_cache_activation_notice() {
  /* Check transient, if available display notice */
  if( get_transient( 'rylib_lg_cache_activation_notice' ) ){
    ?>
    <div class="updated notice is-dismissible">
      <p>Don't forget to set up the site_id and api keys in settings!</p>
    </div>
    <?php
    /* Delete transient, only display this notice once. */
    delete_transient( 'rylib_lg_cache_activation_notice' );
  }
}
