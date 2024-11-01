<?php
namespace Wovax\IDX\Shortcodes;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

abstract class Shortcode {
    private $errors  = array();
    private $scripts = array();
    private $styles  = array();
    private $tag     = '';
    private $loaded  = FALSE;
    public function __construct($tag) {
        $url = plugins_url('assets/css/shortcode.css', __FILE__);
        $this->addStyle('wovax-idx-shortcode', $url);
        $this->tag = 'wovax-idx-'.$tag;
        add_shortcode($this->tag, array($this, 'render'));
        add_action('wp_enqueue_scripts', array($this, 'loadResources'));
    }
    // Same as Wordpress's wp_enqueue_script for parameters.
    public function addScript($handle, $src = '', $deps = array(), $ver = FALSE) {
        $this->scripts[$handle] = array($src, $deps, $ver, TRUE);
        wp_register_script($handle, ...$this->scripts[$handle]);
    }
    // Same as Wordpress's wp_enqueue_style for parameters.
    public function addStyle($handle, $src = '', $deps = array(), $ver = FALSE, $media = 'all') {
        $this->styles[$handle] = array($src, $deps, $ver, $media);
        wp_register_style($handle, ...$this->styles[$handle]);
    }
    public function render($attr) {
        $html = $this->getContent($attr);
        $html = $this->getErrorsHTML().$html;
        return $html;
    }
    public function tag(){
        return $this->tag;
    }
    // Error stuff
    public function addError($msg) {

        $this->errors[] = $msg;
    }
    public function getErrors() {
        return $this->errors;
    }
    private function getErrorsHTML() {
        $html = '';
        foreach($this->getErrors() as $msg) {
            $html .= '<div class="wovax-idx-shrt-warn"><p>'.esc_html($msg)."</p></div>\n";
        }
        return $html;
    }
    public function loadResources() {
        if($this->loaded) {
            return;
        }
        foreach($this->scripts as $handle => $params) {
            wp_enqueue_script($handle, ...$params);
        }
        foreach($this->styles as $handle => $params) {
            wp_enqueue_style($handle, ...$params);
        }
        $this->loaded = TRUE;
    } 
    abstract protected function getContent($attr);
}