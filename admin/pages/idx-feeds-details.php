<?php
if (!defined('ABSPATH')) exit;
$active_tab = isset($_GET['tab']) ? filter_var($_GET['tab'], FILTER_SANITIZE_STRING) : 'general';

$feed_attr_info = wovax_idx_get_feed_attr_by_id_feed(filter_var($_GET['idfeed'], FILTER_SANITIZE_STRING));
$wovax_idx_order_section = json_decode($feed_attr_info[0]->attributes)->feed_rules_order;

if (is_null($wovax_idx_order_section)) {
  $wovax_idx_order_section = array();
}

if ($_GET['tab'] == 'rules') {
    if (isset($_GET['a']) && $_GET['a'] == 'delete') {
        /* Delete rule */
        wovax_idx_feed_rule_delete(filter_var($_GET['idfeed'], FILTER_SANITIZE_STRING) , filter_var($_GET['idrule'], FILTER_SANITIZE_STRING));
    }
    $wovax_idx_feed_rules = wovax_idx_find_feed_rules_by_id(filter_var($_GET['idfeed'], FILTER_SANITIZE_STRING) , $wovax_idx_order_section);
    $wovax_idx_feed_rules_array = wovax_idx_feed_rule_table($wovax_idx_feed_rules);
    $wovax_idx_feed_rules_table = $wovax_idx_feed_rules_array['table'];
    $wovax_idx_feed_rules_count = $wovax_idx_feed_rules_array['total_count'];
    $wovax_idx_save_input_rule = 'Add Rule';
    $selected = '';
    if (isset($_GET['idrule']) && $_GET['a'] != 'delete') {
        //cambiar feed rule
        $wovax_idx_save_input_rule = 'Edit Rule';
        $wovax_idx_feed_rule_values = wovax_idx_find_one_feed_rule_by_id(filter_var($_GET['idrule'], FILTER_SANITIZE_STRING));
        $selected = $wovax_idx_feed_rule_values[0]->field;
        //cambiar feed rule
        $wovax_idx_rule_type = $wovax_idx_feed_rule_values[0]->rule_type;
        $wovax_idx_rule_value = $wovax_idx_feed_rule_values[0]->rule_value;
    }

    $api = Wovax\IDX\API\WovaxConnect::createFromOptions();
    $select_rule = '<option value="">Select Field</option>';
    if($api !== NULL) {
        $selects = array();
        $feed_id = array($_GET['idfeed']);
        $feed_info = $api->getFeedDetails($feed_id);
        foreach($feed_info as $field) {
            $name = $field['default_alias'];
            // the saving logic seems to use the ID for a couple things
            $id   = $field['id'];
            $selects[$name] = sprintf(
                '<option %s value="%s-%s">%s</option>',
                ($name == $selected) ? 'selected' : '',
                esc_attr($id),
                esc_attr($name),
                esc_html($name)
            );
        }
        $str = sprintf('<option %s value="">Select Field</option>', strlen($selected) > 0 ? '' : 'selected');
        ksort($selects);
        $select_rule = $str.implode('', $selects);
    }
}

$data = wovax_idx_list_feeds_field_by_id(filter_var($_GET['idfeed'], FILTER_SANITIZE_STRING));
$tabs = array();
$tabs[] = array('title' => 'General', 'id' => 'general');
// Don't show tab if in block mode
if((new Wovax\IDX\Settings\InitialSetup())->inDetailsBlockMode() === 'legacy') {
    $tabs[] = array('title' => 'Fields',   'id' => 'fields');
}
$tabs[] = array('title' => 'Rules', 'id' => 'rules');
$tab_html = '';
foreach($tabs as $tab) {
    $url_data = array(
        'page'   => 'wovax_idx_feeds',
        'action' => 'update',
        'tab'    => $tab['id'],
        'idfeed' => $_GET["idfeed"]
    );
    $html = '<a class="nav-tab';
    $html .= ($active_tab === $tab['id']) ? ' nav-tab-active"' : '"';
    $html .= ' href="?'.http_build_query($url_data).'">';
    $html .= $tab['title'];
    $html .= "</a>\n";
    $tab_html .= $html;
}
?>

<div class="wrap">
   <?php
/* function to return the information (Board,Feed,Resource,Class,Environment,Status,Updated) of the specific feed  */
$active_tab = (isset($_GET['tab'])) ? filter_var($_GET['tab'], FILTER_SANITIZE_STRING) : 'general';
$idfeed = filter_var($_GET['idfeed'], FILTER_SANITIZE_STRING);
$object = wovax_idx_get_object_by_id_in_option($idfeed);
$feed_status = ($object->status == '1') ? 'Active' : 'Inactive';

