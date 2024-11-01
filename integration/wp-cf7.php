<?php
namespace Wovax\IDX\Integration;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

/* Hook into the contact form 7 tags */
// Documentation https://contactform7.com/2015/01/10/adding-a-custom-form-tag/

add_action('wpcf7_init', function () {
    wpcf7_add_form_tag('wovax_idx_listing_data', function ($tag) {
		$requested_url  = is_ssl() ? 'https://' : 'http://';
    	$requested_url .= $_SERVER['HTTP_HOST'];
    	$requested_url .= $_SERVER['REQUEST_URI'];
		$requested_url = esc_attr($requested_url);
		$title         = esc_attr(get_the_title());
		$html  = '<input type="hidden" id="wovax_idx_url" name="wovax_idx_url" value="'.$requested_url.'">';
		$html .= '<input type="hidden" id="wovax_idx_title" name="wovax_idx_title" value="'.$title.'">';
        return $html;
    }, '');
});