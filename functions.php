<?php

use Wovax\IDX\API\WovaxConnect;

if (!defined('ABSPATH')) exit;
add_action('admin_notices', 'wovax_idx_admin_notice');
add_action('init', 'wovax_idx_form_trash_action');
add_action('template_redirect', 'wovax_idx_login_cookies');
add_action('init', 'wovax_idx_form_trash_action_filter');
add_action('template_redirect', 'wovax_idx_custom_login');

function wovax_idx_custom_login() {
  if (array_key_exists('action_sign_up', $_POST) && is_string(filter_var($_POST['action_sign_up'], FILTER_SANITIZE_STRING)) && filter_var($_POST['action_sign_up'], FILTER_SANITIZE_STRING)=="signup") {
    //Sanitize variables send by POST
    $post_user_name  = filter_var($_POST['first_name_sign_up'], FILTER_SANITIZE_STRING);
    $post_user_last  = filter_var($_POST['last_name_sign_up'], FILTER_SANITIZE_STRING);
    $post_user_email = filter_var($_POST['email_sign_up'], FILTER_SANITIZE_STRING);
    $post_user_phone = filter_var($_POST['phone_sign_up'], FILTER_SANITIZE_STRING);
    $post_user_login = filter_var($_POST['username_sign_up'], FILTER_SANITIZE_STRING);
    $post_user_passw = filter_var($_POST['pass_sign_up'], FILTER_SANITIZE_STRING);
    $post_verf_passw = filter_var($_POST['verf_pass_sign_up'], FILTER_SANITIZE_STRING);
    //Verify that every variable contains a string
    if (is_string($post_user_name) && is_string($post_user_last) && is_string($post_user_email) && is_string($post_user_phone) && is_string($post_user_login) && is_string($post_user_passw) && is_string($post_verf_passw)) {
      //Verify PASSWORD and VERIFY_PASSWORD are equals
      if ($post_user_passw == $post_verf_passw && $post_verf_passw != '' && $post_user_passw != '') {
        //Create user with values ( username - email - password )
        $user_created = wp_create_user($post_user_login, $post_user_passw, $post_user_email);
        if(is_wp_error($user_created)) {
          $key = array_keys($user_created->errors);
          $key = $key[0];
              $message = $user_created->errors[$key][0];
              echo "<script type='text/javascript'>alert('$message');</script>";
        }
        //Add metadata for user created
        update_user_meta( $user_created, 'first_name', $post_user_name );
        update_user_meta( $user_created, 'last_name', $post_user_last );
        update_user_meta( $user_created, 'phone', $post_user_phone );
        if (filter_var($user_created, FILTER_VALIDATE_INT)) {
          //Login user created
          $credentials = array();
          $credentials['user_login'] = $post_user_email;
          $credentials['user_password'] = $post_user_passw;
          $credentials['remember'] = true;
          $user = wp_signon($credentials, false);
          if (!is_wp_error($user)) {
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID, true, false);
          } else {
            $message = "Signup Login Failed: Please try to Sign In again.";
            echo "<script type='text/javascript'>alert('$message');</script>";
          }
        }
        else {
          return false;
        }
      }
      else {
        return false;
      }
    }
    else {
      return false;
    }
  } elseif (array_key_exists('action_sign_in', $_POST) && is_string(filter_var($_POST['action_sign_in'], FILTER_SANITIZE_STRING)) && filter_var($_POST['action_sign_in'], FILTER_SANITIZE_STRING)=="signin") {
    //Sanitize variables send by POST
    $post_user_login = filter_var($_POST['email_sign_in'], FILTER_SANITIZE_STRING);
    $post_user_passw = filter_var($_POST['pass_sign_in'], FILTER_SANITIZE_STRING);
    if (is_string($post_user_login) && is_string($post_user_passw)) {
      //Login user
      $credentials = array();
      $credentials['user_login'] = $post_user_login;
      $credentials['user_password'] = $post_user_passw;
      $credentials['remember'] = true;
      $user = wp_signon($credentials, false);
      if (!is_wp_error($user)) {
        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, true, false);
      } else {
        $message = "Login Failed: Incorrect Username or Password.";
        echo "<script type='text/javascript'>alert('$message');</script>";
      }
    }
    else {
      return false;
    }
  }
}

function add_phone_field($profile_fields) {
	// Adding fields
  $profile_fields['phone'] = 'Phone Number';
  
	return $profile_fields;
}
// Adding the filter
add_filter('user_contactmethods', 'add_phone_field');

function wovax_idx_login_cookies() {
  $view_aux = get_permalink();
  if (!is_user_logged_in()) {
    if(isset($_COOKIE["PageView"])){
      $cookie_value = filter_var($_COOKIE["PageView"], FILTER_SANITIZE_NUMBER_INT);
      if(strpos($view_aux, 'listing-details')) {
        $cookie_value = filter_var($_COOKIE["PageView"], FILTER_SANITIZE_NUMBER_INT) + 1;
      }
      setcookie("PageView", $cookie_value, 0);
    }else{
      setcookie("PageView", 1, 0);
    }
  }else{
    if(isset($_COOKIE["PageView"]) && filter_var($_COOKIE["PageView"], FILTER_SANITIZE_NUMBER_INT)){
      setcookie("PageView", '', time() - 3600);
    }
  }
}

/* function to return all the post/page */
function wovax_idx_get_all_post_page($title) {
  global $wpdb;
  $sql = "SELECT * FROM $wpdb->posts WHERE post_type  IN ( `post`, `page`) AND post_status = `publish` AND  post_title LIKE `%s`;";
  $results = $wpdb->get_results($wpdb->prepare($sql, $title));
  $posts = array();
  foreach($results as $post) {
    $posts[] = array(
      'id' => $post->ID,
      'title' => $post->post_title,
      'type' => $post->post_type
    );
  }
  return $posts;
}

/* function to return the "ID" of the newly created page */
function wovax_idx_create_post_page($title, $content, $post_type = "page") {
  $my_post = array(
    'post_title' => wp_strip_all_tags($title),
    'post_status' => 'publish',
    'post_content' => $content,
    'post_type' => $post_type
  );
  $post_id = wp_insert_post($my_post);
  if (!is_wp_error($post_id)) {
    return $post_id;
  }
  else {
    return false;
  }
}

/* function to display a message that keeps appearing until the Initial Setup settings page is complete. */
function wovax_idx_admin_notice() {
  $wovax_idx_search_val         = get_option('wovax-idx-settings-search-results-page');
  $wovax_idx_listing_val        = get_option('wovax-idx-settings-listing-details-page');
  $wovax_idx_email_val          = get_option('wovax-idx-settings-webmaster-email');
  if (!$wovax_idx_search_val || !$wovax_idx_listing_val || !$wovax_idx_email_val) {
    $class = 'notice notice-warning';
    $message = __('Please complete the Wovax IDX Initial Setup settings.', 'sample-text-domain');
    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
  }

  $validation = wovax_idx_get_validation_token()->type;
  $wovax_idx_settings_eviroment = get_option('wovax-idx-settings-environment');
  if (($validation == 'development' || $validation == null) && $wovax_idx_settings_eviroment == 'production') {
    $class = 'notice notice-warning';
    $message = __('You do not have access to any Production Feeds.', 'sample-text-domain');
    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
  }
  if(array_key_exists('id', $_GET)) {
    $shortcode_vald = wovax_idx_get_shortcode_by_id($_GET['id']);
        if(!empty($shortcode_vald) && $shortcode_vald[0]->per_map >= 251){
            $class = 'notice notice-warning';
            $message = __('Listings Per Map cannot exceed 250 Entries.', 'sample-text-domain');
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
        }
    }
}

/* function to return the data for the wovax_IDX_feeds */
function wovax_idx_feed_request_data($per_page, $paged, $orderby = 'ASC', $ordername = 'feed_id', $s = '', $filter = '') {
  $wovax_url = "https://connect.wovax.com/api/list_feeds";
  $action = 'feeds';
  $wovax_data = wovax_idx_remote_API($wovax_url, $action, "POST");
  if (!is_string($wovax_data)) {
    // SEARCH
    $filtro_data = wovax_idx_search_data($wovax_data, $s);
    // FILTER
    $filtro_data = wovax_idx_filter($filtro_data, $filter, $action);
    // ORDER
    $order_data = wovax_idx_array_sort($filtro_data, $ordername, strtoupper($orderby));
    $order_data = array_values($order_data);
    // PAGINATE
    $count_to = count($order_data);
    $paginate_data = wovax_idx_paginate($order_data, $paged, $per_page);
    $post_data = json_encode(array(
      'status' => 'true',
      'data' => $paginate_data,
      'count_total' => $count_to
    ));
  } else {
    $post_data = false;
  }
  return $post_data;
}

/* function to filter the data */
function wovax_idx_filter($filtro_data, $filter, $action) {
  if (!empty($filter)) {
    $new_filtro_data = array();
    if ($action == 'feeds') {
      foreach($filtro_data as $key => $item) {
        $item = ( object )$item;
        if (strtolower($item->environment) == strtolower($filter)) {
          $new_filtro_data[] = (array)$item;
        }
      }
    }
    $filtro_data = $new_filtro_data;
  }
  return $filtro_data;
}

/* function to search the data */
function wovax_idx_search_data($wovax_data, $s) {
  $search_data = $wovax_data;
  if (!empty($s)) {
    $count_data = 0;
    $search_data = array();
    foreach($wovax_data as $key => $item) {
      $item = ( object )$item;
      if (strpos(strtolower($item->class_id) , strtolower($s)) !== FALSE || strpos(strtolower($item->class_visible_name) , strtolower($s)) !== FALSE || strpos(strtolower($item->resource) , strtolower($s)) !== FALSE || strpos(strtolower($item->board_acronym) , strtolower($s)) !== FALSE || strpos(strtolower($item->environment) , strtolower($s)) !== FALSE) {
        $search_data[] = ( array )$item;
      }
    }
  }
  return $search_data;
}

/* function to retrieve the data paginated */
function wovax_idx_paginate($order_data, $paged, $per_page) {
  $paginate_data = array();
  $offset = $per_page * ($paged - 1);
  $new_per_page = $per_page + $offset;
  for ($i = $offset; $i < $new_per_page; $i++) {
    if (isset($order_data[$i])) {
      $paginate_data[] = $order_data[$i];
    }
  }
  return $paginate_data;
}

/* function to retrive the data */
function wovax_idx_remote_API($url, $action, $type = "GET", $body = null) {
  $api_key = wovax_idx_get_validation_token();
  $response = wp_remote_post($url, array(
    'method' => $type,
    'timeout' => 45,
    'redirection' => 5,
    'httpversion' => '1.0',
    'blocking' => true,
    'headers' => array(
      "Authorization" => $api_key->data
    ),
    'body' => $body,
    'cookies' => array()
  ));
  if (is_wp_error($response)) {
    $error_message = $response->get_error_message();
    $wovax_data = 'error';
    return $wovax_data;
  } else {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    $wovax_data = [];
    if (isset($data->data)) {
      foreach($data->data as $key => $value) {
        $value = ( array )$value;
        array_push($wovax_data, $value);
      }
    }
    return $wovax_data;
  }
}

/* function to sort the rows */
function wovax_idx_array_sort($array, $on, $order = 'ASC') {
  $new_array = array();
  $sortable_array = array();
  if (count($array) > 0) {
    foreach($array as $k => $v) {
      if (is_array($v)) {
        foreach($v as $k2 => $v2) {
          if ($k2 == $on) {
            $sortable_array[$k] = $v2;
          }
        }
      }
      else {
        $sortable_array[$k] = $v;
      }
    }
    switch ($order) {
      case 'ASC':
        natsort($sortable_array);
        break;
      case 'DESC':
        natsort($sortable_array);
        $sortable_array = array_reverse($sortable_array, true);
        break;
    }
    foreach($sortable_array as $k => $v) {
      $new_array[$k] = $array[$k];
    }
  }
  return $new_array;
}

/* function to return the data for wovax_IDX_feed_details. */
function wovax_idx_api_data_feed_details($id_feed) {
  $action = 'feed_details';
  $url = "https://connect.wovax.com/api/list_resource_class";
  $data = wovax_idx_remote_API($url, $action, "POST", array(
    "array_class_id" => "[$id_feed]"
  ));
  return $data;
}

