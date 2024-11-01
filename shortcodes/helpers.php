<?php

if (!defined('ABSPATH')) exit;

use Wovax\IDX\Utilities\ListingLoader;
use Wovax\IDX\Settings\ShortcodeSettings;
use Wovax\IDX\Settings\ListingCardSettings;

// SHORTCODE * by ID
function wovax_idx_get_shortcode_by_id($id) {
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
  if (!empty($id) && filter_var($id, FILTER_VALIDATE_INT)) {
    global $wpdb;
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `id` = %d;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id));
    return $results;
  }
  else {
    return array();
  }
}

// FILTER * by SHORTCODE ID
function wovax_idx_get_filter_by_shortcode_id($id) {
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
  if (!empty($id) && filter_var($id, FILTER_VALIDATE_INT)) {
    global $wpdb;
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode_filters` WHERE `id_shortcode` = %d;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id));
    return $results;
  }
  else {
    return array();
  }
}

// RULES * by SHORTCODE ID
function wovax_idx_get_rule_by_shortcode_id($id) {
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
  if (!empty($id) && filter_var($id, FILTER_VALIDATE_INT)) {
    global $wpdb;
    $sql = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode_rules` WHERE `id_shortcode` = %d;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id));
    return $results;
  }
  else {
    return array();
  }
}

function wovax_idx_get_feed_attr_by_id_feed($id) {
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
  if (!empty($id) && filter_var($id, FILTER_VALIDATE_INT)) {
    global $wpdb;
    $sql = "SELECT `attributes` FROM `{$wpdb->base_prefix}wovax_idx_feeds` WHERE `id_feed` = %d;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id));
    return $results;
  }
  else {
    return array();
  }
}

function wovax_idx_get_permalink_page_search_result() {
  $id_page = get_option('wovax-idx-settings-search-results-page');
  if ($id_page) {
    return get_permalink($id_page);
  }
  return '';
}

function wovax_idx_get_list_buildings_by_filters( $shortcode_details, $filters, $sort_option, $view_option, $api_key, $feed_rules, $extra_fields = array() ) {
  // $filters     = filter_var_array( $filters, FILTER_SANITIZE_STRING );
  $sort_option = filter_var($sort_option, FILTER_SANITIZE_STRING);
  /* $feeds_array = these feeds were delimited on shortcode admin feed tab */
  $feeds_array = json_encode(wovax_idx_return_id_feeds_array($shortcode_details[0]->feeds));
  /* $pagination = this was delimited on shortcode admin general tab */
  $pagination = $shortcode_details[0]->pagination;
  $per_page = $shortcode_details[0]->per_page;
  if (!empty($pagination) && !empty($per_page) && $pagination !== 'no') {
    // Check WP Query vars first, then the GET variable as a fallback
    if(!empty(get_query_var('paged'))) {
      $paged = get_query_var('paged');
    } else if ( array_key_exists('paged', $_GET) && !empty($_GET['paged']) ) {
      $paged = $_GET['paged'];
    } else {
      $paged = 1;
    }
  }
  $where_params = [];

  // Loop to get and compare every id_feed from array
  if ($filters != '') {
    // Loop to get every field name and push to the array
    foreach($filters as $key => $item) {
      if(empty($item->field)) {
        //skip bad fields
        continue;
      }
      $arr  = array();
      $text = str_replace(' ', '_', $item->field);
      $name = strtolower($item->filter_type . '-' . $text . '-' . $item->id_filter);
      if($item->filter_type === 'preset_value') {
        $filter_value = json_decode($item->filter_data);
        //grab the preset value for the search
        $val = $filter_value->value;
        //run the preset value as a text search
        $filter_type = 'input_text';
      } else {
        // values we´ll add to the array 'fields_value'
        $val = htmlspecialchars(stripslashes($_GET[$name]));
        $val = str_replace(',', '', $val);
        $filter_type = $item->filter_type;
      }
      if($item->filter_type === 'preset_range') {
        $filter_value = json_decode($item->filter_data);
        $arr_min['filter_type'] = 'numeric_min';
        $arr_min['value'] = $filter_value->range_start;
        $arr_min['alias_name'] = $item->field;
        array_push($where_params, $arr_min);

        $arr_max['filter_type'] = 'numeric_max';
        $arr_max['value'] = $filter_value->range_end;
        $arr_max['alias_name'] = $item->field;
        array_push($where_params, $arr_max);
      }
      //range types need to be manually split into min/max searches. Values are formatted x-y, where x is 
      // the minimum and y is the maximum. The last value is just an x value, and needs to be treated differently.
      if($item->filter_type === 'range' && $val != null) {
        $value = explode('-', $val);
        //Range end has only 1 value
        if(count($value) > 1) {
          $arr_min['filter_type'] = 'numeric_min';
          $arr_min['value'] = $value[0];
          $arr_min['alias_name'] = $item->field;
          array_push($where_params, $arr_min);

          $arr_max['filter_type'] = 'numeric_max';
          $arr_max['value'] = $value[1];
          $arr_max['alias_name'] = $item->field;
          array_push($where_params, $arr_max);
        } else {
          $arr['filter_type'] = 'numeric_min';
          $arr['value'] = $value[0];
          $arr['alias_name'] = $item->field;
          array_push($where_params, $arr);
        }
      } else if($val != null) {
        $arr['filter_type'] = $filter_type;
        $arr['value'] = $val;
        $arr['alias_name'] = $item->field;
        // push arr values to 'field_value' array
        array_push($where_params, $arr);
      }
    }
  }

  // Setting 'date-desc' as the default sorted option
  $extra_params = [];
  $arr = [];
  $arr['sorted'] = filter_var($_GET['wovax-idx-select-sort'], FILTER_SANITIZE_STRING);
  if ($arr['sorted'] == null) {
    $arr['sorted'] = 'date-desc';
  }
  // if 'pagination' is available we´ll send more parameters in 'page_option'
  if ($shortcode_details[0]->pagination == "yes") {
    $arr['limit'] = $per_page;
    $arr['offset'] = ($paged - 1) * $per_page;
  }
  if (array_key_exists('wovax-idx-view-option', $_GET) && $_GET['wovax-idx-view-option'] == 'map'){
    $arr['limit'] = $shortcode_details[0]->per_map;
    $arr['offset'] = 0;
  }

  // push arr values to 'page_option' array
  array_push($extra_params, $arr);

  // set values in GET to evaluate next page (listing.php 11)
  $_GET['data_value'] = $feed_rules;
  $_GET['extra_params'] = $extra_params;
  $_GET['where_params'] = $where_params;
  // gets all data from API (helpers.php 233)
  $data = wovax_idx_get_data_list_building( $extra_params, $where_params, $sort_option, $view_option, $api_key, $feed_rules, $extra_fields );
  // insert data into the variable will be returned to listing.php
  if (is_object($data)) {
    $listing_data['data'] = $data->data;
    $listing_data['total'] = $data->count;
  }
  else {
    $listing_data = $data;
  }
  return $listing_data;
}

