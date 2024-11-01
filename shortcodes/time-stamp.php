<?php
namespace Wovax\IDX\Shortcodes;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

use Wovax\IDX\API\WovaxConnect;

/*
The Time Stamp shortcode format: [wovax-idx-time-stamp feed_id=""]
A feed id must be specified or the shortcode won't display any info.
*/

class TimeStampShortcode extends Shortcode {
    public function __construct() {
        parent::__construct('time-stamp');
    }
    protected function getContent($attr) {
        $attr = shortcode_atts(array('feed_id' => '0'), $attr);
        $feeds = WovaxConnect::createFromOptions()->getFeedList();
        $feed  = reset($feeds);
        if(array_key_exists($attr['feed_id'], $feeds)) {
            $feed = $feeds[$attr['feed_id']];
        }
        $html  = '<span class="wovax-idx-feed-update">';
        $html .= $feed['updated_timestamp'];
        $html .= '</span>';
        return $html;
    }
}
new TimeStampShortcode();