/* function to save feed general*/
function wovax_idx_save_feed_general($request) {
  global $wpdb;
  $feed_id = filter_var($request['wovax_idx_feed_id'], FILTER_SANITIZE_STRING);
  $number_format = filter_var($request['wovax-idx-number-format'], FILTER_SANITIZE_STRING);
  $currency_type = filter_var($request['wovax-idx-currency-type'], FILTER_SANITIZE_STRING);

  $feed_attr_data = wovax_idx_get_feed_attr_by_id_feed($feed_id);
  //Values from last attr
  $feed_attr_data = json_decode($feed_attr_data[0]->attributes);
  $map_data = isset($feed_attr_data->map_data)?$feed_attr_data->map_data:'latitude_longitude|-|0|-|0|-|Map|-|400|-|';
  $link_data = isset($feed_attr_data->link_data)?$feed_attr_data->link_data:'|-||-||-||-|';
  $divider_data = isset($feed_attr_data->divider_data)?$feed_attr_data->divider_data:'|-||-|';
  $spacer_data = isset($feed_attr_data->spacer_data)?$feed_attr_data->spacer_data:'|-||-||-|';
  $feed_rules_order = isset($feed_attr_data->feed_rules_order)?$feed_attr_data->feed_rules_order:'';

  $data_array = array(
    "format" => $number_format,
    "currency" => $currency_type,
    "map_data" => $map_data,
    "spacer_data" => $spacer_data,
    "divider_data" => $divider_data,
    "link_data" => $link_data,
    "feed_rules_order" => $feed_rules_order,
  );

  $data_array = json_encode($data_array);
  $sql = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_feeds` (`id_feed`, `attributes`) VALUES (%d, %s) ON DUPLICATE KEY UPDATE `attributes`= %s;";
  $wpdb->query($wpdb->prepare($sql, array($feed_id, $data_array, $data_array)));
}

/* function to update field_states*/
function wovax_idx_save_field_states($request) {
  // ============================SAVING STATE FOR EACH FIELD
  global $wpdb;
  unset($request['wovax_idx_feeds_field_states']);
  unset($request['_wp_http_referer']);
  unset($request['submit']);
  $case_ar_values = array();
  $in_ar_values = array();
  $sql = "UPDATE `{$wpdb->base_prefix}wovax_idx_feed_fields` SET `field_state` = CASE `id_field`";
  $sql2 = " WHERE `id_field` IN (";
  foreach($request as $key => $value) {
    $aux = explode("-", $key);
    if (!isset($aux[1])) {
      $sql.= " WHEN %d THEN %d";
      array_push($case_ar_values, $key);
      array_push($case_ar_values, $value);
      $sql2.= " %d,";

      array_push($in_ar_values, $key);
    }
  }
  $sql.= " END";
  $sql2 = rtrim($sql2, ',');
  $sql.= $sql2;
  $sql.= " );";
  $array_values = array_merge($case_ar_values, $in_ar_values);
  $wpdb->query($wpdb->prepare($sql, $array_values));

  // ============================SAVING ORDER FOR EACH FIELD
  $case_ar_values = array();
  $in_ar_values = array();
  $sql = "UPDATE `{$wpdb->base_prefix}wovax_idx_feed_fields` SET `order` = CASE `id_field`";
  $sql2 = " WHERE `id_field` IN (";
  foreach($request as $key => $value) {
    $aux = explode("-", $key);
    if (isset($aux[1]) && $aux[1] == 'order') {
      $sql.= " WHEN %d THEN %d";
      array_push($case_ar_values, $aux[0]);
      array_push($case_ar_values, $value);
      $sql2.= " %d,";
      array_push($in_ar_values, $key);
    }
  }
  $sql.= " END";
  $sql2 = rtrim($sql2, ',');
  $sql.= $sql2;
  $sql.= " );";
  $array_values = array_merge($case_ar_values, $in_ar_values);
  $wpdb->query($wpdb->prepare($sql, $array_values));

  // ============================SAVING ORDER AND STATE FOR MAP ATTR LAYOUT
  $array_attr = [];
  foreach($request as $key => $value) {
    $aux = explode("-", $key);
    if (isset($aux[1]) && ($aux[1] == 'map_order' || $aux[1] == 'map_state')) {
      if (!in_array($aux[0], $array_attr)) {
        array_push($array_attr, $aux[0]);
      }
      array_push($array_attr, $value);
    }
  }
  $map_aux = wovax_idx_get_feed_attr_by_id_feed(filter_var($_GET[idfeed], FILTER_SANITIZE_STRING));
  $map_aux = json_decode($map_aux[0]->attributes);
  $feed_id_val = filter_var($_GET[idfeed], FILTER_SANITIZE_STRING);
  $map = explode("|-|", $map_aux->map_data);
  $map[1] = $array_attr[2];
  $map[2] = $array_attr[1];
  /* Option value /State        /Order        /Label */
  $map_string = $map[0] . '-' . $map[1] . '-' . $map[2] . '-' . $map[3];
  $map_aux->map_data = $map_string;
  $map_aux = json_encode($map_aux);

}

/* function save fields feeds */
function wovax_idx_save_fields($request) {
  global $wpdb;

  //Auxiliar array to collect multi positions for every field
  $request_aux = [];

  //Auxiliar to validate that map and link field exists on list
  $validate_map_aux = false;
  $validate_link_aux = false;
  $validate_divider_aux = false;
  $validate_spacer_aux = false;

  //Loop for $request fields values
  foreach ($request as $key => $value) {
    //explode key to avoid last digits on field id
    $new_key = explode("-", $key)[0];
    //check if new key already exist in auxiliar array in order to collect information for multi fields
    if( array_key_exists($new_key,$request_aux) && ( strpos($new_key, 'wovax_idx_order') === 0 || strpos($new_key, 'wovax_idx_height') === 0 || strpos($new_key, 'wovax_idx_textlink') === 0 || strpos($new_key, 'wovax_idx_fieldlink') === 0 || strpos($new_key, 'wovax_idx_fieldspacer') === 0 || strpos($new_key, 'wovax_idx_cssclass') === 0 ) ){
      //Only empty value we need to force setting is map height
      if ( strpos($new_key, 'wovax_idx_height') === 0 ) $value = empty($value)?'400':$value;
      //Multi fields are collected in string separated with a comma
      $request_aux[$new_key] = $request_aux[$new_key] . ',' . $value;
    }else{
      //Force height setting - First instance is not recognized in multi fields
      if (strpos($new_key, 'wovax_idx_height') === 0) $value = empty($value)?'400':$value;
      //Collect field in auxilar array
      $request_aux[$new_key] = $value;
    }
  }

  //Return values to $request array
  $request = $request_aux;
  //Store the feed ID inside $id_feed
  $id_feed = filter_var($request['wovax_idx_feed_id'], FILTER_SANITIZE_NUMBER_INT);
  if ($id_feed && filter_var($id_feed, FILTER_VALIDATE_INT)) {

    //Call feed map attr
    $feed_exists = wovax_idx_get_feed_attr_by_id_feed($id_feed);
    //Check - if not exist we set a default value
    if(!$feed_exists) {
      $data_array = array(
        "format" => "decimals_miles",
        "currency" => "left",
        //display option - state - order - title - height
        "map_data" => "latitude_longitude|-|0|-|0|-|Map|-|400|-|",
        //state - order - field
        "spacer_data" => "|-||-||-|",
        //state - order - field
        "divider_data" => "|-||-|",
        //state - order - link text - field label
        "link_data" => "|-||-||-||-|",
        //feed rules order
        "feed_rules_order" => "",
      );

      $data_array = json_encode($data_array);
    //Else we use call to DB
    }else{
      $data_array = $feed_exists[0]->attributes;
    }

    // fields stored in database.
    $list_result = wovax_idx_list_feeds_field_by_id($id_feed);
    $sql_type = array();

    // Iteration through fields
    foreach($request as $key => $value) {

      $key = filter_var($key, FILTER_SANITIZE_STRING);

      //Values for map
      if ($key == 'wovax_idx_ordermap_'.$id_feed){
        $validate_map_aux = true;
        $data_array = json_decode($data_array);
        $map = explode("|-|", $data_array->map_data);
        $map[1] = addslashes(filter_var($request['wovax_idx_field_statemap_' . $id_feed], FILTER_SANITIZE_STRING));
        $map[2] = addslashes(filter_var($request['wovax_idx_ordermap_' . $id_feed], FILTER_SANITIZE_STRING));
        $map[4] = addslashes(filter_var($request['wovax_idx_heightmap_' . $id_feed], FILTER_SANITIZE_STRING));
        $map[5] = addslashes(filter_var($request['wovax_idx_cssclassmap_' . $id_feed], FILTER_SANITIZE_STRING));
        //             display option- state         - order         - title         - height        - css class
        // Add str_replace to fix any clobbered values caused by the improper explode.
        $map_string = str_replace('|', '', $map[0]) . '|-|' . $map[1] . '|-|' . $map[2] . '|-|' . str_replace('|', '', $map[3]) . '|-|' . $map[4] . '|-|' . $map[5];
        $data_array->map_data = $map_string;
        $data_array = json_encode($data_array);
      }

      //Values for link
      if ($key == 'wovax_idx_orderlink_'.$id_feed){
        $validate_link_aux = true;
        $data_array = json_decode($data_array);
        $link = explode("|-|", $data_array->link_data);
        $link[0] = addslashes(filter_var($request['wovax_idx_field_statelink_' . $id_feed], FILTER_SANITIZE_STRING));
        $link[1] = addslashes(filter_var($request['wovax_idx_orderlink_' . $id_feed], FILTER_SANITIZE_STRING));
        $link[2] = addslashes(filter_var($request['wovax_idx_textlink_' . $id_feed], FILTER_SANITIZE_STRING));
        $link[3] = addslashes(filter_var($request['wovax_idx_fieldlink_' . $id_feed], FILTER_SANITIZE_STRING));
        $link[4] = addslashes(filter_var($request['wovax_idx_cssclasslink_' . $id_feed], FILTER_SANITIZE_STRING));
        //              state          - order          - link text      - field label    - css class
        $link_string = $link[0] . '|-|' . $link[1] . '|-|' . $link[2] . '|-|' . $link[3] . '|-|' . $link[4];
        $data_array->link_data = $link_string;
        $data_array = json_encode($data_array);
      }

      //Values for divider
      if ($key == 'wovax_idx_orderdivider_'.$id_feed){
        $validate_divider_aux = true;
        $data_array = json_decode($data_array);
        $divider = explode("|-|", $data_array->divider_data);
        $divider[0] = addslashes(filter_var($request['wovax_idx_field_statedivider_' . $id_feed], FILTER_SANITIZE_STRING));
        $divider[1] = addslashes(filter_var($request['wovax_idx_orderdivider_' . $id_feed], FILTER_SANITIZE_STRING));
        $divider[2] = addslashes(filter_var($request['wovax_idx_cssclassdivider_' . $id_feed], FILTER_SANITIZE_STRING));
        //                 state             - order             - css class
        $divider_string = $divider[0] . '|-|' . $divider[1] . '|-|' . $divider[2];
        $data_array->divider_data = $divider_string;
        $data_array = json_encode($data_array);
      }

      //Values for spacer
      if ($key == 'wovax_idx_orderspacer_'.$id_feed){
        $validate_spacer_aux = true;
        $data_array = json_decode($data_array);
        $spacer = explode("|-|", $data_array->spacer_data);
        $spacer[0] = addslashes(filter_var($request['wovax_idx_field_statespacer_' . $id_feed], FILTER_SANITIZE_STRING));
        $spacer[1] = addslashes(filter_var($request['wovax_idx_orderspacer_' . $id_feed], FILTER_SANITIZE_STRING));
        $spacer[2] = addslashes(filter_var($request['wovax_idx_fieldspacer_' . $id_feed], FILTER_SANITIZE_STRING));
        $spacer[3] = addslashes(filter_var($request['wovax_idx_cssclassspacer_' . $id_feed], FILTER_SANITIZE_STRING));
        //                 state             - order             - field         - css class
        $spacer_string = $spacer[0] . '|-|' . $spacer[1] . '|-|' . $spacer[2] . '|-|' . $spacer[3];
        $data_array->spacer_data = $spacer_string;
        $data_array = json_encode($data_array);
      }

      //If key isn´t equal "wovax_idx_id_field" goes to next value in array
      if (strpos($key, 'wovax_idx_id_field_') === false) {
        continue;
      }

      $key = str_replace('wovax_idx_id_field_', '', $key);
      $value = (isset($request['wovax_idx_alias_update_' . $key])) ? addslashes(filter_var($request['wovax_idx_alias_update_' . $key], FILTER_SANITIZE_STRING)) : '';
      $value_old = (isset($request['wovax_idx_old_field_' . $key])) ? addslashes(filter_var($request['wovax_idx_old_field_' . $key], FILTER_SANITIZE_STRING)) : '';
      $field_name = (isset($request['wovax_idx_field_name_' . $key])) ? addslashes(filter_var($request['wovax_idx_field_name_' . $key], FILTER_SANITIZE_STRING)) : '';
      $default_alias = (isset($request['wovax_idx_default_alias_' . $key])) ? addslashes(filter_var($request['wovax_idx_default_alias_' . $key], FILTER_SANITIZE_STRING)) : '';
      $field_alias = (isset($request['wovax_idx_field_alias_' . $key])) ? addslashes(filter_var($request['wovax_idx_field_alias_' . $key], FILTER_SANITIZE_STRING)) : '';
      $status_alias = (isset($request['wovax_idx_status_alias_' . $key])) ? addslashes(filter_var($request['wovax_idx_status_alias_' . $key], FILTER_SANITIZE_STRING)) : '';
      $field_state = (isset($request['wovax_idx_field_state_' . $key])) ? addslashes(filter_var($request['wovax_idx_field_state_' . $key], FILTER_SANITIZE_STRING)) : '0';

      $order_aux = (isset($request['wovax_idx_order_' . $key])) ? addslashes(filter_var($request['wovax_idx_order_' . $key], FILTER_SANITIZE_STRING)) : '0';
      $css_aux = (isset($request['wovax_idx_cssclass_' . $key])) ? addslashes(filter_var($request['wovax_idx_cssclass_' . $key], FILTER_SANITIZE_STRING)) : '0';
      $order = $order_aux . '|-|' . $css_aux;

      // If there aren't values belonging to that feed returns false and inserts the values into the database otherwise updates
      if (wovax_idx_find_by_name_feed($list_result, $key)) {
        $sql_type['update'][] = ( object )array(
          'id_feed' => $id_feed,
          'name' => $field_name,
          'value' => $value,
          'value_old' => $value_old,
          'id_field' => $key,
          'status_alias' => $status_alias,
          'default_alias' => $default_alias,
          'field_alias' => $field_alias,
          'field_state' => $field_state,
          'order' => $order
        );
      }
      else {
        $sql_type['insert'][] = ( object )array(
          'id_feed' => $id_feed,
          'name' => $field_name,
          'value' => $value,
          'value_old' => $value_old,
          'id_field' => $key,
          'status_alias' => $status_alias,
          'default_alias' => $default_alias,
          'field_alias' => $field_alias,
          'field_state' => $field_state,
          'order' => $order
        );
      }
    }

    if( !$validate_map_aux || !$validate_link_aux ) {
      $data_array = json_decode($data_array);
      if ( !$validate_map_aux ) $data_array->map_data = "latitude_longitude|-|0|-|0|-|Map|-|400|-|";
      if ( !$validate_link_aux ) $data_array->link_data = "|-||-||-||-|";
      if ( !$validate_divider_aux ) $data_array->divider_data = "|-||-|";
      if ( !$validate_spacer_aux ) $data_array->spacer_data = "|-||-||-|";
      $data_array = json_encode($data_array);
    }

    $sql = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_feeds` (`id_feed`, `attributes`) VALUES ( %d, %s ) ON DUPLICATE KEY UPDATE `attributes`= %s ";
    $wpdb->query($wpdb->prepare($sql, array($id_feed, $data_array, $data_array)));

    if (isset($sql_type['insert']) && is_array($sql_type['insert'])) {
      if (!empty($sql_type['insert'])) {
        $array_values = array();
        $sql = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_feed_fields` ( `id_feed`, `name`, `alias_update`, `alias_old`, `id_field`, `status_alias`, `default_alias`, `field_alias`, `field_state`, `order` ) VALUES ";
        foreach($sql_type['insert'] as $key => $item) {
          $sql.= " ( %d, %s, %s, %s, %d, %d, %s, %s, %d, %d ) ,";
          foreach($item as $key => $value) {
            array_push($array_values, $value);
          }
        }
        $sql = rtrim($sql, ',');
        $sql = $sql . ';';
        $wpdb->query($wpdb->prepare($sql, $array_values));
      }
    }

    if (isset($sql_type['update']) && is_array($sql_type['update'])) {
      if (!empty($sql_type['update'])) {

        //UPDATING ALIAS_UPDATE
        $when_ar_values = array();
        $in_ar_values = array();
        $sql = "UPDATE `{$wpdb->base_prefix}wovax_idx_feed_fields` SET `alias_update` = CASE `name`";
        $sql2 = "";
        foreach($sql_type['update'] as $key => $item) {
          array_push($when_ar_values, $item->name);
          array_push($when_ar_values, $item->value);
          $sql.= " WHEN %s THEN %s";
          array_push($in_ar_values, $item->name);
          $sql2.= " %s,";
        }
        $sql2 = rtrim($sql2, ',');
        $sql.= " ELSE `alias_update` END WHERE `id_feed` = %d AND `name` IN(";
        array_push($when_ar_values, $id_feed);
        $sql.= $sql2;
        $sql.= " );";
        $array_values = array_merge($when_ar_values, $in_ar_values);
        $wpdb->query($wpdb->prepare($sql, $array_values));

        //UPDATING FIELD_STATE
        $when_ar_values = array();
        $in_ar_values = array();
        $sql = "UPDATE `{$wpdb->base_prefix}wovax_idx_feed_fields` SET `field_state` = CASE `name`";
        $sql2 = "";
        foreach($sql_type['update'] as $key => $item) {
          array_push($when_ar_values, $item->name);
          array_push($when_ar_values, $item->field_state);
          $sql.= " WHEN %s THEN %s";
          array_push($in_ar_values, $item->name);
          $sql2.= " %s,";
        }
        $sql2 = rtrim($sql2, ',');
        $sql.= " ELSE `field_state` END WHERE `id_feed` = %d AND `name` IN(";
        array_push($when_ar_values, $id_feed);
        $sql.= $sql2;
        $sql.= " );";
        $array_values = array_merge($when_ar_values, $in_ar_values);
        $wpdb->query($wpdb->prepare($sql, $array_values));

        //UPDATING ORDER
        $when_ar_values = array();
        $in_ar_values = array();
        $sql = "UPDATE `{$wpdb->base_prefix}wovax_idx_feed_fields` SET `order` = CASE `name`";
        $sql2 = "";
        foreach($sql_type['update'] as $key => $item) {
          array_push($when_ar_values, $item->name);
          array_push($when_ar_values, $item->order);
          $sql.= " WHEN %s THEN %s";
          array_push($in_ar_values, $item->name);
          $sql2.= " %s,";
        }
        $sql2 = rtrim($sql2, ',');
        $sql.= " ELSE `order` END WHERE `id_feed` = %d AND `name` IN(";
        array_push($when_ar_values, $id_feed);
        $sql.= $sql2;
        $sql.= " );";
        $array_values = array_merge($when_ar_values, $in_ar_values);
        $wpdb->query($wpdb->prepare($sql, $array_values));
      }
    }
  }
}

/* function save fields shortcodes */
function wovax_idx_save_shortcode($request) {
  global $wpdb;
  global $current_user;
  $date_time = current_time('mysql');
  $id = filter_var($request['wovax-idx-shortcode-id'], FILTER_SANITIZE_STRING);
  $type = filter_var($request['wovax-idx-shortcode-type'], FILTER_SANITIZE_STRING);
  $title = filter_var($request['wovax-idx-shortcode-title'], FILTER_SANITIZE_STRING);
  if ($id != '') {
    if (filter_var($id, FILTER_VALIDATE_INT) && is_string($title) && is_string($current_user->display_name)) {
      $sql = "UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode` SET  `title` = %s, `author` = %s, `date` = %s, `status` = 'published' WHERE `id` = %d";
      $wpdb->query($wpdb->prepare($sql, array($title, $current_user->display_name, $date_time, $id)));
    }
  }
  else {
    if (is_string($type) && is_string($title) && is_string($current_user->display_name)) {
      $sql = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_shortcode` ( `type`, `title`, `author`, `date`, `status`, `feeds`) VALUES ( %s , %s , %s , %s , 'published', '' )";
      $wpdb->query($wpdb->prepare($sql, array($type, $title, $current_user->display_name, $date_time)));
      $id = $wpdb->insert_id;
    }
  }
  return $id;
}

/* function to return sorted fields array */
function wovax_idx_sort_fields_list_feed_details($feed_list, $feed_attr_info) {
  $map_aux     = explode("|-|", json_decode($feed_attr_info[0]->attributes)->map_data);
  $link_aux    = explode("|-|", json_decode($feed_attr_info[0]->attributes)->link_data);
  $divider_aux = explode("|-|", json_decode($feed_attr_info[0]->attributes)->divider_data);
  $spacer_aux  = explode("|-|", json_decode($feed_attr_info[0]->attributes)->spacer_data);

  //Feed map information
  $map_option = $map_aux[0];
  $map_state  = $map_aux[1];
  $map_order  = $map_aux[2];
  $map_title  = $map_aux[3];
  $map_height = $map_aux[4];
  $map_css    = $map_aux[5];

  //Feed link information
  $link_state = $link_aux[0];
  $link_order = $link_aux[1];
  $link_text  = $link_aux[2];
  $link_field = $link_aux[3];
  $link_css   = $link_aux[4];

  //Feed divider information
  $divider_state = $divider_aux[0];
  $divider_order = $divider_aux[1];
  $divider_css   = $divider_aux[2];

  //Feed spacer information
  $spacer_state = $spacer_aux[0];
  $spacer_order = $spacer_aux[1];
  $spacer_field = $spacer_aux[2];
  $spacer_css   = $spacer_aux[3];

  $content_array_aux = [];
  $info_container_aux = [];

  //Create array of layout to built including map
  foreach ($feed_list as $array_key => $object) {
    $info_container_aux[$object->alias_old] = $object;
    $order_css = explode("|-|", $object->order);
    $order_array = explode(",", $order_css[0]);
    $css_array = explode(",", $order_css[1]);
    for ($i=0; $i < count($order_array); $i++) {
      $content_array_aux[$order_array[$i]] =  $object->alias_old . '|-|' . $css_array[$i];
    }
  }

  //Insert map field in content array
  if( $map_state == 1 ){
    $order_array  = explode(",", $map_order);
    $height_array = explode(",", $map_height);
    $css_array    = explode(",", $map_css);

    for ($i=0; $i < count($order_array); $i++) {
      $content_array_aux[$order_array[$i]] =  'Map|-|' . $height_array[$i] . '|-|' .  $css_array[$i];
    }
  }

  //Insert link field in content array
  if( $link_state == 1 ){
    $order_array = explode(",", $link_order);
    $text_array  = explode(",", $link_text);
    $field_array = explode(",", $link_field);
    $css_array   = explode(",", $link_css);

    for ($i=0; $i < count($order_array); $i++) {
      $content_array_aux[$order_array[$i]] =  'Link|-|' . $text_array[$i] . '|-|' . $field_array[$i] . '|-|' .  $css_array[$i];
    }
  }

  //Insert divider field in content array
  if( $divider_state == 1 ){
    $order_array = explode(",", $divider_order);
    $css_array   = explode(",", $divider_css);

    for ($i=0; $i < count($order_array); $i++) {
      $content_array_aux[$order_array[$i]] =  'Divider|-|' .  $css_array[$i];
    }
  }

  //Insert spacer field in content array
  if( $spacer_state == 1 ){
    $order_array = explode(",", $spacer_order);
    $field_array = explode(",", $spacer_field);
    $css_array   = explode(",", $spacer_css);

    for ($i=0; $i < count($order_array); $i++) {
      $content_array_aux[$order_array[$i]] =  'Spacer|-|' . $field_array[$i] . '|-|' .  $css_array[$i];
    }
  }

  ksort($content_array_aux);

  return array('content_array_aux' => $content_array_aux,
               'info_container_aux' => $info_container_aux);
}

/* function display every field on feed details*/
function wovax_idx_display_feed_details_fields($content_array_aux, $info_container_aux, $feed_id) {
  $content_listing = '';
  $count_aux_var = 1000;
  //Run for loop to create every layout built
  for ($i=0; $i < count($content_array_aux); $i++) {

    //Calling key in order to detect layout
    $key = $content_array_aux[$i];
    $array_value = explode('|-|', $key);
    $layout_builder = $info_container_aux[$array_value[0]];

    //Validate layout -> ( Map layout has different values )
    if( strpos($key, 'Map|-|') !== 0 && strpos($key, 'Link|-|') !== 0 && strpos($key, 'Divider|-|') !== 0 && strpos($key, 'Spacer|-|') !== 0 ) {
      $content_listing.= '<li id="list-item-' . esc_attr($layout_builder->id_field) . '-' . esc_attr($count_aux_var) . '" class="menu-item menu-item-depth-0 menu-item-page menu-item-edit-inactive" style="position: relative; left: 0px; top: 0px;">';
      $content_listing.= '<div class="menu-item-bar"><div class="menu-item-handle ui-sortable-handle"><span class="item-title">';
      $content_listing.= '<span class="menu-item-title">' . esc_html($layout_builder->alias_old) . '</span>';
      $content_listing.= '</span><span class="item-controls"><span class="item-type">Active</span>';
      $content_listing.= '<a class="item-edit" id="edit-' . esc_attr($layout_builder->id_field) . '" onclick="wovax_idx_change_content(this)">';
      $content_listing.= '</a></span></div></div>';
      $content_listing.= '<div class="menu-item-settings wp-clearfix" id="menu-item-settings-' . esc_attr($layout_builder->id_field) . '">';
      $content_listing.= '<p class="description description-wide">';
      $content_listing.= '<label for="' . esc_attr($layout_builder->name) . '">Field Label<br>';
      $content_listing.= '<input type="text" name="wovax_idx_alias_update_' . esc_attr($layout_builder->id_field) . '-' . esc_attr($count_aux_var) . '" id="' . esc_attr($layout_builder->name) . '" class="widefat edit-menu-item-title"  value="' . esc_attr($layout_builder->alias_update) . '"></label>';

      $content_listing.= '<label for="cssclass-' . esc_attr($layout_builder->name) . '">CSS Classes<br>';
      $content_listing.= '<input type="text" name="wovax_idx_cssclass_' . esc_attr($layout_builder->id_field) . '-' . esc_attr($count_aux_var) . '" id="cssclass-' . esc_attr($layout_builder->name) . '" class="widefat edit-menu-item-title"  value="' . esc_attr($array_value[1]) . '"></label>';

      $content_listing.= '<input type="hidden" class="order-index" name="wovax_idx_order_' . esc_attr($layout_builder->id_field) . '-' . esc_attr($count_aux_var) . '" value="' . esc_attr($i) . '" >';
      $content_listing.= '<input type="hidden" name="wovax_idx_field_state_' . esc_attr($layout_builder->id_field) . '-' . esc_attr($count_aux_var) . '" value="1" >';
      $content_listing.= '</p><div class="menu-item-actions description-wide submitbox">';
      $content_listing.= '<a class="item-delete submitdelete deletion" id="delete-' . esc_attr($layout_builder->id_field) . '" onclick="wovax_idx_clean_input(this)">Remove</a>';
      $content_listing.= '</div></div></li>';
    }else {
      if ( strpos($key, 'Map|-|') === 0 ) {
        $auxiliar_map_array_info = explode("|-|", $key);
        $content_listing.= '<li id="list-item-' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" class="menu-item menu-item-depth-0 menu-item-page menu-item-edit-inactive" style="position: relative; left: 0px; top: 0px;">';
        $content_listing.= '<div class="menu-item-bar"><div class="menu-item-handle ui-sortable-handle"><span class="item-title">';
        $content_listing.= '<span class="menu-item-title">' . esc_html(empty($map_title)?"Map":$map_title) . '</span>';
        $content_listing.= '</span><span class="item-controls"><span class="item-type">Active</span>';
        $content_listing.= '<a class="item-edit" id="edit-' . esc_attr($feed_id) . '" onclick="wovax_idx_change_content(this)">';
        $content_listing.= '</a></span></div></div>';
        $content_listing.= '<div class="menu-item-settings wp-clearfix" id="menu-item-settings-' . esc_attr($feed_id) . '">';
        $content_listing.= '<p class="description description-wide">';
        $content_listing.= '<label for="height-' . esc_attr($count_aux_var) . '">Height<br>';
        $content_listing.= '<input type="text" name="wovax_idx_heightmap_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" id="height-' . esc_attr($count_aux_var) . '" class="widefat edit-menu-item-title"  value="' . esc_attr($auxiliar_map_array_info[1]) . '"></label>';

        $content_listing.= '<label for="cssclass-' . esc_attr($count_aux_var) . '">CSS Classes<br>';
        $content_listing.= '<input type="text" name="wovax_idx_cssclassmap_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" id="cssclass-' . esc_attr($count_aux_var) . '" class="widefat edit-menu-item-title"  value="' . esc_attr($auxiliar_map_array_info[2]) . '"></label>';

        $content_listing.= '<input type="hidden" class="order-index" name="wovax_idx_ordermap_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" value="' . esc_attr($i) . '" >';
        $content_listing.= '<input type="hidden" name="wovax_idx_field_statemap_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" value="1" >';
        $content_listing.= '</p><div class="menu-item-actions description-wide submitbox">';
        $content_listing.= '<a class="item-delete submitdelete deletion" id="delete-' . esc_attr($feed_id) . '" onclick="wovax_idx_clean_input(this)">Remove</a>';
        $content_listing.= '</div></div></li>';
      }

      if ( strpos($key, 'Link|-|') === 0 ) {
        $auxiliar_link_array_info = explode("|-|", $key);
        $content_listing.= '<li id="list-item-' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" class="menu-item menu-item-depth-0 menu-item-page menu-item-edit-inactive" style="position: relative; left: 0px; top: 0px;">';
        $content_listing.= '<div class="menu-item-bar"><div class="menu-item-handle ui-sortable-handle"><span class="item-title">';
        $content_listing.= '<span class="menu-item-title">Virtual Tour URL</span>';
        $content_listing.= '</span><span class="item-controls"><span class="item-type">Active</span>';
        $content_listing.= '<a class="item-edit" id="edit-' . esc_attr($feed_id) . '" onclick="wovax_idx_change_content(this)">';
        $content_listing.= '</a></span></div></div>';
        $content_listing.= '<div class="menu-item-settings wp-clearfix" id="menu-item-settings-' . esc_attr($feed_id) . '">';
        $content_listing.= '<p class="description description-wide">';
        $content_listing.= '<label for="field-' . esc_attr($count_aux_var) . '">Field Label<br>';
        $content_listing.= '<input type="text" name="wovax_idx_fieldlink_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" id="field-' . esc_attr($count_aux_var) . '" class="widefat edit-menu-item-title"  value="' . esc_attr($auxiliar_link_array_info[2]) . '"></label>';
        $content_listing.= '<label for="text-' . esc_attr($count_aux_var) . '">Link Text<br>';
        $content_listing.= '<input type="text" name="wovax_idx_textlink_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" id="text-' . esc_attr($count_aux_var) . '" class="widefat edit-menu-item-title"  value="' . esc_attr($auxiliar_link_array_info[1]) . '"></label>';

        $content_listing.= '<label for="cssclass-' . esc_attr($count_aux_var) . '">CSS Classes<br>';
        $content_listing.= '<input type="text" name="wovax_idx_cssclasslink_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" id="cssclass-' . esc_attr($count_aux_var) . '" class="widefat edit-menu-item-title"  value="' . esc_attr($auxiliar_link_array_info[3]) . '"></label>';

        $content_listing.= '<input type="hidden" class="order-index" name="wovax_idx_orderlink_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" value="' . esc_attr($i) . '" >';
        $content_listing.= '<input type="hidden" name="wovax_idx_field_statelink_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" value="1" >';
        $content_listing.= '</p><div class="menu-item-actions description-wide submitbox">';
        $content_listing.= '<a class="item-delete submitdelete deletion" id="delete-' . esc_attr($feed_id) . '" onclick="wovax_idx_clean_input(this)">Remove</a>';
        $content_listing.= '</div></div></li>';
      }

      if ( strpos($key, 'Divider|-|') === 0 ) {
        $auxiliar_divider_array_info = explode("|-|", $key);
        $content_listing.= '<li id="list-item-' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" class="menu-item menu-item-depth-0 menu-item-page menu-item-edit-inactive" style="position: relative; left: 0px; top: 0px;">';
        $content_listing.= '<div class="menu-item-bar"><div class="menu-item-handle ui-sortable-handle"><span class="item-title">';
        $content_listing.= '<span class="menu-item-title">Divider</span>';
        $content_listing.= '</span><span class="item-controls"><span class="item-type">Active</span>';
        $content_listing.= '<a class="item-edit" id="edit-' . esc_attr($feed_id) . '" onclick="wovax_idx_change_content(this)">';
        $content_listing.= '</a></span></div></div>';
        $content_listing.= '<div class="menu-item-settings wp-clearfix" id="menu-item-settings-' . esc_attr($feed_id) . '">';
        $content_listing.= '<p class="description description-wide">';

        $content_listing.= '<label for="cssclass-' . esc_attr($count_aux_var) . '">CSS Classes<br>';
        $content_listing.= '<input type="text" name="wovax_idx_cssclassdivider_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" id="cssclass-' . esc_attr($count_aux_var) . '" class="widefat edit-menu-item-title"  value="' . esc_attr($auxiliar_divider_array_info[1]) . '"></label>';

        $content_listing.= '<input type="hidden" class="order-index" name="wovax_idx_orderdivider_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" value="' . esc_attr($i) . '" >';
        $content_listing.= '<input type="hidden" name="wovax_idx_field_statedivider_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" value="1" >';
        $content_listing.= '</p><div class="menu-item-actions description-wide submitbox">';
        $content_listing.= '<a class="item-delete submitdelete deletion" id="delete-' . esc_attr($feed_id) . '" onclick="wovax_idx_clean_input(this)">Remove</a>';
        $content_listing.= '</div></div></li>';
      }

      if ( strpos($key, 'Spacer|-|') === 0 ) {
        $auxiliar_spacer_array_info = explode("|-|", $key);
        $content_listing.= '<li id="list-item-' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" class="menu-item menu-item-depth-0 menu-item-page menu-item-edit-inactive" style="position: relative; left: 0px; top: 0px;">';
        $content_listing.= '<div class="menu-item-bar"><div class="menu-item-handle ui-sortable-handle"><span class="item-title">';
        $content_listing.= '<span class="menu-item-title">Spacer</span>';
        $content_listing.= '</span><span class="item-controls"><span class="item-type">Active</span>';
        $content_listing.= '<a class="item-edit" id="edit-' . esc_attr($feed_id) . '" onclick="wovax_idx_change_content(this)">';
        $content_listing.= '</a></span></div></div>';
        $content_listing.= '<div class="menu-item-settings wp-clearfix" id="menu-item-settings-' . esc_attr($feed_id) . '">';
        $content_listing.= '<p class="description description-wide">';
        $content_listing.= '<label for="field-' . esc_attr($count_aux_var) . '">Height<br>';
        $content_listing.= '<input type="text" name="wovax_idx_fieldspacer_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" id="field-' . esc_attr($count_aux_var) . '" class="widefat edit-menu-item-title"  value="' . esc_attr($auxiliar_spacer_array_info[1]) . '"></label>';

        $content_listing.= '<label for="cssclass-' . esc_attr($count_aux_var) . '">CSS Classes<br>';
        $content_listing.= '<input type="text" name="wovax_idx_cssclassspacer_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" id="cssclass-' . esc_attr($count_aux_var) . '" class="widefat edit-menu-item-title"  value="' . esc_attr($auxiliar_spacer_array_info[2]) . '"></label>';

        $content_listing.= '<input type="hidden" class="order-index" name="wovax_idx_orderspacer_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" value="' . esc_attr($i) . '" >';
        $content_listing.= '<input type="hidden" name="wovax_idx_field_statespacer_' . esc_attr($feed_id) . '-' . esc_attr($count_aux_var) . '" value="1" >';
        $content_listing.= '</p><div class="menu-item-actions description-wide submitbox">';
        $content_listing.= '<a class="item-delete submitdelete deletion" id="delete-' . esc_attr($feed_id) . '" onclick="wovax_idx_clean_input(this)">Remove</a>';
        $content_listing.= '</div></div></li>';
      }
    }

    //Increase auxiliar
    $count_aux_var++;
  }

  return $content_listing;
}

/* function save shortcodes view options */
function wovax_idx_save_shortcode_view($request) {
  global $wpdb;
  global $current_user;
  $date_time = current_time('mysql');
  $id = filter_var($request['wovax-idx-shortcode-id'], FILTER_SANITIZE_STRING);
  $grid_view = filter_var($request['wovax-idx-shortcode-grid-view'], FILTER_SANITIZE_STRING);
  $map_view = filter_var($request['wovax-idx-shortcode-map-view'], FILTER_SANITIZE_STRING);
  $page = (empty(filter_var($request['wovax-idx-shortcode-listings-per-page'], FILTER_SANITIZE_STRING)))?12:filter_var($request['wovax-idx-shortcode-listings-per-page'], FILTER_SANITIZE_STRING);
  $pagination = filter_var($request['wovax-idx-shortcode-pagination'], FILTER_SANITIZE_STRING);
  $map = (empty(filter_var($request['wovax-idx-shortcode-listings-per-map'], FILTER_SANITIZE_STRING)))?250:filter_var($request['wovax-idx-shortcode-listings-per-map'], FILTER_SANITIZE_STRING);
  $action_bar = filter_var($request['wovax-idx-shortcode-action-bar'], FILTER_SANITIZE_STRING);
  if ($id != '') {
    if (filter_var($id, FILTER_VALIDATE_INT) && filter_var($page, FILTER_VALIDATE_INT) && is_string($current_user->display_name) && is_string($pagination)) {
      $sql = "UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode` SET  `per_page` = %s, `author` = %s, `date` = %s, `status` = 'published', `pagination` = %s, `action_bar` = %s, `per_map` = %s, `grid_view` = %s, `map_view` = %s WHERE `id` = %d";
      $wpdb->query($wpdb->prepare($sql, array($page, $current_user->display_name, $date_time, $pagination, $action_bar, $map, $grid_view, $map_view, $id)));
    }
  }
  else {
    if (filter_var($page, FILTER_VALIDATE_INT) && is_string($current_user->display_name) && is_string($pagination)) {
      $sql = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_shortcode` ( `per_page`, `author`, `date`, `status`, `pagination`, `action_bar`, `feeds`, `per_map`, `grid_view`, `map_view` ) VALUES ( %s , %s , %s, 'published', %s, %s, '', %s, %s, %s )";
      $wpdb->query($wpdb->prepare($sql, array($page, $current_user->display_name, $date_time, $pagination, $action_bar, $map, $grid_view, $map_view)));
      $id = $wpdb->insert_id;
    }
  }
  return $id;
}

function wovax_idx_save_shortcode_filters($request) {
  global $wpdb;
  global $current_user;
  $date_time = current_time('mysql');
  $id = filter_var($request['wovax-idx-shortcode-id'], FILTER_SANITIZE_NUMBER_INT);
  $filter_id = filter_var($request['wovax-idx-shortcode-filter-id'], FILTER_SANITIZE_NUMBER_INT);
  $filter_type = filter_var($request['wovax-idx-shortcode-filter-type'], FILTER_SANITIZE_STRING);
  if($filter_type != "omnisearch") {
    $field_name = filter_var($request['wovax-idx-shortcode-filter-field'], FILTER_SANITIZE_STRING);

  } else {
    $field_name = filter_var($request['wovax-idx-shortcode-filter-field'], FILTER_SANITIZE_STRING);
  }
  if($filter_type === 'range') {
    $data_array = array();
    $data_array['range_start'] = filter_var($request['wovax-idx-shortcode-filter-range-min'], FILTER_SANITIZE_STRING);
    $data_array['range_end'] = filter_var($request['wovax-idx-shortcode-filter-range-max'], FILTER_SANITIZE_STRING);
    $data_array['interval'] = filter_var($request['wovax-idx-shortcode-filter-range-interval'], FILTER_SANITIZE_STRING);
    $filter_data = json_encode($data_array);
  } else if($filter_type === 'preset_range'){
    $data_array = array();
    $data_array['range_start'] = filter_var($request['wovax-idx-shortcode-filter-range-min'], FILTER_SANITIZE_STRING);
    $data_array['range_end'] = filter_var($request['wovax-idx-shortcode-filter-range-max'], FILTER_SANITIZE_STRING);
    $filter_data = json_encode($data_array);
  } else if($filter_type === 'preset_value'){
    $data_array = array();
    $data_array['value'] = filter_var($request['wovax-idx-shortcode-filter-value'], FILTER_SANITIZE_STRING);
    $filter_data = json_encode($data_array);
  } else {
    $filter_data = "";
  }
  $filter_label = filter_var($request['wovax-idx-shortcode-filter-label'], FILTER_SANITIZE_STRING);
  $filter_placeholder = filter_var($request['wovax-idx-shortcode-filter-placeholder'], FILTER_SANITIZE_STRING);
  if (empty($filter_id)) {
    if (filter_var($id, FILTER_VALIDATE_INT) && is_string($field_name) && is_string($filter_type) && is_string($filter_label) && is_string($filter_placeholder)) {
      $sql2 = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_shortcode_filters` ( `id_shortcode`, `id_field` , `field`, `filter_type`, `filter_label`, `filter_placeholder`, `filter_data`, `date` ) VALUES ( %d , %s , %s , %s , %s , %s , %s, %s)";
      $sql = $wpdb->prepare($sql2, array($id, '', $field_name, $filter_type, $filter_label, $filter_placeholder, $filter_data, $date_time));
    }
  } else {
    if (filter_var($filter_id, FILTER_VALIDATE_INT) && filter_var($id, FILTER_VALIDATE_INT) && is_string($field_name) && is_string($filter_type) && is_string($filter_label) && is_string($filter_placeholder) && is_string($filter_data)) {
      $sql2 = "UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode_filters` SET `id_field` = %s, `field` = %s, `filter_type` = %s, `filter_label` = %s, `filter_placeholder` = %s, `filter_data` = %s, `date` = %s WHERE `id_filter` = %d AND `id_shortcode` = %d";
      $sql = $wpdb->prepare($sql2, array('', $field_name, $filter_type, $filter_label, $filter_placeholder, $filter_data, $date_time, $filter_id, $id));
    }
  }
  $wpdb->query($sql);
  return $id;
}

