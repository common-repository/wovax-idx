<?php
namespace Wovax\IDX\Utilities;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

use Wovax\IDX\Listing;

class CurrentListing {
    private static $instance = null;
    private $listing = NULL;

    // Prevent initiation from other code.
    private function __construct() {}

    public function setListing(Listing $listing) {
        $this->listing = $listing;
    }

    public function getListing() {
        return $this->listing;
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new CurrentListing();
        }
        return self::$instance;
    }
}