function wovax_idx_get_list_buildings_by_rules( $shortcode_details, $rules, $sort_option, $view_option, $api_key, $feed_rules, $extra_fields = array() ) {
  // $rules     = filter_var_array( $rules, FILTER_SANITIZE_STRING );
  $sort_option = filter_var($sort_option, FILTER_SANITIZE_STRING);
  /* $feeds_array = these feeds were delimited on shortcode admin feed tab */
  $feeds_array = json_encode(wovax_idx_return_id_feeds_array($shortcode_details[0]->feeds));
  /* $pagination = this was delimited on shortcode admin general tab */
  $pagination = $shortcode_details[0]->pagination;
  $per_page = $shortcode_details[0]->per_page;
  if (!empty($pagination) && !empty($per_page) && $pagination !== 'no') {

    // Pagination ON
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
  }
  $where_params = [];

  // Loop to get and compare every id_feed from array
  if ($rules != '') {
    // Loop to get every field name and push to the array
    foreach($rules as $key => $item) {
      $arr  = array();
      if ($item->rule_value != null) {
        $arr['filter_type'] = $item->rule_type;
        $arr['value'] = $item->rule_value;
        $arr['alias_name'] = $item->field;
      }
        if(!isset($arr['alias_name'])) {
          continue; // Skip bad fields
        }
        // push arr values to 'field_value' array
        array_push($where_params, $arr);
    }
  }

  // Setting 'date-desc' as the default sorted option
  $extra_params = [];
  $arr = [];
  if(array_key_exists('wovax-idx-select-sort', $_GET)) {
    $arr['sorted'] = filter_var($_GET['wovax-idx-select-sort'], FILTER_SANITIZE_STRING);
  } else {
    $arr['sorted'] = 'date-desc';
  }
  // if 'pagination' is available we´ll send more parameters in 'page_option'
  // Grid and map limits need to be different in case there are multiple shortcodes on a page
  if ($shortcode_details[0]->pagination == "yes" && $shortcode_details[0]->grid_view === 'yes') {
    $arr['limit'] = $per_page;
    $arr['offset'] = ($paged - 1) * $per_page;
  } else if ($shortcode_details[0]->map_view === 'yes') {
	  $arr['limit'] = $shortcode_details[0]->per_map;
      $arr['offset'] = 0;
  } else {
    $arr['limit'] = $per_page;
    $arr['offset'] = 0;
  }
  // If the view option exists but there is a non-map shortcode on the page, we don't want 250 listings per page for that one.
  if(array_key_exists('wovax-idx-view-option', $_GET) && $_GET['wovax-idx-view-option'] == 'map' && $shortcode_details[0]->map_view === 'yes') {
    $arr['limit'] = $shortcode_details[0]->per_map;
    $arr['offset'] = 0;
  }

  // push arr values to 'page_option' array
  array_push($extra_params, $arr);

  // set values in GET to evaluate next page (listing.php 11)
  $_GET['data_value'] = $feed_rules;
  $_GET['extra_params'] = $extra_params;
  $_GET['where_params'] = $where_params;
  // gets all data from API (helpers.php 233)
  $data = wovax_idx_get_data_list_building( $extra_params, $where_params, $sort_option, $view_option, $api_key, $feed_rules, $extra_fields );
  // insert data into the variable will be returned to listing.php
  if (is_object($data)) {
    $listing_data['data'] = $data->data;
    $listing_data['total'] = $data->count;
  }
  else {
    $listing_data = $data;
  }
  return $listing_data;
}

function wovax_idx_get_list_buildings_by_favorites($shortcode_details, $favorites = [], $sort_option, $view_option, $api_key) {

  $sort_option = filter_var($sort_option, FILTER_SANITIZE_STRING);

  $pagination = $shortcode_details[0]->pagination;
  $per_page = $shortcode_details[0]->per_page;
  if (!empty($pagination) && !empty($per_page) && $pagination !== 'no' && $pagination !== 'no') {

    // Pagination ON
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
  }

  // Setting 'date-desc' as the default sorted option
  $extra_params = [];
  $arr = [];
  $arr['sorted'] = filter_var($_GET['wovax-idx-select-sort'], FILTER_SANITIZE_STRING);
  if ($arr['sorted'] == null) {
    $arr['sorted'] = 'date-desc';
  }
  // if 'pagination' is available we´ll send more parameters in 'page_option'
  if ($shortcode_details[0]->pagination == "yes") {
    $arr['limit'] = $per_page;
    $arr['offset'] = ($paged - 1) * $per_page;
  }
  if (array_key_exists('wovax-idx-view-option', $_GET) && $_GET['wovax-idx-view-option'] == 'map'){
    $arr['limit'] = $shortcode_details[0]->per_map;
    $arr['offset'] = 0;
  }
  array_push($extra_params, $arr);

  //Create data_to_search
  //Insert all feeds in feeds_array without duplicates
  $feeds_array = [];
  foreach($favorites as $key => $value) {
    if (!in_array($value['0'], $feeds_array)) {
      array_push($feeds_array, $value['0']);
    }
  }

  //Insert every mls_number in each feeds_array index
  $fav_array = [];
  foreach ($feeds_array as $arr_feed_key => $arr_feed_id) {
    foreach ($favorites as $arr_fav_key => $arr_fav_values) {
      if($arr_feed_id == $arr_fav_values[0]){
        $fav_array[$arr_feed_id][] = $arr_fav_values[1];
      }
    }
  }

  $arr = [];
  $data_to_search = [];
  foreach ($fav_array as $key => $value) {
    $arr['class_id'] = $key;
    $arr['mls_id'] = $value;
    array_push($data_to_search, $arr);
  }

  // set values in GET to evaluate next page (listing.php 11)
  $_GET['data_to_search'] = $data_to_search;
  $_GET['extra_params'] = $extra_params;

  // gets all data from API (helpers.php 233)
  $data = wovax_idx_get_data_list_building_favorites($data_to_search, $extra_params, $sort_option, $view_option, $api_key);
  // insert data into the variable will be returned to listing.php
  if (is_object($data)) {
    $listing_data['data'] = $data->data;
    $listing_data['total'] = $data->count;
  }
  else {
    $listing_data = $data;
  }
  return $listing_data;
}

function wovax_idx_return_id_feeds_array($feeds) {
  $feeds = (empty($feeds)) ? array() : (json_decode($feeds));
  $feeds_array = [];
  foreach($feeds as $key => $value) {
    $indices_feeds = substr($value, 0, 2);
    $feeds_array[] = $indices_feeds;
  }
  return $feeds_array;
}

function wovax_idx_get_url() {
  global $post;
  $query_string = $_SERVER['QUERY_STRING'];
  return get_permalink($post->ID) . '?' . $query_string;
}

function wovax_idx_get_rules_by_shortcode($fields_array, $id_shortcode, $shortcode_type) {
  global $wpdb;
  $id_shortcode = filter_var($id_shortcode, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($id_shortcode, FILTER_VALIDATE_INT)) {
    $sql = "SELECT `feeds` FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `id` = %d;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id_shortcode));
    $feeds = json_decode($results[0]->feeds);
    $feeds_string = "";
    $feed_value = array();
    if ($feeds != null) {
      foreach($feeds as $key => $value) {
        $feeds_ids = explode("-", $value);
        if (!in_array($feeds_ids[0], $feed_value)) {
          array_push($feed_value, $feeds_ids[0]);
          $feeds_string.= $feeds_ids[0] . ",";
        }
      }
      $feeds_string = rtrim($feeds_string, ',');
    }
    if ($feeds_string) {
      $feeds_string = explode(',', $feeds_string);
      $sql = "SELECT `id_feed` AS `class_id`, `id_field` AS `alias_name`, `field`, `rule_type` AS `type`, `rule_value` AS `rule` FROM `{$wpdb->base_prefix}wovax_idx_feed_rules` WHERE `id_feed` IN (";
      foreach ($feeds_string as $key => $value) {
        $sql .= "%d,";
      }
      $sql = substr($sql, 0, -1);
      $sql .= ") ORDER BY `alias_name`";
      $results = $wpdb->get_results($wpdb->prepare($sql, $feeds_string));
      $body = array();
      $rules_array = array();

      if ($shortcode_type == 'search_form'){

        foreach ($results as $key => $value) {
          if (!isset($rules_array[$value->alias_name])){
            $rules_array[$value->alias_name] = array();
          }
          array_push($rules_array[$value->alias_name], array( "type"  => $value->type,
                                                              "value" => $value->rule,
                                                              "alias" => $value->field,
                                                              "class_id" => $value->class_id
                                                            ));
        }

        $body = $rules_array;

      }else{

        foreach ($feeds_string as $index => $feed) {
          $rule_class_body = array();
          $rule_class_body["class_id"] = $feed;
          $rule_class_body["rules"] = array();
          foreach ($results as $key => $value) {
              if ($value->class_id == $feed){
                array_push($rule_class_body["rules"], array(
                  "alias_name" => $value->field,
                  "type" => $value->type,
                  "rule" => $value->rule
                ));
              }
          }
          array_push($body, $rule_class_body);
        }

      }
      return $body;
    }else{
      return false;
    }
  }
  else {
    return false;
  }
}

