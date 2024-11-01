<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

use Wovax\IDX\Utilities\ListingLoader;
use Wovax\IDX\Settings\ShortcodeSettings;

function wovax_idx_get_content_listings_grid($shortcode_details, $query_restrictions, $shortcode_type, $api_key) {
    // Global variable for user info
    global $current_user;
    $view_option   = 'grid';
    $fav_avail     = get_option('wovax-idx-settings-users-favorites');
    $shortcode_id = $shortcode_details[0]->id;
    if($shortcode_type != 'favorites') {
        $fields_array = array();
        foreach ($query_restrictions as $key => $value) {
            array_push($fields_array, $value->field);
        }
        $feed_rules = wovax_idx_get_rules_by_shortcode($fields_array, $shortcode_id, 'listing_embed');
    }

  wp_register_script('wovax-idx-feed-js', WOVAX_PLUGIN_URL . 'admin/assets/js/wovax-idx-feed-general.js', array(
    'jquery'
  ) , '1.0', true);

  // Set variable in order to avoid error on calling
  $user_fav_properties = array();

  // Get user information to verify email, login, and ID
  if (is_user_logged_in()) {
    get_currentuserinfo();
    $user_id = $current_user->data->ID;
    $user_favorites = get_user_meta($user_id, 'wovax-idx-favorites', true);
    $user_fav_properties = json_decode($user_favorites, true);
    // Sets favorites on user_meta in order to save favorite properties
    if(!is_array($user_fav_properties)) {
      update_user_meta($user_id, 'wovax-idx-favorites', json_encode(array()));
      $user_fav_properties = array();
    }
  }

    // Get sort option for calling data
    $sort_option = (new ShortcodeSettings($shortcode_id))->sortOrder();
    if(
        array_key_exists('wovax-idx-select-sort', $_GET) &&
        in_array($_GET['wovax-idx-select-sort'], ShortcodeSettings::allSortValues(), TRUE)
    ) {
        $sort_option = $_GET['wovax-idx-select-sort'];
    } else { // Should not have todo this, but this use the $_GET is used else where.
        $_GET['wovax-idx-select-sort'] = $sort_option;
    }

  $pagination_setting = $shortcode_details[0]->pagination;
  $shortcode_details[0]->pagination = 'yes';
  $results_per_page = ($shortcode_details[0]->per_page)? $shortcode_details[0]->per_page : 12;

  // Get data depending on shortcode type
  if(array_key_exists('wovax-idx-user-favorites', $_GET) && $_GET['wovax-idx-user-favorites'] === 'true') {
    $data = wovax_idx_get_list_buildings_by_favorites($shortcode_details, $user_fav_properties, $sort_option, $view_option, $api_key);
  } else {
    switch($shortcode_type) {
      case 'rules':
          $data = wovax_idx_get_list_buildings_by_rules($shortcode_details, $query_restrictions, $sort_option, $view_option, $api_key, $feed_rules);
          break;
      case 'favorites':
          $data = wovax_idx_get_list_buildings_by_favorites($shortcode_details, $user_fav_properties, $sort_option, $view_option, $api_key);
          break;
      default:
          $data = wovax_idx_get_list_buildings_by_filters($shortcode_details, $query_restrictions, $sort_option, $view_option, $api_key, $feed_rules);
          break;
    }
  }
  

  if (is_array($data) && $data['total']) {

    // =================================================
    if($pagination_setting != 'yes' && $data['total'] > $results_per_page) {
      $total_prop = $results_per_page;
    } else {
      $total_prop = $data['total'];
    }
    if( !empty( $results_per_page ) ) {
      $max_pages = ceil($total_prop/$results_per_page);
     } else {
      $max_pages = 1;
     }
    $data = $data['data'];
    $pagination_html = "";

    // =================================================
    // If user is not logged in, then he will in the modal.
    if (!is_user_logged_in()) {
      if (isset($_POST['email']) && isset($_POST['pass'])) {
        wovax_idx_custom_login();
      }
    }

    if ( ($shortcode_details[0]->pagination == 'yes' && !empty($results_per_page)) ) {
      /* pagination */
      $actual_link = wovax_idx_get_url();
      $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
      $paged_next = $paged + 1;
      $paged_prev = $paged - 1;

      // Change page_option values to verify next page;
      $page_option = filter_var_array($_GET['extra_params'], FILTER_SANITIZE_STRING);
      $page_option[0]['offset'] = ($paged_next - 1) * filter_var($_GET['extra_params'][0]['limit'], FILTER_SANITIZE_STRING);
      $pagination_html = ($paged < $max_pages || $paged > 1) && ($pagination_setting == 'yes') ? '<div class="wx-nav-links">' : '';

      // if page is over 1 'prev' option is available
      if ($paged > 1 && $pagination_setting == 'yes') {
        $pagination_html.= '<a href="' . esc_url($actual_link . '&paged=' . $paged_prev) . '" id="wovax-idx-page-previous" >&lt; Previous |</a>';
      }

      if ( $paged_next <= $max_pages && $pagination_setting == 'yes') {
        $pagination_html.= '<a href="' . esc_url($actual_link . '&paged=' . $paged_next) . '" id="wovax-idx-page-next" >| Next &gt;</a>';
      }

      $pagination_html.= ($pagination_setting == 'yes')?'</div>':'';
    }
    $current_view = 'grid';
    $shortcode_views = array(
      'grid_view' => $shortcode_details[0]->grid_view,
      'map_view' => $shortcode_details[0]->map_view
    );
    $output = '';
    if($shortcode_details[0]->action_bar === 'yes') {
      $action_bar_html = wovax_idx_get_action_bar($current_view, $shortcode_type, $shortcode_views, $total_prop, $sort_option);
      $output .= $action_bar_html;
    }
    $output .= '<input type="hidden" name="session_stat" value="' . esc_attr(is_user_logged_in()) . '">';
    $listing_grid_html = wovax_idx_get_listing_grid_html($data, $fav_avail, $user_fav_properties);
    $output .= $listing_grid_html;
    $output .= $pagination_html;
    $output .= '<script>
            function wovax_idx_get_grid_view_search(event){
              event.preventDefault();
              jQuery("input:hidden[name=wovax-idx-view-option]").val("map");
              jQuery("#form-tab-1").submit();
            }
            function wovax_idx_get_grid_view_listing(event){
              event.preventDefault();
              jQuery("input:hidden[name=wovax-idx-view-option]").val("map");
              jQuery("#wovax-idx-view").submit();
            }
            function wovax_idx_submit_favorites_button(event){
              event.preventDefault();
              jQuery("input:hidden[name=wovax-idx-user-favorites]").val("true");
              jQuery("#wovax-idx-favorites-form").submit();
            }
            function wovax_idx_submit_favorites_button_search(event){
              event.preventDefault();
              jQuery("input:hidden[name=wovax-idx-user-favorites]").val("true");
              jQuery("#form-tab-1").submit();
            }
						function wovax_idx_go_listing_details(e, url){
						    if(jQuery(e.target).attr("class") == "toggle-heart" || jQuery(e.target).attr("id") == "toggle-heart-section"){
						        return;
						    }
						    location.href = url;
						}
					</script>';
    return $output;
  }
  else {
    if (is_array($data)) {
      $data = "0 results returned";
    }
    return '<br />
			  <div class=warning>
    			<p>' . esc_html($data) . '</p>
			  </div>
			  <style media=screen type=text/css>
			  	.warning {
			  		font-size: 13px;
					  color: #333;
					  padding-top: 20px;
					  padding-bottom: 1px;
          }
        </style>';
  }
}
