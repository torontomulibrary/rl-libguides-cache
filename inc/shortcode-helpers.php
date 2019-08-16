<?php
// General html builder
function rl_html_build_element($tag, $properties) {
  $html = "<{$tag}";

  if ( isset($properties['attrs']) ) {
    foreach ($properties['attrs'] as $attr_key => $attr_value) {
      $html .= " {$attr_key}=\"{$attr_value}\"";
    }
  }

  // if this is a self closing tag, close it and continue now.
  if ( !isset($properties['content']) && !isset($properties['children']) ) {
    $html .= ' />'; 
    return $html;
  }

  $html .= '>'; 
  // end element opening tag

  // element content
  if ( is_array( $properties['children'] ) ) {
    $html .= rl_html_build_elements( $properties['children']) ;
  } else {
    $html .= $properties['content']; 
  }
  // end element content

  // element closing tag
  $html .= "</{$tag}>";
  return $html;
}


function rl_html_build_elements($elements) {
  $html = '';

  foreach ( $elements as $element ) {
    // build_element
    foreach ( $element as $tag => $properties ) {
      $html .= rl_html_build_element( $tag, $properties );
    }
  }

  return $html;
}

function rl_html_generate_table($table, $attributes = null) {
  $html = '<table class="table table-bordered table-striped';
  if ( isset($attributes['class']) ) { 
    $html .= ' ' . implode(' ', $attributes['class']) . '"';  
    unset($attributes['class']);
  }
  
  if ( isset($attributes) ) {
    foreach ($attributes as $attr_key => $attr_value) {
      $html .= " {$attr_key}=\"{$attr_value}\"";
    }
  }

  $html .= '>';

  if ( isset( $table['thead'] ) ) {
    $html .= '<thead><tr>';
    foreach ($table['thead'] as $table_heading) { $html .= "<th>{$table_heading}</th>"; }
    $html .= '</tr></thead>';
  }
  
  if ( isset( $table['tbody'] ) ) {
    $html .= '<tbody>';
    foreach ($table['tbody'] as $row_data) {
      $html .= '<tr>';
      foreach ($row_data as $column) {$html .= "<td>{$column}</td>"; }
      $html .= '</tr>';
    }
    $html .= '</tbody>';
  }

  $html .= '</table>';

  return $html;
}