function wovax_idx_get_feeds_by_shortcode($id_shortcode) {
  global $wpdb;
  $id_shortcode = filter_var($id_shortcode, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($id_shortcode, FILTER_VALIDATE_INT)) {
    $sql = "SELECT `feeds` FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `id` = %d;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id_shortcode));
    $feeds = json_decode($results[0]->feeds);
    $feeds_string = "";
    $feed_value = array();
    if ($feeds != null) {
      foreach($feeds as $key => $value) {
        $feeds_ids = explode("-", $value);
        if (!in_array($feeds_ids[1], $feed_value)) {
          array_push($feed_value, $feeds_ids[1]);
          $feeds_string.= $feeds_ids[1] . ",";
        }
      }
      $feeds_string = rtrim($feeds_string, ',');
    }
    return $feeds_string;
  }
  else {
    return false;
  }
}

function wovax_idx_get_feeds_ids_by_shortcode($id_shortcode) {
  global $wpdb;
  $id_shortcode = filter_var($id_shortcode, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($id_shortcode, FILTER_VALIDATE_INT)) {
    $sql = "SELECT `feeds` FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `id` = %d;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $id_shortcode));
    $feeds = json_decode($results[0]->feeds);
    $feeds_string = "";
    $feed_value = array();
    if ($feeds != null) {
      foreach($feeds as $key => $value) {
        $feeds_ids = explode("-", $value);
        $feeds_string.= $feeds_ids[0] . ',';
      }
      $feeds_string = rtrim($feeds_string, ',');
    }
    return explode(",", $feeds_string);
  }
  else {
    return false;
  }
}

function wovax_idx_get_class_ids_by_shortcode($shortcode_id) {
  global $wpdb;
  $shortcode_id = filter_var($shortcode_id, FILTER_SANITIZE_NUMBER_INT);
  if (filter_var($shortcode_id, FILTER_VALIDATE_INT)) {
    $sql = "SELECT `feeds` FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `id` = %d;";
    $results = $wpdb->get_results($wpdb->prepare($sql, $shortcode_id));
    $included_classes = json_decode($results[0]->feeds);
    $classes = array();
    if ($included_classes != null) {
      foreach($included_classes as $key => $value) {
        $class_id = explode("-", $value);
        if (!in_array($class_id[0], $classes)) {
          array_push($classes, $class_id[0]);
        }
      }
    }
    return $classes;
  }
  else {
    return false;
  }
}

function wovax_idx_get_data_list_building_favorites($data_to_search, $extra_params, $sort_option, $view_option, $api_key) {
  $url       = "https://connect.wovax.com/api/my_mls_favorites";
  $extra_params[0]['grid_view'] = ($view_option == 'grid')?true:false;

  if (!empty($sort_option)) {
    $extra_params[0]['sorted'] = $sort_option;
  }
  $extra_params = str_replace(array(
    '[',
    ']'
  ) , '', htmlspecialchars(json_encode($extra_params) , ENT_NOQUOTES));
  $body = array(
    "data_to_search" => json_encode($data_to_search),
    "extra_params" => $extra_params
  );

  $rest = wovax_idx_remote_API_list_building($url, "", "POST", $body, $api_key);
  return $rest;
}

// ==============================================

// data for list building - listing view
function wovax_idx_get_data_list_building( $extra_params, $where_params, $sort_option, $view_option, $api_key, $feed_rules, $extra_fields = array() ) {
$url = ($view_option == 'grid')?"https://connect.wovax.com/api/search_resources":"https://connect.wovax.com/api/resources_geolocation";
  if (!empty($sort_option)) {
    $extra_params[0]['sorted'] = $sort_option;
  }
  $extra_params = str_replace(array(
    '[',
    ']'
  ) , '', htmlspecialchars(json_encode($extra_params) , ENT_NOQUOTES));
  $body = array(
    "data_values" => json_encode($feed_rules),
    "extra_params" => $extra_params,
    "where_params" => json_encode($where_params),
    "extra_fields" => json_encode($extra_fields),
  );
  $rest = wovax_idx_remote_API_list_building($url, "", "POST", $body, $api_key);
  return $rest;
}

function wovax_idx_remote_API_list_building($url, $action, $type = "GET", $body = null, $api_key) {
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
    if ($data->data == 'No data found') {
      return 'NO DATA FOUND - \'Empty data response\'';
    } elseif ($data->data == 'Unauthorised') {
      return 'UNAUTHORISED - \'API key denied\'';
    } elseif ($data->data == 'There is a problem with your setup') {
      return 'THERE IS A PROBLEM WITH YOUR SETUP - \'API in maintance\'';
    } elseif ($data->data == 'Data values is required') {
      return 'BAD REQUEST - \'There is a problem with your body request\'';
    } elseif ($data->data == null) {
      return 'PROBLEM IN RESPONSE - \'API isn´t sending data\'';
    } else {
      return $data;
    }
  }
}

function wovax_idx_get_data_filter($filters, $feeds, $api_key, $rules, $class_ids) {
  $return_array = array();
  $filters_array = array();
  $rules_array = array();
  $classes = json_encode($class_ids);
  $permission = (get_option("wovax-idx-settings-environment")=="production")?"production":"development";

  foreach($filters as $value) {
    if($value->filter_type == 'select'){
      $filter['filter_type'] = $value->filter_type;
      $filter['alias_name'] = $value->field;
      array_push($filters_array, $filter);
    }
  }

  foreach($rules as $field_id => $data) {
	  $select_name_array = array();
    $exclude_name_array = array();
    if(!isset($field_alias_name_check)) {
      $field_alias_name_check = array();
    }
    if(!in_array($data[0]['alias'], $field_alias_name_check)) {
      $field_alias_name_check[] = $data[0]['alias'];
      $field_alias_name = $data[0]['alias'];
    }

    foreach($data as $rule) {
      if($rule['type'] === 'select') {
        $select_array[] = $rule['value'];
      } else if ($rule['type'] === 'exclude') {
        $exclude_array[] = $rule['value'];
      }
    }

    if(!empty($select_array)) {
      if(!isset($rules_array['select']['alias_field'])) {
        $rules_array['select']['alias_field'] = array();
      }
      if(!isset($rules_array['select']['class_id'])) {
        $rules_array['select']['class_id'] = array();
      }
      $select_name_array['name'] = $field_alias_name;
      $select_name_array['values'] = $select_array;
      array_push($rules_array['select']['alias_field'],$select_name_array);
      array_push($rules_array['select']['class_id'],$data[0]['class_id']);
	    unset($select_array);
    }
    if(!empty($exclude_array)) {
      if(!isset($rules_array['exclude']['alias_field'])) {
        $rules_array['exclude']['alias_field'] = array();
      }
      if(!isset($rules_array['exclude']['class_id'])) {
        $rules_array['exclude']['class_id'] = array();
      }
      $exclude_name_array['name'] = $field_alias_name;
      $exclude_name_array['values'] = $exclude_array;
      array_push($rules_array['exclude']['alias_field'],$exclude_name_array);
      array_push($rules_array['exclude']['class_id'],$data[0]['class_id']);
	    unset($exclude_array);
    }
  }
  $return_array['filters'] = $filters_array;
  $return_array['rules'] = $rules_array;
  $json_alias = json_encode($return_array);
  $url = "https://connect.wovax.com/api/alias_data";
  $body = array(
    "alias_data" => $json_alias,
    "feed_id" => "[" . $feeds . "]",
    "class_ids" => $classes,
    "type_permission" => $permission
  );
  $rest = wovax_idx_remote_API_fields($url, "", "POST", $body, $api_key);
  return $rest;
}

