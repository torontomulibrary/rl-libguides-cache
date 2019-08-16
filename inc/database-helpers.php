<?php
if ( !function_exists('array_flatten') ) {
  function array_flatten($items){
    if (!is_array($items)) {
      return [$items];
    }

    return array_reduce($items, function ($carry, $item) {
      return array_merge($carry, array_flatten($item));
    }, []);
  }
}

function rl_libguides_cache_create_table($table) {
  global $wpdb, $rl_libguides_cache_tables;
  $table_name = $wpdb->prefix . "libguides_cache_{$rl_libguides_cache_tables[$table]['table_id']}"; 

  $charset_collate = $wpdb->get_charset_collate();
  
  $sql = "CREATE TABLE $table_name (";

  foreach($rl_libguides_cache_tables[$table]['columns'] as $column) {
    $sql .= "{$column['name']} {$column['type']}";
    if ( $column['constraints']['not_null'] ) { $sql .= ' NOT NULL'; }
    if ( $column['fields']['auto_increment'] ) { $sql .= ' AUTO_INCREMENT'; }
    $sql .= ', ';
  }
  
  $sql .= "PRIMARY KEY ({$rl_libguides_cache_tables[$table]['primary_key']})";
  $sql .= ") $charset_collate;";

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql );
}

function rl_libguides_cache_update_table($table, $columns, $data) {
  global $wpdb, $rl_libguides_cache_tables;
  $table_name = $wpdb->prefix . "libguides_cache_{$rl_libguides_cache_tables[$table]['table_id']}"; 

  // extract the information we need out of the $columns parameter
  $placeholders = array();
  $column_names = array();
  foreach($columns as $column) {
    $column_names[] = $column['name'];
    $placeholders[] = $column['placeholder'];
  }

  // prepare the placeholders for `$wpdb->prepare()`
  $value_placeholders = array();
  $placeholder_string = '(' . implode(', ', $placeholders) . ')';
  for($i = 0; $i < count($data); $i++ ){
    $value_placeholders[] = $placeholder_string;
  }

  // flatten the the data array so we can give it to `$wpdb->prepare()`
  $values = array_flatten($data);
  
  // prepare the the string to be added to `ON DUPLICATE KEY UPDATE`
  $on_duplicate_key_update_columns = array();
  foreach($column_names as $column_name) {
    $on_duplicate_key_update_columns[] = "{$column_name}=values($column_name)";
  }

  // prepare the sql statement with placeholders
  $sql = "INSERT INTO {$table_name} ";
  $sql .= '('. implode(', ', $column_names) .')';
  $sql .= ' VALUES ' . implode(', ', $value_placeholders);
  $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $on_duplicate_key_update_columns);
  $sql .= ";";

  return $wpdb->query( $wpdb->prepare($sql, $values) );
}

function rl_libguides_cache_truncate_table($table) {
  global $wpdb, $rl_libguides_cache_tables;
  $table_name = $wpdb->prefix . "libguides_cache_{$rl_libguides_cache_tables[$table]['table_id']}"; 
  $sql = "TRUNCATE TABLE {$table_name};";

  return $wpdb->query($sql);
}

function rl_libguides_cache_drop_table($table) {
  global $wpdb, $rl_libguides_cache_tables;
  $table_name = $wpdb->prefix . "libguides_cache_{$rl_libguides_cache_tables[$table]['table_id']}";
  $sql = "DROP TABLE IF EXISTS $table_name;";

  return $wpdb->query($sql);
}
