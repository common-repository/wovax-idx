<?php
namespace Wovax\IDX\API;
if (defined('ABSPATH') === FALSE) {
    exit();// Exit via direct access.
}
use WP_Http;
use Wovax\IDX\Settings\InitialSetup;

class WovaxConnect {
    const TRANS_AUTH_TOKEN = 'wovax-idx-api-auth-tkn';
    const TRANS_FEED_LIST  = 'wovax-idx-api-feed-list';
    const TRANS_AGG_FIELDS = 'wovax-idx-api-aggregated-fields';
    const PROTO            = 'https';
    const BASE             = 'connect.wovax.com';
    const VERSION          = 'api';
    private $http          = NULL;
    private $email         = '';
    private $environ       = 'development';
    // Set to true to disable transients
    private $debug         = FALSE;
    // Public methods
    public function __construct($email, $production = FALSE) {
        $this->http = new WP_Http();
        $this->email = $email;
        if($production) {
            $this->setToProduction();
        }
    }
    public function getAggregatedFields() {
        $fields = $this->getTransient(self::TRANS_AGG_FIELDS);
        // If aggregated fields have not expired return it.
        if(is_array($fields) && count($fields) > 0) {
            return $fields;
        }
        $feeds  = $this->getFeedClassIDs();
        $feeds = implode(',', $feeds);
        $feeds = explode(',', $feeds);
        $fields = array();
        $feed_details = $this->getFeedDetails($feeds);
        foreach($feed_details as $field_detail) {
            $fields[] = $field_detail['default_alias'];
        }
        $fields = array_unique($fields);
        natcasesort($fields);
        $fields = array_values($fields);
        set_transient(
            self::TRANS_AGG_FIELDS,
            $fields,
            8 * 60 // 8 minutes expiration
        );
        return $fields;
    }
    public function getFeedClassIDs() {
        return array_keys($this->getFeedList());
    }
    public function getFeedList() {
        $feeds = $this->getTransient(self::TRANS_FEED_LIST);
        // If feed details has not expired return it.
        if(is_array($feeds) && count($feeds) > 0) {
            return $feeds;
        }
        $data = $this->postRequest('list_feeds');
        if(!is_array($data)) {
            return array();
        }
        $feeds = array();
        foreach($data as $feed) {
            $class_id = intval($feed['class_id']);
            $feed['class_id'] = $class_id;
            $feeds[$class_id] = $feed;
        }
        // Update the stored data.
        set_transient(
            self::TRANS_FEED_LIST,
            $feeds,
            3 * 60 // 3 minutes expiration
        );
        return $feeds;
    }
    public function getListingDetails($class_id, $prop_id, $fields, $photos = 'all') {
        $data = $this->postRequest(
            'resource_detail',
            array(
                'class_id'     => $class_id,
                'resource_id'  => $prop_id,
                'count_photos' => $photos,
                'data_values'  => json_encode(array_values($fields))
            )
        );
        if(!is_array($data)) {
            return $data;
        }
        $data = $this->trimData($data);
        if(!array_key_exists('Street Address', $data) || strlen($data['Street Address']) < 1) {
            $data['Street Address'] = 'No Street Address Provided';
        }
        return $data;
    }
    public function getFeedDetails(array $class_ids) {
        $transient_ids = implode('_', $class_ids);
        $transient_tag = sprintf('wovax-idx-api-feed-details-%d', $transient_ids);
        $data          = $this->getTransient($transient_tag);
        if(is_array($data) && count($data) > 0) {
            return $data;
        }
        $data = $this->postRequest(
            'list_resource_class',
            array(
                'array_class_id'  => json_encode($class_ids)
            )
        );
        $keyed = array();
        foreach($class_ids as $class_id) {
            if(
                !is_array($data) ||
                !array_key_exists($class_id, $data) ||
                !is_array($data[$class_id])
            ) {
                return array();
            }

            foreach($data[$class_id] as $info) {
                $field = $info['id'];
                $keyed[$field] = $info;
            }
        }
        ksort($keyed, SORT_NATURAL);
        $data = array_values($keyed);
        // Update the stored data.
        set_transient(
            $transient_tag,
            $data,
            5 * 60 // 5 minutes expiration
        );
        return $data;
    }
    public function getFeedFields($class_id) {
        $class_id = array($class_id);
        $data = $this->getFeedDetails($class_id);
        $fields = array();
        foreach($data as $field) {
            $fields[] = $field['default_alias'];
        }
        // at least return the default
        if(empty($fields)) {
            return array(
                'Acres',
                'Bathrooms',
                'Bedrooms',
                'City',
                'Description',
                'MLS Number',
                'Price',
                'Property Type',
                'State',
                'Status',
                'Street Address',
                'Square Footage',
                'Lot Size',
                'Zip Code'
            );
        }
        return $fields;
    }
    public function setToDevelopment() {
        $this->environ = 'development';
    }
    public function setToProduction() {
        $this->environ = 'production';
    }
    // Private Methods
    private function getBaseURL() {
        return self::PROTO.'://'.self::BASE.'/'.self::VERSION.'/';
    }
    private function getToken() {
        $val = $this->getTransient(self::TRANS_AUTH_TOKEN);
        // If token has no expired return it.
        if(is_array($val) && $val['type'] === $this->environ) {
            if($val['exp'] < time()) {
                return $val['token'];
            } 
        }
        $response = $this->http->request(
            $this->getBaseURL().'validate_domain',
            array(
                'method'  => "POST",
                'timeout' => 45,
                'body'    => array(
                    'guest_email' => $this->email,
                    'environment' => $this->environ
                )
            )
        );
		if(is_wp_error($response)) {
			return '';
		}
        $body = json_decode($response['body'], TRUE);
        if(!is_array($body)) {
            return '';
        }
        $token  = $body['data'];
        $exp    = intval($body['token_exp']) - time() - 1;
        if($exp < 1) {
            // Try to use it anyways but don't store it.
            return $token;
        }
        // Update the stored token with the new one.
        $token = array(
            'token' => $token,
            'type'  => $this->environ,
            'exp'   => intval($body['token_exp'])
        );
        set_transient(
            self::TRANS_AUTH_TOKEN,
            $token,
            $exp
        );
		return $token['token'];
    }
    private function getTransient($name) {
        if($this->debug) {
            return FALSE;
        }
        return get_transient($name);
    }
    private function postRequest($func, $body = array()) {
        $response = $this->http->request(
            $this->getBaseURL().$func,
            array(
                'method'  => "POST",
                'timeout' => 45,
                'headers' => array("Authorization" => $this->getToken()),
                'body'    => $body
            )
        );
        if(is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return $error_message;
        }
        $data = json_decode($response['body'], TRUE)['data'];
        if(empty($data)) {
            return 'NO DATA - Data element is empty';
        }
        return $data;
    }
    private function trimData($data) {
        $trimmed = array();
        foreach($data as $index => $value) {
            if(is_string($value)) {
                $value = trim($value);
            }
            $trimmed[$index] = $value;
        }
        return $trimmed;
    }
    // Public static methods
    public static function createFromOptions() {
        $opts = new InitialSetup();
        if(strlen($opts->email()) < 1) {
            return NULL;
        }
        $api = new WovaxConnect($opts->email(), $opts->inProduction());
        return $api;
    }
}