// data for filters - search form view
function wovax_idx_remote_API_fields($url, $action, $type = "GET", $body = null, $api_key) {
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
    if ($data->data == 'No data found') {
      $wovax_data = 'PROBLEM IN RESPONSE - \'API isn´t sending data OR no filter has been selected\'';
    } elseif ($data->data == 'Unauthorised') {
      $wovax_data = 'UNAUTHORISED - \'API key denied\'';
    } elseif ($data->data == 'There is a problem with your setup') {
      $wovax_data = 'THERE IS A PROBLEM WITH YOUR SETUP - \'API in maintance\'';
    } elseif ($data->data == 'Data values is required') {
      $wovax_data = 'BAD REQUEST - \'There is a problem with your body request\'';
    } elseif ($data->data == null) {
      $wovax_data = 'PROBLEM IN RESPONSE - \'API isn´t sending data OR no filter has been selected\'';
    } else {
      if (isset($data->data)) {
        foreach($data as $key => $value) {
          array_push($wovax_data, $value);
        }
      }
    }
    return $wovax_data;
  }
}

function wovax_idx_get_validation_token() {
  $url = "https://connect.wovax.com/api/validate_domain";
  $body = array(
    "guest_email" => get_option('wovax-idx-settings-webmaster-email') ,
    "environment" => (get_option('wovax-idx-settings-environment') == 'production')?"production":"development"
  );

  $rest = wovax_idx_remote_API_validation_token($url, $body);
  return $rest;
}

function wovax_idx_remote_API_validation_token($url, $body = null) {
  $response = wp_remote_post($url, array(
    'method' => "POST",
    'timeout' => 45,
    'redirection' => 5,
    'httpversion' => '1.0',
    'blocking' => true,
    'headers' => array() ,
    'body' => $body,
    'cookies' => array()
  ));
  if (is_wp_error($response)) {
    $error_message = $response->get_error_message();
    return $error_message;
  } else {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    return $data;
  }
}

function wovax_idx_get_action_bar($current_view, $shortcode_type, $shortcode_views, $results_total = '', $sort_option = '') {
  $saved_searches = get_option('wovax-idx-settings-users-saved-searches');
  if($saved_searches === 'yes' && is_user_logged_in() && $shortcode_type == 'filters') {
    $user = wp_get_current_user();
    $user_searches = get_user_meta($user->ID, 'wovax-idx-searches');
    $saved_searches = json_decode($user_searches[0]);
    ob_start();
    ?>
    <div class="wovax-idx-search-select-section wovax-idx-section">
      <form class="wovax-idx-form" action="" method="get" id="wovax-idx-saved-searches">
        <label for="saved-searches">Saved Searches</label>
        <select id="saved-searches" name="wovax-idx-saved-searches" class="wovax-idx-saved-search-select">
          <option value="" selected disabled>Select a Saved Search</option>
          <?php
          if(!empty($saved_searches)) {
            foreach($saved_searches as $saved_search) {
              ?>
              <option value="<?php echo $saved_search[1];?>"><?php echo $saved_search[0]; ?></option> 
              <?php
            }
          }
        ?>
        </select>
      </form>
    </div>
    <?php
    $saved_searches_html = ob_get_clean();
    ob_start();
    ?>
    <div class="wovax-idx-save-search-section wovax-idx-section">
      <a href='#' class="wovax-idx-button wovax-idx-save-search-button" >Save Search</a>
    </div>
    <?php
    $save_search_button_html = ob_get_clean();
  } else {
    $saved_searches_html = '';
    $save_search_button_html = '';
  }
  if($shortcode_views['map_view'] === 'yes' && $shortcode_views['grid_view'] === 'yes') {
    if($current_view === 'grid') {
      $view_button_text = 'Map View';
    } else if ($current_view === 'map') {
      $view_button_text = 'Grid View';
    } else {
      $view_button_text = 'Alt View';
    }
    if($shortcode_type === 'rules' || $shortcode_type === 'favorites') {
      $view_button_function = 'wovax_idx_get_grid_view_listing(event);';
    } else {
      $view_button_function = 'wovax_idx_get_grid_view_search(event);';
    }
    ob_start();
    ?>
    <div class="wovax-idx-view-section wovax-idx-section">
      <a href="#" class="wovax-idx-button" onclick="<?php echo $view_button_function; ?>"><?php echo $view_button_text; ?></a>
    </div>
    <?php
    $view_button_html = ob_get_clean();
  }
  if( array_key_exists('wovax-idx-view-option', $_GET) && !empty(filter_var($_GET['wovax-idx-view-option'], FILTER_SANITIZE_STRING)) ) {
    $view_value = esc_attr(filter_var($_GET['wovax-idx-view-option'], FILTER_SANITIZE_STRING));
  } else {
    $view_value = $current_view;
  }
  if (is_user_logged_in() && ($shortcode_type == 'rules' || $shortcode_type == 'filters')) {
    if($shortcode_views['map_view'] === 'yes' && $shortcode_views['grid_view'] === 'yes') {
      $favorites_section_class = 'wovax-idx-fav-map';
    } else {
      $favorites_section_class = 'wovax-idx-fav-default';
    }
    
    if(array_key_exists('wovax-idx-user-favorites', $_GET) && $_GET['wovax-idx-user-favorites'] == 'true') {
      $favorites_input = '';
      $favorites_button_text = 'Results';
      $results_total_label = 'Favorites';
      $map_input = '';
    } else {
      $favorites_input = '<input type="hidden" id="wovax-idx-user-favorites" name="wovax-idx-user-favorites" value="true">';
      $favorites_button_text = 'Favorites';
      $results_total_label = 'Results';
      $map_input = '<form id="wovax-idx-view" hidden><input type="hidden" id="wovax-idx-button-view-option" name="wovax-idx-view-option" value="' . $view_value . '" ></form>';
    }
    if($shortcode_type == 'filters') {
      $favorites_button_function = 'wovax_idx_submit_favorites_button_search(event);';
    } else {
      $favorites_button_function = 'wovax_idx_submit_favorites_button(event);';
    }
    ob_start()
    ?>
    <div class="wovax-idx-favorites-section wovax-idx-section <?php echo $favorites_section_class; ?>">
      <a href="#" class="wovax-idx-button" onclick="<?php echo $favorites_button_function; ?>"><?php echo $favorites_button_text; ?></a>
      <form class='wovax-idx-favorites-form' id="wovax-idx-favorites-form">
        <?php echo $favorites_input; ?>
      </form>
    </div>
    <?php
    $favorites_button_html = ob_get_clean();
  } else {
    $favorites_button_html = '';
    switch($shortcode_type) {
      case 'favorites':
        $results_total_label = 'Favorites';
        $map_input .= '<form id="wovax-idx-view" hidden>';
        $map_input .= '<input type="hidden" id="wovax-idx-button-view-option" name="wovax-idx-view-option" value="' . $view_value . '" >';
        $map_input .= '</form>';
        break;
      case 'map':
        $map_input = '';
        break;
      case 'filters':
      case 'rules':
        $results_total_label = 'Results';
        $map_input .= '<form id="wovax-idx-view" hidden>';
        $map_input .= '<input type="hidden" id="wovax-idx-button-view-option" name="wovax-idx-view-option" value="' . $view_value . '" >';
        $map_input .= '</form>';
        break;
      default:
        $results_total_label = 'Results';
        $map_input = '';
        break;
    }
  }

  // Search shortcode needs to sort differently than other types
  if($shortcode_type === 'filters') {
    $sort_js = "document.getElementById('wovax-idx-select-sort-hidden').value=this.value;document.getElementById('form-tab-1').submit();";
  } else {
    $sort_js = "this.form.submit()";
  }

  $select_sort_array = array(
    'date-desc' => 'Most Recent First',
    'date-asc' => 'Most Recent Last',
    'price-desc' => 'Price High to Low',
    'price-asc' => 'Price Low to High'
  );
  $option_html = '';
  foreach($select_sort_array as $key => $value) {
    if($sort_option == $key) {
      $selected = 'selected';
    } else {
      $selected = '';
    }
    $option_html .= '<option value="' . esc_attr($key) . '" ' . esc_attr($selected) . '>' . esc_html($value) . '</option>';
  }

  ob_start();
  ?>
  <div class="wovax-idx-action-bar-container">
    <div class="wovax-idx-result-section">
      <label class="wovax-idx-listing-count"><?php echo esc_html(number_format($results_total, 0, '', ',')) . ' ' . $results_total_label;?></label>
    </div>
    <?php echo $save_search_button_html; ?>
    <?php echo $saved_searches_html; ?>
    <?php echo $favorites_button_html; ?>
    <?php echo $map_input; ?>
    <?php echo $view_button_html; ?>
    <div class="wovax-idx-sort-section">
      <form class="wovax-idx-form" action="" method="get" id="wovax-idx-sort">
        <label for="sort">Sorted</label>
          <select id="sort" name="wovax-idx-select-sort" onchange="<?php echo $sort_js; ?>">
            <option value="" disabled>Select Sort Option</option>';
            <?php echo $option_html; ?>
          </select>
      </form>
      </div>
  </div>
  <?php
  $output = ob_get_clean();
  return $output;
}

