<?php

if (!defined('ABSPATH')) exit;

function wovax_idx_get_content_search_form($id, $view_columns, $view_option, $api_key) {
  $shortcode_details = wovax_idx_get_shortcode_by_id($id) [0]->order_section;
  $shortcode_filters = wovax_idx_find_shortcode_filters_by_id($id, $shortcode_details);
  if($view_columns === 0) {
    $column_override = '';
  } else {
    $column_override = 'wx-override col-'.$view_columns;
  }
  $fields_array = array();
  foreach ($shortcode_filters as $key => $value) {
    if($value->filter_type == 'select'){
      array_push($fields_array, $value->field);
    }
  }
  $action_url = wovax_idx_get_permalink_page_search_result();
  if(array_key_exists('wovax-idx-user-favorites', $_GET) && $_GET['wovax-idx-user-favorites'] == 'true') {
    $favorites_input = '';
  } else {
    $favorites_input = '<input type="hidden" id="wovax-idx-user-favorites" name="wovax-idx-user-favorites" value="">';
  }
  if( array_key_exists('wovax-idx-view-option', $_GET) && !empty(filter_var($_GET['wovax-idx-view-option'], FILTER_SANITIZE_STRING)) ) {
    $view_value = esc_attr(filter_var($_GET['wovax-idx-view-option'], FILTER_SANITIZE_STRING));
  } else {
    $view_value = $view_option;
  }
  $option_selected = '';
  $output = '';
  $output .= '<div class="wovax-idx-search-form-container '.esc_attr($column_override).'" id="wovax-idx-search-form">
                <form class="wovax-idx-form" id="form-tab-1" action="' . esc_attr($action_url) . '" method="get">
                  <input type="hidden" name="wovax-idx-shortcode-id" value="' . esc_attr($id) . '" >
                  <input type="hidden" id="wovax-idx-select-sort-hidden" name="wovax-idx-select-sort" value="" >
                  <input type="hidden" id="wovax-idx-button-view-option" name="wovax-idx-view-option" value="' . $view_value . '" >';
  $output .= $favorites_input;
  $feed_ids = wovax_idx_get_feeds_by_shortcode($id);
  $class_ids = wovax_idx_get_class_ids_by_shortcode($id);
  $rules = wovax_idx_get_rules_by_shortcode($fields_array, $id, 'search_form');
  if(!empty($fields_array)) {
    $values = wovax_idx_get_data_filter($shortcode_filters, $feed_ids, $api_key, $rules, $class_ids);
    if (is_array($values)) {
      $values = $values[1];
    } else {
      $error_message = 'No filter values returned from API for shortcode id ' . $id;
      error_log($error_message);
    }
  } else {
    $values = array();
  }
  if ($feed_ids != null && $shortcode_filters != null) {
    $shortcode_filters_count = count($shortcode_filters);
    $shortcode_filters_count++;
    switch($shortcode_filters_count) {
      case 2:
        $section_size = 'huge';
        $button_size = 'huge';
        break;
      case 3:
        $section_size = 'large';
        $button_size = 'large';
        break;
      case 4:
        $section_size = 'quarter';
        $button_size = 'quarter';
        break;
      case 5;
        $section_size = 'medium';
        $button_size = 'large';
        break;
      default;
        $section_size = 'medium';
        $button_size = 'medium';
        break;
    }
    foreach($shortcode_filters as $key => $value) {
      $search_value = "";
      if($value->filter_type === 'preset_value' || $value->filter_type === 'preset_range') {
        //skip preset value display
        continue;
      }
      // Field type string to insert in the URL
      $filter_id = strtolower(str_replace(' ', '_', $value->id_filter));
      $text = str_replace(' ', '_', $value->field);

      // Insert class "number" when filter is "Price"
      $class_holder = ($text == 'Price') ? "number" : "";

      // Edit query string in URL using filter type, field type and id
      $name = $value->filter_type . '-' . $text;
      $name = strtolower($name);
      foreach($_GET as $url_key => $url_value) {
        if ($url_key == $name . '-' . $filter_id) {
          $search_value = stripslashes($url_value);
        }
      }

      $output .= '<div class="wovax-idx-section '.$section_size.'">';

      // Store actual select's field name
      $field_name = $value->field;
      $data = array();

      // Compare field names to get values inside the array
      if($value->filter_type === 'select' && is_object($values)) {
        foreach($values as $key => $val) {
          if ($field_name == $key) {
            $data = $val;
          }
        }
      }

      // Look for placeholder for filter
      $placeholder = (empty($value->filter_placeholder)) ? $value->field : $value->filter_placeholder;

      $output .= '<label for="' . esc_attr($filter_id) . '">' . esc_html($value->filter_placeholder) . '</label>';

      // Creates filter depending on its filter type
      switch ($value->filter_type) {
        case "select":

          $output .= '<select id="' . esc_attr($filter_id) . '" name="' . esc_attr($name) . '-' . esc_attr($value->id_filter) . '"><option value="">' . esc_html($placeholder) . '</option>';
          $options = $data;
          if (is_array($options)) {
            foreach($options as $option => $val) {
              if (!empty($val)) {
                $option_selected = ($search_value == $val->alias_id && $search_value != '') ? 'selected' : '';
                $output .= '<option value="' . esc_attr($val->alias_id) . '" ' . $option_selected . ' >' . esc_html($val->alias_id) . '</option>';
              }
            }
          }
          $output .= '</select>';

          break;
        case "boolean":
          $output .= '<select id="' . esc_attr($filter_id) . '" name="' . esc_attr($name) . '-' . esc_attr($value->id_filter) . '"><option value="">' . esc_html($placeholder) . '</option>';
          $options = array(
            'Yes' => 'yes',
            'No'  => 'no'
          );
          if (is_array($options)) {
            foreach($options as $option => $val) {
              if (!empty($val)) {
                $option_selected = ($search_value == $val && $search_value != '') ? 'selected' : '';
                $output .= '<option value="' . esc_attr($val) . '" ' . $option_selected . ' >' . esc_html($option) . '</option>';
              }
            }
          }
          $output .= '</select>';

          break;
        case "numeric":

          $output .= '<input type="number" id="' . esc_attr($filter_id) . '" name="' . esc_attr($name) . '-' . esc_attr($value->id_filter) . '" placeholder="' . esc_attr($placeholder) . '" value="' . esc_attr($search_value) . '" class="' . esc_attr($class_holder) . '">';

          break;
        case "numeric_max":

          $output .= '<input type="number" id="' . esc_attr($filter_id) . '" name="' . esc_attr($name) . '-' . esc_attr($value->id_filter) . '" placeholder="' . esc_attr($placeholder) . '" value="' . esc_attr($search_value) . '" class="' . esc_attr($class_holder) . '">';

          break;
        case "numeric_min":

          $output .= '<input type="number" id="' . esc_attr($filter_id) . '" name="' . esc_attr($name) . '-' . esc_attr($value->id_filter) . '" placeholder="' . esc_attr($placeholder) . '" value="' . esc_attr($search_value) . '" class="' . esc_attr($class_holder) . '">';

          break;
        case "input_text":

          $output .= '<input type="text" id="' . esc_attr($filter_id) . '" name="' . esc_attr($name) . '-' . esc_attr($value->id_filter) . '" placeholder="' . esc_attr($placeholder) . '" value="' . esc_attr($search_value) . '" class="' . esc_attr($class_holder) . '">';

          break;
        case "omnisearch":

          $output .= '<input type="text" id="' . esc_attr($filter_id) . '" name="' . esc_attr($name) . '-' . esc_attr($value->id_filter) . '" placeholder="' . esc_attr($placeholder) . '" value="' . esc_attr($search_value) . '" class="' . esc_attr($class_holder) . '">';

          break;
        case "preset_value":
          break;
        case "range":

          $output .= '<select id="' . esc_attr($filter_id) . '" name="' . esc_attr($name) . '-' . esc_attr($value->id_filter) . '"><option value="">' . esc_html($placeholder) . '</option>';
          $range_data = json_decode($value->filter_data);
          $range_start = $range_data->range_start;
          $range_end = $range_data->range_end;
          $display_end = number_format($range_end);
          $range_interval = $range_data->interval;
          $range_array = array();
          if($range_interval === 'dynamic') {
            $start = intval(log10($range_start));
            $end = intval(log10($range_end));
            $current_value = $range_start;
            if($range_start !== 0) {
              $range_array["No Minimum"] = "0-$range_start";
            }
            
            for($i = $start; $i <= $end; $i++) {
              $interval = 10 ** $i;
              for($k = 0; $k < 9; $k++) {
                $new_value = $current_value + $interval;
                if($new_value > $range_end) {
                  break;
                }
                $display_current = number_format($current_value);
                $display_new = number_format($new_value);
                $range_array["$display_current - $display_new"] = "$current_value-$new_value";
                $current_value = $new_value;
                if($current_value >= $range_end) {
                  break;
                }
                if(intval(log10($current_value)) > $i) {
                  break;
                }
              }
            }
            $range_array["No Maximum"] = $range_end;
          } else {
            $range_array["No Minimum"] = "0-$range_start";
            for($i = $range_start; $i < $range_end; $i += $range_interval) {
              $new_value = $i + $range_interval;
              $display_current = number_format($i);
              $display_new = number_format($new_value);
              $range_array["$display_current - $display_new"] = "$i-$new_value";
              unset($new_value);
            }
            $range_array["No Maximum"] = $range_end;
          }
          if (is_array($range_array)) {
            foreach($range_array as $option => $val) {
              if (!empty($val)) {
                $option_selected = ($search_value == $val && $search_value != '') ? 'selected' : '';
                $output .= '<option value="' . esc_attr($val) . '" ' . $option_selected . ' >' . esc_html($option) . '</option>';
              }
            }
          }
          $output .= '</select>';

          break;
      }

      $output .= '</div>';

    } // end foreach

    $output .= '<div class="wovax-idx-section '.$button_size.'">
                  <a href="" class="wovax-idx-button wovax-idx-button-highlight" onclick="wovax_idx_submit_form(event, this);">Search</a>
                </div>
              </form>
            </div>
            <script>
            function wovax_idx_submit_form(event, element){
              event.preventDefault();
              var formSubmit = jQuery(element).closest("form");
              formSubmit.submit();
            }
            </script>';

    return $output;
  } else {
    $output .= '<div class="wovax-idx-section '.$button_size.'" >
                  <a href="" class="wovax-idx-button wovax-idx-button-highlight" onclick="wovax_idx_submit_form(event, this);">Search</a>
                </div>
              </form>
            </div>
            <script>
              function wovax_idx_submit_form(event, element){
                event.preventDefault();
                var formSubmit = jQuery(element).closest("form");
                formSubmit.submit();
              }
            </script>';

    return $output;
  }
}
?>
