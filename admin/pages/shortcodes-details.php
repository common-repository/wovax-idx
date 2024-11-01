<?php
if (defined('ABSPATH') === FALSE) {
    exit();// Exit via direct access.
}

use Wovax\IDX\API\WovaxConnect;

$active_tab = isset($_GET['tab']) ? filter_var($_GET['tab'], FILTER_SANITIZE_STRING) : 'general';

// Builds the select list for field Drop-downs
function getFieldSelectOptions($feed_ids, $selected = '') {
    $api       = WovaxConnect::createFromOptions();
    $select    = array();
    $group     = array();
    $feed_info = $api->getFeedDetails($feed_ids);
    foreach($feed_info as $field) {
        $name = $field['default_alias'];
        $group[] = $name;
    }
    foreach($group as $field_name) {
      $select[$field_name] = sprintf(
        '<option %s value="%s">%s</option>',
        ($field_name == $selected) ? 'selected' : '',
        esc_attr($field_name),
        esc_html($field_name)
      );
    }
    $str = sprintf('<option %s value="">Select Field</option>', strlen($selected) > 0 ? '' : 'selected');
    ksort($select);
    return $str.implode('', $select);
}

function get_warning_html($title, $msg) {
    $html = '<div style="font-size:30px;padding-left:15px;background:#FDECC4;border-left:9px solid #FFB913;color:#2c3e50;padding-top:20px;padding-bottom:1px;margin-top:5px;">';
    $html .= esc_html($title);
	$html .= '<p>'.esc_html($msg).'</p>';
    $html .= '</div>';
    return $html;
}

