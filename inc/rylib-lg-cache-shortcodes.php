<?php
require_once 'shortcode-helpers.php';

// TODO: deprecate rl_subject_librarians shortcode
// [rl_subject_librarians] 
// [subject_librarians_list]
function rylib_lg_cache_subject_librarians_shortcode( $atts ){
  $subjects_list = rylib_lg_cache_get_subject_librarians_list();
  
  $tbody_rows = array();
  // Generate a row for each subject
  foreach ( $subjects_list as $subject ) {
    $subj_libr_column_html = '';
    // Generate a profile for each librarian in the subject
    foreach ($subject['librarians'] as $librarian) {
      $subj_libr_column_html .= rl_libguides_cache_librarian_profile_html($librarian);
    }
    $tbody_rows[] = array($subject['name'], $subj_libr_column_html);
  }
  $html = '
  <style>
    .subject-librarians-table thead {
      font-size: 1.25em;
    }
    .subject-librarians-table thead th:first-child {
      width: 300px;
    }
    .subject-librarians-table tbody td:first-child {
      font-size: 1.25em;
    }

    .librarian-profile { display: flex; }
    .librarian-profile + .librarian-profile { margin-top: 8px; }
    .librarian-profile a { text-decoration: underline; }
    .librarian-profile > a { display: block; }
    .librarian-profile > a img { width: 100px; }
    .librarian-profile > p { margin-left: 8px; }

    .librarian-profile .librarian-name {
      font-size: 1.25em;
    }
  </style>
  ';

  $html .= rl_html_generate_table(
    array(
      'thead' => array('Subject', 'Subject Librarian(s)'),
      'tbody' => $tbody_rows
    ), 
    array(
      'class' => array('subject-librarians-table')
    )
  );

	return $html;
}
add_shortcode( 'rl_subject_librarians', 'rylib_lg_cache_subject_librarians_shortcode' );
add_shortcode( 'subject_librarians_list', 'rylib_lg_cache_subject_librarians_shortcode' );

// [databases_by_subject_dropdown] 
function rylib_lg_cache_dbs_by_subject_dropdown_shortcode( $atts = [] ) {
  $atts = array_change_key_case((array)$atts, CASE_LOWER);
  // override default attributes with user attributes
  $shortcode_atts = shortcode_atts([
    'first-option-text' => 'Select a subject area ...',
    'articles-filter' => 'no',
    'show-empty' => 'no',
    'style' => '',
  ], $atts);
  
  $articles_filter = $shortcode_atts['articles-filter'] == 'yes';
  $show_empty = $shortcode_atts['show-empty'] == 'yes';

  $subjects_list = rylib_lg_cache_get_dbs_by_subject();

  if (!$show_empty) {
    $subjects_list = array_filter($subjects_list, function($sub) {
      if ( $sub['count'] > 0 ) { return true; } 
      else { return false; }
    });
  }

  // Shortcode output
  $html = "<select style=\"{$shortcode_atts['style']}\">";

  $html .= "<option selected=\"true\" disabled=\"disabled\">";
  $html .= $shortcode_atts['first-option-text'];
  $html .= "</option>";

  foreach ( $subjects_list as $s ) {
    $value = "https://learn.library.torontomu.ca/az.php?s={$s['id']}";
    if ($articles_filter) { $value .= '&t=16691'; }
    $html .= "<option value=\"{$value}\">{$s['name']}</option>";
  }

  $html .= '</select>';

  return $html;
}
add_shortcode( 'databases_by_subject_dropdown', 'rylib_lg_cache_dbs_by_subject_dropdown_shortcode' );

