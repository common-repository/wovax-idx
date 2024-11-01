<?php
/*
Plugin Name: Wovax IDX
Plugin URI: https://wovax.com/products/idx/
Description: Wovax IDX is a WordPress plugin built for real estate agents, brokers, or website developers. Wovax IDX allows you to easily build and display MLS real estate listings on your WordPress website.
Version: 1.2.2
Author: Wovax, LLC.
Author URI: https://wovax.com/
License: GPLv2 or later
*/

// Plugin admin menu

if (!defined('ABSPATH')) exit;
/******** Include Files ********/
// API
require_once(__DIR__.'/api/connect.php');
// Options
require_once(__DIR__.'/settings/options.php');
// Integration
require_once(__DIR__.'/integration/wp-cf7.php');
require_once(__DIR__.'/integration/elementor/elementor.php');
// Utilties
require_once(__DIR__.'/utilities/base32.php');
include_once(__DIR__.'/utilities/data-types.php');
require_once(__DIR__.'/utilities/current-listing.php');
require_once(__DIR__.'/utilities/detail-elements.php');
// Other
require_once(__DIR__.'/listing.php');
require_once(__DIR__.'/shortcodes/shortcode.php');
require_once(__DIR__.'/shortcodes/listing-details.php');
require_once(__DIR__.'/shortcodes/time-stamp.php');
// Listing loader
require_once(__DIR__.'/utilities/listing-loader.php');
// Modal
require_once(__DIR__.'/login-modal.php');
// Blocks
if(class_exists('WP_Block_Type')) {
    require_once(__DIR__.'/blocks/blocks.php');
}
// WP JSON Controller
require_once(__DIR__.'/utilities/idx-controller.php');
$IDX_CONTROLLER = new wovax_idx_listings_controller();
$IDX_CONTROLLER->init();
// Elementor
// TODO: only load if Elementor is installed.
//require_once(__DIR__.'/elementor/elementor-blocks.php');
// Admin Pages
include_once(__DIR__.'/admin/pages/search-appearance.php');
include_once(__DIR__.'/admin/pages/listing-card.php');
include_once(__DIR__.'/admin/pages/shortcode-list.php');

require plugin_dir_path(__FILE__) . "/functions.php";

require plugin_dir_path(__FILE__) . "/wovax-idx-ajax.php";

require plugin_dir_path(__FILE__) . "/shortcodes/wovax-idx-shortcode.php";

if (!defined('WOVAX_PLUGIN_URL')) {
  define('WOVAX_PLUGIN_URL', plugins_url('/', __FILE__));
}

if ( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Feeds_List extends WP_List_Table {

  function get_columns() {
    $columns = [
      'resource'    => __( 'Resource', 'sp' ),
      'feed'        => __( 'Feed', 'sp' ),
      'board'       => __( 'Board', 'sp' ),
      'environment' => __( 'Environment', 'sp' ),
      'status'      => __( 'Status', 'sp' ),
      'updated'     => __( 'Updated', 'sp' )
    ];

    return $columns;
  }
}

class Shortcode_List extends WP_List_Table {

  function get_columns() {
    $columns = [
      'shortcode' => __( 'Shortcode', 'sp' ),
      'type'      => __( 'Type', 'sp' ),
      'author'    => __( 'Author', 'sp' ),
      'created'   => __( 'Created', 'sp' )
    ];

    return $columns;
  }
}

class User_List extends WP_List_Table {

  function get_columns() {
    $columns = [
      'fullname'  => __( 'Fullname', 'sp' ),
      'phone'     => __( 'Phone', 'sp' ),
      'email'     => __( 'Email', 'sp' ),
      'favorites' => __( 'Favorites', 'sp' )
    ];

    return $columns;
  }
}
// TODO probably add just a filter for pages that have no menu.
add_action('init', function () {
    global $pagenow;
    // add pre-existing page to array.
    // NOTE!!! Don't add any new pages here please use the wovax_idx_submenus filter.
    $menus = array(
        array('order' => 0, 'page_title' => 'Wovax IDX Settings',      'menu_title' => 'Settings',      'slug' => 'settings',      'call' => 'wovax_idx_settings'),
        array('order' => 1, 'page_title' => 'Wovax IDX Feeds',         'menu_title' => 'IDX Feeds',     'slug' => 'feeds',         'call' => 'wovax_idx_feeds'),
        array('order' => 3, 'page_title' => 'Wovax IDX Groups',        'menu_title' => 'Groups',        'slug' => 'groups',        'call' => 'wovax_idx_groups'),
        //array('order' => 4, 'page_title' => 'Wovax IDX User Activity', 'menu_title' => 'User Activity', 'slug' => 'user_activity', 'call' => 'wovax_idx_user_activity')
    );
    // Use a filter to make it easier to localize admin page code to indivdual files.
    $menus = apply_filters('wovax_idx_submenus', $menus);
    // Sort menu array based of order number
    usort($menus, function($a, $b) {
        $a_val = PHP_INT_MAX;
        $b_val = PHP_INT_MAX;
        if(isset($a['order'])) {
            $a_val = intval($a['order']);
        }
        if(isset($b['order'])) {
            $b_val = intval($b['order']);
        }
        if($a_val === $b_val) {
            return 0;
        }
        return ($a_val < $b_val) ? -1 : 1;
    });
    // Remove Bad Submenus provided to the filter
    $clean = array();
    foreach($menus as $menu) {
        if(!isset($menu['call']) && !is_callable($menu['call'])) { // Need Call back
            continue;
        }
        if(!isset($menu['slug'])) { // Need slug
            continue;
        }
        $menu['slug'] = sanitize_key($menu['slug']);
        // Slug must be longer than one after sanitzation.
        if(strlen($menu['slug']) < 1) {
            continue;
        }
        $menu['slug'] = 'wovax_idx_'.$menu['slug'];
        if(!array_key_exists('perm', $menu)) {
            $menu['perm'] = '';
        }
        $menu['perm'] = trim($menu['perm']);
        if(strlen($menu['perm']) < 1) {
            $menu['perm'] = 'manage_options';
        }
        if(!array_key_exists('menu_title', $menu) || strlen($menu['menu_title']) < 1) {
            $menu['menu_title'] = 'No Menu Title';
        }
        if(!array_key_exists('page_title', $menu) || strlen($menu['page_title']) < 1) {
            $menu['page_title'] = 'No Page Title';
        }
        if(isset($menu['before_call']) && !is_callable($menu['before_call'])) {
            unset($menu['before_call']);
        }
        if(array_key_exists('init', $menu)) {
            // Check if we can call init function
            if( $pagenow == 'admin.php' && $menu['slug'] == $_REQUEST['page'] && is_callable($menu['init']) ) {
                call_user_func($menu['init']);
            }
            unset($menu['init']); // No longer needed
        }
        $clean[] = $menu;
    }
    $menus = $clean;
    // Add Admin Menu Action to create menus after processing them.
    class SubMenus { // define class for Closure
        private $menus = array();
        public function __construct($menus) {
            $this->menus = $menus;
        }
    }
    add_action('admin_menu', Closure::bind(function() {
        $wovax_icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMjRweCIgaGVpZ2h0PSIyNHB4IiB2aWV3Qm94PSIwIDAgMjQgMjQiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+CiAgICA8IS0tIEdlbmVyYXRvcjogU2tldGNoIDU4ICg4NDY2MykgLSBodHRwczovL3NrZXRjaC5jb20gLS0+CiAgICA8dGl0bGU+QXJ0Ym9hcmQ8L3RpdGxlPgogICAgPGRlc2M+Q3JlYXRlZCB3aXRoIFNrZXRjaC48L2Rlc2M+CiAgICA8ZyBpZD0iQXJ0Ym9hcmQiIHN0cm9rZT0ibm9uZSIgc3Ryb2tlLXdpZHRoPSIxIiBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGZvbnQtZmFtaWx5PSJIZWx2ZXRpY2FOZXVlLUJvbGQsIEhlbHZldGljYSBOZXVlIiBmb250LXNpemU9IjI5IiBmb250LXdlaWdodD0iYm9sZCIgbGV0dGVyLXNwYWNpbmc9Ii0wLjAyOTQ4NzIiPgogICAgICAgIDx0ZXh0IGlkPSJ3IiBmaWxsPSIjMDAwMDAwIj4KICAgICAgICAgICAgPHRzcGFuIHg9IjAuMjExNzQzNiIgeT0iMTkuNSI+dzwvdHNwYW4+CiAgICAgICAgPC90ZXh0PgogICAgPC9nPgo8L3N2Zz4=';
        add_menu_page('Wovax IDX', 'Wovax IDX', 'manage_options', 'wovax_idx_settings', '', $wovax_icon_svg, 25);
        foreach($this->menus as $menu) {
            $hook = add_submenu_page('wovax_idx_settings', $menu['page_title'], $menu['menu_title'], $menu['perm'], $menu['slug'], $menu['call']);
            if(isset($menu['before_call'])) {  // Hook before load hook for like screens and so forth
                add_action("load-$hook", $menu['before_call']);
            }
        }
        // Setting parent to NULL just creates a page without a menu.
        // Keep this old page for edit GUI
        add_submenu_page(NULL, 'Wovax IDX Shortcodes', 'Shortcodes', 'manage_options', 'wovax_idx_shortcodes', 'wovax_idx_shortcodes');
    }, new SubMenus($menus), 'SubMenus'));
});

// Initial Setup admin page
function wovax_idx_settings() {
  require plugin_dir_path(__FILE__) . "/admin/pages/settings.php";
}

// IDX Feeds admin page
function wovax_idx_feeds() {
  if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'update'):
    require plugin_dir_path(__FILE__) . "/admin/pages/idx-feeds-details.php";
  else:
    require plugin_dir_path(__FILE__) . "/admin/pages/idx-feeds.php";
  endif;
}

// Shortcodes admin page
function wovax_idx_shortcodes() {
  if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'update'):
    require plugin_dir_path(__FILE__) . "/admin/pages/shortcodes-details.php";
  else:
    require plugin_dir_path(__FILE__) . "/admin/pages/shortcodes.php";
  endif;
}