if ($_GET['id']) {
  /* Eit Shortcode */
  $wovax_idx_shortcode_header_title = 'Edit Shortcode  <span class="wp-ui-highlight">[wovax-idx id="' . esc_html(filter_var($_GET['id'], FILTER_SANITIZE_STRING)) . '"]</span>';
  $wovax_idx_shortcode_values = wovax_idx_get_shortcode_by_id(filter_var($_GET['id'], FILTER_SANITIZE_STRING));
  /* shortcode settings */
  $wovax_idx_type_val = $wovax_idx_shortcode_values[0]->type;
  $wovax_idx_title_val = $wovax_idx_shortcode_values[0]->title;
  $wovax_idx_grid_view_val = $wovax_idx_shortcode_values[0]->grid_view;
  $wovax_idx_map_view_val = $wovax_idx_shortcode_values[0]->map_view;
  $wovax_idx_per_page_val = $wovax_idx_shortcode_values[0]->per_page;
  $wovax_idx_pagination_val = $wovax_idx_shortcode_values[0]->pagination;
  $wovax_idx_feeds = json_decode($wovax_idx_shortcode_values[0]->feeds);
  $wovax_idx_order_section = $wovax_idx_shortcode_values[0]->order_section;
  $wovax_idx_per_map_val = $wovax_idx_shortcode_values[0]->per_map;
  $wovax_idx_action_bar_val = $wovax_idx_shortcode_values[0]->action_bar;

  // is null order filters or rules
  if (is_null($wovax_idx_order_section)) {
    $wovax_idx_order_section = array();
  }

  if ($_GET['tab'] == "filters") {
    /* Filters Tab */
    $delete = FALSE;
    if (isset($_GET['a']) && $_GET['a'] == 'delete') {
        $delete = TRUE;
        /* Delete filter */
        wovax_idx_shortcode_filter_delete(filter_var($_GET['id'], FILTER_SANITIZE_STRING) , filter_var($_GET['idfilter'], FILTER_SANITIZE_STRING));
    }
    /* New Filter */
    $wovax_idx_shortcode_filters = wovax_idx_find_shortcode_filters_by_id(filter_var($_GET['id'], FILTER_SANITIZE_STRING) , $wovax_idx_order_section);
    $wovax_idx_shortcode_filters_array = wovax_idx_shortcode_filter_table($wovax_idx_shortcode_filters);
    $wovax_idx_shortcode_filters_table = $wovax_idx_shortcode_filters_array['table'];
    $wovax_idx_shortcode_filters_count = $wovax_idx_shortcode_filters_array['total_count'];
    $wovax_idx_save_input_filter = 'Add Filter';
    $selected  = '';
    $feed_ids  = wovax_idx_get_shortcode_feeds($_GET['id']);
    if(!is_array($feed_ids) || empty($feed_ids)) {
        $msg = get_warning_html(
            "Shortcode Missing Feed ID",
            "Failed get feed ID for this shortcode please go back and reload this page."
        );
        echo $msg;
        $feed_ids = array(190); // demo feed has some fields
    }
    if (isset($_GET['idfilter']) && !$delete) {
        /* Edit Filter */
        $wovax_idx_save_input_filter = 'Edit Filter';
        $wovax_idx_shortcode_filter_values = wovax_idx_find_one_shortcode_filter_by_id(filter_var($_GET['idfilter'], FILTER_SANITIZE_STRING));
        $selected = $wovax_idx_shortcode_filter_values[0]->field;
        $wovax_idx_filter_type = $wovax_idx_shortcode_filter_values[0]->filter_type;
        if($wovax_idx_filter_type === 'range' || $wovax_idx_filter_type === 'preset_value' || $wovax_idx_filter_type === 'preset_range') {
          $wovax_idx_filter_data = json_decode($wovax_idx_shortcode_filter_values[0]->filter_data);
        } else {
          $wovax_idx_filter_data = '';
        }
        $wovax_idx_filter_label = $wovax_idx_shortcode_filter_values[0]->filter_label;
        $wovax_idx_filter_placeholder = $wovax_idx_shortcode_filter_values[0]->filter_placeholder;
    }
    $select_filter = getFieldSelectOptions($feed_ids, $selected);
  } elseif ($_GET['tab'] == 'rules') {
    /* Rules Tab */
    $delete = FALSE;
    if (isset($_GET['a']) && $_GET['a'] == 'delete') {
        $delete = TRUE;
        /* Delete rule */
        wovax_idx_shortcode_rule_delete(filter_var($_GET['id'], FILTER_SANITIZE_STRING) , filter_var($_GET['idrule'], FILTER_SANITIZE_STRING));
    }
    /* New Rule */
    $wovax_idx_shortcode_rules = wovax_idx_find_shortcode_rules_by_id(filter_var($_GET['id'], FILTER_SANITIZE_STRING) , $wovax_idx_order_section);
    $wovax_idx_shortcode_rules_array = wovax_idx_shortcode_rule_table($wovax_idx_shortcode_rules);
    $wovax_idx_shortcode_rules_table = $wovax_idx_shortcode_rules_array['table'];
    $wovax_idx_shortcode_rules_count = $wovax_idx_shortcode_rules_array['total_count'];
    $wovax_idx_save_input_rule = 'Add Rule';
    $selected  = '';
    $feed_ids  = wovax_idx_get_shortcode_feeds($_GET['id']);
    if(!is_array($feed_ids) || empty($feed_ids)) {
        $msg = get_warning_html(
            "Shortcode Missing Feed ID",
            "Failed get feed ID for this shortcode please go back and reload this page."
        );
        echo $msg;
        $feed_ids = array(190); // demo feed has some fields
    }
    if (isset($_GET['idrule']) && !$delete) {
        /* Edit Rule */
        $wovax_idx_save_input_rule = 'Edit Rule';
        $wovax_idx_shortcode_rule_values = wovax_idx_find_one_shortcode_rule_by_id(filter_var($_GET['idrule'], FILTER_SANITIZE_STRING));
        $selected = $wovax_idx_shortcode_filter_values[0]->field;
        $wovax_idx_rule_type = $wovax_idx_shortcode_rule_values[0]->rule_type;
        $wovax_idx_rule_value = $wovax_idx_shortcode_rule_values[0]->rule_value;
    }
    $select_rule = getFieldSelectOptions($feed_ids, $selected);
  } elseif ($_GET['tab'] == 'feeds') {
        /* Feeds Tab */
        global $wpdb;
        $api     = WovaxConnect::createFromOptions();
        $feeds   = $api->getFeedList();
        $checks  = array();
        $sql     = $wpdb->prepare("SELECT `feeds` FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `id` = %d;", intval($_GET['id']));
        $checked = json_decode($wpdb->get_var($sql), TRUE);
        if(!is_array($checked)) {
            $checked = array();
        }
        foreach($feeds as $feed) {
            $class_id = $feed['class_id'];
            $label    = implode('-', array($feed['board_acronym'], $feed['feed_name'], $feed['resource'], $feed['class_visible_name']));
            $name     = esc_attr("wovax-idx-shortcode-feed-$class_id");
            $check    = isset($checked[$name]) ? 'checked' : '';
            $value    = esc_attr($class_id.'-'.$feed['feed_id']);
            $html     = '<div class="feed-item">';
            $html    .= '<label for="'.$name.'">';
            $html    .= '<input type="checkbox" value="'.$value.'" id="'.$name.'" name="'.$name.'" '.$check.'> ';
            $html    .= esc_html($label);
            $html    .= '</label><br>';
            $html    .= '</div>';
            $checks[] = $html;
        }
        $wovax_idx_checkbox_data = implode('', $checks);
    }
  $wovax_idx_first_tab = "<a href=\"" . esc_url('?page=wovax_idx_shortcodes&tab=general&id=' . filter_var($_GET['id'], FILTER_SANITIZE_STRING) . '&action=update') . "\" class=\"nav-tab" . (($active_tab == "general") ? " nav-tab-active" : "") . "\">General</a>";
  $wovax_idx_second_tab = "<a href=\"" . esc_url('?page=wovax_idx_shortcodes&tab=view&id=' . filter_var($_GET['id'], FILTER_SANITIZE_STRING) . '&action=update') . "\" class=\"nav-tab" . (($active_tab == "view") ? " nav-tab-active" : "") . "\">View Options</a>";
  $wovax_idx_third_tab = "<a href=\"" . esc_url('?page=wovax_idx_shortcodes&tab=feeds&id=' . filter_var($_GET['id'], FILTER_SANITIZE_STRING) . '&action=update') . "\" class=\"nav-tab" . (($active_tab == "feeds") ? " nav-tab-active" : "") . "\"" . (($wovax_idx_type_val == "user_profile") ? " hidden" : "") . ">Feeds</a>";
  $wovax_idx_fourth_tab = ($wovax_idx_feeds != null) ? "<a href=\"" . esc_url('?page=wovax_idx_shortcodes&tab=filters&id=' . filter_var($_GET['id'], FILTER_SANITIZE_STRING) . '&action=update') . "\" class=\"nav-tab" . (($active_tab == "filters") ? " nav-tab-active" : "") . "\"" . (($wovax_idx_type_val != "search_form") ? " hidden" : "") . ">Filters</a>" : "<a href=\"#\" class=\"nav-tab " . (($active_tab == "filters") ? " nav-tab-active" : "") . "\"" . (($wovax_idx_type_val != "search_form") ? " hidden" : "") . ">Filters</a>";
  $wovax_idx_fifth_tab = ($wovax_idx_feeds != null) ? "<a href=\"" . esc_url('?page=wovax_idx_shortcodes&tab=rules&id=' . filter_var($_GET['id'], FILTER_SANITIZE_STRING) . '&action=update') . "\" class=\"nav-tab" . (($active_tab == "rules") ? " nav-tab-active" : "") . "\"" . (($wovax_idx_type_val != "listings") ? " hidden" : "") . ">Rules</a>" : "<a href=\"#\" class=\"nav-tab " . (($active_tab == "rules") ? " nav-tab-active" : "") . "\"" . (($wovax_idx_type_val != "listings") ? " hidden" : "") . ">Rules</a>";
  $wovax_idx_sixth_tab = "<a href=\"" . esc_url('?page=wovax_idx_shortcodes&tab=sorting&id=' . filter_var($_GET['id'], FILTER_SANITIZE_STRING) . '&action=update') . "\" class=\"nav-tab" . (($active_tab == "sorting") ? " nav-tab-active" : "") . "\"" . (($wovax_idx_type_val == "user_profile") ? " hidden" : "") . ">Sorting</a>";
  $wovax_idx_seventh_tab = "<a href=\"" . esc_url('?page=wovax_idx_shortcodes&tab=fields&id=' . filter_var($_GET['id'], FILTER_SANITIZE_STRING) . '&action=update') . "\" class=\"nav-tab" . (($active_tab == "fields") ? " nav-tab-active" : "") . "\"" . (($wovax_idx_type_val != "user_profile") ? " hidden" : "") . ">Fields</a>";
  $wovax_idx_disabled = 'disabled';
  } else {
  /* New Shortcode */
  /* shortcode settings */
  $wovax_idx_type_val = (isset($wovax_idx_type_val)) ? $wovax_idx_type_val : '';
  $wovax_idx_title_val = (isset($wovax_idx_title_val)) ? $wovax_idx_title_val : '';
  $wovax_idx_per_page_val = (isset($wovax_idx_per_page_val)) ? $wovax_idx_per_page_val : '12';
  $wovax_idx_pagination_val = (isset($wovax_idx_pagination_val)) ? $wovax_idx_pagination_val : 'yes';
  $wovax_idx_grid_view_val = (isset($wovax_idx_grid_view_val)) ? $wovax_idx_grid_view_val : '';
  $wovax_idx_map_view_val = (isset($wovax_idx_map_view_val)) ? $wovax_idx_map_view_val : '';
  $wovax_idx_per_map_val = (isset($wovax_idx_per_map_val)) ? $wovax_idx_per_map_val : '250';
  $wovax_idx_action_bar_val = (isset($wovax_idx_action_bar_val)) ? $wovax_idx_action_bar_val : 'yes';
  /* HTML */
  $wovax_idx_disabled = '';
  $wovax_idx_shortcode_header_title = 'New Shortcode';
  $wovax_idx_first_tab = "<a href=\"?page=wovax_idx_shortcodes&tab=general&action=update\" class=\"nav-tab " . (($active_tab == "general") ? "nav-tab-active" : "") . " \">General</a>";
  $wovax_idx_second_tab = '';
  $wovax_idx_third_tab = '';
  $wovax_idx_fourth_tab = '';
  $wovax_idx_fifth_tab = '';
  $wovax_idx_sixth_tab = '';
  $wovax_idx_seventh_tab = '';
}