function wovax_idx_get_listing_grid_html($data, $fav_avail, $user_fav_properties = array()) {
    $opts = new ListingCardSettings();
    $status_class = $opts->StatusClass();
    $output = '';
    $output .= '<input type="hidden" name="session_stat" value="' . esc_attr(is_user_logged_in()) . '">
                <div class="wovax-idx-listings-container three-column">';
    foreach($data as $key => $item) {
      $item_arr = (array)$item;
      $mls_logo = "https://cache.wovax.com/images/mls-board-logo.jpg";
      if(array_key_exists('MLS Logo URL', $item_arr) && filter_var($item_arr['MLS Logo URL'], FILTER_VALIDATE_URL)) {
        $mls_logo = $item_arr['MLS Logo URL'];
      }
      // ===================================================================
      // getting format and currency for each property depending on its class_id
      $feed = wovax_idx_get_feed_attr_by_id_feed($item->class_id);
      if( empty($feed) ) {
        $format = 'miles';
        $currency = 'left';
      } else {
        $current_feed = json_decode($feed[0]->attributes);
        $format = $current_feed->format;
        $currency = $current_feed->currency;
      }
      // ===================================================================
      // changing price format and currency
      switch($format){
        case 'entire':
          $price = number_format($item->Price, 0, '', '');
          break;
        case 'miles':
          $price = number_format($item->Price, 0, '', ',');
          break;
        case 'decimals':
          $price = number_format($item->Price, 2, '.', '');
          break;
        case 'decimals_miles':
          $price = number_format($item->Price, 2, '.', ',');
          break;
        default:
          $price = number_format($item->Price, 0, '', ',');
          break;
      }

      if($currency === 'right') {
        $price = $price . "$";
      } else {
        $price = "$" . $price;
      }

      // ===================================================================
      $custom_default_image = get_option('wovax-idx-settings-styling-default-image', '');
      if(empty($custom_default_image)) {
        $default_image = 'https://cache.wovax.com/images/placeholder.jpg';
      } else {
        $default_image = $custom_default_image;
      }
      $street = "Street Address";
      $main_photo = (empty($item->location)) ? $default_image : $item->location;
      $desc_photo = (empty($item->photo_description)) ? 'alt="Main photo for '. esc_attr($item->$street) .'"' : 'alt="' . esc_attr($item->photo_description) . '"';
      $zip_code = "Zip Code";
      $sq_ft = "Square Footage";
      $ac_lt = "Acres";
      $mls_number = "MLS Number";
      $full_baths = "Full Bathrooms";
      $half_baths = "Half Bathrooms";

      if(!empty($item->Bedrooms) || !empty($item->Bathrooms)) {
        if(!empty($item->Bedrooms)) {
          $bedrooms = $item->Bedrooms . ' Bed';
          if($item->Bedrooms > 1) {
            $bedrooms .= 's';
          }
        } else {
          $bedrooms = '';
        }
        if(!empty($item->Bathrooms)) {
          $bathrooms = floatval(number_format($item->Bathrooms, 2)) . ' Bath';
          if(floatval(floatval(number_format($item->Bathrooms, 2))) > 1) {
            $bathrooms .= 's';
          }
        } else if (!empty($item->$full_baths) || !empty($item->$half_baths)) {
          if(!empty($item->$half_baths)) {
            $bathrooms = $item->$full_baths + ($item->$half_baths/2);
          } else {
            $bathrooms = $item->$full_baths;
          }
          $bathrooms = floatval(number_format($bathrooms, 2));
          if($bathrooms > 1) {
            $bathrooms .= ' Baths';
          } else {
            $bathrooms .= ' Bath';
          }
        } else {
          $bathrooms = '';
        }
        if(!empty($bedrooms) && !empty($bathrooms)) {
          $rooms = $bedrooms . ' · ' . $bathrooms . '<br />';
        } else {
          $rooms = $bedrooms . $bathrooms . '<br />';
        }
      } else {
        $rooms = '';
      }
      if(!empty($item->$sq_ft) || !empty($item->$ac_lt)) {
        if(!empty($item->$sq_ft)) {
          $square_footage = number_format($item->$sq_ft) . ' sqft';
        } else {
          $square_footage = '';
        }
        if(!empty($item->$ac_lt)) {
          $acreage = floatval(number_format($item->$ac_lt, 2)) . ' acres';
        } else {
          $acreage = '';
        }
        if(!empty($square_footage) && !empty($acreage)) {
          $lot_details = $square_footage . ' · ' . $acreage;
        } else {
          $lot_details = $square_footage . $acreage;
        }
      } else {
        $lot_details = '';
      }
      if( $fav_avail == 'yes' && is_user_logged_in() ){
        if( in_array(array($item->class_id, $item->$mls), $user_fav_properties) ) {
          $checked = ' checked';
          $label_value = '<svg width="32px" height="29px" viewBox="0 0 32 29" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
          <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
              <g id="Search-Results---Grid-View" transform="translate(-470.000000, -637.000000)" fill="#FF5C5C" fill-rule="nonzero">
                  <g id="Listings" transform="translate(199.000000, 419.000000)">
                      <g id="Listing" transform="translate(0.665296, 0.000000)">
                          <g id="Favorite" transform="translate(270.334704, 218.000000)">
                              <path d="M31.1790033,5.0756545 C30.1901794,2.84299593 28.2900863,1.12841284 26.0758963,0.400640156 C23.8966058,-0.318909105 21.4730178,-0.0516479513 19.4721034,1.10785429 C18.1148941,1.89730262 17.0717818,3.14726248 15.9937698,4.29854129 C15.7378389,4.02716843 15.481908,3.75579557 15.2259771,3.4844227 C14.7606482,2.99101749 14.3069525,2.46883032 13.7950907,2.02887734 C11.7011106,0.227948333 8.88974839,-0.434036987 6.27227325,0.285512274 C3.80990774,0.963944434 1.69653893,2.85944277 0.692204024,5.34291565 C-0.238453804,7.64547329 -0.226820581,10.2399623 0.703837247,12.5384083 C1.19631035,13.7595861 1.96410305,14.7710668 2.84047251,15.7003133 L4.88791973,17.8712962 C5.9271543,18.9732345 6.96638888,20.0710611 8.00174571,21.1729994 C9.09139091,22.3283899 10.1810361,23.4837804 11.2706813,24.639171 C12.1043956,25.5231886 12.9419877,26.411318 13.7795797,27.2994474 C14.2720528,27.8216346 14.7994256,28.5411838 15.5012967,28.7344342 C16.1605127,28.9153494 16.8468728,28.7138756 17.3393459,28.2286939 C17.3471014,28.2204704 17.3548569,28.212247 17.3626124,28.2040236 C17.3703678,28.1958002 17.3781233,28.1875768 17.3820011,28.183465 C17.7309977,27.8134111 18.0799944,27.4433572 18.4251134,27.077415 C19.2937273,26.156392 20.1623413,25.2353689 21.0309553,24.3143459 C22.1206005,23.1589553 23.206368,22.0076765 24.2960132,20.852286 C25.3081036,19.7791297 26.3201939,18.7059734 27.3361621,17.6287053 L29.2711548,15.576962 C29.3797315,15.4618341 29.492186,15.3467062 29.6007628,15.2274666 C30.4267216,14.3352256 31.0549156,13.2702927 31.4698339,12.102567 C32.2802818,9.83290304 32.1445608,7.25897254 31.1790033,5.0756545 Z" id="filled"></path>
                          </g>
                      </g>
                  </g>
              </g>
            </g>
          </svg>';
        } else {
          $checked = '';
          $label_value = '<svg width="32px" height="29px" viewBox="0 0 32 29" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
          <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
              <g id="Search-Results---Grid-View" transform="translate(-470.000000, -637.000000)" fill="#FF5C5C" fill-rule="nonzero">
                  <g id="Listings" transform="translate(199.000000, 419.000000)">
                      <g id="Listing" transform="translate(0.665296, 0.000000)">
                          <g id="Favorite" transform="translate(270.334704, 218.000000)">
                              <path d="M16.2560915,26.695487 C16.2333822,26.7194748 16.1879636,26.7514585 16.1728241,26.7754463 C16.1728241,26.7754463 16.3393588,26.6595054 16.2560915,26.7074809 C16.206888,26.7354667 16.1387602,26.7714483 16.0933417,26.80743 C16.2295973,26.695487 16.2598763,26.7434626 16.1728241,26.7714483 C16.1387602,26.7794443 16.1084812,26.7954361 16.0744173,26.803432 C16.0479232,26.811428 16.021429,26.8154259 15.9987197,26.8234219 C15.8889583,26.8554056 16.2333822,26.8154259 16.0706324,26.811428 C16.0100744,26.811428 15.9495163,26.811428 15.8889583,26.80743 C15.7678422,26.80743 16.1009115,26.8554056 15.9873651,26.8194239 C15.960871,26.811428 15.9343768,26.80743 15.9116675,26.7994341 C15.8700339,26.7874402 15.6732202,26.7154768 15.8284002,26.7754463 C15.9684407,26.8314178 15.8586793,26.7834422 15.8208305,26.7634524 C15.7867666,26.7434626 15.7451329,26.703483 15.7072841,26.6874911 C15.711069,26.6874911 15.8548944,26.8274198 15.7905514,26.7554565 C15.8094758,26.7794443 15.6656505,26.6315196 15.7299934,26.695487 C15.7299934,26.695487 15.7299934,26.695487 15.7299934,26.695487 C15.7262085,26.6914891 15.7224236,26.6874911 15.7186388,26.6834931 C15.7034992,26.6675013 15.6883597,26.6515094 15.6732202,26.6355176 C15.5104705,26.4636052 15.3477207,26.2916927 15.1849709,26.1197803 C14.469629,25.3641652 13.754287,24.6085501 13.0389451,23.852935 C12.0321675,22.7894768 11.0291748,21.7300164 10.0223972,20.6665582 C8.99669525,19.5831101 7.97477816,18.5036599 6.9490762,17.4202119 C6.18074594,16.6086253 5.40863081,15.7930407 4.64030055,14.9814542 C4.43591714,14.7655641 4.23153372,14.5496741 4.0271503,14.3337841 C3.98551664,14.2898065 3.94388298,14.2458289 3.90224932,14.2018513 C3.67515664,13.9619735 3.48969761,13.6781181 3.26260493,13.4422382 C3.27395956,13.4542321 3.40264542,13.6381384 3.31180834,13.5062057 C3.29666883,13.4822179 3.27774444,13.4582301 3.26260493,13.4382403 C3.22475614,13.3822688 3.18690736,13.3302953 3.15284346,13.2743238 C3.06957614,13.1503869 2.9900937,13.0224521 2.91439614,12.8905193 C2.77435565,12.6506415 2.64566979,12.4027677 2.52833857,12.1468981 C2.50184443,12.0869286 2.47535028,12.0269592 2.44885613,11.9669897 C2.41857711,11.8990244 2.34287955,11.6871323 2.44885613,11.9749857 C2.39965272,11.8430529 2.3504493,11.715118 2.30503077,11.5831852 C2.21797857,11.3193197 2.13849613,11.0554541 2.07793808,10.7835926 C2.01359515,10.515729 1.96817661,10.2438675 1.92654296,9.96800801 C1.91140344,9.85606504 1.94546735,10.1119347 1.94168247,10.0959428 C1.93789759,10.0599612 1.93411271,10.0239795 1.93032783,9.98799783 C1.92275808,9.92403042 1.9189732,9.860063 1.91140344,9.79609559 C1.90004881,9.66016483 1.89247905,9.52023611 1.88869417,9.38430535 C1.87733954,9.10844588 1.88112442,8.8325864 1.90004881,8.55672692 C1.90761856,8.42879209 1.9189732,8.30085726 1.93032783,8.17292243 C1.93411271,8.13694076 1.94168247,8.10095909 1.94168247,8.06497742 C1.94168247,8.07297335 1.90383369,8.32884301 1.93032783,8.1649265 C1.94168247,8.09296316 1.9530371,8.02099982 1.96439174,7.94503852 C2.05522881,7.39731752 2.1952693,6.86159042 2.39208296,6.34585314 C2.4034376,6.32186536 2.41100735,6.29387962 2.42236199,6.26989184 C2.46778052,6.1539509 2.33909467,6.46979001 2.38829808,6.35384907 C2.41479223,6.29387962 2.4375015,6.23391016 2.46399565,6.17394071 C2.52076882,6.04600588 2.57754199,5.91807105 2.64188492,5.79413419 C2.76678589,5.54226249 2.90682638,5.29838672 3.05822151,5.06250688 C3.13013419,4.9505639 3.20583175,4.84261889 3.27774444,4.73467388 C3.34965712,4.63072683 3.15284346,4.89859038 3.22854102,4.79864129 C3.24746541,4.77465351 3.26260493,4.75466369 3.28152932,4.73067591 C3.33451761,4.66271053 3.3875059,4.59474516 3.4404942,4.53077774 C3.62595322,4.30289383 3.82276688,4.08300584 4.03093518,3.8791097 C4.14448152,3.76716672 4.26559762,3.65522375 4.38671372,3.5512767 C4.40942299,3.53128688 4.43591714,3.51129706 4.45862641,3.48730928 C4.58731226,3.37536631 4.4245625,3.5072991 4.41320787,3.52329096 C4.45862641,3.4633215 4.54946348,3.41534594 4.61380641,3.36737038 C4.86360836,3.1794661 5.12476495,3.00755367 5.3934913,2.8516331 C5.51839227,2.77966976 5.64329325,2.71170438 5.77197911,2.64773696 C5.83632204,2.61575326 5.90066496,2.58776751 5.96500789,2.5557838 C6.07855423,2.49981232 5.7909035,2.63174511 5.90444984,2.57977158 C5.94986838,2.55978177 5.99907179,2.53979195 6.04449033,2.51980213 C6.32457131,2.40386119 6.61222205,2.30791007 6.90365766,2.2279508 C7.18373864,2.1519895 7.4676045,2.09601801 7.75525523,2.04804245 C7.88015621,2.02805263 7.55465669,2.07203023 7.67955767,2.06003634 C7.7098367,2.05603837 7.74011572,2.05204041 7.76660987,2.04804245 C7.84609231,2.04004652 7.92557475,2.03205059 8.00505719,2.02405467 C8.15266743,2.01206078 8.30406256,2.00406485 8.4516728,2.00006689 C8.75067817,1.99207096 9.05346842,2.00806281 9.35247379,2.03604856 C9.41681672,2.04404448 9.50008404,2.07203023 9.56064209,2.06003634 C9.56442697,2.06003634 9.35247379,2.02805263 9.45845038,2.04404448 C9.50008404,2.05204041 9.53793282,2.05603837 9.57956648,2.0640343 C9.72717673,2.08802208 9.87857185,2.12000579 10.0261821,2.1519895 C10.3214026,2.21995488 10.6166231,2.30791007 10.9004889,2.41585508 C11.0329597,2.46383064 11.1654304,2.52779806 11.2979011,2.57577362 C11.0405294,2.48382046 11.2941163,2.57577362 11.3508894,2.60375936 C11.426587,2.63974104 11.4984997,2.67572271 11.5741972,2.71570234 C11.8504933,2.86362699 12.1192197,3.02754349 12.3765914,3.20745184 C12.4977075,3.29140908 12.6150387,3.38336223 12.7361548,3.47531539 C12.7891431,3.51529503 12.8307768,3.59125633 12.6945212,3.43933372 C12.7172304,3.4633215 12.7437246,3.48331132 12.7702187,3.50330114 C12.8459163,3.56726855 12.917829,3.63123597 12.9897416,3.69920135 C13.1865553,3.88310766 13.3720143,4.07900787 13.5612582,4.27490808 C14.034368,4.77465351 14.5112626,5.2783969 14.9843724,5.77814233 C15.0979188,5.89808124 15.2076802,6.01402218 15.3212266,6.13396108 C15.68079,6.51376761 16.2977251,6.51776557 16.6610734,6.13396108 C17.0282066,5.74615863 17.3915549,5.36235414 17.7586881,4.97455168 C18.1598851,4.55076756 18.5497276,4.10699362 18.9774188,3.71119524 C19.0796105,3.61524411 19.1855871,3.52728892 19.2915637,3.43533576 C19.3710461,3.36737038 19.2007266,3.51129706 19.2082964,3.50330114 C19.2385754,3.48331132 19.2650695,3.45532558 19.2953486,3.43533576 C19.3521217,3.39135816 19.4126798,3.34738056 19.469453,3.30340297 C19.6889759,3.14348443 19.9160686,2.99555978 20.150731,2.85962902 C20.2604925,2.79566161 20.370254,2.73569216 20.4800154,2.67972067 C20.5443583,2.64773696 20.6087013,2.61575326 20.6692593,2.58376955 C20.6919686,2.57177566 20.7184627,2.55978177 20.741172,2.55178584 C20.8812125,2.48781843 20.7449569,2.5557838 20.6843988,2.57577362 C20.9342008,2.49581435 21.1726481,2.37987341 21.4262349,2.29991414 C21.6760369,2.21995488 21.9296237,2.15598746 22.1869954,2.10401394 C22.3043267,2.08002615 22.4254428,2.06803226 22.5465589,2.04004652 C22.5389891,2.04004652 22.292972,2.07203023 22.448152,2.05603837 C22.4746462,2.05204041 22.5011403,2.04804245 22.5238496,2.04804245 C22.5919774,2.04004652 22.6601052,2.03205059 22.7320179,2.02805263 C23.2505462,1.98407503 23.7690745,1.99207096 24.2838179,2.05603837 C24.4919862,2.08002615 24.0983589,2.02405467 24.2648935,2.05204041 C24.3254516,2.0640343 24.3860096,2.07203023 24.4427828,2.08402412 C24.5790384,2.1080119 24.715294,2.13599764 24.8515496,2.17197931 C25.0975667,2.23194877 25.3397989,2.30391211 25.5782462,2.38786934 C25.7031472,2.43184694 25.8242633,2.4798225 25.9491643,2.52779806 C25.9718735,2.53979195 25.9983677,2.54778788 26.021077,2.55978177 C26.1649023,2.61975122 25.9491643,2.52779806 25.9415945,2.5238001 C26.0059374,2.5557838 26.0702804,2.58376955 26.1346233,2.61575326 C26.3768555,2.73169419 26.6115179,2.86362699 26.8386106,3.00755367 C27.0694882,3.15148036 27.2890111,3.31539686 27.5085341,3.47931336 C27.6410048,3.57926244 27.3495692,3.3433826 27.5085341,3.47931336 C27.5577375,3.51929299 27.6069409,3.56327059 27.6561443,3.60724819 C27.7696906,3.70719727 27.8794521,3.81114432 27.9854287,3.91509137 C28.1860272,4.11498954 28.3752711,4.32688161 28.5569453,4.54676959 C28.5985789,4.59874312 28.6402126,4.65071664 28.6780614,4.70269017 C28.719695,4.75866166 28.8408111,4.94656594 28.6818463,4.70269017 C28.7575438,4.81863111 28.8408111,4.93057408 28.9165087,5.05051299 C29.0679038,5.28239487 29.2041594,5.52627064 29.3290604,5.77414437 C29.3858336,5.89008531 29.4426068,6.00602625 29.4955951,6.12196719 C29.5220892,6.18193664 29.5485833,6.23790813 29.5712926,6.29787758 C29.5864321,6.32986129 29.5977868,6.36584296 29.6129263,6.39782667 C29.5826473,6.33385925 29.529659,6.18993257 29.5864321,6.33785721 C29.775676,6.84160061 29.9195014,7.36533382 30.0103385,7.89706295 C30.0216931,7.9690263 30.0330477,8.04098964 30.0444024,8.11295298 C30.0481873,8.14093872 30.0519721,8.16892447 30.055757,8.19291225 C30.0746814,8.32084708 30.0292629,7.96103037 30.0444024,8.0889652 C30.0595419,8.22489596 30.0746814,8.36082671 30.0822512,8.49675747 C30.1011756,8.76861898 30.1087453,9.04447846 30.1011756,9.31633997 C30.0936058,9.59219945 30.0595419,9.860063 30.0406175,10.1359225 C30.0406175,10.1279266 30.0784663,9.86805893 30.0519721,10.0319754 C30.0481873,10.0599612 30.0444024,10.0879469 30.0406175,10.1119347 C30.0330477,10.1759021 30.0216931,10.2398695 30.0103385,10.299839 C29.9838443,10.4437657 29.9573502,10.5876923 29.9232863,10.731619 C29.8627282,10.9994826 29.7908155,11.2633482 29.7037634,11.5232158 C29.6621297,11.6471527 29.620496,11.7670916 29.5750775,11.8870305 C29.5145194,12.046949 29.6696994,11.6591465 29.5788624,11.8790345 C29.5523682,11.9469999 29.5220892,12.0149653 29.4918102,12.0829307 C29.3782638,12.3348024 29.2533629,12.5826761 29.1133224,12.8225539 C29.0489794,12.9344969 28.9808516,13.0464399 28.908939,13.1543849 C28.874875,13.2103564 28.8370263,13.2623299 28.8029624,13.3183014 C28.7802531,13.3502851 28.7613287,13.3782708 28.7386194,13.4102545 C28.7159102,13.4422382 28.6175033,13.578169 28.7159102,13.4462362 C28.8105321,13.3143034 28.6818463,13.4902138 28.6553521,13.5261955 C28.6137185,13.578169 28.5758697,13.6301425 28.534236,13.682116 C28.4396141,13.798057 28.3412072,13.9139979 28.2390155,14.0259409 C28.2049516,14.0659205 28.1671028,14.1059002 28.1292541,14.1418818 C28.0876204,14.1858594 28.0459867,14.229837 28.0043531,14.2738146 C27.4669004,14.8415254 26.9332326,15.4052383 26.3957799,15.9729491 C25.4646999,16.9564481 24.5336199,17.9399471 23.598755,18.9274441 C22.5503437,20.0348799 21.5019325,21.1423158 20.4535213,22.2497517 C19.5527203,23.201267 18.6481344,24.1567802 17.7473334,25.1082955 C17.2704388,25.6120389 16.7935441,26.1157823 16.3166495,26.6195257 C16.30151,26.6515094 16.2788007,26.6714992 16.2560915,26.695487 C15.9040978,27.0672976 15.8813885,27.7429534 16.2560915,28.1107661 C16.6345793,28.4785787 17.2212354,28.5065645 17.5959383,28.1107661 C17.9441471,27.7429534 18.288571,27.3791388 18.6367798,27.0113261 C19.4845925,26.1157823 20.3324052,25.2202385 21.1802179,24.3246947 C22.2437686,23.201267 23.3035345,22.0818372 24.3670852,20.9584095 C25.3549384,19.914941 26.3427916,18.8714726 27.3344297,17.8240062 C27.9627194,17.1603442 28.5947941,16.4926843 29.2230838,15.8290224 C29.3290604,15.7170794 29.4388219,15.6051365 29.5447985,15.4891955 C30.396396,14.5696639 31.0360404,13.4742219 31.4675165,12.2748329 C32.2964049,9.96800801 32.1336551,7.35733789 31.1458019,5.13447022 C30.1390243,2.87162292 28.1898121,1.14450271 25.9378096,0.404879475 C23.7160862,-0.326747834 21.236991,-0.046890393 19.2007266,1.13250882 C17.823031,1.92810354 16.7708349,3.19545795 15.6732202,4.35486735 C15.5596739,4.47480625 15.4423427,4.59874312 15.3287963,4.71868202 C15.7754119,4.71868202 16.2220275,4.71868202 16.6686432,4.71868202 C16.2863705,4.31488772 15.9040978,3.91109341 15.5180402,3.50330114 C15.0638549,3.02354553 14.6210241,2.51580417 14.1214202,2.08802208 C13.0427299,1.16449253 11.7861504,0.492834671 10.4235943,0.196985377 C9.09888696,-0.0908679908 7.72497621,-0.0668802102 6.41162351,0.292936499 C3.92495859,0.976588247 1.75622344,2.89161273 0.738091231,5.39433784 C0.26119659,6.57373706 -0.00374487715,7.82110165 0,9.10844588 C0.00382487906,10.3877942 0.272551224,11.6271628 0.749445865,12.7985661 C1.24148002,14.013947 2.01738003,15.0254318 2.89168687,15.9529593 C3.55782542,16.6566008 4.22396396,17.3602424 4.89010251,18.063884 C5.90444984,19.1353382 6.91879717,20.2067924 7.92935963,21.2742486 C8.99291037,22.3976763 10.0564611,23.5211041 11.1200119,24.6445318 C11.9337607,25.5040939 12.7512943,26.367654 13.5650431,27.2272162 C14.1024958,27.794927 14.6475183,28.5145604 15.4120636,28.7224545 C16.1993183,28.9383445 17.016852,28.6944687 17.5997232,28.1027702 C17.9592866,27.7389555 17.9706412,27.0473078 17.5997232,26.6874911 C17.2098807,26.3236764 16.6383641,26.3076846 16.2560915,26.695487 Z" id="empty"></path>
                          </g>
                      </g>
                  </g>
              </g>
          </g>
          </svg>';
        }
        ob_start()
        ?>
          <div class="wovax-idx-listing-favorite-container" id="<?php echo esc_attr($item->id); ?>">
            <div class="wovax-idx-listing-favorite">
              <input type="hidden" class="<?php echo esc_attr($item->id) . '_feed'; ?>" name="feed-id" value="<?php echo esc_attr($item->class_id); ?>">
              <input type="hidden" class="<?php echo esc_attr($item->id) . '_mls'; ?>" name="feed-id" value="<?php echo esc_attr($item->$mls); ?>">
              <input class="toggle-heart" id="<?php echo esc_attr($item->id) . '_heart'; ?>" type="checkbox" <?php echo $checked; ?> onclick="wovax_idx_save_favorite_click(this)">
              <label id="toggle-heart-section" for="<?php echo esc_attr($item->id) . '_heart'; ?>" onclick="wovax_idx_change_heart(this)"><?php echo $label_value; ?></label>
            </div>
          </div>
        <?php
        $favorite_html = ob_get_clean();
      } else if ( $fav_avail == 'yes' && !is_user_logged_in() ) {
        ob_start()
        ?>
          <div class="wovax-idx-listing-favorite-container" id="<?php echo esc_attr($item->id); ?>">
            <div class="wovax-idx-listing-favorite">
              <input type="hidden" class="<?php echo esc_attr($item->id) . '_feed'; ?>" name="feed-id" value="<?php echo esc_attr($item->class_id); ?>">
              <input type="hidden" class="<?php echo esc_attr($item->id) . '_mls'; ?>" name="feed-id" value="<?php echo esc_attr($item->$mls); ?>">
              <input class="toggle-heart" id="<?php echo esc_attr($item->id) . '_heart'; ?>" type="checkbox" onclick="wovax_idx_save_favorite_click(this)">
              <div href="#wovax-idx-login-registration-modal" onclick="wovax_idx_open_modal(this)">
                <label id="toggle-heart-section" for="<?php echo esc_attr($item->id) . '_heart'; ?>" onclick="wovax_idx_change_heart(this)"><?php echo $label_value; ?></label>
              </div>
            </div>
          </div>
        <?php
        $favorite_html = ob_get_clean();
      } else {
        $favorite_html = '';
      }
      ob_start();
      ?>
      <div class="wovax-idx-section">
        <div class="wovax-idx-listing-container">
          <div class="wovax-idx-listing">
              <a href="<?php echo ListingLoader::buildURL($item->class_id, $item->id, $item); ?>">
                <div class="wovax-idx-listing-image">
                  <img class="wovax-idx-listing-featured-image" src="<?php echo esc_url('https://cache.wovax.com/image/?src=' . $main_photo . '&width=480&height=300&crop-to-fit&aro'); ?>" <?php echo $desc_photo; ?> >
                  <div class="wovax-idx-listing-status <?php echo $status_class; ?>"><?php echo esc_html($item->Status); ?></div>
                </div>
              </a>
              <a href="<?php echo ListingLoader::buildURL($item->class_id, $item->id, $item); ?>">
                <div class="wovax-idx-listing-content">
                  <div class="wovax-idx-listing-title">
                    <?php echo esc_html($item->$street); ?>
                    <div class="wovax-idx-listing-subtitle">
                      <?php echo esc_html($item->City) . ' ' . esc_html($item->State) . ' ' . esc_html($item->$zip_code); ?>
                    </div>
                  </div>
                  <?php echo $favorite_html; ?>
                  <div class="wovax-idx-listing-details"><?php echo $rooms . $lot_details; ?></div>
                  <div class="wovax-idx-listing-price"><?php echo esc_html($price); ?></div>
                  <div class="wovax-idx-listing-mls-logo"><img src="https://cache.wovax.com/image/?src=<?php echo $mls_logo; ?>"></div>
                </div>
              </a>
            </div>
          </div>
        </div>
      <?php
      $output .= ob_get_clean();
    }
    $output.= '</div>';
    return $output;
}