// Groups admin page
function wovax_idx_groups() {
  if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'update'):
    require plugin_dir_path(__FILE__) . "admin/pages/groups-details.php";
  else:
    require plugin_dir_path(__FILE__) . "admin/pages/groups.php";
  endif;
}

// User Activity admin page
function wovax_idx_user_activity() {
  if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'update'):
    require plugin_dir_path(__FILE__) . "/admin/pages/user-activity-details.php";
  else:
    require plugin_dir_path(__FILE__) . "/admin/pages/user-activity.php";
  endif;
}

// WordPress Plugins page Settings action link
add_filter('plugin_action_links_' . plugin_basename(__FILE__) , 'wovax_idx_add_action_links');

function wovax_idx_add_action_links($links) {
  $mylinks = array(
    '<a href="' . admin_url('admin.php?page=wovax_idx_settings') . '">Settings</a>',
  );
  return array_merge($mylinks, $links);
}

// Scripts & Styles Public
function wovax_idx_scripts_styles() {
  if (get_option('wovax-idx-settings-styling-floating-label') == 'yes') {
    wp_enqueue_script('wovax_idx_script_float', plugins_url('assets/libraries/float-labels/float-labels.min.js', __FILE__) , array(
      'jquery'
    ));
    wp_enqueue_style('wovax_idx_min_stylesheet_float', plugins_url('assets/libraries/float-labels/float-labels.min.css', __FILE__));
  }

  if (get_option('wovax-idx-settings-styling-css') == 'yes') {
    wp_enqueue_style('wovax_idx_min_stylesheet', plugins_url('assets/css/wovax-idx.min.css', __FILE__));
    $color = get_option( 'wovax-idx-settings-styling-element-color', '');
    if(!empty($color)){
      $custom_css = "
      .wovax-idx-listing .wovax-idx-listing-status{
        background: {$color};
      }
      .wovax-idx-section a.wovax-idx-button.wovax-idx-button-highlight{
        background: {$color};
        border: 1px solid {$color};
      }
      .fl-form .fl-has-focus input.fl-input,
      .fl-form .fl-has-focus select.fl-select,
      .fl-form .fl-has-focus textarea.fl-textarea {
        border-color: {$color};
        border: 1px solid {$color};
      }
      .wovax-idx-section input[type]:focus,
      .wovax-idx-section select:focus{
        border: 1px solid {$color};
      }
      .fl-form .fl-has-focus label.fl-label {
        color: {$color};
      }
      ";
      wp_add_inline_style( 'wovax_idx_min_stylesheet', $custom_css );
    }
  }

  if (get_option('wovax-idx-settings-styling-js') == 'yes') {
    wp_enqueue_script('wovax-idx-script', plugins_url('assets/js/wovax-idx.min.js', __FILE__) , array(
      'jquery'
    ));
    $variables = array(
      'ajaxurl' => admin_url( 'admin-ajax.php' )
    );
    wp_localize_script('wovax-idx-script', "wovaxIdx", $variables);
  }
  wp_enqueue_script('jquery');
}

add_action('wp_enqueue_scripts', 'wovax_idx_scripts_styles');

function wovax_idx_add_styles() {
  wp_enqueue_style('wovax_idx_best_css', plugins_url('assets/libraries/jquery-modal/jquery.modal.min.css', __FILE__));
}

add_action('wp_enqueue_scripts', 'wovax_idx_add_styles');

function wovax_idx_add_scripts() {
  wp_enqueue_script('wovax_idx_best_js', plugins_url('assets/libraries/jquery-modal/jquery.modal.min.js', __FILE__) , array(
    'jquery'
  ));
  wp_enqueue_script('jquery');
}

add_action('wp_enqueue_scripts', 'wovax_idx_add_scripts');