?>

 <div class="wrap">
  <h1 class="wp-heading-inline" id="wovax-idx-shortcode-header-title"><?php echo $wovax_idx_shortcode_header_title ?></h1>
  <hr class="wp-header-end">
  <h2 class="nav-tab-wrapper">
    <?php echo $wovax_idx_first_tab ?>
    <?php echo $wovax_idx_second_tab ?>
    <?php echo ($wovax_idx_type_val == "user_profile")?'':$wovax_idx_third_tab ?>
    <?php echo ($wovax_idx_type_val == "search_form")?$wovax_idx_fourth_tab:'' ?>
    <?php echo ($wovax_idx_type_val == "listings")?$wovax_idx_fifth_tab:'' ?>
    <?php echo ($wovax_idx_type_val == "user_profile")?'':$wovax_idx_sixth_tab ?>
    <?php echo ($wovax_idx_type_val == "user_profile")?$wovax_idx_seventh_tab:'' ?>
  </h2>
  <?php

if ($_GET["tab"] == "general") {
  wovax_idx_display_general($wovax_idx_type_val, $wovax_idx_title_val, $wovax_idx_disabled);
} elseif ($_GET["tab"] == "view") {
  if ($wovax_idx_type_val == "user_profile") {
    wovax_idx_display_view_profile();
  } else {
    wovax_idx_display_view($wovax_idx_per_page_val, $wovax_idx_pagination_val, $wovax_idx_grid_view_val, $wovax_idx_map_view_val, $wovax_idx_per_map_val, $wovax_idx_action_bar_val);
  }
} elseif ($_GET["tab"] == "feeds") {
  if ($wovax_idx_type_val != "user_profile") {
    wovax_idx_display_feeds($wovax_idx_feeds, $wovax_idx_checkbox_data);
  }
} elseif ($_GET["tab"] == "filters") {
  if ($wovax_idx_type_val == "search_form") {
    wovax_idx_display_search_filters($wovax_idx_shortcode_filters_table, $wovax_idx_filter_type, $wovax_idx_filter_label, $wovax_idx_filter_placeholder, $wovax_idx_filter_data, $wovax_idx_save_input_filter, $wovax_idx_shortcode_filters_count, $select_filter);
  }
} elseif ($_GET["tab"] == "rules") {
  if ($wovax_idx_type_val == "listings") {
    wovax_idx_display_rules($wovax_idx_shortcode_rules_table, $wovax_idx_rule_type, $wovax_idx_rule_value, $wovax_idx_save_input_rule, $wovax_idx_shortcode_rules_count, $select_rule);
  }
} elseif ($_GET["tab"] == "sorting") {
  if ($wovax_idx_type_val != "user_profile") {
    wovax_idx_display_sorting();
  }
} elseif ($_GET["tab"] == "fields") {
  if ($wovax_idx_type_val == "user_profile") {
    wovax_idx_display_fields();
  }
} else {
  wovax_idx_display_general($wovax_idx_type_val, $wovax_idx_title_val, $wovax_idx_disabled);
}