function wovax_idx_save_feed_rules($request) {
  global $wpdb;
  global $current_user;
  $date_time = current_time('mysql');
  $id = filter_var($request['wovax-idx-feed-id'], FILTER_SANITIZE_NUMBER_INT);
  $rule_id = filter_var($request['wovax-idx-feed-rule-id'], FILTER_SANITIZE_NUMBER_INT);
  $field = filter_var($request['wovax-idx-feed-rule-field'], FILTER_SANITIZE_STRING);
  $field = explode('-', $field);
  $field_id = $field[0];
  $field_name = $field[1];
  $rule_type = filter_var($request['wovax-idx-feed-rule-type'], FILTER_SANITIZE_STRING);
  $rule_value = filter_var($request['wovax-idx-feed-rule-value'], FILTER_SANITIZE_STRING);
  if (empty($rule_id)) {
    if (filter_var($id, FILTER_VALIDATE_INT) && is_string($field_id) && is_string($field_name) && is_string($rule_type) && is_string($rule_value)) {
      $sql2 = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_feed_rules` ( `id_feed`, `id_field` , `field`, `rule_type`, `rule_value`, `date` ) VALUES ( %d , %s , %s , %s , %s , %s)";
      $sql = $wpdb->prepare($sql2, array($id, $field_id, $field_name, $rule_type, $rule_value, $date_time));
    }
  }
  else {
    if (filter_var($rule_id, FILTER_VALIDATE_INT) && filter_var($id, FILTER_VALIDATE_INT) && is_string($field_id) && is_string($field_name) && is_string($rule_type) && is_string($rule_value)) {
      $sql2 = "UPDATE `{$wpdb->base_prefix}wovax_idx_feed_rules` SET `id_field` = %s, `field` = %s, `rule_type` = %s, `rule_value` = %s, `date` = %s WHERE `id_rule` = %d AND `id_feed` = %d";
      $sql = $wpdb->prepare($sql2, array($field_id, $field_name, $rule_type, $rule_value, $date_time, $rule_id, $id));
    }
  }
  $wpdb->query($sql);
  return $id;
}

function wovax_idx_save_shortcode_rules($request) {
  global $wpdb;
  global $current_user;
  $date_time = current_time('mysql');
  $id = filter_var($request['wovax-idx-shortcode-id'], FILTER_SANITIZE_NUMBER_INT);
  $rule_id = filter_var($request['wovax-idx-shortcode-rule-id'], FILTER_SANITIZE_NUMBER_INT);
  $field_name = filter_var($request['wovax-idx-shortcode-rule-field'], FILTER_SANITIZE_STRING);
  $rule_type = filter_var($request['wovax-idx-shortcode-rule-type'], FILTER_SANITIZE_STRING);
  $rule_value = htmlspecialchars(stripslashes($request['wovax-idx-shortcode-rule-value']));
  if (empty($rule_id)) {
    if (filter_var($id, FILTER_VALIDATE_INT) && is_string($field_name) && is_string($rule_type) && is_string($rule_value)) {
      $sql2 = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_shortcode_rules` ( `id_shortcode`, `id_field` , `field`, `rule_type`, `rule_value`, `date` ) VALUES ( %d , %s , %s , %s , %s , %s)";
      $sql = $wpdb->prepare($sql2, array($id, '', $field_name, $rule_type, $rule_value, $date_time));
    }
  }
  else {
    if (filter_var($rule_id, FILTER_VALIDATE_INT) && filter_var($id, FILTER_VALIDATE_INT) && is_string($field_name) && is_string($rule_type) && is_string($rule_value)) {
      $sql2 = "UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode_rules` SET `id_field` = %s, `field` = %s, `rule_type` = %s, `rule_value` = %s, `date` = %s WHERE `id_rule` = %d AND `id_shortcode` = %d";
      $sql = $wpdb->prepare($sql2, array('', $field_name, $rule_type, $rule_value, $date_time, $rule_id, $id));
    }
  }
  $wpdb->query($sql);
  return $id;
}