// Scripts & Styles Admin
function wovax_idx_scripts_admin($hook) {
  $variables_array = array(
    'ajax_url' => admin_url('admin-ajax.php') ,
    'paged' => (isset($_REQUEST['paged'])) ? filter_var($_REQUEST['paged'], FILTER_SANITIZE_STRING) : 1,
    's' => (isset($_REQUEST['s'])) ? filter_var($_REQUEST['s'], FILTER_SANITIZE_STRING) : '',
    'orderby' => (isset($_REQUEST['orderby'])) ? filter_var($_REQUEST['orderby'], FILTER_SANITIZE_STRING) : 'ASC',
    'ordername' => (isset($_REQUEST['ordername'])) ? filter_var($_REQUEST['ordername'], FILTER_SANITIZE_STRING) : 'feed_id',
    'filter' => (isset($_REQUEST['filter'])) ? filter_var($_REQUEST['filter'], FILTER_SANITIZE_STRING) : '',
    'idfeed' => (isset($_REQUEST['idfeed'])) ? filter_var($_REQUEST['idfeed'], FILTER_SANITIZE_STRING) : '',
  );
  $variables_array["type_page"] = '';

  if (strpos($hook, 'wovax_idx_settings') !== false) {
    $variables_array["type_page"] = 'wovax_idx_settings';
  }
  elseif (strpos($hook, 'wovax_idx_feeds') !== false && !isset($_REQUEST['action'])) {
    $variables_array["type_page"] = 'wovax_idx_feeds_list';
  }
  elseif (strpos($hook, 'wovax_idx_feeds') !== false && isset($_REQUEST['action']) == 'updated') {
    $variables_array["type_page"] = 'wovax_idx_feeds_detail';
  }
  elseif (strpos($hook, 'wovax_idx_shortcodes') !== false && !isset($_REQUEST['action'])) {
    $variables_array["type_page"] = 'wovax_idx_shortcodes';
  }
  elseif (strpos($hook, 'wovax_idx_user_activity') !== false && !isset($_REQUEST['action'])) {
    $variables_array["type_page"] = 'wovax_idx_user_activity';
  }
  if(array_key_exists('page', $_GET) && ($_GET['page'] === 'wovax_idx_settings')){
    wp_enqueue_media();
    wp_enqueue_style( 'wp-color-picker' );
	}

  wp_enqueue_script('jquery-ui-sortable', array(
    'jquery'
  ));
  wp_register_script('wovax-idx-feed-js', WOVAX_PLUGIN_URL . 'admin/assets/js/wovax-idx-feed-general.js', array(
    'jquery',
    'jquery-ui-autocomplete',
    'wp-color-picker'
  ) , '1.0', true);
  wp_localize_script('wovax-idx-feed-js', 'object_name', $variables_array);
  wp_enqueue_script('wovax-idx-feed-js');
  wp_register_script('wovax-idx-admin-js', WOVAX_PLUGIN_URL . 'admin/assets/js/wovax-idx-admin.js', array(
    'jquery',
    'jquery-ui-autocomplete'
  ) , '1.0', true);
  wp_enqueue_script('wovax-idx-admin-js');
  wp_enqueue_style('wovax_idx_admin', plugins_url('admin/assets/css/wovax-idx-admin.css', __FILE__));
}

add_action('admin_enqueue_scripts', 'wovax_idx_scripts_admin');

function wovax_idx_display_options() {
  // initial_setup fields
  register_setting("settings_menu_initial_setup", "wovax-idx-settings-webmaster-email");
  register_setting("settings_menu_initial_setup", "wovax-idx-settings-details-block-mode");
  register_setting("settings_menu_initial_setup", "wovax-idx-settings-environment");
  register_setting("settings_menu_initial_setup", "wovax-idx-settings-search-results-page");
  register_setting("settings_menu_initial_setup", "wovax-idx-settings-listing-details-page");
  register_setting("settings_menu_initial_setup", "wovax-idx-settings-default-search");

  // listings users
  register_setting("settings_menu_users", "wovax-idx-settings-users-registration");
  register_setting("settings_menu_users", "wovax-idx-settings-users-registration-force");
  register_setting("settings_menu_users", "wovax-idx-settings-users-registration-force-count");
  register_setting("settings_menu_users", "wovax-idx-settings-users-favorites");
  register_setting("settings_menu_users", "wovax-idx-settings-users-saved-searches");
  register_setting("settings_menu_users", "wovax-idx-settings-users-admin-bar");
  // listings fields
  register_setting("settings_menu_listing", "wovax-idx-settings-styling-css");
  register_setting("settings_menu_listing", "wovax-idx-settings-styling-js");
  register_setting("settings_menu_listing", "wovax-idx-settings-styling-floating-label");
  register_setting("settings_menu_listing", "wovax-idx-settings-styling-default-image");
  register_setting("settings_menu_listing", "wovax-idx-settings-styling-element-color");
  // listings services
  register_setting("settings_menu_services", "wovax-idx-settings-google-maps-api-key");
  register_setting("settings_menu_services", "wovax-idx-settings-apple-mapkit-token");
  register_setting("settings_menu_services", "wovax-idx-settings-location-iq-api-key");
  register_setting("settings_menu_services", "wovax-idx-settings-map-quest-api-key");
  register_setting("settings_menu_services", "wovax-idx-settings-list-trac-id");
}

add_action('admin_init', 'wovax_idx_display_options');

