<?php
use Wovax\IDX\Settings\ShortcodeSettings;
use Wovax\IDX\API\WovaxConnect;
use Wovax\IDX\Utilities\ListingLoader;

class wovax_idx_listings_controller {
	public function __construct() {
		$this->namespace = '/wovax/v1';
		$this->resource = 'idx';
	}

	public function init() {
		add_action('rest_api_init', array($this, 'register_routes'));
	}

	public function register_routes() {
		register_rest_route($this->namespace, '/' . $this->resource . '/get-listing-details',
			array(
				'methods' => 'GET',
				// 'permission_callback' => array($this, 'check_is_admin'),
				'callback' => array($this, 'get_listing_details')
		));

		register_rest_route($this->namespace, '/' . $this->resource . '/get-search-results',
			array(
				'methods' => 'GET',
				// 'permission_callback' => array($this, 'check_is_admin'),
				'callback' => array($this, 'get_search_results')
		));

		register_rest_route($this->namespace, '/' . $this->resource . '/get-listing-embed',
			array(
				'methods' => 'GET',
				// 'permission_callback' => array($this, 'check_is_admin'),
				'callback' => array($this, 'get_listing_embed')
		));

		register_rest_route($this->namespace, '/' . $this->resource . '/get-search-form',
			array(
				'methods' => 'GET',
				// 'permission_callback' => array($this, 'check_is_admin'),
				'callback' => array($this, 'get_search_form')
		));

		register_rest_route($this->namespace, '/' . $this->resource . '/get-shortcode-details',
			array(
				'methods' => 'GET',
				// 'permission_callback' => array($this, 'check_is_admin'),
				'callback' => array($this, 'get_shortcode_details')
		));
		
		register_rest_route($this->namespace, '/' . $this->resource . '/get-user-favorites',
			array(
				'methods' => 'GET',
				// 'permission_callback' => array($this, 'check_is_admin'),
				'callback' => array($this, 'get_user_favorites')
		));

		register_rest_route($this->namespace, '/' . $this->resource . '/save-user-favorite',
			array(
				'methods' => 'GET',
				// 'permission_callback' => array($this, 'check_is_admin'),
				'callback' => array($this, 'save_user_favorite')
		));

		register_rest_route($this->namespace, '/' . $this->resource . '/remove-user-favorite',
			array(
				'methods' => 'GET',
				// 'permission_callback' => array($this, 'check_is_admin'),
				'callback' => array($this, 'remove_user_favorite')
		));

	}

