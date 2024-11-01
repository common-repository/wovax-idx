<?php

if (!defined('ABSPATH')) exit;
require_once plugin_dir_path(__FILE__) . "/helpers.php";

add_shortcode("wovax-idx", "wovax_idx_shortcode");

function wovax_idx_shortcode($atts, $content = null) {
    extract(shortcode_atts(array(
      "id" => '',
      "search-form-columns" => ''
    ) , $atts));
    // Where the heck is $id assigned???
    $shortcode_details = wovax_idx_get_shortcode_by_id($id);
    // Could not find any shortcodes with this ID.
    if(empty($shortcode_details)) {
      return;
    }

    $api_key = wovax_idx_get_validation_token();
    require_once plugin_dir_path(__FILE__) . "/views/search-form.php";
    require_once plugin_dir_path(__FILE__) . "/views/listings-map.php";
    require_once plugin_dir_path(__FILE__) . "/views/listings-properties.php";
    require_once plugin_dir_path(__FILE__) . "/views/login-register-modal.php";
    require_once plugin_dir_path(__FILE__) . "/views/user-profile.php";

    if($shortcode_details[0]->type == 'user_profile') {
        return wovax_idx_get_content_user_profile();
    }

    if($shortcode_details[0]->type == 'search_form') {
        // shortcode search form
        if( !isset($atts['search-form-columns']) || !is_numeric($atts['search-form-columns']) || $atts['search-form-columns'] < 0 || empty($atts['search-form-columns']) ) {
          $atts['search-form-columns'] = 0;
        } elseif ( $atts['search-form-columns'] >= 6 ) {
          $atts['search-form-columns'] = 6;
        }

        $content = '<br/><div class=warning><p>NO VALUE FOUND</p></div><style media=screen type=text/css>
                    .warning {
                    font-size: 13px;
                    padding-left: 15px;
                    background: #FDECC4;
                    border-left: 9px solid #FFB913;
                    color: #2c3e50;
                    padding-top: 20px;
                    padding-bottom: 1px;
                    }
                </style>';

        $feeds_id = wovax_idx_get_feeds_by_shortcode($id);
        // Not NULL populate content
        if ($feeds_id != NULL) {
            if($shortcode_details[0]->grid_view != 'yes' && $shortcode_details[0]->map_view == 'yes'){
                $view_option = 'map';
            } else {
                $view_option = 'grid';
            }
            $content = wovax_idx_get_content_search_form($id, $atts['search-form-columns'], $view_option, $api_key);
        }
        return $content;
    }

    // Not sure if the types in the previous code was exhaustive
    // So keep this here in case it was not exhaustive.
    if($shortcode_details[0]->type != 'listings' && $shortcode_details[0]->type != 'user_favorites') {
        return '';
    }

    $type    = 'favorites';
    $rules   = '';
    $content = '';
    if($shortcode_details[0]->type == 'listings') {
        $type = 'rules';
        $rules = wovax_idx_get_rule_by_shortcode_id($id);
    }

    /* API */
    /* 
    three options here:
    1. only map
    2. only grid
    3. both map and grid / fallback
    */

    // Four Possible cases
    // | GRID |  MAP  |
    // +------+-------+
    // |  N   |   N   |  The else branch
    // |  N   |   Y   |  The Main if
    // |  Y   |   N   |  THe Else if branch
    // |  Y   |   Y   |  Also the Else branch
    // The logic seems to be if they are the same, use $_GET otherwise call the function for what is currently Yes

    // Not sure what uses $view_option is, it may be a global like $id...
    if($shortcode_details[0]->grid_view !== 'yes' && $shortcode_details[0]->map_view === 'yes') {
        $content = wovax_idx_get_content_listings_map($shortcode_details, $rules, $type, $api_key);
    } else if($shortcode_details[0]->grid_view === 'yes' && $shortcode_details[0]->map_view !== 'yes') {
        $content = wovax_idx_get_content_listings_grid($shortcode_details, $rules, $type, $api_key);
    } else {
        if(array_key_exists('wovax-idx-view-option', $_GET) && $_GET['wovax-idx-view-option'] == 'map') {
            $content = wovax_idx_get_content_listings_map($shortcode_details, $rules, $type, $api_key);
        } else {
            $content = wovax_idx_get_content_listings_grid($shortcode_details, $rules, $type, $api_key);
        }
    }
    $content .= wovax_idx_get_login_register_modal();
    return $content;
}

add_shortcode("wovax-idx-search-results", "wovax_idx_search_results_shortcode");

function wovax_idx_search_results_shortcode($atts, $content = null) {
  global $wpdb;
  extract(shortcode_atts(array(
    "search-form-columns" => ''
  ) , $atts));
  $id = filter_var($_REQUEST['wovax-idx-shortcode-id'], FILTER_SANITIZE_STRING);
  if ($id) {
    $shortcode_details = wovax_idx_get_shortcode_by_id($id);
  } else {
    $id = get_option('wovax-idx-settings-default-search');
    if(empty($id)) {
      $search_shortcode_sql = $wpdb->prepare("SELECT `id` FROM `{$wpdb->prefix}wovax_idx_shortcode` WHERE `type` = %s AND `status` = %s ORDER BY `id` DESC LIMIT 1", "search_form", "published");
      $default_shortcode = $wpdb->get_results($search_shortcode_sql);
      $id = $default_shortcode[0]->id;
    }
    $shortcode_details = wovax_idx_get_shortcode_by_id($id);
  }
  if (!empty($shortcode_details)) {
    $api_key = wovax_idx_get_validation_token();
    require_once plugin_dir_path(__FILE__) . "/views/search-form.php";
    require_once plugin_dir_path(__FILE__) . "/views/listings-map.php";
    require_once plugin_dir_path(__FILE__) . "/views/listings-properties.php";
    require_once plugin_dir_path(__FILE__) . "/views/login-register-modal.php";
    $filters = wovax_idx_get_filter_by_shortcode_id($id);

    if( !isset($atts['search-form-columns']) || !is_numeric($atts['search-form-columns']) || $atts['search-form-columns'] < 0 || empty($atts['search-form-columns']) ) {
      $columns_view = 0;
    } elseif ( $atts['search-form-columns'] >= 6 ) {
      $columns_view = 6;
    } else {
      $columns_view = $atts['search-form-columns'];
    }

    /* API */
    if($shortcode_details[0]->grid_view != 'yes' && $shortcode_details[0]->map_view == 'yes'){
      $view_option = 'map';
      return wovax_idx_get_content_search_form($id, $columns_view, $view_option, $api_key) . wovax_idx_get_content_listings_map($shortcode_details, $filters, 'filters', $api_key) . wovax_idx_get_login_register_modal();
    } else {
      $view_option = 'grid';
      if(array_key_exists('wovax-idx-view-option', $_GET) && $_GET['wovax-idx-view-option'] == 'map') {
        return wovax_idx_get_content_search_form($id, $columns_view, $view_option, $api_key) . wovax_idx_get_content_listings_map($shortcode_details, $filters, 'filters', $api_key) . wovax_idx_get_login_register_modal();
      }else {
        return wovax_idx_get_content_search_form($id, $columns_view, $view_option, $api_key) . wovax_idx_get_content_listings_grid($shortcode_details, $filters, 'filters', $api_key) . wovax_idx_get_login_register_modal();
      }
    }
  }
}