if ($object):
  /* shows all the feed information */
?>
  <h1><?php echo esc_html($object->class_visible_name); ?></h1>
  <p>
    <strong>Board</strong>: <?php echo esc_html($object->board_acronym); ?><br />
    <strong>Feed</strong>: <?php echo esc_html($object->feed_description); ?><br />
    <strong>Resource</strong>: <?php echo esc_html($object->resource); ?><br />
    <strong>Class</strong>: <?php echo esc_html($object->class_visible_name); ?><br />
    <strong>Environment</strong>: <?php echo esc_html($object->environment); ?><br />
    <strong>Status</strong>: <?php echo esc_html($feed_status); ?><br />
    <strong>Last Import</strong>: <?php echo esc_html($object->updated); ?>
  </p>
  <?php
endif;
?>
  <h2 class="nav-tab-wrapper">
    <?php echo $tab_html; ?>
  </h2>
  <?php

if ($_GET["tab"] == "general") {
  /* function to display all the HTML general tab */
  wovax_idx_display_general();
  }
elseif ($_GET["tab"] == "fields") {
  /* function to display all the HTML fields tab */
  wovax_idx_display_fields($feed_attr_info);
  }
elseif ($_GET["tab"] == "rules") {
  /* function to display all the HTML rules tab */
  wovax_idx_display_rules($select_rule, $wovax_idx_save_input_rule, $wovax_idx_rule_type, $wovax_idx_rule_value, $wovax_idx_feed_rules_table, $wovax_idx_feed_rules_count);
  }
else {
  /* function to display all the HTML general tab */
  wovax_idx_display_general();
  }

?>
  </form>
</div>
  <?php
/* Display HTML Fields General */

function wovax_idx_display_general() {
  $format = array(
    array(
      'value' => 'miles',
      'text' => '###,###,###,###'
    ) ,
    array(
      'value' => 'decimals_miles',
      'text' => '###,###,###,###.##'
    ) ,
    array(
      'value' => 'entire',
      'text' => '############'
    ) ,
    array(
      'value' => 'decimals',
      'text' => '############.##'
    )
  );
  $currency = array(
    array(
      'value' => 'left',
      'text' => 'Left'
    ) ,
    array(
      'value' => 'right',
      'text' => 'Right'
    )
  );
  $map_data = array(
    array(
      'value' => 'latitude_longitude|-|0|-|0|-|Map|-|400|-|',
      'text' => 'Latitude/Longitude'
    )
  );
  $feed_attr = wovax_idx_get_feed_attr_by_id_feed(filter_var($_GET["idfeed"], FILTER_SANITIZE_STRING));
  if ($feed_attr != null) {
    $option_values = json_decode($feed_attr[0]->attributes);
    $map_aux = (substr($option_values->map_data, 0, 3) == 'add') ? 'address|-|0|-|0|-|Map|-|400|-|' : 'latitude_longitude|-|0|-|0|-|Map|-|400|-|';
  }

?>
    <form method="post" action="">
      <section id="general"> <!-- Initial Setup tab content -->
        <?php wp_nonce_field('save_change_feeds_general', 'wovax_idx_feeds_feed_general'); ?>
        <input type="hidden" name="wovax_idx_feed_id" value="<?php echo esc_attr(filter_var($_GET["idfeed"], FILTER_SANITIZE_STRING)) ?>">
        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="number-format">Price Formatting</label>
              </th>
              <td>
                <select id="number-format" name="wovax-idx-number-format">
                  <?php
  foreach($format as $key => $value) {
    if ($option_values->format === $value['value']) {
?>
                  <option value="<?php echo esc_attr($value['value']) ?>" selected>
                    <?php echo esc_html($value['text']) ?>
                  </option>
                  <?php
    }
    else { ?>
                  <option value="<?php echo esc_attr($value['value']) ?>">
                    <?php echo esc_html($value['text']) ?>
                  </option>
                  <?php
    }
  } ?>
                </select>
                <p class="description" id="number-format-description">Choose the format prices should be displayed.</p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="currency-type">Currency Symbol Position</label>
              </th>
              <td>
                <select id="currency-type" name="wovax-idx-currency-type">
                  <?php
  foreach($currency as $key => $value) {
    if ($option_values->currency === $value['value']) {
?>
                  <option value="<?php echo esc_attr($value['value']) ?>" selected>
                    <?php echo esc_html($value['text']) ?>
                  </option>
                  <?php
    }
    else { ?>
                  <option value="<?php echo esc_attr($value['value']) ?>">
                    <?php echo esc_html($value['text']) ?>
                  </option>
                  <?php
    }
  } ?>
                </select>
                <p class="description" id="currency-type-description">Choose where to display the currency symbol.</p>
              </td>
            </tr>
          </tbody>
        </table>
        <?php
  submit_button(); ?>
  <?php
}

