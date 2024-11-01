<?php
namespace Wovax\IDX\Shortcodes;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

use Wovax\IDX\API\WovaxConnect;
use Wovax\IDX\Settings\FeedDisplay;
use Wovax\IDX\Settings\InitialSetup;

class listingDetails extends Shortcode {
    private $listing       = NULL;
    private $display       = NULL;
    public function __construct() {
        $this->addStyle('slickcss', plugins_url('wovax-idx/assets/libraries/slick/slick.css'));
        $this->addScript('slickjs', plugins_url('wovax-idx/assets/libraries/slick/slick.min.js'), array(), FALSE, TRUE);
        $this->display = new FeedDisplay();
        parent::__construct('listing-details');
    }
    public function addListing($listing) {
        $this->listing = $listing;
    }
    public function changeFeedDisplay(FeedDisplay $display) {
        $this->display = $display;
    }
    private function displayPhotos() {
        if(!is_array($this->listing['photos_list']) ) {
            return '';
        }
        $photos = $this->listing['photos_list'];
        $thumbs = array();
        $mains  = array();
        $number = 1;
        foreach($photos as $photo) {
            $alt      = empty($photo['description']) ? "" : $photo['description'];
            $src      = 'https://cache.wovax.com/imgp.php?src='.esc_url($photo['location']);
            $thumbs[] = "<div><img src=\"$src&width=400&height=250&crop-to-fit&aro\" alt=\"$alt\"></div>\n";
            $mains[]  = "<div><img src=\"$src&width=1800&aro\" alt=\"$alt\"></div>\n";
            $number++;
        }
        if(count($mains) <= 1) {
            return $mains[0]."\n";
        }
        $html = "<div class='wovax-idx-slider-wrapper'>";
        $html .= '<section class="slider wovax-idx-slider no-select">'."\n";
        $html .= implode("\n", $mains);
        $html .= "</section>\n";

        $html .= '<section class="wovax-idx-slider-thumbnails slider no-select">'."\n";
        $html .= implode("\n", $thumbs);
        $html .= "</section>\n";
        $html .= "</div>";

        $html .= '<script>
                  jQuery(document).ready(function(){

                    jQuery(".wovax-idx-slider").slick({
                      asNavFor: ".wovax-idx-slider-thumbnails",
                      adaptiveHeight: true,
                      infinite: true,
                      slidesToShow: 1,
                      arrows: false,
                      fade: true,
                    });
                    jQuery(".wovax-idx-slider-thumbnails").slick({
                        asNavFor: ".wovax-idx-slider",
                        adaptiveHeight: true,
                        slidesToShow: 5,
                        slidesToScroll: 5,
                        infinite: true,
                        centerMode: false,
                        focusOnSelect: true,
                        arrows: true,
                    });
                  });
                </script>';
            return $html;
    }
    private function addMapLoader() {
        if(!$this->display->hasMap()) {
            return;
        }
        $key = (new InitialSetup())->googleMapsKey();
        $url = plugins_url('assets/js/map-loader.min.js', __FILE__);
        $icon_url_1 = WOVAX_PLUGIN_URL.'assets/graphics/spotlight-poi.png';
        $icon_url_2 = WOVAX_PLUGIN_URL.'assets/graphics/spotlight-poi_hdpi.png';
        $this->addScript('wovax-idx-listing-map-loader', $url);
        wp_add_inline_script('wovax-idx-listing-map-loader',"WovaxIDXMapInfo.setMarker(\"$icon_url_1\", \"$icon_url_2\");");
        $this->addScript('wovax-google-maps-library','https://maps.googleapis.com/maps/api/js?key='.$key.'&callback=wovax_idx_map_loader', 'wovax-idx-listing-map-loader');
    }
    protected function getContent($attr) {
        if(is_null($this->listing)) {
            return '';
        }
        $this->addMapLoader();
        $html  = $this->displayPhotos();
        $html .= "<div class='wovax-idx-listing-details-field-wrapper'>";
        foreach($this->display->getAllFormatting() as $element) {
            $html .= $element->generateHTML($this->listing);
        }
        $html .= "</div>";
        return $html;
    }
}
