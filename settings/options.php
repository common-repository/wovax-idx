<?php
namespace Wovax\IDX\Settings;
if (defined('ABSPATH') === FALSE) {
    exit();// Exit via direct access.
}

use Exception;
Use Wovax\IDX\Utilities\DataType;

abstract class Options {
    private $prefixes = array('wovax', 'idx', 'settings');
    private $options = array();
    // useful for plugin un-installation
    public function deleteAll() {
        foreach($this->options as $name => $info) {
            delete_option($this->prefix.$name);
        }
    }
    protected function add($name, DataType $type) {
        if(preg_match('/^[a-z][0-9a-z\-_]*$/', $name) !== 1) {
            throw new Exception('Can not add this option improperly formatted name!');
        }
        $this->options[$name] = $type;
    }
    protected function addToPrefix($prefix) {
        if(preg_match('/^[a-z][0-9a-z\-_]*$/', $prefix) !== 1) {
            throw new exception('Bad additional prefix!');
        }
        $this->prefixes[] = $prefix;
    }
    protected function get($name) {
        if(!array_key_exists($name, $this->options)) {
            throw new Exception('To get the option it must first be added!');
        }
		$default = $this->options[$name]->getDefault();
        $val = get_option($this->getPrefixString().$name, $default);
        $val = $this->options[$name]->sanitizeValue($val);
        return $val;
    }
    protected function getPrefixString() {
        return implode('-', $this->prefixes).'-';
    }
    protected function removePrefixes(...$prefixes) {
        $this->prefixes = array_diff($this->prefixes, $prefixes);
    }
    protected function set($name, $value) {
        if(!array_key_exists($name, $this->options)) {
            throw new Exception('To set the option it must first be added!');
        }
        $value = $this->options[$name]->sanitizeValue($value);
		// wordpress default serialize was not working as expected when you tried
		// setting a value to FALSE.
		if(is_bool($value)) {
			$value = intval($value);
		}
        update_option($this->getPrefixString().$name, $value);
    }
}

// Include the Options files
require_once(__DIR__.'/feed-display.php');
require_once(__DIR__.'/initial-setup.php');
require_once(__DIR__.'/search-appearance.php');
require_once(__DIR__.'/shortcode-settings.php');
require_once(__DIR__.'/listing-card-settings.php');