/* Display HTML Fields Tab */

function wovax_idx_display_fields($feed_attr_info) {
?>
<div id="nav-menus-frame" class="wp-clearfix">
  <div id="menu-settings-column" class="metabox-holder">
    <div class="clear"></div>
    <form id="nav-menu-meta" class="nav-menu-meta" method="post" enctype="multipart/form-data">
      <div id="side-sortables" class="accordion-container">
        <ul class="outer-border">
          <li class="control-section accordion-section  add-post-type-page open" id="add-post-type-fields">
            <h3 class="accordion-section-title hndle" tabindex="0" onclick="wovax_idx_get_content(this)">
              Fields<span class="screen-reader-text">Press return or enter to open this section</span>
            </h3>
            <div class="accordion-section-content show" style="display: none;">
              <div class="inside">
                <div id="posttype-page" class="posttypediv">

  <!--Nav Menu list-->

                  <ul id="posttype-page-tabs" class="posttype-tabs add-menu-item-tabs">
                    <li class="tabs">
                      <a class="nav-tab-link" data-type="tabs-panel-posttype-page-most-recent" onclick="wovax_idx_enable_content(this)">
                      Default</a>
                    </li>
                    <li class="">
                      <a class="nav-tab-link" data-type="page-all" onclick="wovax_idx_enable_content(this)">
                      View All</a>
                    </li>
                    <li class="">
                      <a class="nav-tab-link" data-type="tabs-panel-posttype-page-search" onclick="wovax_idx_enable_content(this)">
                      Search</a>
                    </li>
                  </ul>

<!--List content for each nav menu-->

                  <div id="tabs-panel-posttype-page-most-recent" class="tabs-panel tabs-panel-inactive show">
                    <ul id="wovax-idx-pagechecklist-default" class="categorychecklist form-no-clear">

                      <!--Space to load default feed fields-->

                    </ul>
                  </div>

                  <div class="tabs-panel tabs-panel-inactive" id="tabs-panel-posttype-page-search">
                    <p class="quick-search-wrap">
                      <label for="quick-search-posttype-page" class="screen-reader-text">Search</label>
                      <input type="search" class="quick-search" value="" name="quick-search-posttype-page" id="quick-search-posttype-page">
                      <span class="spinner"></span>
                      <input type="submit" name="submit" id="submit-quick-search-posttype-page" class="button button-small quick-search-submit hide-if-js" value="Search">
                    </p>
                    <input type="hidden" name="wovax_idx_id_for_search" value="<?php echo esc_attr(filter_var($_GET["idfeed"], FILTER_SANITIZE_STRING)) ?>">
                    <ul id="wovax-idx-pagechecklist-search" data-wp-lists="list:page" class="categorychecklist form-no-clear">

                      <!--Space to load search feed fields-->

                    </ul>
                  </div>

                  <div id="page-all" class="tabs-panel tabs-panel-view-all tabs-panel-inactive">
                    <ul id="wovax-idx-pagechecklist-all" data-wp-lists="list:page" class="categorychecklist form-no-clear">

                      <!--Space to load all feed fields-->

                    </ul>
                  </div>

                  <p class="button-controls wp-clearfix">
                    <span class="list-controls">
                      <a class="select-all aria-button-if-js" role="button" onclick="wovax_idx_select_all_fields()">Select All</a>
                    </span>

                    <span class="add-to-menu">
                      <input type="submit" class="button submit-add-to-menu right" value="Add to Layout" name="add-post-type-menu-item" id="submit-posttype-page">
                      <span class="spinner"></span>
                    </span>
                  </p>

                </div>
              </div>
            </div>
          </li>
          <li class="control-section accordion-section  add-post-type-page-open" id="add-post-type-styling">
            <h3 class="accordion-section-title hndle" tabindex="1" onclick="wovax_idx_get_content(this)">
              Styling Tools<span class="screen-reader-text">Press return or enter to open this section</span>
            </h3>
            <div class="accordion-section-content" style="display: none;">

              <div class="inside">
                <div id="stylingtype-page" class="posttypediv">

  <!--Nav Menu list-->

                  <ul id="stylingtype-page-tabs" class="posttype-tabs add-menu-item-tabs">
                    <li class="tabs">
                      <a class="nav-tab-link" data-type="tabs-panel-styling-page-all-tools" onclick="wovax_idx_enable_content(this)">
                      All Tools</a>
                    </li>
                    <li class="">
                      <a class="nav-tab-link" data-type="tabs-panel-styling-page-search" onclick="wovax_idx_enable_content(this)">
                      Search</a>
                    </li>
                  </ul>

                  <div id="tabs-panel-styling-page-all-tools" class="tabs-panel tabs-panel-all-tools tabs-panel-inactive show">
                    <ul id="wovax-idx-stylingchecklist-all" data-wp-lists="list:page" class="categorychecklist form-no-clear">

                      <li>
                        <label class="menu-item-title">
                          <input type="checkbox" class="menu-item-checkbox" name="wovax_idx_map_option" value="URL"> Divider
                        </label>
                        <input type="hidden" name="wovax_idx_feed_id" value="<?php echo esc_attr(filter_var($_GET["idfeed"], FILTER_SANITIZE_STRING)) ?>" >
                        <input type="hidden" name="wovax_idx_divider_state" value="1" >
                        <input type="hidden" name="wovax_idx_divider_order" value="0" >
                        <input type="hidden" name="wovax_idx_divider_title" value="Divider" >
                      </li>
                      <li>
                        <label class="menu-item-title">
                          <input type="checkbox" class="menu-item-checkbox" name="wovax_idx_map_option" value="URL"> Spacer
                        </label>
                        <input type="hidden" name="wovax_idx_feed_id" value="<?php echo esc_attr(filter_var($_GET["idfeed"], FILTER_SANITIZE_STRING)) ?>" >
                        <input type="hidden" name="wovax_idx_spacer_state" value="1" >
                        <input type="hidden" name="wovax_idx_spacer_order" value="0" >
                        <input type="hidden" name="wovax_idx_spacer_title" value="Spacer" >
                      </li>

                    </ul>
                  </div>

                  <div class="tabs-panel tabs-panel-inactive" id="tabs-panel-styling-page-search">
                    <p class="quick-search-wrap">
                      <label for="quick-search-stylingtype-page" class="screen-reader-text">Search</label>
                      <input type="search" class="quick-search" value="" name="quick-search-stylingtype-page" id="quick-search-stylingtype-page">
                      <span class="spinner"></span>
                      <input type="submit" name="submit" id="submit-quick-search-stylingtype-page" class="button button-small quick-search-submit hide-if-js" value="Search">
                    </p>
                    <input type="hidden" name="wovax_idx_id_for_search" value="<?php echo esc_attr(filter_var($_GET["idfeed"], FILTER_SANITIZE_STRING)) ?>">
                    <ul id="wovax-idx-stylingchecklist-search" data-wp-lists="list:page" class="categorychecklist form-no-clear">

                      <!--Space to load search feed fields-->

                    </ul>
                  </div>

                  <p class="button-controls wp-clearfix">
                    <span class="list-controls">
                      <a class="select-all aria-button-if-js" role="button" onclick="wovax_idx_select_all_styling()">Select All</a>
                    </span>

                    <span class="add-to-menu">
                      <input type="submit" class="button submit-add-to-menu right" value="Add to Layout" name="add-post-type-menu-item" id="submit-stylingtype-page">
                      <span class="spinner"></span>
                    </span>
                  </p>

                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </form>
  </div>

<!--Sorting div-->

  <div id="menu-management-liquid">
    <div id="menu-management">
      <form id="wovax_idx_feeds_layout_form_container" method="post" action="">
        <?php wp_nonce_field('save_change_feeds_fields', 'wovax_idx_feeds_feed_fields'); ?>
        <input type="hidden" name="wovax_idx_feed_id" value="<?php echo esc_attr(filter_var($_GET["idfeed"], FILTER_SANITIZE_STRING)) ?>">
        <input type="hidden" id="wovax_idx_feed_tab" value="<?php echo esc_attr(filter_var($_GET["tab"], FILTER_SANITIZE_STRING)) ?>">
        <div class="menu-edit ">
          <div id="nav-menu-header">
            <div class="major-publishing-actions wp-clearfix">
              <div class="publishing-action">
                <h4>Layout Structure</h4>
                <input type="submit" name="save_menu" id="save_menu_header" class="button button-primary button-large menu-save" value="Save Layout">
              </div>
            </div>
          </div>
          <div id="post-body">
            <div id="post-body-content" class="wp-clearfix">
              <div class="drag-instructions post-body-plain">
                <p>Drag each item into the order you prefer. Click the arrow on the right of the item to reveal additional configuration options.</p>
              </div>
              <div id="menu-instructions" class="post-body-plain menu-instructions-inactive"><p>Add menu items from the column on the left.</p>
              </div>

              <ul class="menu-layout" id="wovax-idx-sortable-layout">

                <!--Space to load every layout for structure-->

  <?php

    //Call every feed with status 1 on DB
    $feed_list = wovax_idx_list_feeds_field_by_id_status_1(filter_var($_GET['idfeed'], FILTER_SANITIZE_STRING));
    //Call map options
    $field_list = wovax_idx_sort_fields_list_feed_details($feed_list, $feed_attr_info);

    $content_array_aux = $field_list['content_array_aux'];
    $info_container_aux = $field_list['info_container_aux'];

    //Create auxiliar variable to create multiple layouts
    $content_listing = wovax_idx_display_feed_details_fields($content_array_aux, $info_container_aux, filter_var($_GET['idfeed'], FILTER_SANITIZE_STRING));

    //Display every layout
    echo $content_listing;

  ?>
              </ul>

            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

</div>

<script type="text/javascript">
  var count_aux_var = 5000;

  //#submit-posttype-page click function
  jQuery( "input#submit-posttype-page" ).click(function( event ) {
    //FIRST: prevent submition
    event.preventDefault();
    //SECOND: find container with show class
    var div_content = jQuery('div#posttype-page').find('div.show');

    //THIRD: get every checked box on container obtained a step before
    //We iterate every element in this search and displayed it on layout container
    jQuery(div_content).find('input[type=checkbox]:checked').each(function(){
      var info_container = jQuery(this).parent().parent();
      var layout_container = jQuery('ul#wovax-idx-sortable-layout');

      //FOURTH: display field on container depending type
      //4.1 Map field
      if( jQuery(info_container).find('input[name="wovax_idx_map_state"]').length > 0 ){

        //Elements we need to display field
        var item_id   = jQuery(info_container).find('input[name="wovax_idx_feed_id"]').val();
        var map_state = jQuery(info_container).find('input[name="wovax_idx_map_state"]').val();
        var map_order = jQuery(info_container).find('input[name="wovax_idx_map_order"]').val();
        var map_title = jQuery(info_container).find('input[name="wovax_idx_map_title"]').val();

        //Prepend on container before other fields
        jQuery( layout_container ).prepend(
            '<li id="list-item-' + item_id + '-' + count_aux_var + '" class="menu-item menu-item-depth-0 menu-item-page menu-item-edit-inactive" style="position: relative; left: 0px; top: 0px;">'
          + '<div class="menu-item-bar"><div class="menu-item-handle ui-sortable-handle"><span class="item-title">'
          + '<span class="menu-item-title">' + map_title + '</span>'
          + '</span><span class="item-controls"><span class="item-type">Active</span>'
          + '<a class="item-edit" id="edit-' + item_id + '" onclick="wovax_idx_change_content(this)">'
          + '</a></span></div></div>'
          + '<div class="menu-item-settings wp-clearfix" id="menu-item-settings-' + item_id + '">'
          + '<p class="description description-wide">'
          + '<label for="height-' + count_aux_var + '">Height<br>'
          + '<input type="text" name="wovax_idx_heightmap_' + item_id + '-' + count_aux_var + '" id="height-' + count_aux_var + '" class="widefat edit-menu-item-title"  value=""></label>'

          + '<label for="cssclass-' + count_aux_var + '">CSS Classes<br>'
          + '<input type="text" name="wovax_idx_cssclassmap_' + item_id + '-' + count_aux_var + '" id="cssclass-' + count_aux_var + '" class="widefat edit-menu-item-title"  value=""></label>'

          + '<input type="hidden" class="order-index" name="wovax_idx_ordermap_' + item_id + '-' + count_aux_var + '" value="0" >'
          + '<input type="hidden" name="wovax_idx_field_statemap_' + item_id + '-' + count_aux_var + '" value="1" >'
          + '</p><div class="menu-item-actions description-wide submitbox">'
          + '<a class="item-delete submitdelete deletion" id="delete-' + item_id + '" onclick="wovax_idx_clean_input(this)">Remove</a>'
          + '</div></div></li>'
        )
      }

      //4.2 Link field
      if( jQuery(info_container).find('input[name="wovax_idx_link_state"]').length > 0 ){

        //Elements we need to display field
        var item_id   = jQuery(info_container).find('input[name="wovax_idx_feed_id"]').val();
        var link_state = jQuery(info_container).find('input[name="wovax_idx_link_state"]').val();
        var link_order = jQuery(info_container).find('input[name="wovax_idx_link_order"]').val();
        var link_title = jQuery(info_container).find('input[name="wovax_idx_link_title"]').val();

        //Prepend on container before other fields
        jQuery( layout_container ).prepend(
            '<li id="list-item-' + item_id + '-' + count_aux_var + '" class="menu-item menu-item-depth-0 menu-item-page menu-item-edit-inactive" style="position: relative; left: 0px; top: 0px;">'
          + '<div class="menu-item-bar"><div class="menu-item-handle ui-sortable-handle"><span class="item-title">'
          + '<span class="menu-item-title">' + link_title + '</span>'
          + '</span><span class="item-controls"><span class="item-type">Active</span>'
          + '<a class="item-edit" id="edit-' + item_id + '" onclick="wovax_idx_change_content(this)">'
          + '</a></span></div></div>'
          + '<div class="menu-item-settings wp-clearfix" id="menu-item-settings-' + item_id + '">'
          + '<p class="description description-wide">'
          + '<label for="field-' + count_aux_var + '">Field Label<br>'
          + '<input type="text" name="wovax_idx_fieldlink_' + item_id + '-' + count_aux_var + '" id="field-' + count_aux_var + '" class="widefat edit-menu-item-title"  value=""></label>'
          + '<label for="text-' + count_aux_var + '">Link Text<br>'
          + '<input type="text" name="wovax_idx_textlink_' + item_id + '-' + count_aux_var + '" id="text-' + count_aux_var + '" class="widefat edit-menu-item-title"  value="View"></label>'

          + '<label for="cssclass-' + count_aux_var + '">CSS Classes<br>'
          + '<input type="text" name="wovax_idx_cssclasslink_' + item_id + '-' + count_aux_var + '" id="cssclass-' + count_aux_var + '" class="widefat edit-menu-item-title"  value=""></label>'

          + '<input type="hidden" class="order-index" name="wovax_idx_orderlink_' + item_id + '-' + count_aux_var + '" value="0" >'
          + '<input type="hidden" name="wovax_idx_field_statelink_' + item_id + '-' + count_aux_var + '" value="1" >'
          + '</p><div class="menu-item-actions description-wide submitbox">'
          + '<a class="item-delete submitdelete deletion" id="delete-' + item_id + '" onclick="wovax_idx_clean_input(this)">Remove</a>'
          + '</div></div></li>'
        )
      }

      //4.3 Common field
      if( jQuery(info_container).find('input[name="wovax_idx_id"]').length > 0 ){

        //Elements we need to display field
        var item_id = jQuery(info_container).find('input[name="wovax_idx_id"]').val();
        var item_alias_old = jQuery(info_container).find('input[name="wovax_idx_old_field"]').val();
        var item_mls_field_name = jQuery(info_container).find('input[name="wovax_idx_field_name"]').val();

        //Prepend on container before other fields
        jQuery( layout_container ).prepend(
            '<li id="list-item-' + item_id + '-' + count_aux_var + '" class="menu-item menu-item-depth-0 menu-item-page menu-item-edit-inactive" style="position: relative; left: 0px; top: 0px;">'
          + '<div class="menu-item-bar"><div class="menu-item-handle ui-sortable-handle"><span class="item-title">'
          + '<span class="menu-item-title">' + item_alias_old + '</span>'
          + '</span><span class="item-controls"><span class="item-type">Active</span>'
          + '<a class="item-edit" id="edit-' + item_id + '" onclick="wovax_idx_change_content(this)">'
          + '</a></span></div></div>'
          + '<div class="menu-item-settings wp-clearfix" id="menu-item-settings-' + item_id + '">'
          + '<p class="description description-wide">'
          + '<label for="' + item_mls_field_name + '">Field Label<br>'
          + '<input type="text" name="wovax_idx_alias_update_' + item_id + '-' + count_aux_var + '" id="' + item_mls_field_name + '" class="widefat edit-menu-item-title" value=""></label>'

          + '<label for="cssclass-' + item_mls_field_name + '">CSS Classes<br>'
          + '<input type="text" name="wovax_idx_cssclass_' + item_id + '-' + count_aux_var + '" id="cssclass-' + item_mls_field_name + '" class="widefat edit-menu-item-title"  value=""></label>'

          + '<input type="hidden" class="order-index" name="wovax_idx_order_' + item_id + '-' + count_aux_var + '" value="0" >'
          + '<input type="hidden" name="wovax_idx_field_state_' + item_id + '-' + count_aux_var + '" value="1" >'
          + '</p><div class="menu-item-actions description-wide submitbox">'
          + '<a class="item-delete submitdelete deletion" id="delete-' + item_id + '" onclick="wovax_idx_clean_input(this)">Remove</a>'
          + '</div></div></li>'
        )
      }

      //Increase auxiliar var for multi fields
      count_aux_var++;

      //Reorder field in order to avoid problems with order
      jQuery(layout_container).sortable('refresh');
      list = jQuery(layout_container).find(".order-index");
      list.each(function(index, elem){
        jQuery(elem).val(index);
      });

      //Uncheck boxes on container after displaying every field
      jQuery( this ) .prop('checked', false);
    })
  });

  //#submit-stylingtype-page click function
  jQuery( "input#submit-stylingtype-page" ).click(function( event ) {
    //FIRST: prevent submition
    event.preventDefault();
    //SECOND: find container with show class
    var div_content = jQuery('div#stylingtype-page').find('div.show');

    //THIRD: get every checked box on container obtained a step before
    //We iterate every element in this search and displayed it on layout container
    jQuery(div_content).find('input[type=checkbox]:checked').each(function(){
      var info_container = jQuery(this).parent().parent();
      var layout_container = jQuery('ul#wovax-idx-sortable-layout');

      //FOURTH: display field on container depending type
      //4.1 Map field
      if( jQuery(info_container).find('input[name="wovax_idx_divider_order"]').length > 0 ){

        //Elements we need to display field
        var item_id   = jQuery(info_container).find('input[name="wovax_idx_feed_id"]').val();
        var divider_state = jQuery(info_container).find('input[name="wovax_idx_divider_state"]').val();
        var divider_order = jQuery(info_container).find('input[name="wovax_idx_divider_order"]').val();
        var divider_title = jQuery(info_container).find('input[name="wovax_idx_divider_title"]').val();

        //Prepend on container before other fields
        jQuery( layout_container ).prepend(
            '<li id="list-item-' + item_id + '-' + count_aux_var + '" class="menu-item menu-item-depth-0 menu-item-page menu-item-edit-inactive" style="position: relative; left: 0px; top: 0px;">'
          + '<div class="menu-item-bar"><div class="menu-item-handle ui-sortable-handle"><span class="item-title">'
          + '<span class="menu-item-title">' + divider_title + '</span>'
          + '</span><span class="item-controls"><span class="item-type">Active</span>'
          + '<a class="item-edit" id="edit-' + item_id + '" onclick="wovax_idx_change_content(this)">'
          + '</a></span></div></div>'
          + '<div class="menu-item-settings wp-clearfix" id="menu-item-settings-' + item_id + '">'
          + '<p class="description description-wide">'

          + '<label for="cssclass-' + count_aux_var + '">CSS Classes<br>'
          + '<input type="text" name="wovax_idx_cssclassdivider_' + item_id + '-' + count_aux_var + '" id="cssclass-' + count_aux_var + '" class="widefat edit-menu-item-title"  value=""></label>'

          + '<input type="hidden" class="order-index" name="wovax_idx_orderdivider_' + item_id + '-' + count_aux_var + '" value="0" >'
          + '<input type="hidden" name="wovax_idx_field_statedivider_' + item_id + '-' + count_aux_var + '" value="1" >'
          + '</p><div class="menu-item-actions description-wide submitbox">'
          + '<a class="item-delete submitdelete deletion" id="delete-' + item_id + '" onclick="wovax_idx_clean_input(this)">Remove</a>'
          + '</div></div></li>'
        )
      }

       //4.2 Link field
      if( jQuery(info_container).find('input[name="wovax_idx_spacer_order"]').length > 0 ){

        //Elements we need to display field
        var item_id   = jQuery(info_container).find('input[name="wovax_idx_feed_id"]').val();
        var spacer_state = jQuery(info_container).find('input[name="wovax_idx_spacer_state"]').val();
        var spacer_order = jQuery(info_container).find('input[name="wovax_idx_spacer_order"]').val();
        var spacer_title = jQuery(info_container).find('input[name="wovax_idx_spacer_title"]').val();

        //Prepend on container before other fields
        jQuery( layout_container ).prepend(
            '<li id="list-item-' + item_id + '-' + count_aux_var + '" class="menu-item menu-item-depth-0 menu-item-page menu-item-edit-inactive" style="position: relative; left: 0px; top: 0px;">'
          + '<div class="menu-item-bar"><div class="menu-item-handle ui-sortable-handle"><span class="item-title">'
          + '<span class="menu-item-title">' + spacer_title + '</span>'
          + '</span><span class="item-controls"><span class="item-type">Active</span>'
          + '<a class="item-edit" id="edit-' + item_id + '" onclick="wovax_idx_change_content(this)">'
          + '</a></span></div></div>'
          + '<div class="menu-item-settings wp-clearfix" id="menu-item-settings-' + item_id + '">'
          + '<p class="description description-wide">'
          + '<label for="field-' + count_aux_var + '">Height<br>'
          + '<input type="text" name="wovax_idx_fieldspacer_' + item_id + '-' + count_aux_var + '" id="field-' + count_aux_var + '" class="widefat edit-menu-item-title"  value=""></label>'

          + '<label for="cssclass-' + count_aux_var + '">CSS Classes<br>'
          + '<input type="text" name="wovax_idx_cssclassspacer_' + item_id + '-' + count_aux_var + '" id="cssclass-' + count_aux_var + '" class="widefat edit-menu-item-title"  value=""></label>'

          + '<input type="hidden" class="order-index" name="wovax_idx_orderspacer_' + item_id + '-' + count_aux_var + '" value="0" >'
          + '<input type="hidden" name="wovax_idx_field_statespacer_' + item_id + '-' + count_aux_var + '" value="1" >'
          + '</p><div class="menu-item-actions description-wide submitbox">'
          + '<a class="item-delete submitdelete deletion" id="delete-' + item_id + '" onclick="wovax_idx_clean_input(this)">Remove</a>'
          + '</div></div></li>'
        )
      }

      //Increase auxiliar var for multi fields
      count_aux_var++;

      //Increase auxiliar var for multi fields
      jQuery(layout_container).sortable('refresh');
      list = jQuery(layout_container).find(".order-index");
      list.each(function(index, elem){
        jQuery(elem).val(index);
      });

      //Uncheck boxes on container after displaying every field
      jQuery( this ) .prop('checked', false);
    })
  });
</script>

<style type="text/css">

  h4{
    display: inline-block;
  }

  #save_menu_header{
    margin-top: 10px;
    float: right;
  }

  #post-body-content {
    position: relative;
    float: none;
  }

  #post-body {
    padding: 0 10px 10px;
    border-top: 1px solid #fff;
    border-bottom: 1px solid #ddd;
    background: #fff;
  }

  .item-edit {
    position: absolute;
    right: -20px;
    top: 0;
    display: block;
    width: 30px;
    height: 40px;
    outline: 0;
  }

  .item-edit:before{
    margin-top: 10px;
    margin-left: 4px;
    width: 20px;
    border-radius: 50%;
    text-indent: -1px;
  }

  .item-edit:before{
    content:"\f140";
    font:400 20px/1 dashicons;
    speak:none;
    display:block;
    -webkit-font-smoothing:antialiased;
    -moz-osx-font-smoothing:grayscale;
    text-decoration:none!important;
  }

  .different:after{
    content:"\f142"!important;
  }

  .special:before{
    content:"\f142"!important;
  }

  .show {
    display: block!important;
  }

  .nav-tab-link, .item-delete, .select-all{
    cursor: pointer!important;
  }

