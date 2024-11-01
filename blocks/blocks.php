<?php
namespace Wovax\IDX\Blocks;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

use Exception;
use WP_Block_Type;
use Wovax\IDX\Listing;
use Wovax\IDX\API\WovaxConnect;
use Wovax\IDX\Settings\InitialSetup;
use Wovax\IDX\Utilities\Base32;
use Wovax\IDX\Utilities\ArrayType;
use Wovax\IDX\Utilities\BoolType;
Use Wovax\IDX\Utilities\DataType;
Use Wovax\IDX\Utilities\EnumType;
Use Wovax\IDX\Utilities\IntType;
Use Wovax\IDX\Utilities\RealType;
Use Wovax\IDX\Utilities\StringType;
Use Wovax\IDX\Utilities\StructType;

add_filter('block_categories', function($categories, $post) {
    return array_merge(
        $categories,
        array(
            array(
                'slug'  => 'wovax-idx',
                'title' => __( 'Wovax IDX', 'wovax-idx-text'),
            ),
        )
    );
}, 10, 2 );

add_action('enqueue_block_editor_assets', function() {
    $api       = WovaxConnect::createFromOptions();
    if(empty($api)){
        error_log("Missing initial setup information - please complete the initial setup before continuing.");
        return;
    }
    $opts      = new InitialSetup();
    $fields    = json_encode($api->getAggregatedFields());
    $feeds     = array();
    $google_tk = json_encode($opts->googleMapsKey());
    $loc_iq_tk = json_encode($opts->locationIqKey());
    $map_qu_tk = json_encode($opts->mapQuestKey());
    foreach($api->getFeedList() as $feed) {
        $feeds[$feed['class_id']] = array(
            'id'     => $feed['class_id'],
            'name'   => $feed['feed_name'].' - '.$feed["class_visible_name"],
            'update' => $feed['updated']
        );
    }
    $feeds = json_encode($feeds);
    $js_data   = "WovaxBlockData.feeds  = $feeds;\n";
    $js_data  .= "WovaxBlockData.fields = $fields;\n";
    $js_data  .= "WovaxBlockData.mapTokens.google = $google_tk;\n";
    $js_data  .= "WovaxBlockData.mapTokens.location_iq = $loc_iq_tk;\n";
    $js_data  .= "WovaxBlockData.mapTokens.map_quest = $map_qu_tk;\n";
    // Beging Enqueuing scripts and css

    // Enqueue Scripts
    wp_enqueue_script(
        'wovax-idx-block-data',
        plugins_url('assets/js/block-data.js', __FILE__ ),
        array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
	wp_add_inline_script(
		'wovax-idx-block-data',
		$js_data,
		'after'
    );
    wp_enqueue_script(
        'wovax-idx-block-components',
        plugins_url('assets/js/block-components.es5.min.js', __FILE__ ),
        array('wovax-idx-block-data')
    );
    wp_enqueue_script(
        'wovax-idx-blocks-all-pgs',
        plugins_url('assets/js/blocks-all-pgs.js', __FILE__ ),
        array('wovax-idx-block-components')
    );
    // Enqueue Styles
    wp_enqueue_style(
        'wovax-idx-block-components',
        plugins_url('assets/css/block-components.css', __FILE__ )
    );
    if($opts->detailPage() != get_the_ID()) {
        return; // Not on details page so don't show these blocks
    }
    // Detail page only Assets and blocks
    wp_enqueue_script(
        'wovax-idx-blocks',
        plugins_url('assets/js/blocks.js', __FILE__ ),
        array('wovax-idx-block-components')
    );
    wp_enqueue_script(
        'wovax-idx-map-loader',
        plugins_url('assets/js/map-loader.es5.min.js', __FILE__ )
    );
    wp_enqueue_script(
        'wovax-idx-map-blocks',
        plugins_url('assets/js/map-blocks.es5.min.js', __FILE__ ),
        array('wovax-idx-block-components', 'wovax-idx-map-loader')
    );
    // Enqueue Styles
    wp_enqueue_style(
        'wovax-idx-favorites',
        WOVAX_PLUGIN_URL.'assets/css/favorites.css'
    );
});

// Hook to add front end and editor assets
// https://developer.wordpress.org/reference/hooks/enqueue_block_assets/
// add_action( 'enqueue_block_assets'

abstract class WovaxIdxBlock extends WP_Block_Type {
    private $attribs = array();
    public function __construct($name, $arguments = array()) {
        $name = 'wovax-idx-wordpress/'.trim(strtolower($name));
        $args = array_merge(
            $arguments,
            array(
                'render_callback' => function($attr, $content) {
                    foreach($this->attribs as $name => $dataType) {
                        $value = $dataType->getDefault();
                        if(isset($attr[$name])) {
                            $value = $dataType->sanitizeValue($attr[$name]);
                        }
                        $attr[$name] = $value;
                    }
                    return $this->generateHTML($attr, $content);
                }
            )
        );
        $this->setAttribute('className', new StringType(''));
        parent::__construct($name, $args);
    }
    abstract public function generateHTML($attr, $content);
    protected function setAttribute($name, DataType $type) {
        if(preg_match('/^[a-z][0-9a-z_]*$/i', $name) !== 1) {
            throw new Exception('Can not add this attribute improperly formatted name!');
        }
        $this->attribs[$name] = $type;
    }
    public function buildInlineStyle($styles) {
        $merged = array();
        foreach($styles as $style => $value) {
            if(is_null($value) || strlen($value) < 1) {
                continue;
            }
            $style = strtolower($style);
            $merged[$style] = $style.': '.$value.';';
        }
        return implode('', $merged);
    }
}

abstract class WoxaxIdxListingBlock extends WovaxIdxBlock {
    private $listing = NULL;
    public function __construct($name, Listing $listing, $arguments = array()) {
        parent::__construct($name, $arguments);
        $this->listing = $listing;
    }
    public function getListing() {
        return $this->listing;
    }
}

abstract class WovaxIdxFieldBlock extends WoxaxIdxListingBlock {
    public function __construct($name, Listing $listing, $arguments = array()) {
        parent::__construct($name, $listing, $arguments);
        $this->setAttribute('listingField', new StringType(''));
        $this->setAttribute('fieldType', new StructType(
            [
                'type',
                new EnumType(
                    new StringType('text'),
                    'link', 'numeric', 'price', 'text', 'boolean'
                )
            ],
            [
                'link',
                new StructType(
                    [
                        'label',
                        new StringType(
                            'Click Here',
                            function($val) {
                                return esc_html(trim($val));
                            }
                        )
                    ]
                )
            ],
            [
                'numeric',
                new StructType(
                    ['commas', new BoolType(true)],
                    ['decimals', new IntType(2)]
                )
            ],
            [
                'price',
                new StructType(
                    ['left', new BoolType(true)],
                    ['symbol', new StringType('$')]
                )
            ]
        ));
    }
    protected function getValueStr($fieldType, $value) {
        $link  = (object)$fieldType['link'];
        $num   = (object)$fieldType['numeric'];
        $price = (object)$fieldType['price'];
        $boolean = (object)$fieldType['boolean'];
        switch($fieldType['type']) {
            case 'link':
                if ( $parts = parse_url($value) ) {
                    if ( !isset($parts["scheme"]) )
                    {
                        $value = "https://$value";
                    }
                }
				if(filter_var($value, FILTER_VALIDATE_URL) === FALSE) {
					$value = esc_html($value);
            		break;
        		}
				$value = '<a target="_blank" href="'.$value.'">'.$link->label.'</a>';
				break;
            case 'price':
				$value = $this->getPriceString($value, $num->commas, $num->decimals, $price->left, $price->symbol);
				break;
			case 'numeric':
				$value = $this->getNumericString($value, $num->commas, $num->decimals);
				break;
            case 'boolean':
                if($value === 1 || $value === '1') {
                    $value = 'Yes';
                } else if(empty($value)) {
                    $value = 'No';
                } else {
                    $value = esc_html($value);
                }
			default:
				$value = esc_html($value);
				break;
        }
        return $value;
    }
    private function getNumericString($value, $comma = true, $decimals = 2) {
		$com = $comma ? ',' : '';
		return number_format(floatval($value), $decimals, '.', $com);
	}
	private function getPriceString($value, $comma = true, $decimals = 2, $left = true, $symbol = '$') {
		$val = $this->getNumericString($value, $comma, $decimals);
		if($left) {
			return $symbol.$val;
		}
		return $val.$symbol;
	}
}

class LabeledField extends WovaxIdxFieldBlock {
    private $listing = NULL;
    public function __construct(Listing $listing) {
        $this->setAttribute('label', new StringType(''));
        parent::__construct('labeled-field', $listing);
    }
    public function generateHTML($attr, $content) {
        $class = $attr['className'];
        $field = $attr['listingField'];
        $label = $attr['label'];
		$value = $this->getListing()->getField($field);
		// Nothing to display.
        if(strlen($value) < 1 && $attr['fieldType']['type'] !== 'boolean') {
			return '';
		}
        $html  = '<div class="'.$class.'" style="display:flex;justify-content:space-between;">';
        $html .= '<div style="">'.$label.'</div>';
        $html .= '<div style="align-items:right">'.$this->getValueStr($attr['fieldType'], $value).'</div>';
        $html .= '</div>';
		return $html;
    }
}

class FieldData extends WovaxIdxFieldBlock {
    public function __construct(Listing $listing) {
        parent::__construct('field-data', $listing);
        $this->setAttribute('backgroundColor', new StringType(''));
        $this->setAttribute('textAlign', new EnumType(new StringType(''), '', 'left', 'center', 'right'));
        $this->setAttribute('textColor', new StringType(''));
        $this->setAttribute('textSize', new StringType(''));
        $this->setAttribute('textStyle', new IntType(0));
    }
    public function generateHTML($attr, $content) {
        $class   = $attr['className'];
        $field   = $attr['listingField'];
        $align   = $attr['textAlign'];
        $bgColor = $attr['backgroundColor'];
        $color   = $attr['textColor'];
        $size    = $attr['textSize'];
        $styles  = $attr['textStyle'];
		$value = $this->getListing()->getField($field);
		// Nothing to display.
        if(strlen($value) < 1 && $attr['fieldType']['type'] !== 'boolean') {
			return '';
		}
        if(strlen($size) > 0) {
            $size .= 'px';
        }
        $decorations = '';
        if(($styles & 0xC) > 0) {
            $decs = array();
            if(($styles & 0x4) > 0) {
                $decs[] = 'underline';
            }
            if(($styles & 0x8) > 0) {
                $decs[] = 'line-through';
            }
            $decorations = implode(' ', $decs);
        }
        $style = array(
            'background-color' => $bgColor,
            'box-sizing'       => 'inherit',
            'color'            => $color,
            'line-height'      => 'normal',
            'font-size'        => $size,
            'font-style'       => ($styles & 0x2) > 0 ? 'italic' : '',
            'font-weight'      => ($styles & 0x1) > 0 ? 'bold' : '',
            'text-align'       => $align,
            'text-decoration'  => $decorations,

        );
        $style = $this->buildInlineStyle($style);
        $html  = '<div';
        if(strlen($class) > 0) {
            $html .=  ' class="'.$class.'"';
        }
        if(strlen($style) > 0) {
            $html .=  ' style="'.$style.'"';
        }
        $html .= '><p>'.$this->getValueStr($attr['fieldType'], strip_tags($value)).'</p></div>';
		return $html;
    }
}

class ImageGallery extends WoxaxIdxListingBlock {
    public function __construct(Listing $listing) {
        parent::__construct('image-gallery', $listing);
        add_action('enqueue_block_assets', array($this, 'enqueueAssets'));
        $this->setAttribute('autoplay', new BoolType(false));
        $this->setAttribute('displayNumbers', new BoolType(false));
        $this->setAttribute('displayThumbs', new BoolType(true));
        $this->setAttribute('draggable', new BoolType(true));
        $this->setAttribute('fade', new BoolType(true));
        $this->setAttribute('slideTime', new IntType(300));
        $this->setAttribute('showMainArrows', new BoolType(false));
        $this->setAttribute('showMainDots', new BoolType(false));
        $this->setAttribute('showThumbArrows', new BoolType(true));
        $this->setAttribute('showThumbDots', new BoolType(false));
    }
    public function enqueueAssets() {
        wp_enqueue_style('slickcss', plugins_url('wovax-idx/assets/libraries/slick/slick.css'));
        wp_enqueue_script('slickjs', plugins_url('wovax-idx/assets/libraries/slick/slick.min.js'));
    }
    public function generateHTML($attr, $content) {
        $class  = $attr['className'];
        $photos = $this->getListing()->getImages();
        if(empty($photos)) {
            return '';
        }
        $mains  = array();
        $mains_set = array(
            'slidesToShow'   => 1,
            'adaptiveHeight' => true,
            'arrows'         => $attr['showMainArrows'],
            'autoplay'       => $attr['autoplay'],
            'dots'           => $attr['showMainDots'],
            'fade'           => $attr['fade'],
            'infinite'       => true,
            'speed'          => $attr['slideTime']
        );
        $thumbs = array();
        $thumbs_set = array(
            'slidesToShow'   => 5,
            'slidesToScroll' => 5,
            'adaptiveHeight' => true,
            'arrows'         => $attr['showThumbArrows'],
            //'centerMode'     => false,
            'centerMode'     => true,
            'dots'           => $attr['showThumbDots'],
            'focusOnSelect'  => true,
            'infinite'       => true,
            'speed'          => $attr['slideTime']
        );
        $number = 1;
        foreach($photos as $photo) {
            $alt      = empty($photo['alt']) ? 'Property Listing Photo' : $photo['alt'];
            $src      = 'https://cache.wovax.com/imgp.php?src='.esc_url($photo['url']);
            $main     = '<div>';
            $main    .= "<img src=\"$src&width=1800&aro\" alt=\"$alt\">";
            if($attr['displayNumbers']) {
                $main .= "<div class='wx-idx-image-numbers'>";
                $main .= "<small>".$number." of ".count($photos)."</small>";
                $main .= "</div>";
            }
            $main    .= "</div>\n";
            $thumbs[] = "<div><img src=\"$src&width=400&height=250&crop-to-fit&aro\" alt=\"$alt\"></div>\n";
            $mains[]  = $main;
            $number++;
        }
        if(count($mains) <= 1) {
            return $mains[0]."\n";
        }
        $el_id = Base32::getRandomID();
		$main = 'wovax-main-sync-'.$el_id;
		$thumb = 'wovax-thumb-sync-'.$el_id;
        $html  = '<div class="'.$class.'">';
        $html .= '<section class="wovax-idx-slider slider no-select '.$main.'">'."\n";
        $html .= implode("\n", $mains);
        $html .= "</section>\n";
        if($attr['displayThumbs']) {
            $mains_set['asNavFor'] = ".$thumb";
            $thumbs_set['asNavFor'] = ".$main";
            $html .= '<section class="wovax-idx-slider-thumbnails slider no-select '.$thumb.'">'."\n";
            $html .= implode("\n", $thumbs);
            $html .= "</section>\n";
            $html .= "</div>";
        }
        $html .= "<script>\njQuery(document).ready(function() {\n\t";
        $html .= 'jQuery(".'.$main.'").slick('.json_encode($mains_set).');';
        $html .= "\n\t";
        if($attr['displayThumbs']) {
            $html .= 'jQuery(".'.$thumb.'").slick('.json_encode($thumbs_set).');';
        }
        $html .= "\n});\n</script>";
        return $html;
    }
}

abstract class WovaxMapBlock extends WoxaxIdxListingBlock {
    private static $queued = FALSE;
    public function __construct($name, Listing $listing, $arguments = array()) {
        parent::__construct($name, $listing, $arguments);
        $this->setAttribute('height', new IntType(500));
        // We only want to enque this once between the two types of map blocks.
        if(!self::$queued) {
            self::$queued = TRUE;
            add_action('enqueue_block_assets', array($this, 'enqueueLoaderScript'));
        }
    }
    public function displayMessage($height, $msg) {
        $styles = array(
            'height'           => $height.'px',
            'width'            => '100%',
            'background-color' => '#FF365E',
            'color'            => 'white',
            'display'          => 'flex',
            'flexWrap'         => 'wrap',
            'align-items'      => 'center',
            'justify-content'  => 'center',
            'textAlign'        => 'center'
        );
        $textStyle = array(
            'font-weight' => 'bold',
            'color'       => '#FFF',
            'font-size'   => '12px'
        );
        $html  = '<div style="'.$this->buildInlineStyle($styles).'">';
        $html .= '<p style="'.$this->buildInlineStyle($textStyle).'">'.$msg.'</p>';
        $html .= '</div>';
        return $html;
    }
    public function enqueueLoaderScript() {
        wp_enqueue_script('wovax-idx-map-block-loader', plugins_url('assets/js/map-loader.es5.min.js', __FILE__));
    }
}

class ListingMap extends WovaxMapBlock {
    public function __construct(Listing $listing) {
        parent::__construct('listing-map', $listing);
    }
    public function generateHTML($attr, $content) {
        $class  = $attr['className'];
        $height = $attr['height'];
        $type   = isset($attr['mapType']) ? $attr['mapType'] : '';
        $color  = isset($attr['listingPinColor']) ? $attr['listingPinColor'] : '';
        $lat    = $this->getListing()->getField('Latitude');
        $long   = $this->getListing()->getField('Longitude');
        if(!is_numeric($lat) || !is_numeric($long)) {
            return '';
        }
        if($lat == 0 && $long == 0) {
            return '';
        }
        if(strlen($type) < 1) {
            return $this->displayMessage($height, 'Select a map provider for the map.');
        }
        $token      = '';
        $map_class  = '';
        $opt = new InitialSetup();
        switch($type) {
            case 'google':
                $token = $opt->googleMapsKey();
                $map_class = 'WovaxIdxGoogleMap';
                break;
            case 'location_iq':
                $token = $opt->locationIqKey();
                $map_class = 'WovaxIdxLocationIqMap';
                break;
            case 'map_quest':
                $token = $opt->mapQuestKey();
                $map_class = 'WovaxIdxMapQuest';
                break;
        }
        if(strlen($token) < 1) {
            return $this->displayMessage($height, 'The Wovax IDX maps block requires an API key to be set on the Wovax IDX Initial Setup page.');
        }
        $id     = Base32::getRandomID();
        $mapId = 'wovax-idx-map-'.$id;
        $html  = "<div class=\"$class\" style=\"width:100%;height:".$height."px;margin-top:1em;margin-bottom:1em;\" id=\"$mapId\"></div>";
        $html .= '<script>';
        $html .= "var map_$id = new $map_class('$mapId', '$token', $lat, $long);";
        $html .= "(new WovaxIdxMapLoader()).registerMap(map_$id);";
        $html .= "map_$id.addMarker(new WovaxIdxMarker($lat, $long, '$color'));";
        $html .= '</script>';
        return $html;
    }
}

class PoiMap extends WovaxMapBlock {
    private $token = '';
    public function __construct(Listing $listing) {
        parent::__construct('points-of-interest', $listing);
        $this->token = (new InitialSetup())->locationIqKey();
        add_action('enqueue_block_assets', array($this, 'enqueueAssets'));
        $this->setAttribute('places', new ArrayType(new StringType(''), array('college','school')));
        $this->setAttribute('searchRadiusMiles', new RealType(1.5));
        $this->setAttribute('pinColors', new StructType(
            ['__listing',        new StringType('')],
            ['airport',          new StringType('')],
            ['atm',              new StringType('')],
            ['bank',             new StringType('')],
            ['bus_station',      new StringType('')],
            ['cinema',           new StringType('')],
            ['college',          new StringType('')],
            ['fuel',             new StringType('')],
            ['gym',              new StringType('')],
            ['hospital',         new StringType('')],
            ['hotel',            new StringType('')],
            ['park',             new StringType('')],
            ['pharmacy',         new StringType('')],
            ['place_of_worship', new StringType('')],
            ['pub',              new StringType('')],
            ['railway_station',  new StringType('')],
            ['restaurant',       new StringType('')],
            ['school',           new StringType('')],
            ['stadium',          new StringType('')],
            ['supermarket',      new StringType('')],
            ['toilet',           new StringType('')]
        ));
    }
    public function enqueueAssets() {
        wp_add_inline_script(
            'wovax-idx-map-block-loader',
            'var WovaxIdxPoiApi = new WovaxIdxLocationIqPOI(\''.$this->getToken().'\');',
            'after'
        );
    }
    public function getToken() {
        return $this->token;
    }
    public function generateHTML($attr, $content) {
        $token  = $this->getToken();
        $class  = $attr['className'];
        $height = $attr['height'];
        $places = $attr['places'];
        $radius = $attr['searchRadiusMiles'];
        $colors = $attr['pinColors'];
        $color  = $colors['__listing'];
        $lat    = $this->getListing()->getField('Latitude');
        $long   = $this->getListing()->getField('Longitude');
        if(!is_numeric($lat) || !is_numeric($long)) {
            return '';
        }
        if($lat == 0 && $long == 0) {
            return '';
        }
        if(strlen($token) < 1) {
            return $this->displayMessage($height, 'The Points of Interest block requires a Location IQ key to be set on the Wovax IDX Initial Setup page.');
        }
        $radius *= 1609.344; // Convert to meters
        $id    = Base32::getRandomID();
        $mapId = 'wovax-idx-map-'.$id;
        $html  = "<div class=\"$class\" style=\"width:100%;height:".$height."px;margin-top:1em;margin-bottom:1em;\" id=\"$mapId\"></div>";
        $html .= '<script>';
        $html .= "var map_$id = new WovaxIdxLocationIqMap('$mapId', '$token', $lat, $long);";
        $html .= "(new WovaxIdxMapLoader()).registerMap(map_$id);";
        $html .= "map_$id.addMarker(new WovaxIdxMarker($lat, $long, ''));\n";
        // Generate Script to grab places of interest.
        $combine = array();
        foreach($places as $place) {
            $color = '';
            if(array_key_exists($place, $colors)) {
                $color = $colors[$place];
            }
            $combine[] = array(
                $place,
                $color
            );
        }
        $html .= json_encode($combine).".map(function(place_type) {
    var color = place_type[1];
    var type  = place_type[0];
    WovaxIdxPoiApi.getPlaces(type, '$lat', '$long', '$radius', function(places) {
        places.map(function(place) {
                var m = new WovaxIdxMarker(place.lat, place.lon, color, type);
                if(place.hasOwnProperty('name')) {
                    m.setPopup(place.name, '');
                }
                map_$id.addMarker(m);
        });
    });
});\n";
        $html .= '</script>';
        return $html;
    }
}