/* function to save shortcode's feeds */
function wovax_idx_save_shortcode_feeds($request) {
  global $wpdb;
  $feeds = array();
  $id = filter_var($request['wovax-idx-shortcode-id'], FILTER_SANITIZE_NUMBER_INT);
  //makes sure that the id is in fact an integer
  if (filter_var($id, FILTER_VALIDATE_INT)) {
    foreach($request as $key => $value) {
      if (stripos($key, 'wovax-idx-shortcode-feed') !== false) {
        $key = filter_var($key, FILTER_SANITIZE_STRING);
        $feeds[$key] = filter_var($value, FILTER_SANITIZE_STRING);
      }
    }
    $feeds = json_encode($feeds);
    $sql = "UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode` SET `feeds` = %s WHERE `id` = %d";
    $wpdb->query($wpdb->prepare($sql, array(
      $feeds,
      $id
    )));
  }
  return $id;
}

/* function to return the values that belong to specificly field */
function wovax_idx_find_by_name_feed($data, $id_field) {
  foreach($data as $key => $item) {
    if (isset($item->id_field) && $item->id_field == $id_field) {
      return $item;
    }
  }
  return false;
}

/* function to return the values that belong to that feed  stored in the database */
function wovax_idx_list_feeds_field_by_id($id_feed) {
  global $wpdb;
  $id_feed = filter_var($id_feed, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($id_feed, FILTER_VALIDATE_INT)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_feed_fields` AS `ff` WHERE ff.`id_feed` = %d ORDER BY ff.`order`, ff.`default_alias`;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id_feed));
  }
  return $results;
}

function wovax_idx_list_feeds_field_by_id_status_1($id_feed) {
  global $wpdb;
  $id_feed = filter_var($id_feed, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($id_feed, FILTER_VALIDATE_INT)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_feed_fields` AS `ff` WHERE ff.`id_feed` = %d AND ff.`field_state` = 1 ORDER BY ff.`order`;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id_feed));
  }
  return $results;
}

