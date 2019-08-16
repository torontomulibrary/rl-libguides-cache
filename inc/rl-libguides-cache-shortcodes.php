<?php
require_once 'shortcode-helpers.php';

//[rl_subject_librarians]
function rl_libguides_cache_subject_librarians_shortcode( $atts ){
  $subjects_list = rl_libguides_cache_get_subjects_list();
  
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
add_shortcode( 'rl_subject_librarians', 'rl_libguides_cache_subject_librarians_shortcode' );

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
  $profile_text .= "<a class=\"librarian-name\" href=\"{$profile_url}\">{$librarian->first_name} {$librarian->last_name}</a>";
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