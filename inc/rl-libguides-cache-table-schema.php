<?php
global $rl_libguides_cache_db_version;
$rl_libguides_cache_db_version = '0';

global $rl_libguides_cache_tables;
$rl_libguides_cache_tables = array(
  'accounts' => array(
    'table_id' => 'accounts',
    'primary_key' => 'id',
    'columns' => array(
      array( 
        'name' => 'id', 
        'type' => 'mediumint(9)', 
        'contraints' => array( 'not_null' => true ),
      ),
      array( 'name' => 'email', 'type' => 'tinytext' ),
      array( 'name' => 'phone_number', 'type' => 'tinytext' ),
      array( 'name' => 'first_name', 'type' => 'tinytext' ),
      array( 'name' => 'last_name', 'type' => 'tinytext' ),
      array( 'name' => 'nickname', 'type' => 'tinytext' ),
      array( 'name' => 'signature', 'type' => 'tinytext' ),
      array( 'name' => 'profile_image_url', 'type' => 'tinytext' ),
      array( 'name' => 'libguides_profile_url', 'type' => 'tinytext' )
    )
  ),
  'relation_accounts_subjects' => array(
    'table_id' => 'relation_accounts_subjects',
    'primary_key' => 'id',
    'columns' => array(
      array(
        'name' => 'id',
        'type' => "mediumint(9)",
        'constraints' => array(
          'not_null' => true
        ),
        'fields' => array(
          'auto_increment' => true,
        )
      ),
      array(
        'name' => 'account_id',
        'type' => "mediumint(9)",
        'constraints' => array(
          'not_null' => true
        )
      ),
      array(
        'name' => 'subject_id',
        'type' => "mediumint(9)",
        'constraints' => array(
          'not_null' => true
        )
      )
    )
  ),  
  'subjects' => array(
    'table_id' => 'subjects',
    'primary_key' => 'id',
    'columns' => array(
      array( 
        'name' => 'id', 
        'type' => 'mediumint(9)', 
        'contraints' => array( 'not_null' => true ),
      ),
      array( 'name' => 'name', 'type' => 'tinytext' ),
    )
  )
);