	public function get_search_form() {
		global $wpdb;
		$api_key = wovax_idx_get_validation_token();
		if(array_key_exists('wovax-idx-shortcode-id', $_GET)) {
			$search_form_shortcode_id = filter_var(stripslashes($_GET['wovax-idx-shortcode-id']) , FILTER_SANITIZE_STRING);
		} else {
			return array(
				'message' => 'Shortcode ID must be provided'
			);
		}
		$shortcode_details = wovax_idx_get_shortcode_by_id($search_form_shortcode_id) [0]->order_section;
  		$shortcode_filters = wovax_idx_find_shortcode_filters_by_id($search_form_shortcode_id, $shortcode_details);
		$fields_array = array();
		foreach ($shortcode_filters as $key => $value) {
			if($value->filter_type == 'select'){
				array_push($fields_array, $value->field);
			}
		}
		$feed_ids = wovax_idx_get_feeds_by_shortcode($search_form_shortcode_id);
  		$class_ids = wovax_idx_get_class_ids_by_shortcode($search_form_shortcode_id);
  		$rules = wovax_idx_get_rules_by_shortcode($fields_array, $search_form_shortcode_id, 'search_form');
		if(!empty($fields_array)) {
			$values = wovax_idx_get_data_filter($shortcode_filters, $feed_ids, $api_key, $rules, $class_ids);
			if (is_array($values)) {
				$values = $values[1];
			} else {
				$error_message = 'No filter values returned from API for shortcode id ' . $id;
				error_log($error_message);
				$values = array();
			}
		} else {
			$values = array();
		}
		if(!empty($search_form_shortcode_id)) {
			$search_form = array();
			$search_shortcodes_sql = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}wovax_idx_shortcode_filters` WHERE `id_shortcode` = %d", $search_form_shortcode_id);
			$search_data = $wpdb->get_results($search_shortcodes_sql);
			$search_order_sql = $wpdb->prepare("SELECT `order_section` FROM `{$wpdb->prefix}wovax_idx_shortcode` WHERE `id` = %d", $search_form_shortcode_id);
			$search_order = $wpdb->get_var($search_order_sql);
			$search_order = json_decode($search_order);
			foreach($search_data as $filter) {
				$id = $filter->id_filter;
				unset($filter->date);
				unset($filter->id_field);
				unset($filter->id_filter);
				foreach($values as $key => $options) {
					if($key === $filter->field) {
						$select_options = array();
						foreach($options as $object) {
							$select_options[] = $object->alias_id;
						}
						$filter->filter_options = $select_options;
					}
				}
				$search_form[$id] = $filter;
			}
			$search_return = array();
			if(!empty($search_order)) {
				foreach($search_order as $filter_id) {
					$search_return[$filter_id] = $search_form[$filter_id];
				}
			} else {
				$search_return = $search_form;
			}
		} else {
			$search_return = array(
				'message' => 'search form not found'
			);
		} 
		
		return $search_return;
	}

	public function get_listing_embed() {
		global $wpdb;
		if(array_key_exists('wovax-idx-shortcode-id', $_GET)) {
			$shortcode_id = filter_var(stripslashes($_GET['wovax-idx-shortcode-id']) , FILTER_SANITIZE_STRING);
		} else {
			return array(
				'message' => 'Shortcode ID must be provided'
			);
		}
		
		$shortcode_data = wovax_idx_get_shortcode_by_id($shortcode_id);
		$view_option = 'grid';
		$api_key = wovax_idx_get_validation_token();
		if($shortcode_data[0]->type == 'listings') {
			$rules = wovax_idx_get_rule_by_shortcode_id($shortcode_id);
			$fields_array = array();
        	foreach ($rules as $key => $value) {
            	array_push($fields_array, $value->field);
        	}
        	$feed_rules = wovax_idx_get_rules_by_shortcode($fields_array, $shortcode_id, 'listing_embed');
			$sort_option = (new ShortcodeSettings($shortcode_id))->sortOrder();

		} else {
			return array(
				'message' => 'Shortcode provided should be a listing embed shortcode'
			);
		}
		// Pass in any additional fields for return beyond the default as a comma separated list.
		if(array_key_exists('wovax-idx-extra-fields', $_GET)) {
			$extra_fields = explode(',', $_GET['wovax-idx-extra-fields']);
		} else {
			$extra_fields = array();
		}
		$data = wovax_idx_get_list_buildings_by_rules($shortcode_data, $rules, $sort_option, $view_option, $api_key, $feed_rules, $extra_fields);
		$data['data'] = (array) $data['data'];
		foreach($data['data'] as $listing) {
			$listing_permalink = ListingLoader::buildURL($listing->class_id, $listing->id, $listing);
			$listing->permalink = $listing_permalink;
		}
		return $data;
	}

	public function get_search_results() {
		global $wpdb;
		if(array_key_exists('wovax-idx-shortcode-id', $_GET)) {
			$shortcode_id = filter_var(stripslashes($_GET['wovax-idx-shortcode-id']) , FILTER_SANITIZE_STRING);;
		} else {
			return array(
				'message' => 'Shortcode ID must be provided'
			);
		}
		$shortcode_data = wovax_idx_get_shortcode_by_id($shortcode_id);
		$view_option = 'grid';
		$api_key = wovax_idx_get_validation_token();
		if($shortcode_data[0]->type == 'search_form') {
			$filters = wovax_idx_get_filter_by_shortcode_id($shortcode_id);
			$fields_array = array();
        	foreach ($filters as $key => $value) {
            	array_push($fields_array, $value->field);
        	}
			//For some reason the rules function requires the type to be listing embed
        	$feed_rules = wovax_idx_get_rules_by_shortcode($fields_array, $shortcode_id, 'listing_embed');
			$sort_option = (new ShortcodeSettings($shortcode_id))->sortOrder();

		} else {
			return array(
				'message' => 'Shortcode provided should be a search form shortcode'
			);
		}
		// Pass in any additional fields for return beyond the default as a comma separated list.
		if(array_key_exists('wovax-idx-extra-fields', $_GET)) {
			$extra_fields = explode(',', $_GET['wovax-idx-extra-fields']);
		} else {
			$extra_fields = array();
		}
		$data = wovax_idx_get_list_buildings_by_rules($shortcode_data, $rules, $sort_option, $view_option, $api_key, $feed_rules, $extra_fields);
		$data['data'] = (array) $data['data'];
		foreach($data['data'] as $listing) {
			$listing_permalink = ListingLoader::buildURL($listing->class_id, $listing->id, $listing);
			$listing->permalink = $listing_permalink;
		}
		return $data;
	}

	public function get_listing_details() {
		global $wpdb;
		$feed_id_array = array();
		if(array_key_exists('wovax-idx-feed-id', $_GET)) {
			$feed_id = filter_var(stripslashes($_GET['wovax-idx-feed-id']) , FILTER_SANITIZE_STRING);
		} else {
			return array(
				'message' => 'Feed ID must be provided'
			);
		}
		if(array_key_exists('wovax-idx-mls-id', $_GET)) {
			$mls_number = filter_var(stripslashes($_GET['wovax-idx-mls-id']) , FILTER_SANITIZE_STRING);
		} else {
			return array(
				'message' => 'MLS # must be provided'
			);
		}
		$feed_id_array[] = $feed_id;
		$idx_api = WovaxConnect::createFromOptions();
		$fields = array();
    	$feed_info = $idx_api->getFeedDetails($feed_id_array);
    	foreach($feed_info as $field) {
        	$fields[] = $field['default_alias'];
    	}
		$fields = array_unique($fields);
		$data = $idx_api->getListingDetails(
            $feed_id,
            $mls_number,
            $fields,
            'all'
        );
		if(empty($data)) {
			return array(
				'message' => 'listing detail error'
			);
		} else {
			return $data;
		}
	}
	
	public function get_shortcode_details () {
		global $wpdb;

		if(array_key_exists('wovax-idx-shortcode-id', $_GET)) {
			$shortcode_id = filter_var(stripslashes($_GET['wovax-idx-shortcode-id']) , FILTER_SANITIZE_STRING);
		} else {
			return array(
				'message' => 'Shortcode ID must be provided'
			);
		}
		$shortcode_details = wovax_idx_get_shortcode_by_id($shortcode_id);
		if(!empty($shortcode_details)) {
			unset($shortcode_details[0]->author);
			unset($shortcode_details[0]->date);
			return $shortcode_details;
		} else {
			return array(
				'message' => 'Invalid Shortcode ID'
			);
		}
		
	}

	public function get_user_favorites() {
		global $wpdb;
		if(array_key_exists('wovax-idx-user-id', $_GET)) {
			$user_id = filter_var(stripslashes($_GET['wovax-idx-user-id']) , FILTER_SANITIZE_STRING);
		} else {
			return array(
				'message' => 'User ID must be provided'
			);
		}
		if(array_key_exists('wovax-idx-shortcode-id', $_GET)) {
			$shortcode_id = filter_var(stripslashes($_GET['wovax-idx-shortcode-id']) , FILTER_SANITIZE_STRING);
		} else {
			return array(
				'message' => 'Shortcode ID must be provided'
			);
		}
		$shortcode_details = wovax_idx_get_shortcode_by_id($shortcode_id);
		$sort_option = (new ShortcodeSettings($shortcode_id))->sortOrder();
		$view_option = 'grid';
		$api_key = wovax_idx_get_validation_token();
		$user_favorites = get_user_meta($user_id, 'wovax-idx-favorites', true);
    	$user_fav_properties = json_decode($user_favorites, true);
    	// Sets favorites on user_meta in order to save favorite properties
    	if(!is_array($user_fav_properties)) {
      		update_user_meta($user_id, 'wovax-idx-favorites', json_encode(array()));
      		$user_fav_properties = array();
    	}

		$data = wovax_idx_get_list_buildings_by_favorites($shortcode_details, $user_fav_properties, $sort_option, $view_option, $api_key);

		return $data;

	}

	public function save_user_favorite() {
		global $wpdb;
		$favorite_array = array();
		if(array_key_exists('wovax-idx-user-id', $_GET)) {
			$user_id = $_GET['wovax-idx-user-id'];
		} else {
			return array(
				'message' => 'User ID must be provided'
			);
		}
		if(array_key_exists('wovax-idx-feed-id', $_GET)) {
			$favorite_feed_id = filter_var(stripslashes($_GET['wovax-idx-feed-id']) , FILTER_SANITIZE_STRING);
		} else {
			return array(
				'message' => 'Feed ID must be provided'
			);
		}
		if(array_key_exists('wovax-idx-mls-id', $_GET)) {
			$favorite_mls_id = filter_var(stripslashes($_GET['wovax-idx-mls-id']) , FILTER_SANITIZE_STRING);
		} else {
			return array(
				'message' => 'MLS # must be provided'
			);
		}
  		$user_meta = get_user_meta($user_id);
		$favorites = $user_meta['wovax-idx-favorites'][0];
		$new_fav = array( $favorite_feed_id, $favorite_mls_id );
		if ($favorites != null) {
			$favorite_array = (array)json_decode($favorites);
		}

		if (!in_array($new_fav, $favorite_array)) {
			array_push($favorite_array, $new_fav);
		}

		$favorite_array = json_encode($favorite_array);
		update_user_meta($user_id, 'wovax-idx-favorites', $favorite_array);
		return array(
			'success' => 'Success'
		);
	}

	public function remove_user_favorite() {
		$fav_array = array();
		if(array_key_exists('wovax-idx-user-id', $_GET)) {
			$user_id = $_GET['wovax-idx-user-id'];
		} else {
			return array(
				'message' => 'User ID must be provided'
			);
		}
		if(array_key_exists('wovax-idx-feed-id', $_GET)) {
			$favorite_feed_id = filter_var(stripslashes($_GET['wovax-idx-feed-id']) , FILTER_SANITIZE_STRING);
		} else {
			return array(
				'message' => 'Feed ID must be provided'
			);
		}
		if(array_key_exists('wovax-idx-mls-id', $_GET)) {
			$favorite_mls_id = filter_var(stripslashes($_GET['wovax-idx-mls-id']) , FILTER_SANITIZE_STRING);
		} else {
			return array(
				'message' => 'MLS # must be provided'
			);
		}
		$user_meta = get_user_meta($user_id);
		$favorites = $user_meta['wovax-idx-favorites'][0];
		$remove_favorite = array( $favorite_feed_id, $favorite_mls_id );
		$fav_array = (array)json_decode($favorites);
		$key = array_search($remove_favorite, $fav_array);
		unset($fav_array[$key]);
		$fav_array = array_values($fav_array);
		$fav_array = json_encode($fav_array);
		update_user_meta($user->ID, 'wovax-idx-favorites', $fav_array);
		return array(
			'success' => 'Success'
		);
	}

}