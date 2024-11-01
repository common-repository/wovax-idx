<?php
/*
 * Description : The image widget for Wovax IDX
 * Author      : Keith Cancel
 * Author Email: admin@keith.pro
 */

namespace Wovax\IDX\Integration\Elementor;

use Wovax\IDX\API\WovaxConnect;


// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

//require_once(ELEMENTOR_PATH. 'includes/widgets/image-carousel.php');

// Post IDs are normally positive however wordpress code does not mind
// negative ints. So we can use this populate the cache with fictitious
// attachments. Kinda annoying since the methods I would like to override
// in the base image carasoul class are private.

class FakeAttachments {
    // A random negative number to reduce likely hood of conflicts with any
    // other plugins using this technique.
    const START_ID = -1779033703;
    protected $sample  = FALSE;
    protected $listing = NULL;
    protected $images  = array();
    private   $columns = array(
        'ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content',
        'post_title', 'post_excerpt', 'post_status', 'comment_status',
        'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged',
        'post_modified', 'post_modified_gmt', 'post_content_filtered',
        'post_parent', 'guid', 'menu_order', 'post_type', 'post_mime_type',
        'comment_count'
    );

    public function __construct() {
        $this->listing = \Wovax\IDX\Utilities\CurrentListing::getInstance()->getListing();
        if($this->listing == NULL) {
            $this->load_sample_images();
        } else {
            $this->load_images();
        }
        add_filter('wp_get_attachment_url', array($this, 'fetch_url'), 10, 2);
        add_filter('wp_get_attachment_image_src', array($this, 'gen_images'), 10, 4);
    }

    private function load_sample_images() {
        $this->sample = TRUE;
        for ($i = 0; $i < 7; $i++) {
            $id  = self::START_ID - $i;
            $url = 'https://s3.us-west-1.wasabisys.com/wovax-idx-us-west-1/wovax/1/' . strval($i + 1). '.jpg';
            $this->images[$id] = $url;

            $post = array();
            foreach($this->columns as $col) {
                $post[$col] = '';
            }
            $date = date("Y-n-j G:i");
            $post['ID']                = $id;
            $post['post_type']         = 'attachment';
            $post['post_mime_type']    = 'image/jpeg';
            $post['post_status']       = 'inherit';
            $post['post_title']        = 'Sample Photo '. strval($i + 1);
            $post['post_content']      = 'Sample Photo '. strval($i + 1);
            $post['post_excerpt']      = 'Sample Photo '. strval($i + 1);
            $post['post_name']         = 'sample-photo-'. strval($i + 1);
            $post['comment_status']    = 'closed';
            $post['ping_status']       = 'closed';
            $post['post_author']       = 0;
            $post['post_parent']       = 0;
            $post['comment_count']     = 0;
            $post['guid']              = $url;
            $post['post_date']         = $date;
            $post['post_date_gmt']     = $date;
            $post['post_modified']     = $date;
            $post['post_modified_gmt'] = $date;
            wp_cache_add($id, (object) $post, 'posts');
        }
    }

    private function load_images() {
        $address = $this->listing->getField('Street Address');
        if(strlen($address) > 0) {
            $address = ' for ' . $address;
        }
        $id = FakeAttachments::START_ID;
        $i  = 1;
        foreach($this->listing->getImages() as $image) {
            $this->images[$id] = $image['url'];
            $post = array();
            foreach($this->columns as $col) {
                $post[$col] = '';
            }
            $date = date("Y-n-j G:i");
            $post['ID']                = $id;
            $post['post_type']         = 'attachment';
            $post['post_mime_type']    = 'image/jpeg';
            $post['post_status']       = 'inherit';
            $post['post_content']      = 'Photo '. strval($i) . ' for ' . $address;
            $post['post_excerpt']      = 'Photo '. strval($i) . ' for ' . $address;
            $post['post_title']        = 'Photo '. strval($i) . ' for ' . $address;
            $post['post_name']         = 'photo-'. strval($i);
            $post['comment_status']    = 'closed';
            $post['ping_status']       = 'closed';
            $post['post_author']       = 0;
            $post['post_parent']       = 0;
            $post['comment_count']     = 0;
            $post['guid']              = $image['url'];
            $post['post_date']         = $date;
            $post['post_date_gmt']     = $date;
            $post['post_modified']     = $date;
            $post['post_modified_gmt'] = $date;
            wp_cache_add($id, (object) $post, 'posts');
            $id--;
            $i++;
        }
    }

    public function fetch_url($url, $id) {
        if(array_key_exists($id, $this->images)) {
            return $this->images[$id];
        }
        return $url;
    }

    public function gen_images($image, $attachment_id, $size, $icon) {
        if(is_int($attachment_id) && !array_key_exists($attachment_id, $this->images) || empty($this->images[$attachment_id])) {
            return $image;
        }
        // process size string
        if(is_string($size)) {
            $sz = array(
                0,
                0,
                "crop" => FALSE
            );
            $sizes = $this->all_wp_image_sizes();
            if(array_key_exists($size, $sizes)) {
                $sz[0] = $sizes[$size]['width'];
                $sz[1] = $sizes[$size]['height'];
                $sz['crop'] = $sizes[$size]['crop'];
            }
            $size = $sz;
        }
        $url = $this->images[$attachment_id];
        if(!empty($url)) {
            $prt = 'https://cache.wovax.com/image/?src=' . $url;
            if($size[0] > 0) {
                $prt .= '&width=' . $size[0];
            }
            if($size[1] > 0) {
                $prt .= '&height=' . $size[1];
            }
            if($size['crop']) {
                $prt .= '&crop-to-fit';
            }
            $prt .= '&aro';
            $url = $prt;
            return array(
                $url,
                $size[0],
                $size[1],
                true
            );
        }
    }

    private function all_wp_image_sizes() {
        global $_wp_additional_image_sizes;
        $default_image_sizes = get_intermediate_image_sizes();
        foreach ($default_image_sizes as $size) {
            $image_sizes[$size]['width'] = intval( get_option( "{$size}_size_w" ) );
            $image_sizes[$size]['height'] = intval( get_option( "{$size}_size_h" ) );
            $image_sizes[$size]['crop'] = get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false;
        }
        if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) ) {
            $image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
        }
        return $image_sizes;
    }
}

class ImageWidget extends \Elementor\Widget_Image_Carousel {

    public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
        wp_register_script('wovax-idx-images', plugins_url('assets/js/image-carousel.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true );
    }

    public function get_script_depends() {
        return ['wovax-idx-images'];
    }

	public function get_name() {
        return 'wovax-idx-image-carousel';
	}

	public function get_title() {
		return 'Wovax Image Carousel';
	}

    public function get_keywords() {
        $arr = parent::get_keywords();
        $arr[] = 'wovax';
		return $arr;
    }

    public function get_settings_for_display($setting_key = null) {
        $imgs = array();
        $id = FakeAttachments::START_ID;
        while(TRUE) {
            $post = wp_cache_get($id, 'posts');
            if($post == FALSE) {
                break;
            }
            $imgs[] = array(
                'id'  => $id,
                'url' => $post->guid
            );
            $id--;
        }

        if($setting_key == 'carousel') {
            return $imgs;
        }

        $settings = parent::get_settings_for_display($setting_key);
        if(!is_string($setting_key)) {
            $settings['carousel'] = $imgs;
        }
        return $settings;
    }

    public function get_categories() {
        return ['wovax-idx'];
	}

    protected function register_controls() {
        parent::register_controls();
        $this->remove_control('carousel');
    }

    protected function render() {
        parent::render();
    }
}

$injector = new FakeAttachments();