?>
  </form>
</div>
  <?php

// function to display all the HTML general tab
function wovax_idx_display_general($wovax_idx_type_val, $wovax_idx_title_val, $wovax_idx_disabled) {
?>
  <form method="post" action="">
  <section>
    <?php wp_nonce_field('save_change_shortcode_fields', 'wovax_idx_shortcode_fields'); ?>
    <input type="hidden" name="wovax-idx-shortcode-id" value="<?php echo esc_attr(filter_var($_GET["id"], FILTER_SANITIZE_STRING)) ?>">
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">
            <label for="shortcode-type">Shortcode Type</label>
          </th>
          <td>
            <select id="shortcode-type" name="wovax-idx-shortcode-type" <?php echo esc_attr($wovax_idx_disabled) ?> >
              <option value="listings" <?php echo esc_attr($selected_type = ($wovax_idx_type_val == 'listings') ? 'selected' : ''); ?>>Listings Embed</option>
              <option value="search_form" <?php echo esc_attr($selected_type = ($wovax_idx_type_val == 'search_form') ? 'selected' : ''); ?> >Search Form</option>
              <option value="user_favorites" <?php echo esc_attr($selected_type = ($wovax_idx_type_val == 'user_favorites') ? 'selected' : ''); ?>>User Favorites</option>
              <option value="user_profile" <?php echo esc_attr($selected_type = ($wovax_idx_type_val == 'user_profile') ? 'selected' : ''); ?>>User Profile</option>
            </select>
            <p class="description" id="shortcode-type-description">Choose Search Form to build a search form, choose Listings Embed to display listings.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="shortcode-title">Shortcode Title</label>
          </th>
          <td>
            <input name="wovax-idx-shortcode-title" type="text" id="shortcode-title" placeholder="Main site search" value="<?php echo esc_attr($wovax_idx_title_val); ?>"  class="regular-text">
            <p class="description" id="shortcode-title-description">Describe your shortcode.</p>
          </td>
        </tr>
      </tbody>
    </table>
    <?php submit_button(); ?>
  </section>
<?php
}

