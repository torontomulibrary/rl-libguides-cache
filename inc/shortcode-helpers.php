<?php
// Builds the html for the librarian profile
function rl_libguides_cache_librarian_profile_html($librarian) {
  if ( !empty($librarian->profile_image_url) ) {
    $profile_image_url = $librarian->profile_image_url;
    $profile_image_alt = "profile picture of {$librarian->first_name} {$librarian->last_name}";
  } else {
    $profile_image_url = 'https://libapps-ca.s3.amazonaws.com/apps/common/images/profile.jpg';
    $profile_image_alt = "no profile picture available";
  }

  $profile_url = $librarian->libguides_profile_url;
  $profile_text = "<a class=\"librarian-name\" href=\"{$profile_url}\">{$librarian->first_name} {$librarian->last_name}</a>";
  if ( !empty($librarian->phone_number) ) { $profile_text .= "<br>Phone: {$librarian->phone_number}"; }
  if ( !empty($librarian->email ) ) { 
    $profile_text .= "<br>Email: <a href=\"mailto:{$librarian->email}\">{$librarian->email}</a>"; 
  }

  $librarian_profile_html = array(
    array(
      'div' => array(
        'attrs' => array(
          'class' => 'librarian-profile'
        ),
        'children' => array(
          array(
            'a' => array(
              'attrs' => array(
                'href' => $profile_url,
              ),
              'children' => array(
                array(
                  'img' => array(
                    'attrs' => array(
                      'src' => $profile_image_url,
                      'alt' => $profile_image_alt,
                    ),
                  )
                ),
              ),
            ),
          ),
          array(
            'p' => array(
              'content' => $profile_text,
            )
          ),
        ),
      )
    ),
  );

  return rl_html_build_elements($librarian_profile_html);
}

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
  if ( isset( $properties['children'] ) && is_array( $properties['children'] ) ) {
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
