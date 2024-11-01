<?php
/*
 * Description : The data field widget for Wovax IDX
 * Author      : Keith Cancel
 * Author Email: admin@keith.pro
 */

namespace Wovax\IDX\Integration\Elementor;


use Wovax\IDX\API\WovaxConnect;
use Wovax\IDX\Utilities\Base32;
use Wovax\IDX\Settings\InitialSetup;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

class MapWidget extends \Elementor\Widget_Base {
	private $poi_types = array(
		['airport',          'Airports'],
		['atm',              'ATM'],
		['bank',             'Banks'],
		['bus_station',      'Bus Stations'],
		['cinema',           'Movie Theatres'],
		['college',          'Colleges'],
		['fuel',             'Gas Stations'],
		['gym',              'Gyms'],
		['hospital',         'Hospitals'],
		['hotel',            'Hotels'],
		['park',             'Parks'],
		['pharmacy',         'Pharmacy'],
		//['place_of_worship', 'Places of Worship'],
		['pub',              'Pubs/Bars'],
		['railway_station',  'Railway Statsions'],
		['restaurant',       'Restaurant'],
		['school',           'Schools'],
		['stadium',          'Stadiums'],
		['supermarket',      'Supermarkets'],
		//['toilet',           'Restrooms']
	);

	public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
		//wp_register_script('wovax-idx-maps-loader', plugins_url('../../blocks/assets/js/map-loader.es6.js', __FILE__ ), [ 'elementor-frontend' ], '1.0.0', true);
		//wp_register_script('wovax-idx-elementor-maps', plugins_url('assets/js/map-handler.js', __FILE__ ), [ 'wovax-idx-maps-loader' ]. '1.0.0', true);
	}

	public function get_script_depends() {
        return ['wovax-idx-maps-loader', 'wovax-idx-elementor-maps'];
	}

	public function get_name() {
		return 'wovax_idx_elementor_maps';
    }

	public function get_title() {
		return 'Wovax Maps';
    }

	public function get_icon() {
		return 'eicon-google-maps';
    }

	public function get_categories() {
        return [ 'wovax-idx' ];
	}

	public function get_keywords() {
		return ['wovax', 'mapkit', 'google', 'map', 'embed', 'location'];
	}


	protected function register_controls() {
		$this->start_controls_section(
			'section_map',
			[
				'label' => 'Wovax Maps'
			]
		);

        $this->add_control(
			'wovax_idx_map', array(
				'label'   => 'Map Provider',
				'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'google-map',
				'options' => array(
                    'google-map'   => 'Google Maps',
                    'apple-mapkit' => 'Apple Maps',
                    'location-iq'  => 'Location IQ',
                    'mapquest'    => 'MapQuest',
                )
            )
		);

		$this->add_control(
			'wovax_idx_map_view', array(
				'label'   => 'Default View',
				'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'roadmap',
				'options' => array(
                    'roadmap'   => 'Road Map',
                    'hybrid'    => 'Hybrid',
                    'satellite' => 'Satellite',
                ),
				'conditions' => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'     => 'wovax_idx_map',
							'operator' => '==',
							'value'    => 'google-map'
						),
						array(
							'name'     => 'wovax_idx_map',
							'operator' => '==',
							'value'    => 'apple-mapkit'
						)
					)
				)
            )
		);

		$this->add_control(
			'wovax_idx_pin_color', array(
				'label'   => 'Pin Color',
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '#FF365E'
            )
		);

		$this->add_control(
			'wovax_idx_map_zoom',
			array(
				'label' => 'Zoom',
				'type' => \Elementor\Controls_Manager::SLIDER,
				'default' => array(
					'size' => 16,
                ),
				'range' => array(
					'px' => array(
						'min' => 0,
						'max' => 18,
                    ),
                ),
				'separator' => 'before',
            )
		);

		$this->add_responsive_control(
			'wovax_idx_map_height',
			array(
				'label' => 'Height',
				'type' => \Elementor\Controls_Manager::SLIDER,
				'range' => array(
					'px' => array(
						'min' => 40,
						'max' => 1440,
                    ),
				),
				'default' => array(
					'size' => 400
				),
				'selectors' => array(
					'{{WRAPPER}} .wovax-idx-map-widget' => 'height: {{SIZE}}{{UNIT}};'
                ),
            )
		);



		$this->add_control(
			'wovax_idx_poi', array(
                'label'        => 'Points of Interest',
                'show_label'   => true,
				'type'         =>  \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => 'Enable',
				'label_off'    => 'Disable',
				'return_value' => 'yes',
                'default'      => 'yes',
                'condition'  => array(
                    'wovax_idx_map' => 'location-iq'
                )
            )
		);

		$this->add_control(
			'wovax_idx_poi_radius',
			array(
				'label' => 'Search Radius',
				'type'  => \Elementor\Controls_Manager::SLIDER,
				'default' => array(
					'size' => 1,
                ),
				'range' => array(
					'px' => array(
						'min' => .25,
						'max' => 5.0,
						'step' => .25
                    ),
                ),
				'separator' => 'before',
				'conditions' => array(
					'relation' => 'and',
					'terms'    => array(
						array(
							'name'     => 'wovax_idx_poi',
							'operator' => '==',
							'value'    => 'yes'
						),
						array(
							'name'     => 'wovax_idx_map',
							'operator' => '==',
							'value'    => 'location-iq'
						)
					)
				)
            )
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_wovax_poi_sec', array(
				'label'      => 'Points of Interest',
				'conditions' => array(
					'relation' => 'and',
					'terms'    => array(
						array(
							'name'     => 'wovax_idx_poi',
							'operator' => '==',
							'value'    => 'yes'
						),
						array(
							'name'     => 'wovax_idx_map',
							'operator' => '==',
							'value'    => 'location-iq'
						)
					)
				)
			)
		);

		foreach($this->poi_types as $type) {
			$name  = $type[0];
			$label = $type[1];
			$this->add_control(
				'wovax_idx_poi_label_' . $name, array(
					'label'     => $label,
					'type'      => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				)
			);
			$this->add_control(
				'wovax_idx_' . $name, array(
					'label'        => 'Show',
					'show_label'   => true,
					'type'         =>  \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => 'Show',
					'label_off'    => 'Hide',
					'return_value' => 'yes',
					'default'      => 'yes',
					'condition'  => array(
						'wovax_idx_poi' => 'yes'
					)
				)
			);
			$this->add_control(
				'wovax_idx_color_' . $name, array(
					'label'   => 'Pin Color',
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => '#FF365E',
					'conditions' => array(
						'relation' => 'and',
						'terms'    => array(
							array(
								'name'     => 'wovax_idx_poi',
								'operator' => '==',
								'value'    => 'yes'
							),
							array(
								'name'     => 'wovax_idx_' . $name,
								'operator' => '==',
								'value'    => 'yes'
							)
						)
					)
				)
			);
		}
		$this->end_controls_section();
	}

	protected function render() {
		$listing   = \Wovax\IDX\Utilities\CurrentListing::getInstance()->getListing();
		$settings  = $this->get_settings_for_display();
		$id        = Base32::getRandomID();
		$mapId     = 'wovax_idx_map_' . $id;
		$map_class = 'WovaxIdxGoogleMap';
		$opt       = new InitialSetup();
		$token     = '';
		$long      = -117.0010062;
		$lat       = 46.7323603;
		$view      = $settings['wovax_idx_map_view'];
		$radius    = 1609.344 * floatval($settings['wovax_idx_poi_radius']); // Convert to meters
		switch($settings['wovax_idx_map']) {
            case 'google-map':
                $token = $opt->googleMapsKey();
                $map_class = 'WovaxIdxGoogleMap';
                break;
            case 'location-iq':
                $token = $opt->locationIqKey();
                $map_class = 'WovaxIdxLocationIqMap';
                break;
            case 'mapquest':
                $token = $opt->mapQuestKey();
                $map_class = 'WovaxIdxMapQuest';
				break;
			case 'apple-mapkit':
				$token     = $opt->appleMapsToken();
				$map_class = 'WovaxAppleMap';
				break;
		}
		if($listing != NULL) {
			$lat  = floatval($listing->getField('Latitude'));
			$long = floatval($listing->getField('Longitude'));
		}
		if($lat == 0.0 && $long == 0.0) {
			return;
		}
		$loader = plugins_url('../../blocks/assets/js/map-loader.es5.min.js', __FILE__ );

		if(strlen($token) < 1) {
			echo '<div class="wovax-idx-map-widget" style="text-align: center;" id="'. $mapId .'"><H2>No Token for this map provider!</H2></div>';
		    return;
		}


		// ==== NOTE ===
		// Normally I would use wp_register_script(), but elementor loads the rendered page in an iframe.
		// This was causing issues in apple MapKit because wp_register_script() was loading the script
		// in the main document, instead of the iframe.
		// The issue is related to instanceof which for instance "myObject instanceof Object" ect...
		// behaving differently since Object would referer the main windows Object and not the iframes
		// Object. Same thing with other types.

		// This ensures we only load the script once this is a simple loader
		// Once loaded it also sets a flag so if a map is added after it loads
		// we know we can just render the map immediately.
		?>
		<script>
			function wovax_idx_get_element(id) {
				var frame = null;
				if(window.parent != null) {
					frame = window.parent.document.getElementById('elementor-preview-iframe');
				}
				if(frame != null) {
					var elem  = frame.contentWindow.document.getElementById(id);
					return elem;
				}
				return document.getElementById(id);
			}
			// This really weird calling this here causes it to work properly in safari.
			// I think elementor does something strange if we wait for the script to be called
			<?php echo "wovax_idx_get_element('$mapId');\n"; ?>
			if(window.document.getElementById("wovax_idx_map_loader") == null) {
				wovax_idx_get_element();
				wovax_idx_map_loader_loaded = false;
				var resource = window.document.createElement("script");
				resource.type  = 'text/javascript';
				resource.src   = "<?php echo $loader; ?>";
				resource.setAttribute("id", "wovax_idx_map_loader");
				resource.addEventListener('load', function() {
					wovax_idx_map_loader_loaded = true;
					for(var key in window) {
						var item = window[key];
						if(typeof item !== 'function') {
							continue;
						}
						// Find functions to call to start render
						if(key.startsWith("func_wovax_idx_map_")) {
							item();
						}
					}
				});
				window.document.getElementsByTagName('head')[0].appendChild(resource);
			}
		</script>
		<div class="wovax-idx-map-widget" id="<?php echo $mapId; ?>"></div>
		<script>
			function func_<?php echo $mapId; ?>() {
				var map_el = wovax_idx_get_element('<?php echo $mapId ?>');
				<?php echo "var map_inst = new $map_class(map_el, '$token', $lat, $long);\n"; ?>
				(new WovaxIdxMapLoader()).registerMap(map_inst);
				map_inst.addMarker(new WovaxIdxMarker(<?php echo $lat; ?>, <?php echo $long; ?>, '<?php echo $settings['wovax_idx_pin_color']; ?>'));
				map_inst.setZoom(<?php echo intval($settings['wovax_idx_map_zoom']['size']); ?>);
				map_inst.setView('<?php echo $view; ?>');
				map_el.parentElement.style.overflow = 'hidden';
			<?php
			// Generate Pins of for POI
			if($settings['wovax_idx_map'] == 'location-iq' && $settings['wovax_idx_poi'] == 'yes') {
				$places = array();
				foreach($this->poi_types as $type) {

					if($settings['wovax_idx_' . $type[0]] != 'yes') {
						continue;
					}
					$places[] = array(
						$type[0],
						$settings['wovax_idx_color_' . $type[0]]
					);
				}
				echo "var WovaxIdxPoiApi = new WovaxIdxLocationIqPOI('$token');\n";
				echo json_encode($places).".map(function(place_type) {\n";?>
					var color = place_type[1];
    				var type  = place_type[0];
					WovaxIdxPoiApi.getPlaces(type, '<?php echo $lat; ?>', '<?php echo $long; ?>', '<?php echo $radius; ?>', function(places) {
						places.map(function(place) {
							var m = new WovaxIdxMarker(place.lat, place.lon, color, type);
							if(place.hasOwnProperty('name')) {
								m.setPopup(place.name, '');
							}
							map_inst.addMarker(m);
						});
					});
				<?php echo "});\n";
			}
			?>
			}
			if(wovax_idx_map_loader_loaded) {
				func_<?php echo $mapId; ?>();
			}
		</script>
		<?php
	}
}