// function to display all the HTML view options tab
function wovax_idx_display_view($wovax_idx_per_page_val, $wovax_idx_pagination_val, $wovax_idx_grid_view_val, $wovax_idx_map_view_val, $wovax_idx_per_map_val, $wovax_idx_action_bar_val) {
?>
    <form method="post" action="">
       <?php wp_nonce_field('save_change_shortcode_view', 'wovax_idx_shortcode_view'); ?>
        <input type="hidden" name="wovax-idx-shortcode-id" value="<?php echo esc_attr(filter_var($_GET["id"], FILTER_SANITIZE_STRING)) ?>">
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">
              <label for="shortcode-title">Search Results Views</label>
            </th>
            <td>
              <input type="checkbox" id="shortcode-results-views-grid" name="wovax-idx-shortcode-grid-view" value="yes" <?php echo esc_attr($wovax_idx_grid_view_val = ($wovax_idx_grid_view_val == 'yes') ? 'checked' : ''); ?>>Grid View
              <br>
              <input type="checkbox" id="shortcode-results-views-map" name="wovax-idx-shortcode-map-view" value="yes" <?php echo esc_attr($wovax_idx_map_view_val = ($wovax_idx_map_view_val == 'yes') ? 'checked' : ''); ?>>Map View
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="shortcode-listings-per-page">Listings Per Page</label>
            </th>
            <td>
              <input name="wovax-idx-shortcode-listings-per-page" type="number" id="shortcode-listings-per-page" placeholder="12" value="<?php echo esc_attr($wovax_idx_per_page_val); ?>"  class="small-text">
              <p class="description" id="shortcode-listings-per-page-description">How many listings to display by default.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="shortcode-pagination">Paginated</label>
            </th>
            <td>
              <select id="shortcode-pagination" name="wovax-idx-shortcode-pagination">
                <option value="yes" <?php echo esc_attr($wovax_idx_pagination_val == 'yes' ? 'selected' : ''); ?>>Yes</option>
                <option value="no" <?php echo esc_attr($wovax_idx_pagination_val == 'no' ? 'selected' : ''); ?>>No</option>
              </select>
              <p class="description" id="shortcode-pagination-description">Results can contain multiple pages or not.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="shortcode-listings-per-page">Listings Per Map</label>
            </th>
            <td>
              <input name="wovax-idx-shortcode-listings-per-map" type="number" id="shortcode-listings-per-map" placeholder="250" value="<?php echo esc_attr($wovax_idx_per_map_val); ?>"  class="small-text">
              <p class="description" id="shortcode-listings-per-map-description">How many property markers to display by default.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="shortcode-action-bar">Display Action Bar</label>
            </th>
            <td>
              <select id="shortcode-action-bar" name="wovax-idx-shortcode-action-bar">
                <option value="yes" <?php echo esc_attr($wovax_idx_action_bar_val == 'yes' ? 'selected' : ''); ?>>Yes</option>
                <option value="no" <?php echo esc_attr($wovax_idx_action_bar_val == 'no' ? 'selected' : ''); ?>>No</option>
              </select>
              <p class="description" id="shortcode-action-bar-description">Display the action bar (Results, Sorting, etc)</p>
            </td>
          </tr>
        </tbody>
      </table>
  <?php submit_button();
}

// function to display all the HTML view options tab for profile shortcodes
function wovax_idx_display_view_profile() {
?>
<p>Wovax IDX is currently in beta. This feature will be available in our initial stable release.</p>
<?php
}

// function to display all the HTML feeds tab
function wovax_idx_display_feeds($wovax_idx_feeds, $wovax_idx_checkbox_data) {
?>
    <form method="post" action="">
       <?php wp_nonce_field('save_change_shortcode_feeds', 'wovax_idx_shortcode_feeds'); ?>
        <input type="hidden" name="wovax-idx-shortcode-id" value="<?php echo esc_attr(filter_var($_GET["id"], FILTER_SANITIZE_STRING)) ?>">
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">Include In Results</th>
              <td>
                <div class="wovax-idx-shortcode-details-feeds-div">
                  <input type="text" id="search-text-feeds-results" class="wovax-idx-shortcode-details-feeds-input-search">
                </div>
                <fieldset id="list_wovax_set_datas" class="wovax-idx-shortcode-details-feeds-fieldset">
                  <?php echo $wovax_idx_checkbox_data ?>
                </fieldset>
            </td>
          </tr>
        </tbody>
      </table>
  <?php submit_button();
}

