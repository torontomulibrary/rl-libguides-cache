<?php
class Rylib_WPDB_Interface {
  const PLACEHOLDERS = array(
    'mediumint(9)' => '%d',
    'int' => '%d',
    'tinytext' => '%s',
    'text' => '%s',
  );

  private $wpdb; 
  protected $prefix, $schema;
  
  function __construct( $prefix, $schema ) {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->prefix = $prefix;
    $this->schema = $schema;
  }
  
  private function table_name($table_id) {
    return "{$this->wpdb->prefix}{$this->prefix}_$table_id";
  }

  function create_tables_from_schema() {
    foreach ( $this->schema as $tbl_id => $tbl_props ) {
      $this->create( $tbl_id, $tbl_props['columns'], $tbl_props['primary_key']);
    }
  }

  function drop_tables_from_schema() {
    foreach ( $this->schema as $tbl_id => $tbl_props ) {
      $this->drop( $tbl_id );
    }
  }

  private function create($table_id, $columns, $primary_key) {
    $table_name = $this->table_name($table_id);
    $charset_collate = $this->wpdb->get_charset_collate();

    // Build SQL query to create table
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (";
    foreach ($columns as $col_name => $props) {
      $sql .= "{$col_name} {$props['type']}";
      // Constraints
      if ( isset($props['not_null']) && $props['not_null'] ) {
        $sql .= ' NOT NULL';
      }
      // Fields
      if ( isset($props['auto_increment']) && $props['auto_increment'] ) {
        $sql .= ' AUTO_INCREMENT';
      }
      $sql .= ', ';
    }
    $sql .= "PRIMARY KEY ({$primary_key})";
    $sql .= ") $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    return dbDelta( $sql );
  }

  private function drop($table_id) {
    $table_name = $this->table_name($table_id);
    $sql = "DROP TABLE IF EXISTS $table_name;";
    return $this->wpdb->query($sql);
  }
  
  private function prepare($query, $args) {
    return $this->wpdb->prepare($query, $args);
  }

  function get_row($table_id, $columns, $constraints = array()) {
    $table_name = $this->table_name($table_id);

    $sql = "SELECT ";
    $sql .=  implode(', ', $columns);
    $sql .= " FROM {$table_name}";
    foreach ($constraints as $c) {
      $sql .= " {$c[0]} {$c[1]}";
    }
    $sql .= ";";

    return $this->wpdb->get_row($sql);
  }

  function get_results($table_id, $columns, $constraints = array()) {
    $table_name = $this->table_name($table_id);

    $sql = "SELECT ";
    $sql .=  implode(', ', $columns);
    $sql .= " FROM {$table_name}";
    foreach ($constraints as $c) {
      $sql .= " {$c[0]} {$c[1]}";
    }
    $sql .= ';';

    return $this->wpdb->get_results($sql);
  }

  function insert_into($table_id, $columns, $data) {
    $table_name = $this->table_name($table_id);
    
    // prepare column names and placeholders for data types
    $placeholders = array();
    foreach ( $columns as $col ) {
      $col_type = $this->schema[$table_id]['columns'][$col]['type'];
      $placeholders[] = self::PLACEHOLDERS[$col_type];
    }

    // prepare the placeholders for `$wpdb->prepare()`
    $placeholder_string = '(' . implode(', ', $placeholders) . ')';
    $value_placeholders = array();
    for($i = 0; $i < count($data); $i++ ){
      $value_placeholders[] = $placeholder_string;
    }

    // flatten the the data array so we can give it to `$wpdb->prepare()`
    $insert_values = array_flatten($data); // inc/helpers.php#3

    // prepare the the string to be added to `ON DUPLICATE KEY UPDATE`
    $on_duplicate_key_update_columns = array();
    foreach($columns as $col) {
      $on_duplicate_key_update_columns[] = "{$col}=values($col)";
    }

    // prepare the sql statement with placeholders
    $sql = "INSERT INTO {$table_name} ";
    $sql .= '('. implode(', ', $columns) .')';
    $sql .= ' VALUES ' . implode(', ', $value_placeholders);
    $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $on_duplicate_key_update_columns);
    $sql .= ";";

    /** 
     * Use wpdb->prepare() here because this is the only point where we get 
     * untrusted input from external data.
     * */ 
    return $this->wpdb->query( $this->wpdb->prepare($sql, $insert_values) );
  }

  function truncate($table_id) {
    $table_name = $this->table_name($table_id);
    $sql = "TRUNCATE TABLE {$table_name};";
  
    return $this->wpdb->query($sql);
  }

}
