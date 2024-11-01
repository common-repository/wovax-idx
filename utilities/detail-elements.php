<?php
namespace Wovax\IDX\Utilities;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

use Serializable;

// Abstract classes
abstract class Element implements Serializable {
    private $vars = array();
    protected function appendVariableList($var, $value, $unique = FALSE) {
        $cur_vals = $this->getVariableList($var);
        if($unique && !in_array($value, $cur_vals, TRUE)) {
            $cur_vals[] = $value;
        } else {
            $cur_vals[] = $value;
        }
        $this->setVariable($var, $cur_vals);
    }
    protected function getVariable($var) {
        $value = NULL;
        if(array_key_exists($var, $this->vars)) {
            $value = $this->vars[$var];
        }
        return $value;
    }
    protected function getVariableList($var) {
        $vals = $this->getVariable($var);
        if(!is_array($vals)) {
            return array();
        }
        return $vals;
    }
    protected function setVariable($var, $value) {
        $this->vars[$var] = $value;
    }
    protected function setVariableList($var, $list, $unique = FALSE) {
        if(!is_array($list)) {
            $list = array();
        }
        if($unique) {
            $list = array_unique($list);
        }
        $this->setVariable($var, $list);
    }
    protected function unsetVariable($var) {
        unset($this->vars[$var]);
    }
    protected function unsetVariableListValue($var, $value) {
        $vals     = $this->getVariableList($var);
        $new_vals = array();
        foreach($vals as $cur_value) {
            if($cur_val !== $value) {
                $new_vals[] = $cur_val;
            }     
        }
        $this->setVariable($var, $new_vals);
    }
    abstract public function generateHTML($listing);
    public function serialize() {
        return serialize($this->vars);
    }
    public function unserialize($data) {
        $this->vars = unserialize($data);
    }
}

abstract class Field extends Element {
    public function __construct($field) {
        $this->setField($field);
    }
    public function getField() {
        return $this->getVariable('field');
    }
    public function setField($field) {
        $field = trim($field);
        $this->setVariable('field', $field);
    }
    protected function listingHasField($listing) {
        $field = $this->getField();
        if(!array_key_exists($field, $listing)) {
            return FALSE;
        }
        if(strlen($listing[$field]) < 1) {
            return FALSE;
        }
        return TRUE;
    }
}

class Divider extends Element {
    public function __construct($classes) {
        if(empty($classes)) {
            array_push($classes, 'wovax-idx-listing-details-divider');
        }
        $this->setVariableList('classes', $classes);
    }
    public function addClass($class) {
        $class = trim($class);
        $this->appendVariable('classes', $class, TRUE);
    }
    public function generateHTML($listing) {
        $html = '<div';
        if(count($this->getClasses()) > 0) {
            $html .= ' class="'.implode(' ', $this->getClasses()).'"';
        }
        $html .= "></div>\n";
        return $html;
    }
    public function getClasses() {
        return array_filter($this->getVariableList('classes'), 'esc_attr');
    }
}

//implementations
class GoogleMap extends Element {
    public function __construct($height, $classes = array()) {
        if(empty($classes)) {
            array_push($classes, 'wovax-idx-listing-details-map');
        }
        $this->setVariableList('classes', $classes);
        $this->setHeight($height);

    }
    public function addClass($class) {
        $class = trim($class);
        $this->appendVariable('classes', $class, TRUE);
    }
    public function getClasses() {
        return array_filter($this->getVariableList('classes'), 'esc_attr');
    }
    public function setHeight($height) {
        if($height < 1) {
            $height = 400;
        }
        $this->setVariable('height', $height);
    }
    public function getHeight() {
        $height = $this->getVariable('height');
        if($height < 1) {
            $height = 400;
        }
        return $height;
    }
    public function generateHTML($listing) {
        // Check if values are set
        if(
            !array_key_exists('Latitude', $listing) ||
            !array_key_exists('Longitude', $listing)
        ) {
            return '';
        }
        $latitude  = trim($listing['Latitude']);
        $longitude = trim($listing['Longitude']);
        if(strlen($latitude) < 1 || strlen($longitude) < 1) {
            return '';
        }
        // Done checking if values are set
        $id   = "wovax_idx_map_".$this->generateID();
        $html = '<div';
        if(count($this->getClasses()) > 0) {
            $html .= ' class="'.implode(' ', $this->getClasses()).'"';
        }
        $html .= ' id="'.$id.'"';
        $html .= ' style="width:100%; height:'.$this->getHeight().'px; margin-top:1em; margin-bottom:1em;">';
        $html .= "</div>\n";
        wp_add_inline_script('wovax-idx-listing-map-loader', "WovaxIDXMapInfo.add(\"$id\", $latitude, $longitude);");
        return $html;
    }
    // generates a random 40 bit number encoded in base 32
    // chance of collison should be 1 out of trillion assuming rng works
    private function generateID() {
        $bin_str = '';
        for($i = 0; $i < 5; $i++) {
            $bin_str .= pack('c', mt_rand(0, 0xFF));
        }
        $val = bin2hex($bin_str);
        return base_convert($val, 16, 32);
    }
}

