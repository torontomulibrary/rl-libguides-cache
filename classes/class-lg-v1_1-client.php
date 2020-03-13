<?php 
class LibGuidesApiClient_v1_1 {
  private $base_url = 'https://lgapi-ca.libapps.com';
  private $version = '1.1';
  private $site_id;
  private $api_key;

  function __construct($site_id, $api_key) {
    $this->site_id = $site_id;
    $this->api_key = $api_key;
  }

  private function build_request_url($endpoint, $options = null) {
    $request_url = "{$this->base_url}/{$this->version}/{$endpoint}?";

    // build query string
    if ( isset($options) ) {
      foreach ($options as $key => $value) {
        if ( substr($request_url, -1) != '?' ) {
          $request_url .= '&';
        }
        $request_url .= "{$key}={$value}";
      }
    }

    return $request_url;
  }

  // All endpoints in v1.1 are structured roughly the same, so we can reuse this function 
  // for pretty much everything, only specifying request_options as needed.
  function api_request($endpoint, $ids = null, $request_options = null) {
    // Optional 'id' url parameter
    // If an ID is present the subject with that ID will be retrieved. 
    // Multiple IDs can be specified with a comma delimited list. Ex: `subjects/1,2`
    if (isset($ids)) {
      $endpoint .= "/{$ids}";
    }

    // Query string options array
    $options = array(
      'site_id' => $this->site_id,
      'key' => $this->api_key
    );

    if (isset($request_options)) {
      $options = array_merge($options, $request_options);
    }

    $request_url = $this->build_request_url($endpoint, $options);

    $response = wp_remote_get( $request_url );

    return json_decode( wp_remote_retrieve_body( $response ) );
  }
}