function wovax_idx_upgrades() {
    // Using array to make this extensible in the future since we want to
    // slowly refactor things probably should make an upgrade tracker with
    // dependancy tracking.
    $upgrades = get_option('wovax-idx-upgrade-tracker', array());
    $opt      = new Wovax\IDX\Settings\InitialSetup();
    if(!isset($upgrades['DetailsBlocksMode']) && $opt->inDetailsBlockMode()) {
        // We may need to set this to false for older installs
        $disp = new Wovax\IDX\Settings\FeedDisplay();
        $has_fields = count($disp->getDisplayedFields()) > 0;
        if($has_fields) {
            $opt->setInDetailsBlockMode('legacy');
        }
        update_option('wovax-idx-upgrade-tracker', array('DetailsBlocksMode' => $has_fields));
    }
    if($upgrades['DetailsBlocksMode'] == 1) {
      $block_option = get_option('wovax-idx-settings-details-block-mode');
      if(empty($block_option)) {
        update_option('wovax-idx-settings-details-block-mode', 'legacy');
      } else {
        update_option('wovax-idx-settings-details-block-mode', 'blocks');
      }
      $upgrades['DetailsBlocksMode'] = 2;
      update_option('wovax-idx-upgrade-tracker', $upgrades);
    }
    if(!isset($upgrades['DatabaseVersion']) || $upgrades['DatabaseVersion'] < 1.1) {
      //we can set the database version lower for testing
      global $wpdb;
      $sql_update_db = "
      ALTER TABLE `{$wpdb->base_prefix}wovax_idx_shortcode`
      ADD COLUMN `action_bar` VARCHAR(25) AFTER `pagination`;";
      $wpdb->query($sql_update_db);
      if($wpdb->last_error !== '') {
        error_log($wpdb->last_error);
      }
      $sql_update_pagination = "
      UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode`
      SET `pagination` = 'no'
      WHERE `pagination` = '';";
      $wpdb->query($sql_update_pagination);
      if($wpdb->last_error !== '') {
        error_log($wpdb->last_error);
      }
      $sql_update_action_bar = "
      UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode`
      SET `action_bar` = 'yes'
      WHERE `action_bar` IS NULL;";
      $wpdb->query($sql_update_action_bar);
      if($wpdb->last_error !== '') {
        error_log($wpdb->last_error);
      }
      $upgrades['DatabaseVersion'] = 1.1;
      update_option('wovax-idx-upgrade-tracker', $upgrades);
    }
    //Database updates can be run sequentially and we can skip old updates if not needed
    if(!isset($upgrades['DatabaseVersion']) || $upgrades['DatabaseVersion'] < 1.2) {
      global $wpdb;
      $sql_update_filters = "
      ALTER TABLE `{$wpdb->base_prefix}wovax_idx_shortcode_filters`
      ADD COLUMN `filter_data` text DEFAULT NULL AFTER `filter_placeholder`;";
      $wpdb->query($sql_update_filters);
      if($wpdb->last_error !== '') {
        error_log($wpdb->last_error);
      }
      $upgrades['DatabaseVersion'] = 1.2;
      update_option('wovax-idx-upgrade-tracker', $upgrades);
    }
}
// Also check on load since updates may or may not trigger activation hook
add_action('plugins_loaded', 'wovax_idx_upgrades');


// function to create the DB / Options / Defaults
function wovax_idx_options_install() {
    add_option('wovax-idx-settings-styling-css', 'yes');
    add_option('wovax-idx-settings-styling-js', 'yes');
    add_option('wovax-idx-settings-styling-floating-label', 'yes');
    add_option('wovax-idx-settings-users-registration', 'yes');
    add_option('wovax-idx-settings-users-registration-force', '');
    add_option('wovax-idx-settings-users-registration-force-count', '10');
    add_option('wovax-idx-settings-users-favorites', 'yes');
    add_option('wovax-idx-settings-users-saved-searches', 'yes');
    add_option('wovax-idx-feeds-per-page', '10');
    wovax_idx_create_table_feed_fields();
    wovax_idx_create_table_feed();
    wovax_idx_create_table_feed_rules();
    wovax_idx_create_table_shortcode();
    wovax_idx_create_table_shortcode_filters();
    wovax_idx_create_table_shortcode_rules();
    wovax_idx_create_default_shortcode();
    wovax_idx_upgrades();
    flush_rewrite_rules();
}

function wovax_idx_create_table_feed_fields() {
  global $wpdb;
  $sql_create_fields = "
    CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}wovax_idx_feed_fields` (
      `id_feed` int(11) DEFAULT NULL,
      `id_field` int(11) DEFAULT NULL,
      `name` varchar(255) DEFAULT NULL,
      `alias_old` varchar(255) DEFAULT NULL,
      `alias_update` varchar(255) DEFAULT NULL,
      `status_alias` int(11) DEFAULT NULL,
      `default_alias` varchar(255) DEFAULT NULL,
      `field_alias` varchar(255) DEFAULT NULL,
      `field_state` int(1) DEFAULT 0,
      `order` int DEFAULT 0
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
  $wpdb->query($sql_create_fields);
  if($wpdb->last_error !== '') {
      error_log($wpdb->last_error);
  }
}

function wovax_idx_create_table_feed() {
  global $wpdb;
  $sql_create_feed = "
    CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}wovax_idx_feeds` (
      `id_feed` int(11) NOT NULL PRIMARY KEY,
      `attributes` longtext DEFAULT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
  $wpdb->query($sql_create_feed);
  if($wpdb->last_error !== '') {
      error_log($wpdb->last_error);
  }
}

