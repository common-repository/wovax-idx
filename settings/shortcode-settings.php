<?php
namespace Wovax\IDX\Settings;
if (defined('ABSPATH') === FALSE) {
    exit();// Exit via direct access.
}

Use Wovax\IDX\Utilities\ArrayType;
Use Wovax\IDX\Utilities\StringType;

class ShortcodeSettings extends Options {
    const SORT_PRICE_HIGH  = 'price-desc';
    const SORT_PRICE_LOW   = 'price-asc';
    const SORT_DATE_RECENT = 'date-desc';
    const SORT_DATE_OLD    = 'date-asc';

    const OPT_SORT_ORDER   = 'sort-order';

    private $id = 0;
    public function __construct($shortcode_id) {
        $this->addToPrefix('shortcodes');
        $this->add(self::OPT_SORT_ORDER, new ArrayType(
            new StringType(self::SORT_DATE_RECENT),
            array(),
            FALSE
        ));
        $this->id = intval($shortcode_id);
    }
    public function setSortOrder($order = ShortcodeSettings::SORT_DATE_RECENT) {
        if(!in_array($order, $this->allSortValues(), TRUE)) {
            return FALSE;
        }
        $all = $this->get(self::OPT_SORT_ORDER);
        if(!is_array($all)) {
            $all = array();
        }
        $all[$this->id] = $order;
        $this->set(self::OPT_SORT_ORDER, $all);
    }
    public function sortOrder() {
        $val = self::SORT_DATE_RECENT;
        $all = $this->get(self::OPT_SORT_ORDER);
        if(
            array_key_exists($this->id, $all) &&
            in_array($all[$this->id], $this->allSortValues(), TRUE)
        ) {
            $val = $all[$this->id];
        }
        return $val;
    }
    public static function allSortValues() {
        return array(
            self::SORT_PRICE_HIGH, self::SORT_PRICE_LOW,
            self::SORT_DATE_RECENT, self::SORT_DATE_OLD
        );
    }
}