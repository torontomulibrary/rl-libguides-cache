<?php
require_once 'helpers.php';

// Include the LibGuides API client
require_once plugin_dir_path( __DIR__ ).'classes/class-lg-v1_1-client.php';

// Set up the LibGuides API client
function rylib_lg_cache_get_lg_client() {
  if ( !get_option('rl_libguides_cache-site_id') || !get_option('rl_libguides_cache-api_key') ) {
    return false;
  }

  $libguides_site_id = esc_attr( get_option( 'rl_libguides_cache-site_id' ) );
  $libguides_api_key = esc_attr( get_option( 'rl_libguides_cache-api_key' ) );
  $lg_client = new LibGuidesApiClient_v1_1($libguides_site_id, $libguides_api_key);

  return $lg_client;
}

// Get accounts from Libguides and store them in the database
function rylib_lg_cache_fetch_accounts_data() {
  global $rylib_lg_cache_wpdb;
  if ( !$lg_client = rylib_lg_cache_get_lg_client() ) { return false; }

  $accounts = $lg_client->api_request('accounts', null, array(
    'expand' => 'profile'
  ));

  $columns = array(
    'id',
    'email',
    'phone_number',
    'first_name',
    'last_name',
    'nickname',
    'signature',
    'profile_image_url',
    'libguides_profile_url',
  );

  $data = array();
  foreach ($accounts as $account) {
    $phone_number = '';
    $profile_img_url = '';
    $libguides_profile_url = '';

    // Checking for empty fields returned by LibGuides
    if ( isset($account->profile) ) {
      $profile = $account->profile;

      // phone_number
      if( isset( $profile->connect ) ) {
        $connect = $profile->connect;
        if ( isset( $connect->phone ) ) { $phone_number = $connect->phone; }
      }

      // profile_image_url
      if( isset( $profile->image ) ) {
        $image = $profile->image;
        if ( isset( $image->url ) ) { $profile_image_url = $image->url; }
      }
      
      // libguides_profile_url
      if ( isset( $profile->url ) ) {
        $libguides_profile_url = $profile->url;
      }
    }

    $data[] = array(
      $account->id, 
      $account->email, 
      $phone_number,
      $account->first_name,
      $account->last_name,
      $account->nickname,
      $account->signature,
      $profile_image_url,
      $libguides_profile_url 
    );
  }

  $rylib_lg_cache_wpdb->truncate('accounts');
  return $rylib_lg_cache_wpdb->insert_into('accounts', $columns, $data);
}

// Get accounts/subjects relation from LibGuides and store them in the database
function rylib_lg_cache_fetch_relation_accounts_subjects_data() {
  global $rylib_lg_cache_wpdb;
  if ( !$lg_client = rylib_lg_cache_get_lg_client() ) { return false; }

  $accounts = $lg_client->api_request('accounts', null, array(
    'expand' => 'subjects'
  ));

  $columns = array('account_id', 'subject_id');
  $data = array();
  foreach ($accounts as $account) {
    if ( !isset($account->subjects ) ) { continue; }
    foreach ($account->subjects as $subject) {
      // leave id blank for autoincrement
      $data[] = array( $account->id, $subject->id );
    }
  }

  $rylib_lg_cache_wpdb->truncate('relation_accounts_subjects');
  return $rylib_lg_cache_wpdb->insert_into('relation_accounts_subjects', $columns, $data);
}

// Get subjects from LibGuides and store them in the database
function rylib_lg_cache_fetch_subjects_data() {
  global $rylib_lg_cache_wpdb;
  
  if ( !$lg_client = rylib_lg_cache_get_lg_client() ) { return false; }

  $subjects = $lg_client->api_request('subjects');

  $data = array();
  foreach ($subjects as $subject) {
    $data[] = array( 
      $subject->id, 
      $subject->name,
      $subject->slug
    );
  }

  $rylib_lg_cache_wpdb->truncate('subjects');
  return $rylib_lg_cache_wpdb->insert_into('subjects', array('id','name','slug'), $data);
}