function wovax_idx_create_table_feed_rules() {
  global $wpdb;
  $sql_create_feed_rules = "
    CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}wovax_idx_feed_rules` (
      `id_rule` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `id_feed` int(11) DEFAULT NULL,
      `id_field` varchar(255) DEFAULT NULL,
      `field` varchar(255) DEFAULT NULL,
      `rule_type` varchar(255) DEFAULT NULL,
      `rule_value` varchar(255) DEFAULT NULL,
      `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
  $wpdb->query($sql_create_feed_rules);
  if($wpdb->last_error !== '') {
      error_log($wpdb->last_error);
  }
}

function wovax_idx_create_table_shortcode() {
  global $wpdb;
  $sql_create_shortcode = "
    CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}wovax_idx_shortcode` (
      `id` int(11)  NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `type` varchar(255) DEFAULT NULL,
      `title` varchar(255) DEFAULT NULL,
      `grid_view` varchar(255) DEFAULT NULL,
      `map_view` varchar(255) DEFAULT NULL,
      `per_page` varchar(255) DEFAULT NULL,
      `per_map` varchar(255) DEFAULT NULL,
      `author` varchar(255) DEFAULT NULL,
      `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `status` varchar(255) DEFAULT NULL,
      `pagination` varchar(255) DEFAULT NULL,
      `action_bar` varchar(25) DEFAULT NULL,
      `feeds` longtext DEFAULT NULL,
      `order_section` text DEFAULT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
  $wpdb->query($sql_create_shortcode);
  if($wpdb->last_error !== '') {
      error_log($wpdb->last_error);
  }
}

function wovax_idx_create_table_shortcode_filters() {
  global $wpdb;
  $sql_create_filters = "
    CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}wovax_idx_shortcode_filters` (
      `id_filter` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `id_shortcode` int(11) DEFAULT NULL,
      `id_field` varchar(255) DEFAULT NULL,
      `field` varchar(255) DEFAULT NULL,
      `filter_type` varchar(255) DEFAULT NULL,
      `filter_label` varchar(255) DEFAULT NULL,
      `filter_placeholder` varchar(255) DEFAULT NULL,
      `filter_data` longtext DEFAULT NULL,
      `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
  $wpdb->query($sql_create_filters);
  if($wpdb->last_error !== '') {
      error_log($wpdb->last_error);
  }
}

function wovax_idx_create_table_shortcode_rules() {
  global $wpdb;
  $sql_create_shortcode_rules = "
    CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}wovax_idx_shortcode_rules` (
      `id_rule` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `id_shortcode` int(11) DEFAULT NULL,
      `id_field` varchar(255) DEFAULT NULL,
      `field` varchar(255) DEFAULT NULL,
      `rule_type` varchar(255) DEFAULT NULL,
      `rule_value` varchar(255) DEFAULT NULL,
      `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
  $wpdb->query($sql_create_shortcode_rules);
  if($wpdb->last_error !== '') {
      error_log($wpdb->last_error);
  }
}

function wovax_idx_create_default_shortcode() {
  global $wpdb;
  $shortcode_table = "`{$wpdb->base_prefix}wovax_idx_shortcode`";
  $shortcode_filter_table = "`{$wpdb->base_prefix}wovax_idx_shortcode_filters`";
  $existing = $wpdb->get_col("SELECT `id` FROM $shortcode_table WHERE `type` = 'search_form'");

  if(empty($existing)) {
    $author = wp_get_current_user();
    $shortcode_sql = "INSERT INTO $shortcode_table
	    (`type`, `title`, `grid_view`, `map_view`, `per_page`, `per_map`, `author`, `status`, `pagination`, `action_bar`, `feeds`)
	    VALUES
      ('search_form', 'Property Search', 'yes', 'yes', 12, 250, '$author->display_name', 'published', 'yes', 'yes', '{\"wovax-idx-shortcode-feed-190\":\"190-9\"}');";
    $insert_shortcode = $wpdb->query($shortcode_sql);
    $shortcode_id = $wpdb->get_var("SELECT id FROM $shortcode_table WHERE `type` = 'search_form' LIMIT 1");
    $shortcode_filter_sql = "INSERT INTO $shortcode_filter_table
      (`id_shortcode`, `id_field`, `field`, `filter_type`, `filter_label`, `filter_placeholder`)
      VALUES
      ($shortcode_id, 285,'City','select','City','Select City'),
      ($shortcode_id, 288, 'Bedrooms', 'numeric_min', 'Min Bedrooms', 'Min Bedrooms'),
      ($shortcode_id, 290, 'Price', 'numeric_min', 'Min Price', 'Min Price'),
      ($shortcode_id, 290, 'Price', 'numeric_max', 'Max Price', 'Max Price'),
      ($shortcode_id, 282, 'MLS Number', 'input_text', 'MLS Number', 'MLS Number');";
    if(!empty($shortcode_id)) {
      $insert_filters = $wpdb->query($shortcode_filter_sql);
    }
  }
}

// run the install scripts upon plugin activation
register_activation_hook(__FILE__, 'wovax_idx_options_install');
register_deactivation_hook(__FILE__, function() {
	flush_rewrite_rules();
});

add_action('admin_init', 'wovax_idx_init_shortcode_post');
add_action('admin_init', 'wovax_idx_init_shortcode_get');
add_action('admin_init', 'wovax_idx_init_feed_post');