</style>

  <?php
}

/* Display HTML Rules Tab */

function wovax_idx_display_rules($select_rule, $wovax_idx_save_input_rule, $type = '', $value = '', $wovax_idx_feed_rules_table, $wovax_idx_feed_rules_count) {
?>
<section>
  <form method="post" action="">
    <?php wp_nonce_field('save_change_feeds_rules', 'wovax_idx_feeds_feed_rules'); ?>
    <input type="hidden" name="wovax-idx-feed-id" value="<?php echo esc_attr(filter_var($_GET["idfeed"], FILTER_SANITIZE_STRING)) ?>">
    <input type="hidden" name="wovax-idx-feed-rule-id" value="<?php echo esc_attr(filter_var($_GET["idrule"], FILTER_SANITIZE_STRING)) ?>">
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">
            <label for="feed-field-name">Field</label>
          </th>
          <td>
            <select id="feed-field-name" name="wovax-idx-feed-rule-field">

              <?php echo $select_rule; ?>

            </select>
            <p class="description" id="feed-field-name-description">The data field to be affected by this rule.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="feed-rule-type">Rule Type</label>
          </th>
          <td>
            <select id="feed-rule-type" name="wovax-idx-feed-rule-type">

              <option value="">Select Rule Type</option>
              <option value="exclude" <?php echo esc_attr($selected_type = ($type == 'exclude') ? 'selected' : ''); ?>>Exclude</option>
               <option value="select" <?php echo esc_attr($selected_type = ($type == 'select')? 'selected' : ''); ?>>Include</option>

            </select>
            <p class="description" id="feed-rule-type-description">Type of rule to apply to the field.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="feed-rule-value">Rule Value</label>
          </th>
          <td>
            <input name="wovax-idx-feed-rule-value" type="text" id="feed-rule-value" value="<?php echo esc_attr($value); ?>"  class="short-text">
            <p class="description" id="rule-value-description">The value of the field in this rule.</p>
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
          <span class="displaying-num"><?php echo esc_html($wovax_idx_feed_rules_count); ?> items</span>
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

      <tbody id="the-list-feed-rules-details" data-wp-lists="list:post" data-id-feed="<?php echo esc_attr(filter_var($_GET['idfeed'], FILTER_SANITIZE_STRING)); ?>">
        <?php echo $wovax_idx_feed_rules_table; ?>
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
  </form>
</section>
  <?php
}

  ?>
