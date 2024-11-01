<?php
namespace Wovax\IDX\Settings;
if (defined('ABSPATH') === FALSE) {
    exit();// Exit via direct access.
}

use Wovax\IDX\Utilities\Divider;
use Wovax\IDX\Utilities\Element;
use Wovax\IDX\Utilities\GoogleMap;
use Wovax\IDX\Utilities\LabeledField;
use Wovax\IDX\Utilities\LabeledLink;
use Wovax\IDX\Utilities\LabeledNumeric;
use Wovax\IDX\Utilities\LabeledPrice;
use Wovax\IDX\Utilities\Paragraph;
use Wovax\IDX\Utilities\Spacer;

class FeedDisplay {
    private $feed_id = 0;
    private $attr    = NULL;
    private $has_map = FALSE;
    private $format  = NULL;
    // Public methods
    public function __construct($feed_id = 0) {
        $this->feed_id = intval($feed_id);
        $this->getAllFormatting();
    }

    // Get default price display settings for the feed.
    public function defaultCurrencySymbolLeft() {
        return ($this->defaultCurrencySymbolRight() === FALSE);
    }
    public function defaultCurrencySymbolRight() {
        $attr = $this->getFeedAttributes();
        if(!empty($attr)) {
            return ($attr['currency'] === 'right');
        } else {
            return false;
        }
        
    }
    public function defaultPriceHasComma() {
        $attr  = $this->getFeedAttributes();
        $comma = TRUE;
        if(!empty($attr)){
            if($attr['format'] == 'decimals' || $attr['format'] == 'entire') {
                $comma = FALSE;
            }
        }
        return $comma;
    }
    public function defaultPriceHasDecimalPoint() {
        $attr    = $this->getFeedAttributes();
        $decimal = TRUE;
        if(!empty($attr)) {
            if($attr['format'] == 'miles' || $attr['format'] == 'entire') {
                $decimal = FALSE;
            }
        } else {
            $decimal = FALSE;
        }
        return $decimal;
    }
    // Field Display information
    public function getDisplayedFields() {
        $sql = 'SELECT `default_alias` FROM `%s` WHERE `id_feed` = %d AND `field_state` = 1 ORDER BY `order`';
        $sql = sprintf($sql, $this->getFieldsTableName(), $this->feed_id);
        $results = $GLOBALS['wpdb']->get_col($sql);
        if(count($results) < 1) {
            $results = self::getDefaultFields();
        }
        return $results;
    }
    public function getAllFormatting() {
        if(is_null($this->format) === FALSE) {
            return $this->format;
        }
        // Lots of errors if function is called without a feed id, so just return
        if($this->feed_id === 0) {
            return $this->format;
        }
        // This could be a lot simpler but the data is not stored in very useful way.
        // So this code convert to a simple way to format the data
        global $wpdb;
        $sql = 'SELECT `default_alias`, `alias_update`, `order` FROM `%s` WHERE `id_feed` = %d AND `field_state` = 1 ORDER BY `order`';
        $sql = sprintf($sql, $this->getFieldsTableName(), $this->feed_id);
        $fields = $wpdb->get_results($sql, ARRAY_A);
        if(!is_array($fields)) {
            $fields = array();
            foreach(self::getDefaultFields() as $index => $field) {
                $fields[] = array(
                    'order'         => $index,
                    'default_alias' => $field,
                    'alias_update'  => $field,
                );
            }
        }
        $formatting = $this->getFeedAttributes();
        // Being building format array
        $format = array();
        // Add divider data
        if(!empty($formatting)) {
            foreach($this->decodeInsanity($formatting['divider_data']) as $divider) {
                $pos     = intval($divider[1]);
                $classes = array_filter( explode( ' ', trim($divider[2]) ) );
                $format[$pos] = new Divider($classes);
            }
            // Add spacer data
            foreach($this->decodeInsanity($formatting['spacer_data']) as $spacer) {
                $pos     = intval($spacer[1]);
                $height  = intval($spacer[2]);
                $classes = array_filter( explode( ' ', trim($spacer[3]) ) );
                if($height < 1) {
                    $height = 80;
                }
                $format[$pos] = new Spacer($height, $classes);
            }
            // Add Virtual Tour
            foreach($this->decodeInsanity($formatting['link_data']) as $v_tour) {
                $pos       = intval($v_tour[1]);
                $link_text = trim($v_tour[2]);
                $label     = trim($v_tour[3]);
                $classes   = array_filter( explode( ' ', trim($v_tour[4]) ) );
                if(strlen($link_text) < 1) {
                    $link_text = 'View';
                }
                if(strlen($label) < 1) {
                    $label = 'Virtual Tour';
                }
                $format[$pos] = new LabeledLink(
                    'Virtual Tour',
                    $label,
                    $link_text,
                    $classes,
                    $classes
                );
            }
            // Add Map data
            $map_data = $this->decodeInsanity($formatting['map_data']);
            foreach($map_data as $map) {
                $pos     = intval($map[2]);
                $height  = intval($map[4]);
                $classes = array_filter( explode( ' ', trim($map[5]) ) );
                $format[$pos] = new GoogleMap($height, $classes);
                $this->has_map = TRUE;
            }
        }
        // Add field data
        $currency_right = $this->defaultCurrencySymbolRight();
        $decimal        = $this->defaultPriceHasDecimalPoint();
        $comma          = $this->defaultPriceHasComma();
        foreach($fields as $field_info) {
            $pos   = intval($field_info['order']);
            $field = trim($field_info['default_alias']);
            $label = trim($field_info['alias_update']);
            if(strlen($label) < 1) {
                $label = $field;
            }
            $data = NULL;
            switch($field) {
                case 'Description':
                    $data = new Paragraph($field);
                    break;
                case 'Price':
                    $data = new LabeledPrice($field, $label);
                    if($currency_right) {
                        $data->setCurrencyRight();
                    }
                    if(!$decimal) {
                        $data->hideDecimalPoint();
                    }
                    if(!$comma) {
                        $data->hideComma();
                    }
                    break;
                case 'Square Footage':
                    $data = new LabeledNumeric($field, $label);
                    $data->hideDecimalPoint();
                    break;
                case 'Virtual Tour':
                    $data = new LabeledLink($field, $label);
                    break;
                default:
                    $data = new LabeledField($field, $label);
                    break;
            }
            if($data instanceof Element) {
                $format[$pos] = $data;
            }
        }
        ksort($format);
        $this->format = array_values($format);
        return $this->format;
    }
    public function getRequiredFields() {
        return array_unique(array_merge(self::getDefaultFields(), $this->getDisplayedFields()));
    }
    public function hasMap() {
        return $this->has_map;
    }
    // Private Methods
    private function getFeedAttributes() {
        if(is_array($this->attr)) {
            return $this->attr;
        }
        global $wpdb;
        $sql = "SELECT `attributes` FROM `%s` WHERE `id_feed` = %d;";
        $sql = sprintf($sql, $this->getFormatTableName(), $this->feed_id);
        $this->attr = json_decode($wpdb->get_var($sql), TRUE);
        return $this->attr;
    }
    private function getFieldsTableName() {
        return $GLOBALS['wpdb']->base_prefix.'wovax_idx_feed_fields';
    }
    private function getFormatTableName() {
        return $GLOBALS['wpdb']->base_prefix.'wovax_idx_feeds';
    }
    // Not sure that it is insane, but it's really werid since it was already
    // encoded as JSON no idea why this odd encoding was used at all.
    private function decodeInsanity($str) {
        $data = explode('|-|', $str);
        $items = 0;
        foreach($data as $index => $val) {
            $data[$index] = explode(',', $val);
            if(count($data[$index]) > $items) {
                $items = count($data[$index]);
            }
        }
        $grouped = array();
        for($i = 0; $i < $items; $i++) {
            $item = array();
            foreach($data as $values) {
                $value  = '';
                if(isset($values[$i])) {
                    $value = $values[$i];
                }
                $item[] = $value;
            }
            $grouped[] = $item;
        }
        return $grouped;
    }
    // Public static methods
    public static function getDefaultFields() {
        return array(
            'Acres',
            'Bathrooms',
            'Bedrooms',
            'City',
            'Description',
            'Latitude',
            'Listing Agent',
            'Listing Office',
            'Longitude',
            'MLS Number',
            'Price',
            'Property Type',
            'State',
            'Status',
            'Street Address',
            'Square Footage',
            'Lot Size',
            'Zip Code',
        );
    }
}