function wovax_idx_init_shortcode_post() {
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && array_key_exists('page', $_GET) && $_GET['page'] == 'wovax_idx_shortcodes' && array_key_exists('action', $_GET) && $_GET['action'] == 'update') {
    if (isset($_POST['wovax_idx_shortcode_fields']) && wp_verify_nonce(filter_var($_POST['wovax_idx_shortcode_fields'], FILTER_SANITIZE_STRING) , 'save_change_shortcode_fields')) {
      $shortcode_id = wovax_idx_save_shortcode(filter_var_array($_POST, FILTER_SANITIZE_STRING));
      $url = admin_url("admin.php?page=wovax_idx_shortcodes&tab=general&id=$shortcode_id&action=update");
      wp_redirect($url);
    }
    elseif (isset($_POST['wovax_idx_shortcode_view']) && wp_verify_nonce(filter_var($_POST['wovax_idx_shortcode_view'], FILTER_SANITIZE_STRING) , 'save_change_shortcode_view')) {
      $shortcode_id = wovax_idx_save_shortcode_view(filter_var_array($_POST, FILTER_SANITIZE_STRING));
      $url = admin_url("admin.php?page=wovax_idx_shortcodes&tab=view&id=$shortcode_id&action=update");
      wp_redirect($url);
    }
    elseif (isset($_POST['wovax_idx_shortcode_feeds']) && wp_verify_nonce(filter_var($_POST['wovax_idx_shortcode_feeds'], FILTER_SANITIZE_STRING) , 'save_change_shortcode_feeds')) {
      $shortcode_id = wovax_idx_save_shortcode_feeds(filter_var_array($_POST, FILTER_SANITIZE_STRING));
      $url = admin_url("admin.php?page=wovax_idx_shortcodes&tab=feeds&id=$shortcode_id&action=update");
      wp_redirect($url);
    }
    elseif (isset($_POST['wovax_idx_shortcode_filters']) && wp_verify_nonce(filter_var($_POST['wovax_idx_shortcode_filters'], FILTER_SANITIZE_STRING) , 'save_change_shortcode_filters') && isset($_POST['submit']) && $_POST['submit'] == 'Add Filter' || $_POST['submit'] == 'Edit Filter') {
      $shortcode_id = wovax_idx_save_shortcode_filters(filter_var_array($_POST, FILTER_SANITIZE_STRING));
      $url = admin_url("admin.php?page=wovax_idx_shortcodes&tab=filters&id=$shortcode_id&action=update");
      wp_redirect($url);
    }
    elseif (isset($_POST['wovax_idx_shortcode_rules']) && wp_verify_nonce(filter_var($_POST['wovax_idx_shortcode_rules'], FILTER_SANITIZE_STRING) , 'save_change_shortcode_rules') && isset($_POST['submit']) && $_POST['submit'] == 'Add Rule' || $_POST['submit'] == 'Edit Rule') {
      $shortcode_id = wovax_idx_save_shortcode_rules(filter_var_array($_POST, FILTER_SANITIZE_STRING));
      $url = admin_url("admin.php?page=wovax_idx_shortcodes&tab=rules&id=$shortcode_id&action=update");
      wp_redirect($url);
    }
  }
}

// function that duplicate the shortcode
function wovax_idx_init_shortcode_get() {
  if (isset($_GET['action'], $_GET['page'], $_GET['id']) && $_GET['action'] == 'duplicate' && $_GET['page'] == 'wovax_idx_shortcodes' && !isset($_GET['idfilter'])) {
    $id_shortcode = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
    wovax_idx_shortcode_duplicate_table($id_shortcode);
    $url = admin_url("admin.php?page=wovax_idx_shortcodes");
    wp_redirect($url);
  }
  elseif (isset($_GET['action'], $_GET['page'], $_GET['id'], $_GET['idfilter']) && $_GET['action'] == 'duplicate' && $_GET['page'] == 'wovax_idx_shortcodes') {
    $id_shortcode = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
    $id_filter = filter_var($_GET['idfilter'], FILTER_SANITIZE_STRING);
    wovax_idx_shortcode_duplicate_filter($id_shortcode, $id_filter);
    $url = admin_url("admin.php?page=wovax_idx_shortcodes&action=update&id=$id_shortcode&tab=filters");
    wp_redirect($url);
  }
}