function wovax_idx_merge_list_feeds($list_option, $list_api) {
  foreach($list_api as $key => $item) {
    $item = ( object )$item;
    if ($key_exists = wovax_idx_exists_feed_id($list_option, $item->class_id)) {
      $list_option[$key_exists->key] = $item;
    }
    else {
      $list_option[] = $item;
    }
  }
  return $list_option;
}

function wovax_idx_exists_feed_id($data, $id) {
  foreach($data as $key => $item) {
    $item = ( object )$item;
    if ($id == $item->id) {
      return ( object )array(
        "key" => $key
      );
    }
  }
  return false;
}

/* function to return the parameters of the specific feed */
function wovax_idx_get_object_by_id_in_option($id) {
  $lista_option = get_option('wovax_idx_feeds_list');
  if ($lista_option) {
    $lista_option = json_decode($lista_option);
  }
  else {
    $lista_option = array();
  }
  foreach($lista_option as $key => $item) {
    $item = ( object )$item;
    if ($id == $item->class_id) {
      return $item;
    }
  }
  return false;
}

function wovax_idx_shortcode_duplicate_table($id) {
  global $wpdb;
  global $current_user;
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($id, FILTER_VALIDATE_INT)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `id` = %d ;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id));
    if ($results) {
      $type = $results[0]->type;
      $title = $results[0]->title . ' (Copy)';
      $grid_view = $results[0]->grid_view;
      $map_view = $results[0]->map_view;
      $per_page = $results[0]->per_page;
      $per_map = $results[0]->per_map;
      //author comes from current user
      $date_time = current_time('mysql');
      //status is always published
      $pagination = $results[0]->pagination;
      $action_bar = $results[0]->action_bar;
      $feeds = $results[0]->feeds;
      $order_section = $results[0]->order_section;
      $sql = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_shortcode` ( `type`, `title`, `grid_view`, `map_view`, `per_page`, `per_map`, `author`, `date`, `status`, `pagination`, `action_bar`, `feeds`, `order_section` ) VALUES ( %s, %s, %s, %s, %s, %s, %s, %s, 'published', %s, %s, %s, %s );";
      $wpdb->query($wpdb->prepare($sql, array($type, $title, $grid_view, $map_view, $per_page, $per_map, $current_user->display_name, $date_time, $pagination, $action_bar, $feeds, $order_section)));
      $new_id = $wpdb->insert_id;
      if ($new_id) {

        if ($type == 'search_form') {

          $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode_filters` WHERE `id_shortcode` = %d ;";
          $results = $wpdb->get_results($wpdb->prepare($sql, $id));
          if ($results) {
            $filter_array = array();
            $sql = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_shortcode_filters` (`id_shortcode`, `id_field`, `field`, `filter_type`, `filter_label`, `filter_placeholder`, `date`) VALUES ";
            foreach ($results as $key => $value) {
              $sql .= "(%d, %s, %s, %s, %s, %s, %s),";
              array_push($filter_array, $new_id);
              array_push($filter_array, $value->id_field);
              array_push($filter_array, $value->field);
              array_push($filter_array, $value->filter_type);
              array_push($filter_array, $value->filter_label);
              array_push($filter_array, $value->filter_placeholder);
              array_push($filter_array, current_time('mysql'));
            }
            $sql = substr($sql, 0, -1);
            $wpdb->query($wpdb->prepare($sql, $filter_array));
          }

        } elseif ($type == 'listings') {

          $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode_rules` WHERE `id_shortcode` = %d ;";
          $results = $wpdb->get_results($wpdb->prepare($sql, $id));
          if ($results) {
            $rule_array = array();
            $sql = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_shortcode_rules` (`id_shortcode`, `id_field`, `field`, `rule_type`, `rule_value`, `date`) VALUES ";
            foreach ($results as $key => $value) {
              $sql .= "(%d, %s, %s, %s, %s, %s),";
              array_push($rule_array, $new_id);
              array_push($rule_array, $value->id_field);
              array_push($rule_array, $value->field);
              array_push($rule_array, $value->rule_type);
              array_push($rule_array, $value->rule_value);
              array_push($rule_array, current_time('mysql'));
            }
            $sql = substr($sql, 0, -1);
            $wpdb->query($wpdb->prepare($sql, $rule_array));
          }

        }

      }
      return 'success';
    }
  }
}

function wovax_idx_shortcode_duplicate_filter($idshortcode, $idfilter) {
  global $wpdb;
  $idshortcode = filter_var($idshortcode, FILTER_SANITIZE_NUMBER_INT);
  $idfilter = filter_var($idfilter, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($idshortcode, FILTER_VALIDATE_INT) && filter_var($idfilter, FILTER_VALIDATE_INT)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode_filters` WHERE `id_filter` = %d AND `id_shortcode` = %d;";
    $results = $wpdb->get_results($wpdb->prepare($sql, array(
      $idfilter,
      $idshortcode
    )));
    if ($results) {
      $field = $results[0]->field;
      $filter_type = $results[0]->filter_type;
      $filter_label = $results[0]->filter_label . ' (Copy)';
      $filter_placeholder = $results[0]->filter_placeholder;
      $date_time = current_time('mysql');
      $sql = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_shortcode_filters` ( `id_shortcode`, `field`, `filter_type`, `filter_label`, `filter_placeholder`, `date` ) VALUES ( %d, %s, %s, %s, %s, %s );";
      $wpdb->query($wpdb->prepare($sql, array($idshortcode, $field, $filter_type, $filter_label, $filter_placeholder, $date_time)));
      return 'success';
    }
  }
}

/* function to delete filters */
function wovax_idx_form_trash_action_filter() {
    global $wpdb;
    if(
        !array_key_exists('wovax-idx-shortcode-id', $_POST) ||
        $_SERVER['REQUEST_METHOD'] !== 'POST'
    ) {
        return;
    }
    $id_shortcode = filter_var($_POST['wovax-idx-shortcode-id'], FILTER_SANITIZE_STRING);
    if (isset($_POST['apply_action_field']) && $_POST['apply_action_field'] == 'delete' && isset($_POST['button_action']) && $_POST['button_action'] == 'Apply') {
      if (isset($_POST['post'])) {
        $sql = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_shortcode_filters`  WHERE `id_filter` IN (";
        $ids = array();
        foreach($_POST['post'] as $key => $value) {
          array_push($ids, filter_var($value, FILTER_SANITIZE_NUMBER_INT));
          $sql.= ' %d,';
        }
        $sql = substr($sql, 0, -1);
        $sql.= ' )';
        $wpdb->query($wpdb->prepare($sql, $ids));
        $url = admin_url("admin.php?page=wovax_idx_shortcodes&action=update&id=$id_shortcode&tab=filters");
        wp_redirect($url);
      }
    }
}

function wovax_idx_form_trash_action() {
  global $wpdb;
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_action']) && $_POST['apply_action'] == 'delete' && !isset($_POST['delete_all'])) {
      if (isset($_POST['post'])) {
        $sql = "UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode` SET `status` = 'trash' WHERE `id` IN (";
        $ids = array();
        foreach($_POST['post'] as $key => $value) {
          array_push($ids, filter_var($value, FILTER_SANITIZE_NUMBER_INT));
          $sql.= ' %d,';
        }

        $sql = substr($sql, 0, -1);
        $sql.= ' )';
        $wpdb->query($wpdb->prepare($sql, $ids));
        $url = admin_url("admin.php?page=wovax_idx_shortcodes");
        wp_redirect($url);
      }
    }
    elseif (isset($_POST['shortcode_type'], $_POST['s']) && $_POST['shortcode_type'] != 'all' && $_POST['s'] != '' && !isset($_POST['delete_all'])) {

      // filter and search
      $search = filter_var($_POST['s'], FILTER_SANITIZE_STRING);
      $filter = filter_var($_POST['shortcode_type'], FILTER_SANITIZE_STRING);
      if ($_POST['apply_action'] == 't') {
        $url = admin_url("admin.php?page=wovax_idx_shortcodes&f=$filter&s=$search&t=t");
      }
      else {
        $url = admin_url("admin.php?page=wovax_idx_shortcodes&f=$filter&s=$search");
      }
      wp_redirect($url);
    }
    elseif (isset($_POST['shortcode_type'], $_POST['s']) && $_POST['shortcode_type'] != 'all' && $_POST['s'] == '' && !isset($_POST['delete_all'])) {

      // filter
      $filter = filter_var($_POST['shortcode_type'], FILTER_SANITIZE_STRING);
      if ($_POST['apply_action'] == 't') {
        $url = admin_url("admin.php?page=wovax_idx_shortcodes&f=$filter&t=t");
      }
      else {
        $url = admin_url("admin.php?page=wovax_idx_shortcodes&f=$filter");
      }
      wp_redirect($url);
    }
    elseif (isset($_POST['shortcode_type'], $_POST['s']) && $_POST['shortcode_type'] == 'all' && $_POST['s'] != '' && !isset($_POST['delete_all'])) {

      // search
      $search = filter_var($_POST['s'], FILTER_SANITIZE_STRING);
      if ($_POST['apply_action'] == 't') {
        $url = admin_url("admin.php?page=wovax_idx_shortcodes&s=$search&t=t");
      }
      else {
        $url = admin_url("admin.php?page=wovax_idx_shortcodes&s=$search");
      }
      wp_redirect($url);
    }
    elseif (isset($_POST['delete_all'])) {
      /* deletes the filter of the shortcodes */
      $sql_select = "SELECT `id` FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = 'trash'";
      $results_select = $wpdb->get_results($sql_select);
      $sql_delete_filters = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_shortcode_filters` WHERE `id_shortcode` IN  (";
      $sql_delete_rules   = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_shortcode_rules` WHERE `id_shortcode` IN  (";
      $id_shortcode = array();
      foreach($results_select as $key => $value) {
        array_push($id_shortcode, $value->id);
        $sql_delete_filters.= ' %d,';
        $sql_delete_rules  .= ' %d,';
      }
      $sql_delete_filters = substr($sql_delete_filters, 0, -1);
      $sql_delete_filters.= ' )';
      $results_delete = $wpdb->get_results($wpdb->prepare($sql_delete_filters, $id_shortcode));

      $sql_delete_rules = substr($sql_delete_rules, 0, -1);
      $sql_delete_rules.= ' )';
      $results_delete = $wpdb->get_results($wpdb->prepare($sql_delete_rules, $id_shortcode));
      /* delete the shortcode */
      $sql = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = 'trash'";
      $wpdb->query($sql);
      $url = admin_url("admin.php?page=wovax_idx_shortcodes&a=trash&t=t");
      wp_redirect($url);
    }
    elseif (isset($_POST['apply_action']) && $_POST['apply_action'] == 'delete_permanently' && !isset($_POST['delete_all'])) {
      if (isset($_POST['post'])) {
        $sql_delete_filters = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_shortcode_filters` WHERE `id_shortcode` IN  (";
        $sql_delete_rules   = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_shortcode_rules` WHERE `id_shortcode` IN  (";
        $sql = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_shortcode`  WHERE  `status` = 'trash' AND `id` IN  (";
        $ids = array();
        foreach($_POST['post'] as $key => $value) {
          array_push($ids, filter_var($value, FILTER_SANITIZE_NUMBER_INT));
          $sql_delete_filters.= ' %d,';
          $sql_delete_rules  .= ' %d,';
          $sql.= ' %d,';
        }

        $sql_delete_filters = substr($sql_delete_filters, 0, -1);
        $sql_delete_rules   = substr($sql_delete_rules, 0, -1);
        $sql = substr($sql, 0, -1);
        $sql_delete_filters.= ' )';
        $sql_delete_rules  .= ' )';
        $sql.= ' )';
        /* deletes the filter of the shortcodes  */
        $wpdb->query($wpdb->prepare($sql_delete_filters, $ids));
        /* deletes the rules of the shortcodes  */
        $wpdb->query($wpdb->prepare($sql_delete_rules, $ids));
        /* delete the shortcode */
        $wpdb->query($wpdb->prepare($sql, $ids));
        $url = admin_url("admin.php?page=wovax_idx_shortcodes&a=trash&t=t");
        wp_redirect($url);
      }
    }
    elseif (isset($_POST['apply_action']) && $_POST['apply_action'] == 'untrash' && !isset($_POST['delete_all'])) {
      if (isset($_POST['post'])) {
        $sql = "UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode` SET `status` = 'published' WHERE `id` IN (";
        $ids = array();
        foreach($_POST['post'] as $key => $value) {
          array_push($ids, filter_var($value, FILTER_SANITIZE_NUMBER_INT));
          $sql.= ' %d,';
        }

        $sql = substr($sql, 0, -1);
        $sql.= ' )';
        $wpdb->query($wpdb->prepare($sql, $ids));
        $url = admin_url("admin.php?page=wovax_idx_shortcodes");
        wp_redirect($url);
      }
    }
    else {
      if (isset($_GET['page']) && $_GET['page'] == 'wovax_idx_shortcodes') {
        $url = admin_url("admin.php?page=wovax_idx_shortcodes");
        wp_redirect($url);
      }
    }
  }
}

/* function to return the values of the shortcode */
function wovax_idx_find_shortcode($id, $action, $filter, $search, $page) {
  global $wpdb;
  $status = ($page == 'trash_page') ? 'trash' : 'published';
  if ($id == '' && $action == '' & $filter == '' & $search == '') {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = 'published' ;";
    $val_array = null;
  }
  elseif ($id != '' && $action != '') {
    if ($action == 'trash') {
      $sql_updated = "UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode` SET `status` = 'trash' WHERE `id` = %d";
      $wpdb->query($wpdb->prepare($sql_updated, $id));
      $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = 'published' ;";
      $val_array = null;
    }
    elseif ($action == 'untrash') {
      $sql_updated = "UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode` SET `status` = 'published' WHERE `id` = %d";
      $wpdb->query($wpdb->prepare($sql_updated, $id));
      $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = 'trash' ;";
      $val_array = null;
    }
    elseif ($action == 'delete') {
      /* deletes the filter of the shortcodes  */
      $sql_delete = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_shortcode_filters` WHERE `id_shortcode` = %d";
      $wpdb->query($wpdb->prepare($sql_delete, $id));
      /* delete the shortcode */
      $sql_updated = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = 'trash' AND `id` = %d";
      $wpdb->query($wpdb->prepare($sql_updated, $id));
      $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = 'trash' ;";
      $val_array = null;
    }
  }
  elseif (!empty($filter) && !empty($search)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE ( `type` = %s AND `title` LIKE %s OR `type` LIKE %s ) AND `status` = %s ;";
    $val_array = 1;
  }
  elseif (!empty($filter) && empty($search)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `type` = %s AND  `status` = %s ;";
    $val_array = 2;
  }
  elseif (empty($filter) && !empty($search)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE ( `title` LIKE %s OR `type` LIKE %s ) AND  `status` = %s;";
    $val_array = 3;
  }
  else {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = 'published' ;";
    $val_array = null;
  }

  if (!is_array($val_array) && $val_array == null) {
    $results = $wpdb->get_results($sql);
  }
  elseif ($val_array == 1) {
    $results = $wpdb->get_results($wpdb->prepare($sql, array($filter, '%$search%', '%$search%', $status)));
  }
  elseif ($val_array == 2) {
    $results = $wpdb->get_results($wpdb->prepare($sql, array($filter, $status)));
  }
  elseif ($val_array == 3) {
    $results = $wpdb->get_results($wpdb->prepare($sql, array('%$search%', '%$search%', $status)));
  }
  return $results;
}

/* function to return the shortcode table */
function wovax_idx_shortcode_list_table($id_shortcode, $action_shortcode, $filter, $search, $page) {
  $shortcode_table = '';
  $count = 0;

  $user = get_current_user_id();
  $shortcode_meta = get_user_meta( $user, 'managewovax-idx_page_wovax_idx_shortcodescolumnshidden', true );
  $shortcode_meta = ( is_array($shortcode_meta) )?$shortcode_meta:array();

  $shortcode_hidden = in_array('shortcode', $shortcode_meta)?' hidden':'';
  $type_hidden      = in_array('type', $shortcode_meta)?' hidden':'';
  $author_hidden    = in_array('author', $shortcode_meta)?' hidden':'';
  $created_hidden   = in_array('created', $shortcode_meta)?' hidden':'';

  if ($page == 'trash_page') {
    if ($action_shortcode == 'untrash' || $action_shortcode == 'delete') {
      $result = wovax_idx_find_shortcode($id_shortcode, $action_shortcode, $filter, $search, 'trash_page');
    }
    elseif ($filter != '' || $search != '') {
      $result = wovax_idx_find_shortcode($id_shortcode, $action_shortcode, $filter, $search, 'trash_page');
    }
    else {
      $result = wovax_idx_find_shortcodes_trash('trash');
    }
    $shortcode_untrash = wovax_idx_find_shortcodes_trash('published');
    foreach($result as $key => $item) {
      $count++;
      $date_mys = mysql2date('U', $item->date);
      $last_updated = esc_html(human_time_diff($date_mys, current_time('timestamp')));
      $filter_type = ($item->type == 'listings') ? 'Listings' : 'Search Form';
      $shortcode_table.= '<tr>
			                    		<th scope="row" class="check-column">
			                      			<input type="checkbox" name="post[]" value="' . esc_attr($item->id) . '">
			                    		</th>
			                    		<td class="title column-title has-row-actions column-primary" data-colname="Title">
			                      			<strong><a class="row-title" "' . esc_url('admin.php?page=wovax_idx_shortcodes&action=update&id=' . $item->id . '') . '" title="Edit “Main site search”">' . esc_html($item->title) . '</a></strong>
			                      			<div class="row-actions"><span class="untrash"><a  href="' . esc_url('admin.php?page=wovax_idx_shortcodes&action=untrash&t=t&id=' . $item->id . '') . '" aria-label="Restore “dfg” from the Trash">Restore</a> | </span><span class="delete"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&action=delete&t=t&id=' . $item->id . '') . '" class="submitdelete" aria-label="Delete “dfg” permanently">Delete Permanently</a></span></div>
			                      			<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
			                    		</td>
			                    		<td class="shortcode column-shortcode' . esc_attr($shortcode_hidden) . '" data-colname="Shortcode">
			                      			[wovax-idx id="' . esc_html($item->id) . '"]
			                    		</td>
			                    		<td class="type column-type' . esc_attr($type_hidden) . '" data-colname="Type">
			                     			' . esc_html($filter_type) . '
			                    		</td>
			                    		<td class="author column-author' . esc_attr($author_hidden) . '" data-colname="Author">
			                       			' . esc_html($item->author) . '
			                    		</td>
			                    		<td class="created column-created' . esc_attr($created_hidden) . '" data-colname="Created">
			                      			<abbr title="' . esc_attr($item->date) . '">
			                       				' . esc_html($last_updated) . '
			                      			</abbr>
			                    		</td>
			                  		 </tr>';
    }
    if (empty($result)) {
      $shortcode_table = '<tr><td class="colspanchange" colspan="6">No shortcodes found.</td></tr>';
    }
    $wovax_table = array(
      'table' => $shortcode_table,
      'count_published' => count($shortcode_untrash) ,
      'count_trash' => count($result)
    );
  }
  else {
    $result = wovax_idx_find_shortcode($id_shortcode, $action_shortcode, $filter, $search, '');
    $shortcode_trash = wovax_idx_find_shortcodes_trash('trash');
    foreach($result as $key => $item) {
      $count++;
      $date_mys = mysql2date('U', $item->date);
      $last_updated = esc_html(human_time_diff($date_mys, current_time('timestamp')));
      $filter_type = ($item->type == 'listings' ? 'Listings Embed' : ($item->type == 'search_form' ? 'Search Form' : ($item->type == 'user_favorites' ? 'User Favorites' : 'User Profile')));
      $shortcode_table.= '<tr>
			                    		<th scope="row" class="check-column">
			                      			<input type="checkbox" name="post[]" value="' . esc_attr($item->id) . '">
			                    		</th>
			                    		<td class="title column-title has-row-actions column-primary" data-colname="Title">
			                      			<strong><a class="row-title" "' . esc_url('admin.php?page=wovax_idx_shortcodes&tab=general&id=' . $item->id . '&action=update') . '" title="Edit “Main site search”">' . esc_html($item->title) . '</a></strong>
			                      			<div class="row-actions"><span class="edit"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&tab=general&id=' . $item->id . '&action=update') . '">Edit</a> | </span><span class="copy"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&id=' . $item->id . '&action=duplicate') . '">Duplicate</a> | </span><span class="trash"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&id=' . $item->id . '&action=trash') . '" class="submitdelete" aria-label="Move “Main site search” to the Trash">Trash</a></span></div>
			                      			<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
			                    		</td>
			                    		<td class="shortcode column-shortcode' . esc_attr($shortcode_hidden) . '" data-colname="Shortcode">
			                      			[wovax-idx id="' . esc_html($item->id) . '"]
			                    		</td>
			                    		<td class="type column-type' . esc_attr($type_hidden) . '" data-colname="Type">
			                     			' . esc_html($filter_type) . '
			                    		</td>
			                    		<td class="author column-author' . esc_attr($author_hidden) . '" data-colname="Author">
			                       			' . esc_html($item->author) . '
			                    		</td>
			                    		<td class="created column-created' . esc_attr($created_hidden) . '" data-colname="Created">
			                      			<abbr title="' . esc_attr($item->date) . '">
			                       			' . esc_html($last_updated) . ' ago
			                      			</abbr>
			                    		</td>
			                  		 </tr>';
    }
    if (empty($result)) {
      $shortcode_table = '<tr><td class="colspanchange" colspan="6">No shortcodes found.</td></tr>';;
    }
    $wovax_table = array(
      'table' => $shortcode_table,
      'count_published' => count($result) ,
      'count_trash' => count($shortcode_trash)
    );
  }
  return $wovax_table;
}

/* function to return the users table */
function wovax_idx_user_activity_list_table($search) {
  $users_table = '';
  $count = 0;
  $users = get_users( array( 'fields' => array( 'ID' ) ) );

  $user = get_current_user_id();
  $user_meta = get_user_meta( $user, 'managewovax-idx_page_wovax_idx_user_activitycolumnshidden', true );
  $user_meta = ( is_array($user_meta) )?$user_meta:array();

  $fullname_hidden = in_array('fullname', $user_meta)?' hidden':'';
  $phone_hidden      = in_array('phone', $user_meta)?' hidden':'';
  $email_hidden    = in_array('email', $user_meta)?' hidden':'';
  $favorites_hidden   = in_array('favorites', $user_meta)?' hidden':'';

  foreach ($users as $userid) {
    if ($userid) {
      $avatar = get_avatar($userid->ID, 32);
      $user_meta_aux = get_user_meta ($userid->ID);
      $qty_prop = (!isset($user_meta_aux['wovax-idx-favorites'][0]))?0:count(json_decode($user_meta_aux['wovax-idx-favorites'][0]));
      $username = (!empty($user_meta_aux['first_name'][0]) && !empty($user_meta_aux['last_name'][0]))?$user_meta_aux['first_name'][0] . ' ' .$user_meta_aux['last_name'][0]:$user_meta_aux['last_name'][0].$user_meta_aux['last_name'][0];
      $phone    = (!isset($user_meta_aux['phone'][0]))?'':$user_meta_aux['phone'][0];
      $email    = (empty(get_user_option( 'user_email', $userid->ID )))?'':get_user_option( 'user_email', $userid->ID );
      if($search != '') {
        if ((strpos(strtolower($user_meta_aux['nickname'][0]) , strtolower($search)) !== FALSE || strpos(strtolower($username) , strtolower($search)))) {
          $qty_prop = ($qty_prop>1?$qty_prop . ' Properties': ($qty_prop==0?'None':$qty_prop . ' Property'));
          $users_table .= '<tr>
                             <td class="username column-username has-row-actions column-primary" data-colname="Username">
                              ' . $avatar . '
                              <strong><a class="row-title" href="' . esc_url('admin.php?page=wovax_idx_user_activity&amp;tab=general&amp;action=update&amp;iduser=' . $userid->ID . '') . '" title="Edit ' . esc_attr($user_meta_aux['nickname'][0]) . '">' . esc_html($user_meta_aux['nickname'][0]) . '</a></strong>
                              <div class="row-actions"><span class="edit"><a href="' . esc_url('admin.php?page=wovax_idx_user_activity&amp;tab=general&amp;action=update&amp;iduser=' . $userid->ID . '') . '">Details</a></span></div>
                              <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                              </td>
                              <td class="fullname column-fullname' . esc_attr($fullname_hidden) . '" data-colname="Fullname">
                              ' . $username . '
                              </td>
                              <td class="phone column-phone' . esc_attr($phone_hidden) . '" data-colname="Phone">
                              ' . $phone . '
                              </td>
                              <td class="email column-email' . esc_attr($email_hidden) . '" data-colname="Email">
                              ' . $email . '
                              </td>
                              <td class="favorites column-favorites' . esc_attr($favorites_hidden) . '" data-colname="Favorites">
                              ' . esc_html($qty_prop) . '
                              </td>
                            </tr>';
          $count++;
        }
      } else{
        $qty_prop = ($qty_prop>1?$qty_prop . ' Properties': ($qty_prop==0?'None':$qty_prop . ' Property'));
        $users_table .= '<tr>
                          <td class="username column-username has-row-actions column-primary" data-colname="Username">
                          ' . $avatar . '
                          <strong><a class="row-title" href="' . esc_url('admin.php?page=wovax_idx_user_activity&amp;tab=general&amp;action=update&amp;iduser=' . $userid->ID . '') . '" title="Edit ' . esc_attr($user_meta_aux['nickname'][0]) . '">' . esc_html($user_meta_aux['nickname'][0]) . '</a></strong>
                          <div class="row-actions"><span class="edit"><a href="' . esc_url('admin.php?page=wovax_idx_user_activity&amp;tab=general&amp;action=update&amp;iduser=' . $userid->ID . '') . '">Details</a></span></div>
                          <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                          </td>
                          <td class="fullname column-fullname' . esc_attr($fullname_hidden) . '" data-colname="Fullname">
                          ' . $username . '
                          </td>
                          <td class="phone column-phone' . esc_attr($phone_hidden) . '" data-colname="Phone">
                          ' . $phone . '
                          </td>
                          <td class="email column-email' . esc_attr($email_hidden) . '" data-colname="Email">
                          ' . $email . '
                          </td>
                          <td class="favorites column-favorites' . esc_attr($favorites_hidden) . '" data-colname="Favorites">
                          ' . esc_html($qty_prop) . '
                          </td>
                        </tr>';
          $count++;
      }
    }
  }

  $wovax_table = array(
    'table' => $users_table,
    'count_users' => $count
  );

  return $wovax_table;

}

/* function to return all the shortcode tras */
function wovax_idx_find_shortcodes_trash($status) {
  global $wpdb;
  $status = filter_var($status, FILTER_SANITIZE_STRING);
  if (is_string($status)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = %s ;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $status));
    return $results;
  }
  else {
    return false;
  }
}

/* function to return one shortcode filter by ID */
function wovax_idx_find_one_shortcode_filter_by_id($id) {
  global $wpdb;
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($id, FILTER_VALIDATE_INT)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode_filters` WHERE `id_filter` = %d ;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id));
    return $results;
  }
  else {
    return false;
  }
}

/* function to return one shortcode rule by ID */
function wovax_idx_find_one_shortcode_rule_by_id($id) {
  global $wpdb;
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($id, FILTER_VALIDATE_INT)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode_rules` WHERE `id_rule` = %d ;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id));
    return $results;
  }
  else {
    return false;
  }
}

/* function to return one feed rule by ID */
function wovax_idx_find_one_feed_rule_by_id($id) {
  global $wpdb;
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($id, FILTER_VALIDATE_INT)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_feed_rules` WHERE `id_rule` = %d ;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id));
    return $results;
  }
  else {
    return false;
  }
}