class LabeledField extends Field {
    public function __construct($field, $label = '', $label_classes = array(), $value_classes = array()) {
        $this->setLabel($field);
        $this->setLabel($label);
        if(empty($value_classes)) {
            array_push($value_classes, 'wovax-idx-listing-details-field');
            array_push($value_classes, 'wovax-idx-listing-details-field-value');
        }
        if(empty($label_classes)) {
            array_push($label_classes, 'wovax-idx-listing-details-field');
            array_push($label_classes, 'wovax-idx-listing-details-field-label');
        }
        $this->setVariableList('label_classes', $label_classes, TRUE);
        $this->setVariableList('value_classes', $value_classes, TRUE);
        parent::__construct($field);
    }
    public function addLabelClass($class) {
        $class = trim($class);
        $this->appendVariable('label_classes', $class, TRUE);
    }
    public function addValueClass($class) {
        $class = trim($class);
        $this->appendVariable('value_classes', $class, TRUE);
    }
    public function generateHTML($listing) {
        if(!$this->listingHasField($listing)) {
            return '';
        }
        $html = "<div class='wovax-idx-field-wrapper'>";
        $html .= '<div';
        if(count($this->getLabelClasses()) > 0) {
            $html .= ' class="'.implode(' ', $this->getLabelClasses()).'"';
        }
        $html .= '>'.esc_html($this->getLabel())."</div>\n";
        $html .= '<div';
        if(count($this->getValueClasses()) > 0) {
            $html .= ' class="'.implode(' ', $this->getValueClasses()).'"';
        }
        $html .= '>'.esc_html($listing[$this->getField()])."</div>\n";
        $html .= "</div>";
        return $html;
    }
    public function getLabel() {
        return $this->getVariable('label');
    }
    public function getLabelClasses() {
        return array_filter($this->getVariableList('label_classes'), 'esc_attr');
    }
    public function getValueClasses() {
        return array_filter($this->getVariableList('value_classes'), 'esc_attr');
    }
    public function setLabel($label) {
        $label = trim($label);
        if(strlen($label) < 1) {
            return FALSE;
        }
        $this->setVariable('label', $label);
        return TRUE;
    }
}

class LabeledLink extends LabeledField {
    public function __construct($field, $label = '', $link_label = '', $label_classes = array(), $value_classes = array(), $link_classes = array()) {
        $this->setLinkLabel('View');
        $this->setLinkLabel($link_label);
        if(empty($link_classes)) {
            array_push($link_classes, 'wovax-idx-listing-details-link');  
        }
        $this->setVariableList('link_classes', $value_classes, TRUE);
        parent::__construct($field, $label, $label_classes, $value_classes);
    }
    public function addLinkClass($class) {
        $class = trim($class);
        $this->appendVariable('link_classes', $class, TRUE);
    }
    public function generateHTML($listing) {
        if(!$this->listingHasField($listing)) {
            return '';
        }
        // Check if value is a valid URL
        $url = $listing[$this->getField()];
        if(filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            return parent::generateHTML($listing);
        }
        $html = "<div class='wovax-idx-field-wrapper'>";
        $html .= '<div';
        if(count($this->getLabelClasses()) > 0) {
            $html .= ' class="'.implode(' ', $this->getLabelClasses()).'"';
        }
        $html .= '>'.esc_html($this->getLabel())."</div>\n";
        $html .= '<div';
        if(count($this->getValueClasses()) > 0) {
            $html .= ' class="'.implode(' ', $this->getValueClasses()).'"';
        }
        $html .= '>';
        $html .= '<a target="_blank"';
        if(count($this->getLinkClasses()) > 0) {
            $html .= ' class="'.implode(' ', $this->getLinkClasses()).'"';
        }
        $html .= " href=\"$url\">".$this->getLinkLabel()."</a>\n";
        $html .= "</div>\n";
        $html .= "</div>";
        return $html;
    }
    public function getLinkClasses() {
        return array_filter($this->getVariableList('link_classes'), 'esc_attr');
    }
    public function getLinkLabel() {
        return $this->getVariable('link_label');
    }
    public function setLinkLabel($label) {
        $label = trim($label);
        if(strlen($label) < 1) {
            return FALSE;
        }
        $this->setVariable('link_label', $label);
        return TRUE;
    }
}

