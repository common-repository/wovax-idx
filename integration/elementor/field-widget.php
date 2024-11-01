<?php
/*
 * Description : The data field widget for Wovax IDX
 * Author      : Keith Cancel
 * Author Email: admin@keith.pro
 */

namespace Wovax\IDX\Integration\Elementor;

use Wovax\IDX\API\WovaxConnect;


// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

class FieldWidget extends \Elementor\Widget_Base {
    private $listing  = NULL;
	public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
        $this->listing = \Wovax\IDX\Utilities\CurrentListing::getInstance()->getListing();
    }

	public function get_name() {
		return 'field-data';
	}

	public function get_title() {
		return 'Field Data';
	}

	public function get_icon() {
		return 'eicon-t-letter';
    }

    public function get_keywords() {
        return [
            'field', 'wovax', 'text'
        ];
    }

    public function get_categories() {
        return [ 'wovax-idx' ];
	}

    protected function register_controls() {
		$this->start_controls_section(
			'section_title', array(
				'label' => 'Field Data ',
            )
        );


        $api    = WovaxConnect::createFromOptions();
        $fields = array();
        // Their select widget requires an array with keys
		foreach ($api->getAggregatedFields() as $value) {
            $fields[$value] = $value;
        }

        /*
            Important NOTE - Control names can't have '-' in them
            After spending quite some time trying to figure out why
            conditions were not working. I found the conditions code in
            elementor runs the name through a regex which uses \w which
            is equal to to [a-zA-Z0-9_], so names like 'wovax-idx-field-type'
            would fail.
        */
        $numeric_or_price = array(
            'relation' => 'or',
            'terms' => array(
                array(
                    'name'     => 'wovax_idx_field_type',
                    'operator' => '===',
                    'value'    => 'num',
                ),
                array(
                    'name'     => 'wovax_idx_field_type',
                    'operator' => '===',
                    'value'    => 'price',
                ),
            )
        );

        $this->add_control(
			'wovax_idx_field', array(
				'label'   => 'Data Field',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => reset($fields),
				'options' => $fields
            )
        );

		
        $this->add_control(
			'wovax_idx_label', array(
                'label'        => 'Show Field Label',
                'show_label'   => true,
				'type'         =>  \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => 'Show',
				'label_off'    => 'Hide',
				'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
			'wovax_idx_custom_label', array(
				'label'       => 'Custom Label',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
                'placeholder' => 'Type your label here',
                'condition'  => array(
                    'wovax_idx_label' => 'yes'
                )
            )
		);

        $this->add_control(
			'wovax_idx_display', array(
				'label'   => 'Display Style',
				'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'space-between',
                'condition' => array(
                    'wovax_idx_label' => 'yes'
                ),
				'options' => array(
                    'flex-start'    => 'Inline',
                    'space-between' => 'Space Between',
                    'stacked'       => 'Stacked',
                )
            )
        );

        $this->add_control(
            'wovax_idx_align', array(
            'label'     => 'Alignment',
            'type'      => \Elementor\Controls_Manager::CHOOSE,
            'default'   => 'left',
            'condition' => array(
                'wovax_idx_label!' => 'yes'
            ),
            'selectors' => array(
                '{{WRAPPER}} .wovax-idx-field-data' => 'text-align: {{VALUE}};',
            ),
            'options' => array(
                    'left' => array(
                        'title' => 'Left',
                        'icon'  => 'eicon-text-align-left',
                    ),
                    'center' => array(
                        'title' => 'Center',
                        'icon'  => 'eicon-text-align-center',
                    ),
                    'right' => array(
                        'title' => 'Right',
                        'icon'  => 'eicon-text-align-right',
                    )
                )
            )
        );

        $this->add_control(
			'wovax_idx_field_type', array(
				'label'   => 'Data Type',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'text',
				'options' => array(
                    'link'  => 'Link',
                    'num'   => 'Numeric',
                    'price' => 'Price',
                    'text'  => 'Text',
                )
            )
        );

        $this->add_control(
			'wovax_idx_commas', array(
                'label'        => 'Show Commas',
                'show_label'   => true,
				'type'         =>  \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => 'Show',
				'label_off'    => 'Hide',
				'return_value' => 'yes',
                'default'      => 'yes',
                'conditions'   => $numeric_or_price
            )
        );

        $this->add_control(
			'wovax_idx_decimals', array(
                'label'      => 'Decimals',
				'type'       => \Elementor\Controls_Manager::NUMBER,
				'min'        => 0,
				'max'        => 10,
				'step'       => 1,
                'default'    => 2,
                'conditions' => $numeric_or_price
            )
		);

        $this->add_control(
			'wovax_idx_money_pos', array(
				'label'   => 'Currency Symbol Position',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'left',
				'options' => array(
                    'left'  => 'Left',
                    'right' => 'Right',
                ),
                'condition' => array(
                    'wovax_idx_field_type' => 'price'
                )
            )
        );

        $this->add_control(
			'wovax_idx_symbol', array(
				'label'       => 'Currency Symbol',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '$',
                'placeholder' => '$',
                'condition' => array(
                    'wovax_idx_field_type' => 'price'
                )
            )
		);

        $this->end_controls_section();


        // Style Tab
        $this->start_controls_section(
			'wovax_idx_label_style', array(
				'label' => 'Label',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'wovax_idx_label' => 'yes'
                )
            )
        );

        $this->add_control(
			'wovax_idx_label_color', array(
				'label'  => 'Text Color',
                'type'   => \Elementor\Controls_Manager::COLOR,
                'global' => array(
					'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_TEXT,
                ),
                'selectors' => array(
					'{{WRAPPER}} .wovax-idx-field-label' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(), array(
				'name' => 'wovax_idx_label_typography',
				'global' => array(
					'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Typography::TYPOGRAPHY_PRIMARY,
                ),
				'selector' => '{{WRAPPER}} .wovax-idx-field-label',
            )
		);

        $this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(), array(
				'name'     => 'wovax_idx_label_shadow',
				'selector' => '{{WRAPPER}} .wovax-idx-field-label',
            )
		);

		$this->add_control(
			'wovax_idx_label_blend', array(
				'label' => 'Blend Mode',
				'type'  => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					''            => 'None',
					'multiply'    => 'Multiply',
					'screen'      => 'Screen',
					'overlay'     => 'Overlay',
					'darken'      => 'Darken',
					'lighten'     => 'Lighten',
					'color-dodge' => 'Color Dodge',
					'saturation'  => 'Saturation',
					'color'       => 'Color',
					'difference'  => 'Difference',
					'exclusion'   => 'Exclusion',
					'hue'         => 'Hue',
					'luminosity'  => 'Luminosity',
                ),
				'selectors' => array(
					'{{WRAPPER}} .wovax-idx-field-label' => 'mix-blend-mode: {{VALUE}}',
                ),
				'separator' => 'none'
            )
		);


        $this->end_controls_section();

        $this->start_controls_section(
            'wovax_idx_data_style', array(
                'label' => 'Data',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
			'wovax_idx_data_color', array(
				'label'  => 'Text Color',
                'type'   => \Elementor\Controls_Manager::COLOR,
                'global' => array(
					'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_TEXT,
                ),
                'selectors' => array(
					'{{WRAPPER}} .wovax-idx-field-data' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(), array(
				'name' => 'wovax_idx_data_typography',
				'global' => array(
					'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Typography::TYPOGRAPHY_PRIMARY,
                ),
				'selector' => '{{WRAPPER}} .wovax-idx-field-data',
            )
		);

        $this->add_group_control(
			\Elementor\Group_Control_Text_Shadow::get_type(), array(
				'name'     => 'wovax_idx_data_shadow',
				'selector' => '{{WRAPPER}} .wovax-idx-field-data',
            )
		);

		$this->add_control(
			'wovax_idx_data_blend', array(
				'label' => 'Blend Mode',
				'type'  => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					''            => 'None',
					'multiply'    => 'Multiply',
					'screen'      => 'Screen',
					'overlay'     => 'Overlay',
					'darken'      => 'Darken',
					'lighten'     => 'Lighten',
					'color-dodge' => 'Color Dodge',
					'saturation'  => 'Saturation',
					'color'       => 'Color',
					'difference'  => 'Difference',
					'exclusion'   => 'Exclusion',
					'hue'         => 'Hue',
					'luminosity'  => 'Luminosity',
                ),
				'selectors' => array(
					'{{WRAPPER}} .wovax-idx-field-data' => 'mix-blend-mode: {{VALUE}}',
                ),
				'separator' => 'none'
            )
		);

        $this->end_controls_section();
    }

    // This method is used in the Editor only. It returns a template that is
    // parsed by the Backbone Marionette framework to render via JavaScript.
    protected function content_template() {
		$url = admin_url('admin-ajax.php', 'relative');
        // '<#' starts a JS block and '#>' ends it
        ?>
        <#
        var rand_bytes = new Uint8Array(12);
        var rand_id    = 'wx-editor-id-'
        window.crypto.getRandomValues(rand_bytes);
        rand_bytes.map(function(value) {
            var str = value.toString(16);
            if(str.length < 2) {
                str = '0' + str;
            }
            rand_id += str;
        });

        if (typeof settings.wovax_idx_decimals !== 'number') {
            settings.wovax_idx_decimals = 2;
        }
        var label      = settings.wovax_idx_custom_label.trim();
        var num_part   = settings.wovax_idx_commas == 'yes' ? '1,200,000' : '1200000';
        var dec_part   = Number(.1415926535).toFixed(settings.wovax_idx_decimals);
        var data_html  = 'Field Data';
        var cur_symbol = settings.wovax_idx_symbol.length > 0 ? settings.wovax_idx_symbol : '$';

        if(label.length < 1) {
            label = settings.wovax_idx_field;
        }

        switch(settings.wovax_idx_field_type) {
            case 'link':
                data_html = '<a target="_blank" href="#">Click Here</a>';
                break;
            case 'num':
                data_html = num_part + dec_part.toString();
                break;
            case 'price':
                data_html  = settings.wovax_idx_money_pos != 'right' ? cur_symbol : '';
                data_html += num_part + dec_part.toString();
                data_html += settings.wovax_idx_money_pos == 'right' ? cur_symbol : '';
                break;
            default:
                break;
        }
        var label_html_start = '';
        var label_html_close = '';
        var flex_justify     = settings.wovax_idx_display == 'space-between' ? 'space-between' : 'flex-start';
        var flex_dir         = settings.wovax_idx_display == 'stacked' ? 'column' : 'row';
        var flex_style       = 'display: flex; flex-direction: ' + flex_dir + '; justify-content: ' + flex_justify + ';';

        if(settings.wovax_idx_label == 'yes') {
            label_html_start  = '<div style="' + flex_style + '">';
            label_html_start += '<div class="wovax-idx-field-label"><strong>' + label + '</strong></div>';
            label_html_close  = '</div>';
        }
        #>
        {{{ label_html_start }}}
            <div class="wovax-idx-field-data" id="{{{ rand_id }}}">
                <p>{{{ data_html }}}</p>
            </div>
            <script>
                var log_field   = function(field, data_id) {
                    var request = new XMLHttpRequest();
                    request.open('POST', '<?php echo $url; ?>', true);
                    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                    request.onload = function () {
                        if (request.status >= 200 && request.status < 400) {
                            console.log('Saved Field!');
                        } else {
                            console.log('Failed to save Field');
                        }
                    };
                    request.send('action=wovax_idx_elementor_track_field&field_val=' + field + '&widget_id=' + data_id);
                }
                var get_data_id = function(el) {
                    var cur = el;
                    while(cur != null && !cur.hasAttribute('data-id')) {
                        cur = cur.parentElement;
                    }
                    if(cur == null) {
                        return '0';
                    }
                    return cur.getAttribute('data-id');
                }
                
                log_field('{{{settings.wovax_idx_field}}}', get_data_id(document.getElementById('{{{ rand_id }}}')));
            </script>
        {{{ label_html_close }}}

        <?php
    }

    protected function render() {
        // There is no listing data in the editor mode so we need to handle
        // this case differently. The elementor editor first calls render, and
        // subsequent changes to the setting values will use what is provided
        // by the content_template function
        if(!($this->listing instanceof \Wovax\IDX\Listing) ||
            \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            return;
        }


        $settings    = $this->get_settings_for_display();
        if(isset($settings['__globals__'])) {
            $globals = $settings['__globals__'];
            if(isset($globals['wovax_idx_label_color'])) {
                $label_color = $settings['__globals__']['wovax_idx_label_color'];
            } else {
                $label_color = $settings['wovax_idx_label_color'];
            }
            if(isset($globals['wovax_idx_data_color'])) {
                $data_color  = $settings['__globals__']['wovax_idx_data_color'];
            } else {
                $data_color  = $settings['wovax_idx_data_color'];
            }
           
        }
        $settings['wovax_idx_custom_label'] = trim($settings['wovax_idx_custom_label']);


        $label        = strlen($settings['wovax_idx_custom_label']) > 0 ? $settings['wovax_idx_custom_label'] : $settings['wovax_idx_field'];
        $value        = $this->getValueStr($settings, $this->listing->getField($settings["wovax_idx_field"]));
        $flex_justify = $settings['wovax_idx_display'] == 'space-between' ? 'space-between' : 'flex-start';
        $flex_dir     = $settings['wovax_idx_display'] == 'stacked' ? 'column' : 'row';
        $flex_style   = 'display: flex; flex-direction: ' . $flex_dir . '; justify-content: ' . $flex_justify . ';';


        if($settings['wovax_idx_label'] == 'yes') {
            echo '<div style="' . $flex_style . '">';
            echo '<div class="wovax-idx-field-label"><strong>' . esc_html($label) . '</strong></div>';
        }
        echo '<div class="wovax-idx-field-data">';
        echo $value;
        echo '</div>';
        if($settings['wovax_idx_label'] == 'yes') {
            echo '</div>';
        }
	}


    protected function getValueStr($settings, $value) {
        switch($settings["wovax_idx_field_type"]) {
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
				$value = $this->getPriceString(
                    $value,
                    $settings["wovax_idx_commas"] == 'yes',
                    intval($settings["wovax_idx_decimals"]),
                    $settings["wovax_idx_money_pos"] == 'left',
                    $settings["wovax_idx_symbol"]
                );
				break;
			case 'numeric':
				$value = $this->getNumericString(
                    $value,
                    $settings["wovax_idx_commas"] == 'yes',
                    intval($settings["wovax_idx_decimals"])
                );
				break;
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
    /* Seems like using a theme that handles the selectors properly does not need this.
    private function getColor($value) {
        $kit           = \Elementor\Plugin::$instance->kits_manager->get_active_kit_for_frontend();
        $system_colors = $kit->get_settings_for_display( 'system_colors' );
        switch($value) {
            case \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_PRIMARY:
                $value = $system_colors[0]['color'];
                break;
            case \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_SECONDARY:
                $value = $system_colors[1]['color'];
                break;
            case \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_TEXT:
                $value = $system_colors[2]['color'];
                break;
            case \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_ACCENT:
                $value = $system_colors[3]['color'];
                break;
            default:
                break;
        }
        if(strlen($value) < 1) {
            $value = $system_colors[2]['color'];
        }
        return $value;
    }*/
}