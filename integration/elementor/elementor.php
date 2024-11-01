<?php
/*
 * Description : Integrate Wovax IDX with elementor page builder widgets.
 * Author      : Keith Cancel
 * Author Email: admin@keith.pro
 */

namespace Wovax\IDX\Integration\Elementor;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

define("WX_EL_FIELD_LIST",     "wovax_idx_elementor_fields");
define("WX_EL_FIELD_TRACKING", "wovax_idx_elementor_field_updates");

add_action( 'elementor/init', function() {
    add_action( 'elementor/widgets/widgets_registered', function() {
        require_once(__DIR__.'/field-widget.php');
        require_once(__DIR__.'/image-widget.php');
        require_once(__DIR__.'/map-widget.php');
        $set = new \Wovax\IDX\Settings\InitialSetup();
        // Don't register the widgets on non-listing details pages
        if($set->detailPage() != get_the_ID()) {
            return;
        }

        // call register_widget_type for each widget
        // Turns out though even we pass instance here some reason elementor
        // makes a new instance of the object it's self instead of using the
        // instance we pass this function.
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new FieldWidget() );
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new ImageWidget() );
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new MapWidget() );
    });
});

//add_action('wp_enqueue_scripts', function() {
add_action('elementor/frontend/after_enqueue_scripts', function() {
    wp_enqueue_style('wovax_idx_elementor', plugins_url('assets/css/defaults.css', __FILE__ ));
});

// Create a category for Wovax idx
add_action('elementor/elements/categories_registered', function($el_man) {
    $el_man->add_category(
		'wovax-idx',
		[
			'title' => 'Wovax IDX',
			'icon' => 'fa fa-plug',
		]
	);
});

add_action('wp_ajax_wovax_idx_elementor_track_field', function() {
    $id    = trim($_POST['widget_id']);
    $field = trim($_POST['field_val']);
    $set   = new \Wovax\IDX\Settings\InitialSetup();
    $pg_id = $set->detailPage();
    $last  = get_post_meta($pg_id, WX_EL_FIELD_TRACKING, true);
	
    if(empty($last) || !is_string($last)) {
        $last = array();
    } else {
        $last = json_decode($last, true);
    }
	if(!is_array($last)) {
		$last = array();
	}
	
	$last[$id] = $field;
	$last = json_encode($last);
	
	if(!is_string($last)) {
		wp_send_json_error(array(
			"reason" => "JSON Encoding failed!",
			"status" => "failed"
		));
		return;
	}
    update_post_meta($pg_id, WX_EL_FIELD_TRACKING, $last);
	
    wp_send_json_success(array(
        'saved'  => array(
            'last'      => $last,
			'page_id'   => $pg_id,
            'field'     => $field,
            'widget_id' => $id
        ),
        'status' => 'success'
    ));
});


add_action('elementor/editor/after_save', function() {
    $set      = new \Wovax\IDX\Settings\InitialSetup();
    $pg_id    = $set->detailPage();
    $last     = json_decode(get_post_meta($pg_id, WX_EL_FIELD_TRACKING, true), true);
    $date     = time();
    $threshold = 8 * 24 * 60; // Seconds in 8 days
    $threshold = $date - $threshold;

    if(!is_array($last)) {
        return;
    }
	
	$fields = array();
    foreach($last as $key => $value) {
        array_push($fields, $value);
    }
    $fields = array_unique($fields);
	
	
    $current = json_decode(get_post_meta($pg_id, WX_EL_FIELD_LIST, true), true);
    if(!is_array($current)) {
        $current = array();
    }
 
   $list = array();
   foreach($current as $key => $value) {
        if(in_array($value['field'], $fields)) {
            continue;
        }
        if($value['time'] < $threshold) {
            continue;
        }
        array_push($list, $value);
    }
	
    foreach($fields as $key => $value) {
        array_push($list, array(
            'field' => $value,
            'time'  => $date
        ));
    }

    delete_post_meta($pg_id, WX_EL_FIELD_TRACKING);
    update_post_meta($pg_id, WX_EL_FIELD_LIST, json_encode($list));
});

function get_elementor_fields() {
    $set      = new \Wovax\IDX\Settings\InitialSetup();
    $pg_id    = $set->detailPage();
    $current = json_decode(get_post_meta($pg_id, WX_EL_FIELD_LIST, true), true);
    if(!is_array($current)) {
        $current = array();
    }
 
   $list = array();
   foreach($current as $key => $value) {
        array_push($list, $value['field']);
    }
    return $list;
}