class FavoriteBlock extends WoxaxIdxListingBlock {
    public function __construct(Listing $listing) {
        parent::__construct('favorite', $listing);
        add_action('enqueue_block_assets', array($this, 'enqueueAssets'));
    }
    public function enqueueAssets() {
        wp_enqueue_style('wovax-idx-favorites', WOVAX_PLUGIN_URL.'assets/css/favorites.css');
        wp_enqueue_script('wovax-idx-favorites', WOVAX_PLUGIN_URL.'assets/js/favorites.js', array('jquery'), false, true);
        wp_add_inline_script(
            'wovax-idx-favorites',
            'WovaxIdxFavs.ajaxUrl = '.json_encode(admin_url('admin-ajax.php')).';',
            'after'
        );
    }
    public function generateHTML($attr, $content) {
        $fav     = 'no';
        $feed_id = $this->getListing()->getFeedId();
        $mls_id  = $this->getListing()->getField('MLS Number');
        if(is_user_logged_in()) {
            $favs = json_decode(
                get_user_meta(
                    get_current_user_id(),
                    'wovax-idx-favorites',
                    true
                )
            );
            if(is_array($favs) && in_array(array($feed_id, $mls_id), $favs)) {
                $fav = 'yes';
            }

        }
        $html  = '<div class="'.$attr['className'].'" style="display: flex;justify-content: center;">';
        $html .= '<div class="wovax-idx-heart" data-idx-fav="'.$fav.'" data-idx-feed="'.$feed_id.'" data-idx-id="'.$mls_id.'"></div>';
        $html .= '</div>';
        return $html;
    }
}

class TimeStampBlock extends WovaxIdxBlock {
    public function __construct() {
        parent::__construct('time-stamp');
        $this->setAttribute('feedID', new StringType('0'));
    }
    public function generateHTML($attr, $content) {
        $feeds = WovaxConnect::createFromOptions()->getFeedList();
        $feed  = reset($feeds);
        if(array_key_exists($attr['feedID'], $feeds)) {
            $feed = $feeds[$attr['feedID']];
        }
        $html  = '<div class="'.$attr['className'].'">';
        $html .= $feed['updated_timestamp'];
        $html .= '</div>';
        return $html;
    }
}
add_action('init', function() {
    register_block_type(new TimeStampBlock());
});
