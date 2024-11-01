<?php
namespace Wovax\IDX\Settings;
if (defined('ABSPATH') === FALSE) {
    exit();// Exit via direct access.
}

Use Wovax\IDX\Utilities\ArrayType;
Use Wovax\IDX\Utilities\StringType;
Use Wovax\IDX\Utilities\StructType;

class SearchAppearance extends Options {
    const OPT_DESCRIPTION = 'description-formats';
    const OPT_TITLE       = 'title-formats';
    const OPT_URL         = 'url-formats';
    public function __construct() {
        $this->addToPrefix('search-appearance');

        $this->add(self::OPT_DESCRIPTION, new ArrayType(
            new ArrayType(new StringType(), array('Description')),
            array('global' => array('Description')),
            FALSE
        ));

        $this->add(self::OPT_URL, new ArrayType(
            new ArrayType(new StringType(), array('MLS Number', 'Street Address')),
            array('global' => array('MLS Number', 'Street Address')),
            FALSE
        ));

        $type_san = function($val) {
            return ($val == 'divider') ? 'divider' : 'value';
        };
        $default = array(
            array('type' => 'value',   'value' => 'Street Address'),
            array('type' => 'divider', 'value' => 'line-break'),
            array('type' => 'value',   'value' => 'City'),
            array('type' => 'divider', 'value' => 'comma'),
            array('type' => 'value',   'value' => 'State'),
            array('type' => 'value',   'value' => 'Zip Code')
        );
        $this->add(self::OPT_TITLE, new ArrayType(
            new ArrayType(
                new StructType(
                    ['type', new StringType('value', $type_san)],
                    ['value', new StringType()]
                ),
                $default
            ),
            array('global' => $default),
            FALSE
        ));
    }
    public function getFeedDescriptionFormat($class_id) {
        return $this->getFormat(self::OPT_DESCRIPTION, $class_id);
    }
    public function getFeedTitleFormat($class_id) {
        return $this->getFormat(self::OPT_TITLE, $class_id);

    }
    public function getFeedUrlFormat($class_id) {
        return $this->getFormat(self::OPT_URL, $class_id);
    }
    public function setFeedDescriptionFormat($class_id, $fields) {
        return $this->setFormat(self::OPT_DESCRIPTION, $fields, $class_id);
    }
    public function setFeedTitleFormat($class_id, $format) {
        $this->setTitleFormat($format, $class_id);
    }
    public function setFeedUrlFormat($class_id, $fields) {
        return $this->setFormat(self::OPT_URL, $fields, $class_id);
    }
    public function getGlobalDescriptionFormat() {
        return $this->getFormat(self::OPT_DESCRIPTION);
    }
    public function getGlobalTitleFormat() {
        return $this->getFormat(self::OPT_TITLE);
    }
    public function getGlobalUrlFormat() {
        return $this->getFormat(self::OPT_URL);
    }
    public function setGlobalDescriptionFormat($fields) {
        return $this->setFormat(self::OPT_DESCRIPTION, $fields);
    }
    public function setGlobalTitleFormat($format) {
        $this->setTitleFormat($format);
    }
    public function setGlobalUrlFormat($fields) {
        return $this->setFormat(self::OPT_URL, $fields);
    }
    public function setTitleFormat($format, $class_id = 0) {
        if(!is_array($format)) {
            return FALSE;
        }
        $dividers = array('comma', 'dash', 'forward-slash', 'line-break');
        $clean = array();
        foreach($format as $entry) {
            if(
                !isset($entry['type'])     ||
                !is_string($entry['type']) ||
                !isset($entry['value'])    ||
                !is_string($entry['value'])
            ) {
                    continue;
            }
            $val = trim($entry['value']);
            if(strlen($val) < 1) {
                continue;
            }
            $item = array();
            switch($entry['type']) {
                case 'divider':
                    if(!in_array($val, $dividers, TRUE)) {
                        break;
                    }
                    $item['type']  = 'divider';
                    $item['value'] = $val;
                    break;
                case 'value':
                    $item['type']  = 'value';
                    $item['value'] = $val;
                    break;
            }
            if(count($item) > 0) {
                $clean[] = $item;
            }
        }
        if(count($clean) < 1) {
            return FALSE;
        }
        return $this->setFormat(self::OPT_TITLE, $clean, $class_id);
    }
    private function getFormat($format_name, $class_id = 0) {
        $value = $this->get($format_name);
        if($class_id > 0 && array_key_exists('id-'.$class_id, $value)) {
            return array_values($value['id-'.$class_id]);
        }
        return array_values($value['global']);
    }
    private function setFormat($format_name, $fields, $class_id = 0) {
        if(!is_array($fields)) {
            return FALSE;
        }
        $fields = array_values($fields);
        $value  = $this->get($format_name);
        $index  = 'global';
        if($class_id > 0) {
            $index = 'id-'.$class_id;
        }
        $value[$index] = $fields;
        $this->set($format_name, $value);
        return TRUE;
    }
}