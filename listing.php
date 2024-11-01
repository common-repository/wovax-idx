<?php
namespace Wovax\IDX;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

class Listing {
    private $feed_id    = '';
    private $resource_id = '';
    private $images     = array();
    private $fields     = array();
    public function __construct($arr = array()) {
        if( array_key_exists('photos_list', $arr) && is_array($arr['photos_list']) ) {
            foreach($arr['photos_list'] as $img) {
                $url = $img['location'];
                $alt = empty($arr['description']) ? "Photo for ".$arr['Street Address'] : $arr['description'];
                if(filter_var($url, FILTER_VALIDATE_URL)) {
                    $this->addImage($url, $alt);
                }
            }
        }
        if(array_key_exists('feed_id', $arr)) {
            $this->setResourceId($arr['feed_id']);
        }
        if(array_key_exists('resource_id', $arr)) {
            $this->setResourceId($arr['resource_id']);
        }
        unset($arr['photos_list']);
        unset($arr['feed_id']);
        unset($arr['resource_id']);
        $this->fields = $arr;
    }

    public function getFeedId() {
        return $this->feed_id;
    }
    public function setFeedId($id) {
        $this->feed_id = $id;
    }
    public function getResourceId() {
        return $this->resource_id;
    }
    public function setResourceId($id) {
        $this->resource_id = $id;
    }
    public function addImage($url, $alt_txt) {
        $this->images[] = array('url' => $url, 'alt' => $alt_txt);
    }
    public function getImages() {
        return $this->images;
    }
    public function getField($field) {
        if(array_key_exists($field, $this->fields)) {
            return trim($this->fields[$field]);
        }
        return '';
    }
    public function setField($field, $value) {
        $this->fields[$field] = $value;
    }
}