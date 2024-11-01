<?php
namespace Wovax\IDX\Settings;
if (defined('ABSPATH') === FALSE) {
    exit();// Exit via direct access.
}

Use Wovax\IDX\Utilities\BoolType;
Use Wovax\IDX\Utilities\IntType;
Use Wovax\IDX\Utilities\StringType;

class InitialSetup extends Options {
    const OPT_EMAIL              = 'webmaster-email';
    const OPT_PRODUCTION_MODE    = 'environment';
    const OPT_DETAILS_BLOCK_MODE = 'details-block-mode';
    const OPT_DEFAULT_SEARCH     = 'default-search';

    const OPT_DETAIL_PAGE = 'listing-details-page';
    const OPT_SEARCH_PAGE = 'search-results-page';
    

    const OPT_GOOGLE_MAP_KEY  = 'google-maps-api-key';
    const OPT_APPLE_MAP_TOKEN  = 'apple-mapkit-token';
    const OPT_LOCATION_IQ_KEY = 'location-iq-api-key';
    const OPT_MAP_QUEST_KEY   = 'map-quest-api-key';
    const OPT_LIST_TRAC_ID    = 'list-trac-id';

    public function __construct() {
        $this->addAccessSettings();
        $this->addPageSettings();
        $this->addServiceSettings();
    }
    // access Settings
    private function addAccessSettings() {
        $mode = function($val) {
            return ($val == 'production') ? 'production' : 'development';
        };
        $this->add(self::OPT_EMAIL, new StringType('', 'trim'));
        $this->add(self::OPT_PRODUCTION_MODE, new StringType('development', $mode));
        $this->add(self::OPT_DETAILS_BLOCK_MODE, new StringType('blocks'));
        $this->add(self::OPT_DEFAULT_SEARCH, new StringType('', 'trim'));
    }
    public function email() {
        return $this->get(self::OPT_EMAIL);
    }
    public function inProduction() {
        return ($this->get(self::OPT_PRODUCTION_MODE) === 'production');
    }
    public function inDetailsBlockMode() {
        return $this->get(self::OPT_DETAILS_BLOCK_MODE);
    }
    public function setEmail($email) {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
           return FALSE;
        }
        $this->set(self::OPT_EMAIL, $email);
        return TRUE;
    }
    public function setInDetailsBlockMode($on = 'blocks') {
        $this->set(self::OPT_DETAILS_BLOCK_MODE, $on);
        return TRUE;
    }
    public function setInProduction($on = TRUE) {
        $mode = $on ? 'production' : 'development';
        $this->set(self::OPT_PRODUCTION_MODE, $mode);
        return TRUE;
    }

    public function setDefaultSearch($search) {
        $this->set(self::OPT_DEFAULT_SEARCH, $search);
        return TRUE;
    }

    public function defaultSearch() {
        return $this->get(self::OPT_DEFAULT_SEARCH);
    }
    // END of Access settings

    // Settings for Search and Details Page
    private function addPageSettings() {
        $good_id = function($id) {
            $id = intval($id);
            if(get_post_status($id) === FALSE) {
                $id = 0;
            }
            return $id;
        };
        $this->add(self::OPT_DETAIL_PAGE, new IntType(0, $good_id));
        $this->add(self::OPT_SEARCH_PAGE, new IntType(0, $good_id));
    }
    public function detailPage() {
        return $this->get(self::OPT_DETAIL_PAGE);
    }
    public function searchPage() {
        return $this->get(self::OPT_SEARCH_PAGE);
    }
    public function setDetailPage($post_id) {
        $post_id = intval($post_id);
        if(get_post_status($post_id) === FALSE) {
            return FALSE;
        }
        $this->set(self::OPT_DETAIL_PAGE, $post_id);
        return TRUE;
    }
    public function setSearchPage($post_id) {
        $post_id = intval($post_id);
        if(get_post_status($post_id) === FALSE) {
            return FALSE;
        }
        $this->set(self::OPT_SEARCH_PAGE, $post_id);
        return TRUE;
    }
    // END of page settings

    // Services Settings
    private function addServiceSettings() {
        $this->add(self::OPT_GOOGLE_MAP_KEY , new StringType('', 'trim'));
        $this->add(self::OPT_APPLE_MAP_TOKEN , new StringType('', 'trim'));
        $this->add(self::OPT_LOCATION_IQ_KEY, new StringType('', 'trim'));
        $this->add(self::OPT_MAP_QUEST_KEY  , new StringType('', 'trim'));
        $this->add(self::OPT_LIST_TRAC_ID   , new StringType('', 'trim'));
    }
    public function googleMapsKey() {
        return $this->get(self::OPT_GOOGLE_MAP_KEY);
    }
    public function appleMapsToken() {
        return $this->get(self::OPT_APPLE_MAP_TOKEN);
    }
    public function locationIqKey() {
        return $this->get(self::OPT_LOCATION_IQ_KEY);
    }
    public function mapQuestKey() {
        return $this->get(self::OPT_MAP_QUEST_KEY);
    }
    public function setGoogleMapsKey($key) {
        $this->set(self::OPT_GOOGLE_MAP_KEY, $key);
        return TRUE;
    }
    public function setAppleMapsToken($key) {
        $this->set(self::OPT_APPLE_MAP_TOKEN, $key);
        return TRUE;
    }
    public function setLocationIqKey($key) {
        $this->set(self::OPT_LOCATION_IQ_KEY, $key);
        return TRUE;
    }
    public function setMapQuestKey($key) {
        $this->set(self::OPT_MAP_QUEST_KEY, $key);
        return TRUE;
    }
    public function listTracId() {
        return $this->get(self::OPT_LIST_TRAC_ID);
    }
    public function setListTracId($key) {
        $this->set(self::OPT_LIST_TRAC_ID, $key);
        return TRUE;
    }
    // END of Services Settings
}