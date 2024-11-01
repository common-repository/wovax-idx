<?php
namespace Wovax\IDX\Utilities;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}
use WP_Post;
use Wovax\IDX\Listing;
use Wovax\IDX\API\WovaxConnect;
use Wovax\IDX\Settings\FeedDisplay;
use Wovax\IDX\Settings\InitialSetup;
use Wovax\IDX\Settings\SearchAppearance;
use Wovax\IDX\Shortcodes\ListingDetails;

class ListingLoader {
    private $idx_api     = NULL;
    private $listing     = NULL;
    private $init_opts   = NULL;
    private $short_code  = NULL;
    private $disp_conf   = NULL;
    private $detail_base = '';
    private $detail_url  = '';
    public function __construct() {
        $this->idx_api    = WovaxConnect::createFromOptions();
        $this->init_opts  = new InitialSetup();
        $this->short_code = new ListingDetails();
        add_filter('parse_query', array($this, 'load'));
    }
    public function echoMeta() {
        $des = $this->getDescriptionString();
        // Sets the description text
        if(strlen($des) > 0) {
            // Traditional meta tags like for Google.
            echo '<meta name="description" content="'.htmlspecialchars($des).'">'."\n";
            // Open Graph tags mainly for facebook, but facebook also reads normal meta tags.
            echo '<meta property="og:description" content="'.htmlspecialchars($des).'">'."\n";
            // Twitter tag
            echo '<meta name="twitter:description" content="'.htmlspecialchars($des).'">'."\n";
        }
        // Sets the canonical url
        if(!empty($this->detail_url)) {
            echo '<meta property="og:url" content="'. htmlspecialchars($this->detail_url) .'">'."\n";
            echo '<link rel="canonical" href="'. htmlspecialchars($this->detail_url) .'">'."\n";
        }
        // Sets title string
        $page_title = $this->getTitleString();
        if(!empty($page_title)) {
            echo '<meta property="og:title" content="'. htmlspecialchars($page_title) .'">'."\n";
            echo '<meta property="twitter:text:title" content="'. htmlspecialchars($page_title) .'">'."\n";
        }
        // echo JSON-LD data
        echo $this->getJSON_LD();
        // open graph photo data
        $photos = $this->getPhotoURLs();
        if(count($photos) > 0) { // only main image for now.
            echo '<meta name="twitter:card" content="summary_large_image">'."\n";
            echo '<meta property="og:image" content="'.htmlspecialchars($photos[0]).'">'."\n";
            echo '<meta property="twitter:image" content="'.htmlspecialchars($photos[0]).'">'."\n";
            echo '<meta property="og:image:secure_url" content="'.htmlspecialchars($photos[0]).'">'."\n";
        } else {
            echo '<meta name="twitter:card" content="summary">'."\n";
        }
    }
    // TODO shorten this.
    public function load($query) {
        $detail_page_id = $this->init_opts->detailPage();
        if($detail_page_id < 1 || $query->queried_object_id !== $detail_page_id) {
            return;
        }
        $feed_id     = $this->getFeedId($query->query);
        $resource_id = $this->getResourceId($query->query);
        $view        = $this->getView($query->query);
        $display     = new FeedDisplay($feed_id);
        $block_info  = $this->getBlockInfo($query->queried_object);
        $need_fields = $block_info['fields'];
        $need_fields = array_merge($display->getRequiredFields(), $need_fields);
        $need_fields = array_merge(\Wovax\IDX\Integration\Elementor\get_elementor_fields(), $need_fields);
        $need_fields = array_values(array_unique($need_fields)); // no reason to send doubles.
        $data        = $this->idx_api->getListingDetails(
            $feed_id,
            $resource_id,
            $need_fields,
            $view
        );
        if(!is_array($data)) {
            switch($data) {
                case 'No data found':
                    wp_redirect(get_permalink($this->init_opts->searchPage()), 301);
                    exit();
                    break;
                case 'Missing parameters':
                    $this->short_code->addError('No listing can be queried.');
                    return;
                    break;
                default:
                    $this->short_code->addError('Failure to load listing resources. Try again later.');
                    return;
                    break;
            }
        }
        $this->disp_conf = $display;
        $this->listing   = $data;
        // Maybe redirect
        if (isset($_SERVER['HTTP_HOST'])) {
            $requested_url  = is_ssl() ? 'https://' : 'http://';
            $requested_url .= $_SERVER['HTTP_HOST'];
            $requested_url .= $_SERVER['REQUEST_URI'];
            $generated_url  = self::buildURL($feed_id, $resource_id, $data);
            $requested      = @parse_url($requested_url);
            $generated      = @parse_url($generated_url);
            if($requested['path'] !== $generated['path']) {
                wp_redirect($generated_url, 301);
                exit();
            }
            $this->detail_url = $generated_url;
        }
        $listing = new Listing($data);
        $listing->setFeedId($feed_id);
        $listing->setResourceId($resource_id);
        $this->registerBlocks($listing, $block_info['names']);
        CurrentListing::getInstance()->setListing($listing);
        $this->short_code->addListing($data);
        $this->short_code->changeFeedDisplay($display);
        if($detail_page_id > 0) {
            $this->detail_base = get_permalink($detail_page_id);
            add_action('wp_head',              array($this, 'echoMeta'));
            add_filter('document_title_parts', array($this, 'titleHeaderFilter'));
            add_filter('the_posts',            array($this, 'populatePostData'));
            add_filter('page_link',            array($this, 'overRidePageLink'));
            add_filter('post_link',            array($this, 'overRidePageLink'));
            if(is_object($query->queried_object)) {
                $query->queried_object->post_title = $this->getTitleString(FALSE);
            }
        }
    }
    public function overRidePageLink($link) {
        if($link != $this->detail_base || strlen($this->detail_url) < 1) {
            return $link;
        }
        return $this->detail_url;
    }
    public function populatePostData($posts) {
        if(count($posts) < 1 || $posts[0]->ID !== $this->init_opts->detailPage()) {
            return $posts;
        }
        $post               = $posts[0];
        $post->post_title   = $this->getTitleString();
        $post->post_excerpt = $this->getDescriptionString();
        // Updating the post cache ensures that any call of get_post/get_instance uses our data.
        wp_cache_replace($post->ID, $post, 'posts');
        //update post
        $posts[0]            = $post;
        return $posts;
    }
    public function titleHeaderFilter($parts) {
        $parts['title'] = $this->getTitleString(FALSE);
        return $parts;
    }
    private function getDescriptionString() {
        $opts   = new SearchAppearance();
        $fields = $opts->getGlobalDescriptionFormat();
        $values = array();
        foreach($fields as $field) {
            if(!array_key_exists($field, $this->listing)) {
                continue;
            }
            $val = trim($this->listing[$field]);
            if(strlen($val) > 0) {
                $values[] = $val;
            }
        }
        return implode(' ', $values);
    }
    private function getJSON_LD() {
        // Sadly there is no Realestate type at the time of writing this
        // If ever added this should be updated to use that.
        if(is_numeric($this->listing['Price']) === FALSE) {
            return '';
        }
        $photos = $this->getPhotoURLs();
        $data   = array('@context' => 'http://schema.org', '@type' => 'Product');
        $data['name']        = $this->getTitleString(FALSE);
        $data['description'] = $this->getDescriptionString();
        if(count($photos) > 0) { // only main image for now.
            $data['image']   = $photos;
        }
        $data['offers'] = array(
            'price'         => $this->listing['Price'],
            'availability'  => 'InStock',
            'priceCurrency' => 'USD'
        );
        $txt = '<script type="application/ld+json">';
        $txt .= json_encode($data);
        $txt .= '</script>';
        return $txt;
    }
    private function getPhotoURLs() {
        if(
            !array_key_exists('photos_list', $this->listing) ||
            !is_array($this->listing['photos_list'])         ||
            count($this->listing['photos_list']) < 1
        ) {
            return array();
        }
        $images = array();
        foreach($this->listing['photos_list'] as $photo) {
            $url = $photo['location'];
            if(strlen($url) > 0) {
                $images[] = $url;
            }
        }
        return $images;
    }
    private function getTitleString($line_break = TRUE) {
        $opts  = new SearchAppearance();
        $parts = array();
        $sec   = 0;
        foreach($opts->getGlobalTitleFormat() as $item) {
            if(array_key_exists($sec, $parts) === FALSE) {
                $parts[$sec] = array();
            }
            if($item['type'] === 'value') {
                $field = $item['value'];
                $value = 'No '.$field.' Data';
                if(isset($this->listing[$field])) {
                    $tmp = trim($this->listing[$field]);
                    if(strlen($tmp) > 0) {
                        $value = $tmp;
                        // If price field apply price formatting
                        if($field == 'Price') {
                            $com  = $this->disp_conf->defaultPriceHasComma() ? ',' : '';
                            $cnt  = 2;
                            if(!$this->disp_conf->defaultPriceHasDecimalPoint()) {
                                $cnt = 0;
                            }
                            $value = number_format($value, $cnt, '.', $com);
                            $value = $this->disp_conf->defaultCurrencySymbolLeft() ? '$'.$value : $value.'$';
                        }
                    }
                }
                $parts[$sec][] = $value;
            } else {
                $value = ' ';
                switch($item['value']) {
                    case 'line-break':
                        if($line_break) {
                            $value = ' <br>';
                        }
                        break;
                    case 'forward-slash':
                        $value = '/';
                        break;
                    case 'comma':
                        $value = ', ';
                        break;
                    case 'dash':
                        $value = ' - ';
                }
                $sec++;
                $parts[$sec] = array($value);
                $sec++;
            }
        }
        foreach($parts as $index => $val) {
            $parts[$index] = implode(' ', $val);
        }
        return implode('', $parts);
    }
    private function getFeedId($query) {
        if(!array_key_exists('wovax-idx-list-id', $query))  {
            return 0;
        }
        $tmp  = Base32::decodeToString($query['wovax-idx-list-id']);
        $info = array_map('trim', explode(',', $tmp));
        // probably an old URL
        if(count($info) !== 2 || preg_match('/^[0-9]+$/', $info[0]) !== 1) {
            if( preg_match('/^[0-9]+$/', $query['wovax-idx-list-id']) === 1) {
                return intval($query['wovax-idx-list-id']);
            }
            return 0;
        }
        return intval($info[0]);
    }
    private function getResourceId($query) {
        if(!array_key_exists('wovax-idx-list-id', $query))  {
            return '';
        }
        $tmp  = Base32::decodeToString($query['wovax-idx-list-id']);
        $info = array_map('trim', explode(',', $tmp));
        // probably an old URL
        if(count($info) !== 2) {
            $field_vals = array_map('trim', explode('/', $query['wovax-idx-field-vals']) );
            if(count($field_vals) > 0) {
                return $field_vals[0];
            }
            return '';
        }
        return $info[1];
    }
    private function getView($query) {
        if(!array_key_exists('wovax-idx-view', $query))  {
            return 'details';
        }
        return ($query['wovax-idx-view'] == 'details') ? 'details' : 'all';
    }
    // Handle Block Requirements
    private function getBlockInfo($post) {
        if(
            !function_exists('has_blocks') ||
            !function_exists('parse_blocks')
        ) {
            return array(
                'names'  => array(),
                'fields' => array()
            );
        }
        // most be a post objext with blocks
        if(!($post instanceof WP_Post) || !has_blocks($post)) {
            return array(
                'names'  => array(),
                'fields' => array()
            );
        }
        // Recursive function to fetch all fields
        $get_fields = function($blocks) use (&$get_fields) {
            $fields = array();
            $names  = array();
            foreach($blocks as $block) {
                // Other blocks may contain our idx blocks though.
                if(count($block['innerBlocks']) > 0) {
                    $new = $get_fields($block['innerBlocks']);
                    $fields = array_merge($fields, $new['fields']);
                    $names  = array_merge($names,  $new['names']);
                }
                // Check block prefix in name
                if(strpos($block['blockName'], 'wovax-idx-wordpress') !== 0) {
                    // A non-idx block won't have a field attribute
                    continue;
                }
                $names[] = $block['blockName']; // It's a wovax idx block
                // If the block is a map block we will need these fields.
                if($block['blockName'] === 'wovax-idx-wordpress/listing-map') {
                    $fields[] = 'Latitude';
                    $fields[] = 'Longitude';
                    continue;
                }
                foreach($block['attrs'] as $attribute => $val) {
                    if($attribute == 'listingField') {
                        $fields[] = $val;
                    }
                }
            }
            return array(
                'names'  => array_unique($names),
                'fields' => array_unique($fields)
            );
        };
        return $get_fields(parse_blocks($post->post_content));
    }
    private function registerBlocks($listing, $blocks) {
        if(!class_exists('\Wovax\IDX\Blocks\FieldData')) {
            return;
        }
        // Initialize all blocks only if they are on the page. AKA don't load uneeded JS.
        foreach($blocks as $block) {
            switch($block) {
                case 'wovax-idx-wordpress/labeled-field':
                    register_block_type(new \Wovax\IDX\Blocks\LabeledField($listing));
                    break;
                case 'wovax-idx-wordpress/field-data':
                    register_block_type(new \Wovax\IDX\Blocks\FieldData($listing));
                    break;
                case 'wovax-idx-wordpress/image-gallery':
                    register_block_type(new \Wovax\IDX\Blocks\ImageGallery($listing));
                    break;
                case 'wovax-idx-wordpress/listing-map':
                    register_block_type(new \Wovax\IDX\Blocks\ListingMap($listing));
                    break;
                case 'wovax-idx-wordpress/points-of-interest':
                    register_block_type(new \Wovax\IDX\Blocks\PoiMap($listing));
                    break;
                case 'wovax-idx-wordpress/favorite':
                    register_block_type(new \Wovax\IDX\Blocks\FavoriteBlock($listing));
                    break;

            }
        }
    }
    // Static methods
    public static function addRewriteRules($wp_rules) {
        return array_merge(self::getRewriteRules(), $wp_rules);
    }
    public static function buildURL($class_id, $listing_id, $listing, $post_name = NULL) {
        $page_id = (new InitialSetup())->detailPage();
        if($page_id < 1) {
            return '';
        }
        $listing = (array)$listing; // make sure if it's an obj it's array
        $opts   = new SearchAppearance();
        $fields = $opts->getFeedUrlFormat($class_id);
        $values = array();
        foreach($fields as $field) {
            $value = 'null';
            if(array_key_exists($field, $listing)) {
                $value = self::escapeStrURL($listing[$field]);
            }
            $values[] = $value;
        }
        $prt = implode('/', $values);
        if(strlen($prt) > 0) {
            $prt .= '/';
        }
        $url  = home_url('/');
        $url .= get_post($page_id)->post_name.'/';
        $url .= 'wovax-idx/'.$class_id.'/'.$listing_id.'/';
        $url .= $prt;
        return $url;
    }
    public static function checkRewriteRules() {
        $rules = get_option('rewrite_rules', array());
		if(!is_array($rules)) {
			$rules = array();
		}
        foreach(self::getRewriteRules() as $regex => $vars) {
            if(array_key_exists($regex, $rules) && $rules[$regex] === $vars) {
                continue;
            }
            flush_rewrite_rules();
            break;
		}
    }
    private static function getRewriteRules() {
        $vars1 = array(
            'pagename',
            'wovax-idx-list-id',
            'wovax-idx-field-vals'
        );
        foreach($vars1 as $index => $val) {
            $vars1[$index] = $val.'=$matches['.($index+1).']';
        }
        $vars1[] = 'wovax-idx-view=details';
        $vars2 = $vars1;
        unset($vars1[2]);
        $url_rules = array(
            '^([^\/]+)\/wovax-idx\/([0-9a-v]+)\/?$' => 'index.php?'.implode('&', $vars1),
            '^([^\/]+)\/wovax-idx\/([0-9a-v]+)\/((?!.+\/\/).*[^\/])\/?$' => 'index.php?'.implode('&', $vars2)
        );
        return $url_rules;
    }
    private static function escapeStrURL($str) {
        $str = strtolower($str);
        $str = preg_replace ('/[\s\\_\/.,:&-+]+/', '-', $str);
        $str = preg_replace ('/[^a-z0-9-]/', '', $str);
        $str = trim($str, '-');
        if(strlen($str) < 1) {
            return 'null';
        }
        return $str;
    }
}

$loader = new ListingLoader();
// Add query vars for URL rewriting.
add_filter('query_vars', function($qvars) {
    $qvars1 = array(
        'wovax-idx-list-id',
        'wovax-idx-view',
        'wovax-idx-field-vals'
    );
    return array_merge($qvars1, $qvars);
});
// Add URL rewriting.
add_filter('rewrite_rules_array', __NAMESPACE__.'\\ListingLoader::addRewriteRules');

// Add check to make URL rewrites exist.
add_action('wp_loaded', __NAMESPACE__.'\\ListingLoader::checkRewriteRules');