/* function to return a list of shortcode filters by ID */
function wovax_idx_find_shortcode_filters_by_id($id, $order_numbers) {
  global $wpdb;
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($id, FILTER_VALIDATE_INT)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode_filters` WHERE `id_shortcode` = %d  ORDER BY `id_filter` ASC ;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id));
    return wovax_idx_order_result_by_filters_ids($results, $order_numbers);
  }
  else {
    return false;
  }
}

/* function to return a list of shortcode rules by ID */
function wovax_idx_find_shortcode_rules_by_id($id, $order_numbers = '') {
  global $wpdb;
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($id, FILTER_VALIDATE_INT)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode_rules` WHERE `id_shortcode` = %d  ORDER BY `id_rule` ASC ;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id));
    return wovax_idx_order_result_by_rules_ids($results, $order_numbers);
  }
  else {
    return false;
  }
}

/* function to return a list of feed rules by ID */
function wovax_idx_find_feed_rules_by_id($id, $order_numbers = '') {
  global $wpdb;
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($id, FILTER_VALIDATE_INT)) {
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_feed_rules` WHERE `id_feed` = %d  ORDER BY `id_rule` ASC ;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id));
    return wovax_idx_order_result_by_rules_ids($results, $order_numbers);
  }
  else {
    return false;
  }
}

function wovax_idx_order_result_by_filters_ids($results, $order_numbers) {
  $new_results = array();
  if (!is_array($order_numbers)) {
    $order_numbers = (array)json_decode($order_numbers);
    if (is_null($order_numbers)) {
      $order_numbers = array();
    }
  }
  foreach($order_numbers as $key => $id_filter) {
    foreach($results as $key2 => $item) {
      if ($id_filter == $item->id_filter) {
        $new_results[] = $item;
        unset($results[$key2]);
      }
    }
  }
  $results = array_values($results);
  foreach($results as $key => $item) {
    $new_results[] = $item;
  }
  return $new_results;
}

function wovax_idx_order_result_by_rules_ids($results, $order_numbers) {
  $new_results = array();
  if (!is_array($order_numbers)) {
    $order_numbers = (array)json_decode($order_numbers);
    if (is_null($order_numbers)) {
      $order_numbers = array();
    }
  }
  foreach($order_numbers as $key => $id_rule) {
    foreach($results as $key2 => $item) {
      if ($id_rule == $item->id_rule) {
        $new_results[] = $item;
        unset($results[$key2]);
      }
    }
  }
  $results = array_values($results);
  foreach($results as $key => $item) {
    $new_results[] = $item;
  }
  return $new_results;
}

/* function to return the shorcode filter table */
function wovax_idx_shortcode_filter_table($data) {
  $table = '';
  foreach($data as $key => $item) {
    $date_mys = mysql2date('U', $item->date);
    $last_updated = esc_html(human_time_diff($date_mys, current_time('timestamp')));
    switch ($item->filter_type) {
      case 'select':
        $type = 'Select';
        break;
      case 'numeric':
        $type = 'Numeric';
        break;
      case 'numeric_max':
        $type = 'Numeric Max';
        break;
      case 'numeric_min':
        $type = 'Numeric Min';
        break;
      case 'input_text':
        $type = 'Text Search';
        break;
      case 'omnisearch':
        $type = 'Omnisearch';
        break;
      case 'range':
        $type = "Numeric Range";
        break;
      case 'boolean':
        $type = "Boolean Search";
        break;
      case 'preset_value':
        $type = "Preset Value";
        $item->filter_label = '';
        $item->filter_placeholder = '';
        break;
      case 'preset_range':
        $type = 'Preset Range';
        $item->filter_label = '';
        $item->filter_placeholder = '';
        break;
    }
    $table.= '<tr id="' . esc_attr($item->id_filter) . '" index="' . esc_attr($item->order) . '">
							<input type="hidden" class="order-index" id="' . esc_attr($item->id_filter) . '-order" name="' . esc_attr($item->id_filter) . '-order" value="' . esc_attr($item->order) . '">
		                    <th scope="row" class="check-column">
		                      <input type="checkbox" name="post[]" value="' . esc_attr($item->id_filter) . '">

		                    </th>
		                    <td class="column-primary" data-colname="Title">
		                      <strong>' . esc_html($item->field) . '</strong>
		                      <div class="row-actions"><span class="edit"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&tab=filters&id=' . $item->id_shortcode . '&action=update&idfilter=' . $item->id_filter . '') . '">Edit</a> | </span><span class="copy"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&tab=filters&id=' . $item->id_shortcode . '&action=duplicate&idfilter=' . $item->id_filter . '') . '">Duplicate</a> | </span><span class="trash"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&tab=filters&id=' . $item->id_shortcode . '&action=update&idfilter=' . $item->id_filter . '&a=delete') . '" class="submitdelete" aria-label="Delete “City”">Delete</a></span></div>
		                      <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
		                    </td>
		                    <td class="" data-colname="Filter Type">
		                      ' . esc_html($type) . '
		                    </td>
		                    <td class="" data-colname="Filter Label">
		                      ' . esc_html($item->filter_label) . '
		                    </td>
		                    <td class="" data-colname="Filter Placeholder">
		                      ' . esc_html($item->filter_placeholder) . '
		                    </td>
		                    <td class="date column-date" data-colname="Filter Data">
                          <div>
                            '. print_r($item->filter_data, true) .'
                          </div>
		                    </td>
		                  </tr>';
  }
  if (empty($data)) {
    $table = '<tr><td class="colspanchange" colspan="6">No filters found.</td></tr>';;
  }
  $wovax_data = array(
    'table' => $table,
    'total_count' => count($data)
  );
  return $wovax_data;
}

/* function to return the shorcode rule table */
function wovax_idx_shortcode_rule_table($data) {
  $table = '';
  foreach($data as $key => $item) {
    $date_mys = mysql2date('U', $item->date);
    $last_updated = esc_html(human_time_diff($date_mys, current_time('timestamp')));
    switch ($item->rule_type) {
      case 'select':
        $type = 'Equals';
        break;
      case 'input_text':
        $type = 'Contains';
        break;
      case 'numeric_min':
        $type = 'Numeric Min';
        break;
      case 'numeric_max':
        $type = 'Numeric Max';
        break;
      case 'exclude':
        $type = 'Exclude';
        break;
    }
    $table.= '<tr id="' . esc_attr($item->id_rule) . '" index="' . esc_attr($item->order) . '">
              <input type="hidden" class="order-index" id="' . esc_attr($item->id_rule) . '-order" name="' . esc_attr($item->id_rule) . '-order" value="' . esc_attr($item->order) . '">
                        <th scope="row" class="check-column">
                          <input type="checkbox" name="post[]" value="' . esc_attr($item->id_rule) . '">

                        </th>
                        <td class="column-primary" data-colname="Title">
                          <strong>' . esc_html($item->field) . '</strong>
                          <div class="row-actions"><span class="edit"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&tab=rules&id=' . $item->id_shortcode . '&action=update&idrule=' . $item->id_rule . '') . '">Edit</a> | </span><span class="copy"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&tab=rules&id=' . $item->id_shortcode . '&action=duplicate&idrule=' . $item->id_rule . '') . '">Duplicate</a> | </span><span class="trash"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&tab=rules&id=' . $item->id_shortcode . '&action=update&idrule=' . $item->id_rule . '&a=delete') . '" class="submitdelete" aria-label="Delete “City”">Delete</a></span></div>
                          <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                        </td>
                        <td class="" data-colname="Rule Type">
                          ' . esc_html($type) . '
                        </td>
                        <td class="" data-colname="Rule Value">
                          ' . esc_html($item->rule_value) . '
                        </td>
                        <td class="date column-date" data-colname="Date">
                        <abbr title="' . esc_attr($item->date) . '">
                                 ' . esc_html($last_updated) . ' ago
                        </abbr>
                        </td>
                      </tr>';
  }
  if (empty($data)) {
    $table = '<tr><td class="colspanchange" colspan="5">No rules found.</td></tr>';;
  }
  $wovax_data = array(
    'table' => $table,
    'total_count' => count($data)
  );
  return $wovax_data;
}

/* function to return the feed rule table */
function wovax_idx_feed_rule_table($data) {
  $table = '';
  foreach($data as $key => $item) {
    $date_mys = mysql2date('U', $item->date);
    $last_updated = esc_html(human_time_diff($date_mys, current_time('timestamp')));
    $type = $item->rule_type;
    switch ($item->rule_type) {
      case 'exclude':
        $type = 'Exclude';
        break;
      case 'select':
        $type = 'Include';
        break;
    }
    $table.= '<tr id="' . esc_attr($item->id_rule) . '" index="' . esc_attr($item->order) . '">
              <input type="hidden" class="order-index" id="' . esc_attr($item->id_rule) . '-order" name="' . esc_attr($item->id_rule) . '-order" value="' . esc_attr($item->order) . '">
                        <th scope="row" class="check-column">
                          <input type="checkbox" name="post[]" value="' . esc_attr($item->id_rule) . '">

                        </th>
                        <td class="column-primary" data-colname="Title">
                          <strong>' . esc_html($item->field) . '</strong>
                          <div class="row-actions"><span class="edit"><a href="' . esc_url('admin.php?page=wovax_idx_feeds&tab=rules&action=update&idfeed=' . $item->id_feed . '&idrule=' . $item->id_rule . '') . '">Edit</a> | </span><span class="copy"><a href="' . esc_url('admin.php?page=wovax_idx_feeds&tab=rules&action=duplicate&idfeed=' . $item->id_feed . '&idrule=' . $item->id_rule . '') . '">Duplicate</a> | </span><span class="trash"><a href="' . esc_url('admin.php?page=wovax_idx_feeds&tab=rules&action=update&idfeed=' . $item->id_feed . '&idrule=' . $item->id_rule . '&a=delete') . '" class="submitdelete" aria-label="Delete “City”">Delete</a></span></div>
                          <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                        </td>
                        <td class="" data-colname="Rule Type">
                          ' . esc_html($type) . '
                        </td>
                        <td class="" data-colname="Rule Value">
                          ' . esc_html($item->rule_value) . '
                        </td>
                        <td class="date column-date" data-colname="Date">
                        <abbr title="' . esc_attr($item->date) . '">
                                 ' . esc_html($last_updated) . ' ago
                        </abbr>
                        </td>
                      </tr>';
  }
  if (empty($data)) {
    $table = '<tr><td class="colspanchange" colspan="5">No rules found.</td></tr>';;
  }
  $wovax_data = array(
    'table' => $table,
    'total_count' => count($data)
  );
  return $wovax_data;
}

/* function to delete a shortcode filter by ID (id_filter / id_shortcode) */
function wovax_idx_shortcode_filter_delete($idshortcode, $idfilter) {
  global $wpdb;
  $idshortcode = filter_var($idshortcode, FILTER_SANITIZE_NUMBER_INT);
  $idfilter = filter_var($idfilter, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($idshortcode, FILTER_VALIDATE_INT) && filter_var($idfilter, FILTER_VALIDATE_INT)) {
    $sql = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_shortcode_filters` WHERE `id_filter` = %d AND `id_shortcode` = %d ;";
    $wpdb->query($wpdb->prepare($sql, array($idfilter, $idshortcode)));
  }
}

