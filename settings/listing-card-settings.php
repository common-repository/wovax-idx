<?php
namespace Wovax\IDX\Settings;
if (defined('ABSPATH') === FALSE) {
    exit();// Exit via direct access.
}

Use Wovax\IDX\Utilities\ArrayType;
Use Wovax\IDX\Utilities\StringType;

class ListingCardSettings extends Options {
	const OPT_STATUS = 'status-class';

	const DEFAULT_CLASS = 'rounded-right';

	public function __construct() {
		$this->addToPrefix('listing-card');

        $this->add(self::OPT_STATUS, new StringType(self::DEFAULT_CLASS));
	}

	public function setStatusClass($class = ListingCardSettings::DEFAULT_CLASS) {
		$this->set(self::OPT_STATUS, $class);
	}

	public function statusClass() {
		$class = $this->get(self::OPT_STATUS);
		return $class;
	}
	
}