class LabeledNumeric extends LabeledField {
    public function __construct($field, $label = '', $label_classes = array(), $value_classes = array()) {
        $this->showComma();
        $this->showDecimalPoint();
        $this->setDecimalCount(2);
        parent::__construct($field, $label, $label_classes, $value_classes);
    }
    public function hasComma() {
        return ($this->getVariable('comma') === TRUE);
    }
    public function hasDecimalPoint() {
        return ($this->getVariable('decimal_point') === TRUE);
    }
    public function hideDecimalPoint() {
        $this->setVariable('decimal_point', FALSE);
    }
    public function hideComma() {
        $this->setVariable('comma', FALSE);
    }
    public function generateHTML($listing) {
        if(!$this->listingHasField($listing)) {
            return '';
        }
        $field = $this->getField();
        $listing[$field] = $this->formatNumber($listing[$field]);
        return parent::generateHTML($listing);
    }
    public function getDecimalCount() {
        $dec = $this->getVariable('decimal_count');
        if($dec < 1) {
            $dec = 1;
        }
        return $dec;
    }
    public function setDecimalCount($count) {
        if($count < 1) {
            $count = 1;
        }
        $this->setVariable('decimal_count', $count);
    }
    public function showComma() {
        $this->setVariable('comma', TRUE);
    }
    public function showDecimalPoint() {
        $this->setVariable('decimal_point', TRUE);
    }
    protected function formatNumber($number) {
        $com = $this->hasComma() ? ',' : '';
        $cnt = $this->getDecimalCount();
        if(!$this->hasDecimalPoint()) {
            $cnt = 0;
        }
        return number_format($number, $cnt, '.', $com);
    }
}

class LabeledPrice extends LabeledNumeric {
    public function __construct($field, $label = '', $label_classes = array(), $value_classes = array()) {
        $this->setCurrencyLeft();
        parent::__construct($field, $label, $label_classes, $value_classes);
    }
    public function isCurrencyLeft() {
        return ($this->getVariable('currency_pos') === 'left');
    }
    public function isCurrencyRight() {
        return !$this->isCurrencyLeft();
    }
    public function generateHTML($listing) {
        $parent       = get_parent_class($this);
        $grand_parent = get_parent_class($parent);
        if(!$this->listingHasField($listing)) {
            return '';
        }
        $field = $this->getField();
        $listing[$field] = $this->formatNumber($listing[$field]);
        if($this->isCurrencyLeft()) {
            $listing[$field] = '$'.$listing[$field];
        } else {
            $listing[$field] .= '$';
        }
        return $grand_parent::generateHTML($listing);
    }
    public function setCurrencyLeft() {
        $this->setVariable('currency_pos', 'left');
    }
    public function setCurrencyRight() {
        $this->setVariable('currency_pos', 'right');
    }
}

class Paragraph extends Field {
    public function __construct($field, $classes = array()) {
        if(empty($classes)) {
            array_push($classes, 'wovax-idx-listing-details-description');
        }
        $this->setVariableList('classes', $classes);
        parent::__construct($field);
    }
    public function addClass($class) {
        $class = trim($class);
        $this->appendVariable('classes', $class, TRUE);
    }
    public function getClasses() {
        return array_filter($this->getVariableList('classes'), 'esc_attr');
    }
    public function generateHTML($listing) {
        if(!$this->listingHasField($listing)) {
            return '';
        }
        $html = '<div';
        if(count($this->getClasses()) > 0) {
            $html .= ' class="'.implode(' ', $this->getClasses()).'"';
        }
        $html .= '>'.esc_html($listing[$this->getField()])."</div>\n";
        return $html;
    }
}

class Spacer extends Element {
    public function __construct($height, $classes = array()) {
        if(empty($classes)) {
            array_push($classes, 'wovax-idx-listing-details-spacer');
        }
        $this->setVariableList('classes', $classes);
        $this->setHeight($height);

    }
    public function addClass($class) {
        $class = trim($class);
        $this->appendVariable('classes', $class, TRUE);
    }
    public function getClasses() {
        return array_filter($this->getVariableList('classes'), 'esc_attr');
    }
    public function setHeight($height) {
        if($height < 1) {
            $height = 80;
        }
        $this->setVariable('height', $height);
    }
    public function getHeight() {
        $height = $this->getVariable('height');
        if($height < 1) {
            $height = 80;
        }
        return $height;
    }
    public function generateHTML($listing) {
        $html = '<div';
        if(count($this->getClasses()) > 0) {
            $html .= ' class="'.implode(' ', $this->getClasses()).'"';
        }
        $html .= ' style="height:'.intval($this->getHeight()).'px;">';
        $html .= "</div>\n";
        return $html;
    }
}