/* function to delete a shortcode rule by ID (id_rule / id_shortcode) */
function wovax_idx_shortcode_rule_delete($idshortcode, $idrule) {
  global $wpdb;
  $idshortcode = filter_var($idshortcode, FILTER_SANITIZE_NUMBER_INT);
  $idrule = filter_var($idrule, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($idshortcode, FILTER_VALIDATE_INT) && filter_var($idrule, FILTER_VALIDATE_INT)) {
    $sql = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_shortcode_rules` WHERE `id_rule` = %d AND `id_shortcode` = %d ;";
    $wpdb->query($wpdb->prepare($sql, array($idrule, $idshortcode)));
  }
}

/* function to delete a feed rule by ID (id_rule / id_feed) */
function wovax_idx_feed_rule_delete($idfeed, $idrule) {
  global $wpdb;
  $idfeed = filter_var($idfeed, FILTER_SANITIZE_NUMBER_INT);
  $idrule = filter_var($idrule, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($idfeed, FILTER_VALIDATE_INT) && filter_var($idrule, FILTER_VALIDATE_INT)) {
    $sql = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_feed_rules` WHERE `id_rule` = %d AND `id_feed` = %d ;";
    $wpdb->query($wpdb->prepare($sql, array($idrule, $idfeed)));
  }
}