// Fetch databases data from LibGuides and store it in the database
function rylib_lg_cache_fetch_databases_data() {
  global $rylib_lg_cache_wpdb;
  if ( !$lg_client = rylib_lg_cache_get_lg_client() ) { return false; }

  $databases = $lg_client->api_request(
    'assets', 
    null, 
    array(
      'asset_types' => '10',
      'expand' => 'subjects'
    )
  );

  $databases_data = array();
  $rel_db_subjects_data = array();
  foreach($databases as $database) {
    $databases_data[] = array(
      $database->id,
      $database->name,
      $database->description,
      $database->url,
    );

    if ( isset($database->subjects) ) {
      foreach( $database->subjects as $subject ) {
        $rel_db_subjects_data[] = array($database->id, $subject->id);
      }
    }
  }

  $rylib_lg_cache_wpdb->truncate('databases');
  $rylib_lg_cache_wpdb->insert_into(
    'databases', 
    array('id','name','description','url'), 
    $databases_data
  );

  $rylib_lg_cache_wpdb->truncate('relation_databases_subjects');
  $rylib_lg_cache_wpdb->insert_into(
    'relation_databases_subjects', 
    array('database_id','subject_id'), 
    $rel_db_subjects_data
  );

  return $rel_db_subjects_data;
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
function rylib_lg_cache_get_subject_librarians_list() {
  global $rylib_lg_cache_wpdb;

  $all_subjects = rylib_lg_cache_get_all_subjects();
  $all_accounts = rylib_lg_cache_get_all_accounts();
  $rel_acc_subj = rylib_lg_cache_get_relation_accounts_subjects();

  $subjects_list = array();
  foreach ($rel_acc_subj as $rel) {
    $subject_id = $rel->subject_id;
    $account_id = $rel->account_id;

    // check if subject is already in our list and if it doesn't exist add it.
    if ( !array_key_exists($subject_id, $subjects_list) ) {
      $f_sub = array_filter($all_subjects, function($sub) use($subject_id) {
        if ( $sub->id == $subject_id ) { return true; } 
        else { return false; }
      });
      // $f_sub should only contain at maximum one value, so this is safe.
      if ( count($f_sub) > 0 ) { 
        // only add to the list if we the subject exists
        $subject = array_values($f_sub)[0]; 
        $subjects_list[$subject_id] = array(
          'name' => $subject->name, 
          'librarians' => array()
        );
      }
    }

    $f_acc = array_filter($all_accounts, function($acc) use($account_id) {
      if ( $acc->id == $account_id ) { return true; } 
      else { return false; }
    });
    // $f_acc should only contain at maximum one value, so this is safe.
    if ( count($f_acc) > 0 ) { 
      $account = array_values($f_acc)[0]; 
      // only add to the list if we the account exists
      $subjects_list[$subject_id]['librarians'][] = $account;
    }
  }

  // SORT the list by subject name a-z
  $subject_names = array_column($subjects_list, 'name');
  array_multisort($subject_names, SORT_ASC, $subjects_list);

  return $subjects_list;
}

function rylib_lg_cache_get_dbs_by_subject() {
  $all_subjects = rylib_lg_cache_get_all_subjects();
  $rel_dbs_subs = rylib_lg_cache_get_relation_databases_subjects();
  $subjects_list = array();
  foreach($all_subjects as $sub) {
    $sub_id = $sub->id;
    $dbs = array_filter($rel_dbs_subs, function($rels) use($sub_id){
      if ( $rels->subject_id == $sub_id ) {
        return true;
      }
      return false;
    });

    $subjects_list[] = array(
      'id' => $sub->id,
      'name' => $sub->name,
      'count' => count($dbs)
    );
  }

  $subject_names = array_column($subjects_list, 'name');
  array_multisort($subject_names, SORT_ASC, $subjects_list);

  return $subjects_list;
}

function rylib_lg_cache_get_all_accounts() {
  global $rylib_lg_cache_wpdb;
  return $rylib_lg_cache_wpdb->get_results(
    'accounts', 
    array('*')
  );
}
function rylib_lg_cache_get_all_subjects() {
  global $rylib_lg_cache_wpdb;
  return $rylib_lg_cache_wpdb->get_results(
    'subjects', 
    array('*')
  );
}
function rylib_lg_cache_get_relation_accounts_subjects() {
  global $rylib_lg_cache_wpdb;
  return $rylib_lg_cache_wpdb->get_results(
    'relation_accounts_subjects', 
    array('*')
  );
}
function rylib_lg_cache_get_relation_databases_subjects() {
  global $rylib_lg_cache_wpdb;
  return $rylib_lg_cache_wpdb->get_results(
    'relation_databases_subjects', 
    array('*')
  );
}