// function to display all the HTML filters tab
function wovax_idx_display_search_filters($wovax_idx_shortcode_filters_table, $type = '', $label = '', $placeholder = '', $filter_data = '', $wovax_idx_save_input_filter, $wovax_idx_shortcode_filters_count, $select_filter) {
  if($type === 'range' || $type === 'preset_range') {
    $range_hidden = '';   
    $range_min = $filter_data->range_start;
    $range_max = $filter_data->range_end;
  } else {
    $range_hidden = 'hidden';
    $range_min = '';
    $range_max = '';
  }
  if($type === 'preset_value' || $type === 'preset_range') {
    $label_hidden = 'hidden';
    $placeholder_hidden = 'hidden';
  } else {
    $label_hidden = '';
    $placeholder_hidden = '';
  }
  if($type === 'range') {
    $interval_hidden = '';
    $interval = $filter_data->interval;
  } else {
    $interval_hidden = 'hidden';
    $interval = '';
  }
  if($type === 'preset_value') {
    $preset_hidden = '';
    $value = $filter_data->value;
  } else {
    $preset_hidden = 'hidden';
    $value = '';
  }
  
?>
  <section>
  <form method="post" action="">
   <?php wp_nonce_field('save_change_shortcode_filters', 'wovax_idx_shortcode_filters'); ?>
   <input type="hidden" name="wovax-idx-shortcode-id" value="<?php echo esc_attr(filter_var($_GET["id"], FILTER_SANITIZE_STRING)) ?>">
   <input type="hidden" name="wovax-idx-shortcode-filter-id" value="<?php echo esc_attr(filter_var($_GET["idfilter"], FILTER_SANITIZE_STRING)) ?>">
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">
            <label for="shortcode-filter-type">Filter Type</label>
          </th>
          <td>
            <select id="shortcode-filter-type" name="wovax-idx-shortcode-filter-type" >
              <option value="">Select Filter Type</option>
              <option value="select" <?php echo esc_attr($selected_type = ($type == 'select') ? 'selected' : ''); ?> >Select</option>
              <option value="range" <?php echo esc_attr($selected_type = ($type == 'range') ? 'selected' : ''); ?> >Numeric Range</option>
              <option value="numeric" <?php echo esc_attr($selected_type = ($type == 'numeric') ? 'selected' : ''); ?>>Numeric</option>
              <option value="numeric_max" <?php echo esc_attr($selected_type = ($type == 'numeric_max') ? 'selected' : ''); ?>>Numeric Maximum</option>
              <option value="numeric_min" <?php echo esc_attr($selected_type = ($type == 'numeric_min') ? 'selected' : ''); ?>>Numeric Minimum</option>
              <option value="input_text" <?php echo esc_attr($selected_type = ($type == 'input_text') ? 'selected' : ''); ?>>Text Search</option>
              <option value="boolean" <?php echo esc_attr($selected_type = ($type == 'boolean') ? 'selected' : ''); ?>>Boolean Search</option>
              <option value="preset_value" <?php echo esc_attr($selected_type = ($type == 'preset_value') ? 'selected' : ''); ?>>Preset Value</option>
              <option value="preset_range" <?php echo esc_attr($selected_type = ($type == 'preset_range') ? 'selected' : ''); ?>>Preset Range</option>
              <option value="omnisearch" <?php echo esc_attr($selected_type = ($type == 'omnisearch') ? 'selected' : ''); ?> disabled>Omnisearch (coming soon)</option>
            </select>
            <p class="description" id="shortcode-filter-type-description">Type of filter to add to the search form.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="shortcode-filter-field">Field</label>
          </th>
          <td>
            <select id="shortcode-filter-field" name="wovax-idx-shortcode-filter-field" >
              <?php echo $select_filter; ?>
            </select>
            <p class="description" id="shortcode-filter-field-description">Data field to add to the search form.</p>
          </td>
        </tr>
        <tr class='wovax-range' <?php echo $range_hidden ?>>
          <th scope="row">
            <label for="shortcode-filter-range">Range Start and End</label>
          </th>
          <td>
            <input name="wovax-idx-shortcode-filter-range-min" type="text" id="shortcode-filter-range" class="short-text" value="<?php echo esc_attr($range_min); ?>">
            <p class="description" id="shortcode-filter-range-min-description">Range Start Value</p>
            <input name="wovax-idx-shortcode-filter-range-max" type="text" id="shortcode-filter-range-max" class="short-text" value="<?php echo esc_attr($range_max); ?>">
            <p class="description" id="shortcode-filter-range-max-description">Range End Value</p>
          </td>
        </tr>
        <tr class='wovax-range-interval' <?php echo $interval_hidden ?>>
          <th scope="row">
            <label for="shortcode-filter-interval">Range Interval</label>
          </th>
          <td>
          <select name="wovax-idx-shortcode-filter-range-interval" id="shortcode-filter-interval">
            <option value="">Select Interval</option>
            <option value="dynamic" <?php echo esc_attr($selected_interval = ($interval == 'dynamic') ? 'selected' : ''); ?> >Dynamic</option>
            <option value="1000" <?php echo esc_attr($selected_interval = ($interval == '1000') ? 'selected' : ''); ?> >1,000</option>
            <option value="5000" <?php echo esc_attr($selected_interval = ($interval == '5000') ? 'selected' : ''); ?>>5,000</option>
            <option value="10000" <?php echo esc_attr($selected_interval = ($interval == '10000') ? 'selected' : ''); ?>>10,000</option>
            <option value="50000" <?php echo esc_attr($selected_interval = ($interval == '50000') ? 'selected' : ''); ?>>50,000</option>
            <option value="100000" <?php echo esc_attr($selected_interval = ($interval == '100000') ? 'selected' : ''); ?>>100,000</option>
            <option value="500000" <?php echo esc_attr($selected_interval = ($interval == '500000') ? 'selected' : ''); ?>>500,000</option>
            <option value="1000000" <?php echo esc_attr($selected_interval = ($interval == '1000000') ? 'selected' : ''); ?>>1,000,000</option>
            </select>
            <p class="description" id="shortcode-filter-interval-description">Interval between range values. 'Dynamic' increments by order of magnitude to reduce options if the range is very large.</p>
          </td>
        </tr>
        <tr class='wovax-preset-value' <?php echo $preset_hidden ?>>
          <th scope="row">
            <label for="shortcode-filter-value">Preset Value</label>
          </th>
          <td>
            <input name="wovax-idx-shortcode-filter-value" type="text" id="shortcode-filter-value" class="short-text" value="<?php echo esc_attr($value); ?>">
            <p class="description" id="shortcode-filter-value-description">Value for selecting results to search within.</p>
          </td>
        </tr>
        <tr class="wovax-filter-label" <?php echo $label_hidden; ?>>
          <th scope="row">
            <label for="shortcode-filter-label">Filter Label</label>
          </th>
          <td>
            <input name="wovax-idx-shortcode-filter-label" type="text" id="shortcode-filter-label" class="short-text" value="<?php echo esc_attr($label); ?>">
            <p class="description" id="shortcode-filter-label-description">Descriptive word to label the filter to website visitors.</p>
          </td>
        </tr>
        <tr class="wovax-filter-placeholder" <?php echo $placeholder_hidden; ?>>
          <th scope="row">
            <label for="shortcode-filter-placeholder">Filter Placeholder</label>
          </th>
          <td>
            <input name="wovax-idx-shortcode-filter-placeholder" type="text" id="shortcode-filter-placeholder" class="short-text" value="<?php echo esc_attr($placeholder); ?>">
            <p class="description" id="shortcode-filter-placeholder-description">Text to display before website visitor enters text.</p>
          </td>
        </tr>
      </tbody>
    </table>
    <p class="submit">
      <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr($wovax_idx_save_input_filter); ?>">
    </p>

    <div class="tablenav top">
      <div class="alignleft actions">
        <label class="screen-reader-text" for="bulk-action-selector-top">Select bulk action</label>
        <select name="apply_action_field" id="bulk-action-selector-top">
          <option value="p">Bulk Actions</option>
          <option value="delete">Delete</option>
        </select>
        <input type="submit" name="button_action" id="doaction" class="button action" value="Apply">
      </div>
      <div id="paginado">
        <div class="tablenav-pages">
          <span class="displaying-num"><?php echo esc_html($wovax_idx_shortcode_filters_count); ?> items</span>
        </div>
      </div>
      <br class="clear">
    </div>

    <table class="wp-list-table widefat fixed striped posts">

      <thead>
        <tr>
          <td id="cb" class="manage-column column-cb check-column">
            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
            <input id="cb-select-all-1" type="checkbox">
          </td>
          <th scope="col" id="field" class="manage-column column-title column-primary">
            Field
          </th>
          <th scope="col" id="filter-type" class="manage-column">
            Filter Type
          </th>
          <th scope="col" id="filter-label" class="manage-column">
            Filter Label
          </th>
          <th scope="col" id="filter-placeholder" class="manage-column">
            Filter Placeholder
          </th>
          <th scope="col" id="date" class="manage-column column-data">
            Filter Data
          </th>
        </tr>
      </thead>

      <tbody id="the-list-shortcode-filters-details" data-wp-lists="list:post" data-id-shortcode="<?php echo esc_attr(filter_var($_GET['id'], FILTER_SANITIZE_STRING)); ?>">
        <?php echo $wovax_idx_shortcode_filters_table; ?>
      </tbody>

      <tfoot>
        <tr>
          <td class="manage-column column-cb check-column">
            <label class="screen-reader-text" for="cb-select-all-2">Select All</label>
            <input id="cb-select-all-2" type="checkbox">
          </td>
          <th scope="col" class="manage-column column-title column-primary">
            Field
          </th>
          <th scope="col" class="manage-column">
            Filter Type
          </th>
          <th scope="col" class="manage-column">
            Filter Label
          </th>
          <th scope="col" class="manage-column">
            Filter Placeholder
          </th>
          <th scope="col" class="manage-column column-data">
            Filter Data
          </th>
        </tr>
      </tfoot>

    </table>
  </section>
  <script type="text/javascript">
    jQuery("#shortcode-filter-type").change(function() {
      var p_element = jQuery("p#shortcode-filter-field-description");
      if( jQuery(this).val()=="omnisearch" ){
        var input_element = jQuery("input#shortcode-filter-field");

        p_element.html("Separate field names with commas: City,State,Zip Code,Description,etc...");
        jQuery("select#shortcode-filter-field").remove();

        if(input_element.length<1){
          p_element.parent().prepend("<input name='wovax-idx-shortcode-filter-field' type='text' id='shortcode-filter-field' class='short-text' value=''>");
        }

      }else{
        var select_element = jQuery("select#shortcode-filter-field");
        p_element.html("Data field to add to the search form.");
        jQuery("input#shortcode-filter-field").remove();

        if(select_element.length<1){
          p_element.parent().prepend('<select id="shortcode-filter-field" name="wovax-idx-shortcode-filter-field" ><?php echo $select_filter; ?></select>');
        }
      }
    });
  </script>
<?php
}
//function to display all the shortcode rules tab
function wovax_idx_display_rules($wovax_idx_shortcode_rules_table, $type = '', $value = '', $wovax_idx_save_input_rule, $wovax_idx_shortcode_rules_count, $select_rule){
?>
  <section>
  <form method="post" action="">
  <?php wp_nonce_field('save_change_shortcode_rules', 'wovax_idx_shortcode_rules'); ?>
  <input type="hidden" name="wovax-idx-shortcode-id" value="<?php echo esc_attr(filter_var($_GET["id"], FILTER_SANITIZE_STRING)) ?>">
  <input type="hidden" name="wovax-idx-shortcode-rule-id" value="<?php echo esc_attr(filter_var($_GET["idrule"], FILTER_SANITIZE_STRING)) ?>">
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">
            <label for="shortcode-rule-field">Field</label>
          </th>
          <td>
            <select id="shortcode-rule-field" name="wovax-idx-shortcode-rule-field" >
              <?php echo $select_rule; ?>
            </select>
            <p class="description" id="shortcode-rule-field-description">The data field to be affected by this rule.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="shortcode-rule-type">Rule Type</label>
          </th>
          <td>
            <select id="shortcode-rule-type" name="wovax-idx-shortcode-rule-type" >
              <option value="">Select Rule Type</option>
              <option value="select" <?php echo esc_attr($selected_type = ($type == 'select') ? 'selected' : ''); ?>>Equals</option>
              <option value="input_text" <?php echo esc_attr($selected_type = ($type == 'input_text') ? 'selected' : ''); ?>>Contains</option>
              <option value="exclude" <?php echo esc_attr($selected_type = ($type == 'exclude') ? 'selected' : ''); ?>>Exclude</option>
              <option value="numeric_min" <?php echo esc_attr($selected_type = ($type == 'numeric_min') ? 'selected' : ''); ?>>Numeric Min</option>
              <option value="numeric_max" <?php echo esc_attr($selected_type = ($type == 'numeric_max') ? 'selected' : ''); ?>>Numeric Max</option>
            </select>
            <p class="description" id="shortcode-rule-type-description">Type of rule to apply to the field.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="shortcode-rule-value">Rule Value</label>
          </th>
          <td>
            <input name="wovax-idx-shortcode-rule-value" type="text" id="shortcode-rule-value" class="short-text" value="<?php echo esc_attr($value); ?>">
            <p class="description" id="shortcode-rule-value-description">The value of the field in this rule.</p>
          </td>
        </tr>
      </tbody>
    </table>
    <p class="submit">
      <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr($wovax_idx_save_input_rule); ?>">
    </p>

    <div class="tablenav top">
      <div class="alignleft actions">
        <label class="screen-reader-text" for="bulk-action-selector-top">Select bulk action</label>
        <select name="apply_action_field" id="bulk-action-selector-top">
          <option value="p">Bulk Actions</option>
          <option value="delete">Delete</option>
        </select>
        <input type="submit" name="button_action" id="doaction" class="button action" value="Apply">
      </div>
      <div id="paginado">
        <div class="tablenav-pages">
          <span class="displaying-num"><?php echo esc_html($wovax_idx_shortcode_rules_count); ?> items</span>
        </div>
      </div>
      <br class="clear">
    </div>

    <table class="wp-list-table widefat fixed striped posts">

      <thead>
        <tr>
          <td id="cb" class="manage-column column-cb check-column">
            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
            <input id="cb-select-all-1" type="checkbox">
          </td>
          <th scope="col" id="field" class="manage-column column-title column-primary">
            Field
          </th>
          <th scope="col" id="rule-type" class="manage-column">
            Rule Type
          </th>
          <th scope="col" id="rule-value" class="manage-column">
            Rule Value
          </th>
          <th scope="col" id="date" class="manage-column column-date">
            Created
          </th>
        </tr>
      </thead>

      <tbody id="the-list-shortcode-rules-details" data-wp-lists="list:post" data-id-shortcode="<?php echo esc_attr(filter_var($_GET['id'], FILTER_SANITIZE_STRING)); ?>">
        <?php echo $wovax_idx_shortcode_rules_table; ?>
      </tbody>

      <tfoot>
        <tr>
          <td class="manage-column column-cb check-column">
            <label class="screen-reader-text" for="cb-select-all-2">Select All</label>
            <input id="cb-select-all-2" type="checkbox">
          </td>
          <th scope="col" class="manage-column column-title column-primary">
            Field
          </th>
          <th scope="col" class="manage-column">
            Rule Type
          </th>
          <th scope="col" class="manage-column">
            Rule Value
          </th>
          <th scope="col" class="manage-column column-date">
            Created
          </th>
        </tr>
      </tfoot>

    </table>
  </section>
<?php
}
//function to display all the HTML sorting tab
function wovax_idx_display_sorting () {
    $shortcode_id = intval($_GET['id']);
    $selected     = (new Wovax\IDX\Settings\ShortcodeSettings($shortcode_id))->sortOrder();
?>
<form method="POST" action="<?php echo admin_url('admin-post.php') ?>">
<input type="hidden" name="action" value="wovax_idx_shortcode_sort">
<input type="hidden" name="wovax-idx-shortcode-id" value="<?echo $shortcode_id;?>">
<?php wp_nonce_field('wovax-idx-shortcode-sort'); ?>
	<section id="shortcode_sort">
    <table class="form-table"> 
      <tbody>
        <tr>
          <th scope="row">
            <label for="default-sort-order">Default Sort Order</label>
          </th>
          <td>
            <select autocomplete="off" id="default-sort-order" name="wovax-idx-default-sort-order">
                <?
                $options = array(
                    'date-desc'  => 'Most Recent First',
                    'date-asc'   => 'Most Recent Last</option>',
                    'price-desc' => 'Price High to Low',
                    'price-asc'  => 'Price Low to High'
                );
                foreach($options as $val => $text) {
                    $sel = '';
                    if($val ==  $selected) {
                        $sel = 'selected="selected"';
                    }
                    echo "<option $sel value=\"$val\">$text</option>\n";
                }
                ?>
            </select>
            <p class="description" id="default-sort-description">The initial sorted order of listings.</p>
          </td>
        </tr>
      </tbody>
    </table>
    </section>
    <?php submit_button(); ?>
</form>
<?php
}

//function to display all the HTML fields tab
function wovax_idx_display_fields() {
?>
<p>Wovax IDX is currently in beta. This feature will be available in our initial stable release.</p>
<?php
}
?>