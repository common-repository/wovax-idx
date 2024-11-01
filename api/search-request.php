<?php
namespace Wovax\IDX\API;
if (defined('ABSPATH') === FALSE) {
    exit();// Exit via direct access.
}

abstract class Condition {
    private $field = '';
    private $value = '';
    public function getFieldName() {
        return $this->field;
    }
    public function getValue() {
        return $this->value;
    }
    protected function setValue($val) {
        $this->value = $val;
    }
    protected function setFieldName($name) {
        $this->field = $name;
    }
    protected function floatToStr($value) {
        $value = floatval(trim($value));
        if( ((int)$value < $value) === FALSE ) {
            $value = ($value);
        }
        return $this->strval($value);
    }
    abstract public function getTypeStr();
}

class NumericCondition extends Condition {
    const EQUALS   = 0x0;
    const MAX      = 0x1;
    const MIN      = 0x2;
    private $type  = self::EQUALS;
    private $value = 0;
    public function __construct($field, $value, $type = self::EQUALS) {
        if($type >= self::EQUALS && $type <= self::MIN) {
            $this->type = $type;
        }
        $this->value = $this->floatToStr($value);
        $this->setFieldname($field);
    }
    public function getTypeStr() {
        switch($this->type) {
            case self::MAX:
                return 'numeric_max';
                break;
            case self::MIN:
                return 'numeric_min';
                break;
        }
        return 'numeric';
    }
}

class NumericRange extends Condition {
    public function __construct($field, $min, $max) {
        $this->setFieldname($field);
        $this->setValue(
            array(
                'min_val' => $this->floatToStr($min),
                'max_val' => $this->floatToStr($max)
            )
        );
    }
    public function getTypeStr() {
        return 'numeric_range';
    }
}

class TextCondition extends Condition {
    private $value    = '';
    private $contains = FALSE;
    private $field    = '';
    public function __construct($field, $value, $contains = FALSE) {
        $this->setFieldname($field);
        $this->value = trim($value);
    }
    public function getTypeStr() {
        if($contains) {
           return 'input_text'; 
        }
        return 'select';
    }
}

class SearchRequest {
    private $conditions   = array();
    private $descending   = TRUE;
    private $extra_fields = array();
    private $feed_ids     = array();
    private $limit        = 12;
    private $offset       = 0;
    private $sort_field   = 'date';
    public function __construct($feed_ids) {
        if(!is_array($feed_ids)) {
            throw new Exception('The search request constructor expects an array!');
        }
        if(count($feed_ids) < 1) {
            throw new Exception('A search request must at least have one feed id.');
        }
        foreach($feed_ids as $feed_id) {
            $this->addFeedID($feed_id);
        }
    }
    public function addCondition(Condition $cond) {
        $this->conditions[] = $cond;
    }
    public function addFeedID($id) {
        $id = intval($id);
        $this->feed_ids[$id] = $id;
    }
    public function ascend() {
        $this->descending = FALSE;
    }
    public function ascending() {
        return !$this->descending;
    }
    public function descend() {
        $this->descending = TRUE;
    }
    public function descending() {
        return $this->descending;
    }
    public function getConditions() {
        return $this->condtions;
    }
    public function getFeedIDs() {
        return $this->feed_ids;
    }
    public function getLimit() {
        return $this->limit;
    }
    public function getOffset() {
        return $this->offset;
    }
    public function getSortField() {
        return $this->sort_field;
    }
    public function setLimit($limit) {
        $limit = intval($limit);
        if($limit < 1) {
            $limit = 12;
        }
        $this->limit = $limit;
    }
    public function setOffset($offset) {
        $offset = intval($offset);
        if($offset < 0) {
            $offset = 0;
        }
        $this->offset = $offset;
    }
    public function setSortField($field) {
        if($field == 'price') {
            $this->sort_field = 'price';
        } else {
            $this->sort_field = 'date';
        }
    }
}