// function that duplicate the shortcode
function wovax_idx_init_feed_post() {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['wovax_idx_feeds_feed_fields']) && wp_verify_nonce($_POST['wovax_idx_feeds_feed_fields'], 'save_change_feeds_fields')) {
      /* function to store all fields that belong to the form on Fields tab */
      wovax_idx_save_fields(filter_var_array($_POST, FILTER_SANITIZE_STRING));
    }
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['wovax_idx_feeds_field_states']) && wp_verify_nonce($_POST['wovax_idx_feeds_field_states'], 'save_change_field_states')) {
      /* function to store update all field_state that belong to the form on Fields Layout */
      wovax_idx_save_field_states(filter_var_array($_POST, FILTER_SANITIZE_STRING));
    }
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['wovax_idx_feeds_feed_general']) && wp_verify_nonce($_POST['wovax_idx_feeds_feed_general'], 'save_change_feeds_general')) {
      /* function to store all fields that belong to the form on Fields tab */
      wovax_idx_save_feed_general(filter_var_array($_POST, FILTER_SANITIZE_STRING));
    }
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['wovax_idx_feeds_feed_rules']) && wp_verify_nonce($_POST['wovax_idx_feeds_feed_rules'], 'save_change_feeds_rules')) {
      /* function to store all rules that belong to the form on Fields tab */
      $id_feed = wovax_idx_save_feed_rules(filter_var_array($_POST, FILTER_SANITIZE_STRING));
      $url = admin_url("admin.php?page=wovax_idx_feeds&tab=rules&action=update&idfeed=$id_feed");
      wp_redirect($url);
    }
  }
}

add_action( "load-wovax-idx_page_wovax_idx_feeds", 'wovax_idx_feeds_options' );
add_action( "load-wovax-idx_page_wovax_idx_shortcodes", 'wovax_idx_shortcodes_options' );
add_action( "load-wovax-idx_page_wovax_idx_user_activity", 'wovax_idx_users_options' );

function wovax_idx_feeds_options() {

  if(!isset($_GET['tab'])){

    $feed_per_page = get_option("wovax-idx-feeds-per-page");
    $option = 'per_page';

    $args = array(
        'label' => 'Number of feeds per page',
        'default' => $feed_per_page,
        'option' => 'wovax-idx-feeds-per-page'
    );

    add_screen_option( $option, $args );
    $feed_list = new Feeds_List();

  }
}

function wovax_idx_shortcodes_options() {

  if(!isset($_GET['action'])){

    $short_per_page = get_option("wovax-idx-shortcodes-per-page");
    $option = 'per_page';

    $args = array(
        'label' => 'Number of shortcodes per page',
        'default' => $short_per_page,
        'option' => 'wovax-idx-shortcodes-per-page'
    );

    add_screen_option( $option, $args );
    $shortcode_list = new Shortcode_List();

  }
}

function wovax_idx_users_options() {

  if(!isset($_GET['action'])){

    $short_per_page = get_option("wovax-idx-users-per-page");
    $option = 'per_page';

    $args = array(
        'label' => 'Number of users per page',
        'default' => $short_per_page,
        'option' => 'wovax-idx-users-per-page'
    );

    add_screen_option( $option, $args );
    $user_list = new User_List();

  }
}

function wovax_idx_admin_bar_toggle($content) {
	$admin_bar_hide = get_option('wovax-idx-settings-users-admin-bar');
	if($admin_bar_hide === 'yes') {
		return ( current_user_can( 'administrator' ) ) ? $content : false;
	} else {
		return $content;
	}
}
add_filter( 'show_admin_bar' , 'wovax_idx_admin_bar_toggle');

add_filter('set-screen-option', 'wovax_idx_set_option', 10, 3);

function wovax_idx_set_option($status, $option, $value) {

    if ( 'wovax_idx_feeds_per_page' == $option ) {
      update_option('wovax-idx-feeds-per-page', $value);
    }elseif ( 'wovax_idx_users_per_page' == $option ) {
      update_option('wovax-idx-users-per-page', $value);
    }else{
      update_option('wovax-idx-shortcodes-per-page', $value);
    }

    return $value;

}

add_action('admin_post_wovax_idx_shortcode_sort', function() {
    if (!current_user_can('manage_options')) {
        wp_die('You can not manage options.', 'Unauthorized User', 401);
    }
    check_admin_referer('wovax-idx-shortcode-sort');
    if(
        !array_key_exists('wovax-idx-shortcode-id', $_POST) ||
        intval($POST['wovax-idx-shortcode-id']) < 1
    ) {
        wp_redirect(admin_url('admin.php?page=wovax_idx_shortcodes'), 303);
    }
    $id      = intval($_POST['wovax-idx-shortcode-id']);
    $setting = new Wovax\IDX\Settings\ShortcodeSettings($id);
    if(array_key_exists('wovax-idx-default-sort-order', $_POST)) {
        $setting->setSortOrder($_POST['wovax-idx-default-sort-order']);
    }
    // Begin updating settings
    wp_redirect(admin_url('admin.php?page=wovax_idx_shortcodes&tab=sorting&action=update&id='.$id), 303);
});

add_action('wp_enqueue_scripts', function() {
    $opts = new Wovax\IDX\Settings\InitialSetup;
    $listings_page_id = $opts->detailPage();
    $listtrac_id = $opts->listTracId();
    if(empty($listings_page_id)) {
        return FALSE;
    }
    if(empty($listtrac_id)) {
        return FALSE;
    }
    if(!wp_script_is('jquery', 'done')) {
        wp_enqueue_script('jquery');
    }
    if(is_page($listings_page_id)) {
        wp_enqueue_script('list-trac', 'http://code.listtrac.com/monitor.ashx?acct='.$listtrac_id, array('jquery'));
        return TRUE;
    }
});