/* function to return the table values with filter, search and order by */
function wovax_idx_user_table_sort($per_page, $paged, $orderby, $ordername, $search) {
  $result = array();
  $result_aux = array();
  $users = get_users( array( 'fields' => array( 'ID' ) ) );
  foreach ($users as $userid) {
    $user_meta_aux = get_user_meta( $userid->ID);
    $id = $userid->ID;
    $nickname = $user_meta_aux['nickname'][0];
    $username = (!empty($user_meta_aux['first_name'][0]) && !empty($user_meta_aux['last_name'][0]))?$user_meta_aux['first_name'][0] . ' ' .$user_meta_aux['last_name'][0]:$user_meta_aux['last_name'][0].$user_meta_aux['last_name'][0];
    $phone    = (!isset($user_meta_aux['phone'][0]))?'':$user_meta_aux['phone'][0];
    $email    = (empty(get_user_option( 'user_email', $userid->ID )))?'':get_user_option( 'user_email', $userid->ID );
    $qty_prop = count(json_decode($user_meta_aux['wovax-idx-favorites'][0]));
    $avatar = get_avatar( $id, 32 );
    array_push($result_aux, array('id' => $id,
                              'nickname' => $nickname,
                              'username' => $username,
                              'phone'    => $phone,
                              'email'    => $email,
                              'qty_prop' => $qty_prop,
                              'picture'  => $avatar));
  }

  if(!empty($search)) {
    foreach ($result_aux as $item) {
      if (strpos(strtolower($item['nickname']) , strtolower($search)) !== FALSE || strpos(strtolower($item['username']) , strtolower($search)) !== FALSE) {
        array_push($result, $item);
      }
    }
  }else {
    $result = $result_aux;
  }

  switch ($ordername) {
      case 'nickname':
        $result = ($orderby=='DESC')?wovax_idx_array_sort($result, 'username', 'DESC'):wovax_idx_array_sort($result, 'username');
        break;
      case 'name':
        $result = ($orderby=='DESC')?wovax_idx_array_sort($result, 'fullname', 'DESC'):wovax_idx_array_sort($result, 'fullname');
        break;
      case 'phone':
        $result = ($orderby=='DESC')?wovax_idx_array_sort($result, 'phone', 'DESC'):wovax_idx_array_sort($result, 'phone');
        break;
      case 'email':
        $result = ($orderby=='DESC')?wovax_idx_array_sort($result, 'email', 'DESC'):wovax_idx_array_sort($result, 'email');
        break;
      case 'favorites':
        $result = ($orderby=='DESC')?wovax_idx_array_sort($result, 'favorites', 'DESC'):wovax_idx_array_sort($result, 'favorites');
        break;
      case 'picture':
        $result = ($orderby=='DESC')?wovax_idx_array_sort($result, 'username', 'DESC'):wovax_idx_array_sort($result, 'username');
        break;
  }

  $paginate_data = wovax_idx_paginate($result, $paged, $per_page);

  $post_data = json_encode(array(
    'status' => 'true',
    'data' => $paginate_data,
    'count_total' => count($result)
  ));

  return $post_data;
}

/* function to return the table values with filter, search and order by */
function wovax_idx_shortcode_table_sort($orderby, $ordername, $search, $filter, $section) {
  global $wpdb;
  $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE ";
  $array_values = array();
  if (empty($filter) || $filter == 'all') {
    $sql.= '';
  }
  else {
    $sql.= "`type` = %s AND ";
    array_push($array_values, $filter);
  }
  $sql.= "`status` = %s ORDER BY ";
  array_push($array_values, $section);
  $sql.= $ordername . " " . $orderby . ";";
  $wovax_data = $wpdb->get_results($wpdb->prepare($sql, $array_values));
  if (!empty($search)) {
    foreach($wovax_data as $key => $item) {
      $item = ( object )$item;
      if (strpos(strtolower($item->type) , strtolower($search)) !== FALSE || strpos(strtolower($item->title) , strtolower($search)) !== FALSE) {
        $search_data[] = $item;
      }
    }
  }
  else {
    $search_data = $wovax_data;
  }
  return $search_data;
}

function wovax_idx_get_shortcode_feeds($shortcode_id) {
    global $wpdb;
    $id   = intval($shortcode_id);
    $sql  = "SELECT `feeds` FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `id` = %d;";
    $sql  = $wpdb->prepare($sql, $id);
    $data = $wpdb->get_var($sql);
    if(is_null($data)) {
        return FALSE;
    }
    $data = json_decode($data, TRUE);
    if(!is_array($data)) {
        return FALSE;
    }
    // again an encoding the makes no sense like why store as json and not
    // take advantage that it can store values in a easier way.
    $feed_ids = array();
    foreach($data as $feed) {
        $val = explode('-', $feed);
        if(count($val) !== 2) {
            continue;
        }
        $feed_ids[] = intval($val[0]);
    }
    $feed_ids = array_values(array_unique($feed_ids));
    return $feed_ids;
}

function wovax_idx_update_field_settings_search_results_page($new_value, $old_value) {
  if ($new_value) {
    $my_post = array(
      'ID' => $new_value,
      'post_content' => '[wovax-idx-search-results]'
    );
    wp_update_post($my_post);
  }
  return $new_value;
}

function wovax_idx_update_field_setting_init() {
  add_filter('pre_update_option_wovax-idx-settings-search-results-page', 'wovax_idx_update_field_settings_search_results_page', 10, 2);
}

add_action('init', 'wovax_idx_update_field_setting_init');
