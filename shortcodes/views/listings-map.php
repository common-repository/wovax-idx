<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

use Wovax\IDX\Settings\ShortcodeSettings;

function wovax_idx_get_content_listings_map($shortcode_details, $query_restrictions, $shortcode_type, $api_key) {
    // Global variable for user info
    global $current_user;
    $instance = mt_rand(1000,10000);
    $instance_id = 'map-'.$instance;
    $view_option   = 'map';
    $fav_avail     = (get_option('wovax-idx-settings-users-favorites')) ? get_option('wovax-idx-settings-users-favorites') : "no";
    $shortcode_id = $shortcode_details[0]->id;

    if($shortcode_type != 'favorites') {
        $fields_array = array();
        foreach ($query_restrictions as $key => $value) {
            array_push($fields_array, $value->field);
        }
        $feed_rules = wovax_idx_get_rules_by_shortcode($fields_array, $shortcode_id, 'listing_embed');
    }

    $plugin_url = WOVAX_PLUGIN_URL;

	$map_api_key = get_option('wovax-idx-settings-google-maps-api-key');

    $user_fav_properties = array();

    if (is_user_logged_in()) {
        get_currentuserinfo();
        $user_id   = $current_user->data->ID;
        $user_favs = get_user_meta($user_id, 'wovax-idx-favorites', true);
        $user_fav_properties = json_decode($user_favs, true);
        // Sets favorites on user_meta in order to save favorite properties
        if(!is_array($user_fav_properties)) {
            update_user_meta($user_id, 'wovax-idx-favorites', json_encode(array()));
            $user_fav_properties = array();
        }

    }
    // Get sort option for calling data
    $sort_option = (new ShortcodeSettings($shortcode_id))->sortOrder();
    if(
        array_key_exists('wovax-idx-select-sort', $_GET) &&
        in_array($_GET['wovax-idx-select-sort'], ShortcodeSettings::allSortValues(), TRUE)
    ) {
        $sort_option = $_GET['wovax-idx-select-sort'];
    } else { // Should not have todo this, but this use the $_GET is used else where.
        $_GET['wovax-idx-select-sort'] = $sort_option;
    }

      // Get data depending on shortcode type
    if(array_key_exists('wovax-idx-user-favorites', $_GET) && $_GET['wovax-idx-user-favorites'] === 'true') {
      $data = wovax_idx_get_list_buildings_by_favorites($shortcode_details, $user_fav_properties, $sort_option, $view_option, $api_key);
    } else {
      switch($shortcode_type) {
          case 'rules':
              $data = wovax_idx_get_list_buildings_by_rules($shortcode_details, $query_restrictions, $sort_option, $view_option, $api_key, $feed_rules);
              break;
          case 'favorites':
              $data = wovax_idx_get_list_buildings_by_favorites($shortcode_details, $user_fav_properties, $sort_option, $view_option, $api_key);
              break;
          default:
              $data = wovax_idx_get_list_buildings_by_filters($shortcode_details, $query_restrictions, $sort_option, $view_option, $api_key, $feed_rules);
              break;
      }
    }

  if (is_array($data) && $data['total']) {

    if (!is_user_logged_in()) {
      if (isset($_POST['email']) && isset($_POST['pass'])) {
        wovax_idx_custom_login();
      }
    }

    $icon_url_1 = $plugin_url . '/assets/graphics/spotlight-poi.png';
    $icon_url_2 = $plugin_url . '/assets/graphics/spotlight-poi_hdpi.png';
    $map_array = array();
    $address_array = array();
    $aux_inc = 1;
    $street = "Street Address";
    $zip = "Zip Code";

    foreach ($data['data'] as $key => $value) {
      if(!is_numeric($value->Latitude) || !is_numeric($value->Longitude)) {
        continue;
      }
      if($value->Latitude == 0 && $value->Longitude == 0) {
        continue;
      }
      array_push($map_array, array($value->Latitude, $value->Longitude, $aux_inc, $value->Price, $value->id, $value->class_id));
      $aux_inc++;
      if( $aux_inc == ( $shortcode_details[0]->per_map + 1) || $aux_inc == 251 ){
        break;
      }
    }
  	$total_prop = $data['total'];
    $current_view = 'map';
    $shortcode_views = array(
      'grid_view' => $shortcode_details[0]->grid_view,
      'map_view' => $shortcode_details[0]->map_view
    );
    if($shortcode_details[0]->action_bar === 'yes') {
      $action_bar_html = wovax_idx_get_action_bar($current_view, $shortcode_type, $shortcode_views, $total_prop, $sort_option);
      $output .= $action_bar_html;
    }
    $output .= '<input type="hidden" name="session_stat" value="' . esc_attr(is_user_logged_in()) . '">
    <div class="wovax-idx-map-container">
      <div class="wovax-idx-map-search">
        <div class="wovax-idx-shortcode-map" id="'.$instance_id.'">
        </div>
      </div>
    </div>

      <script>

        var marker_wovax = [];
        var ajaxurl = "' . esc_url(admin_url('admin-ajax.php')) . '";
        var activeWindow;
        var markers_open_wovax_filter = [];
        var marker_icon;

        jQuery( document ).ready(function() {
          setTimeout(function(){
            var script = document.createElement("script");
            script.type = `text/javascript`;
            script.src = `' . $plugin_url . '/assets/js/infobox.min.js`;
            document.head.appendChild(script);
          },500);
        });

        function wovax_idx_get_grid_view_search(event){
          event.preventDefault();
          jQuery("input:hidden[name=wovax-idx-view-option]").val("grid");
          jQuery("#form-tab-1").submit();
        }
        function wovax_idx_get_grid_view_listing(event){
          event.preventDefault();
          jQuery("input:hidden[name=wovax-idx-view-option]").val("grid");
          jQuery("#wovax-idx-view").submit();
        }
        function wovax_idx_submit_favorites_button(event){
          event.preventDefault();
          jQuery("input:hidden[name=wovax-idx-user-favorites]").val("true");
          jQuery("#wovax-idx-favorites-form").submit();
        }
        function wovax_idx_submit_favorites_button_search(event){
          event.preventDefault();
          jQuery("input:hidden[name=wovax-idx-user-favorites]").val("true");
          jQuery("#form-tab-1").submit();
        }

        function wovax_idx_init_map(){
          var marcadores = ' . json_encode($map_array) . ';

          var map = new google.maps.Map(document.getElementById("'.$instance_id.'"), {
            zoom: 8,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
          });

          bounds_wovax = new google.maps.LatLngBounds();

          var i;
          var count_aux = 0;
          var geocoder = new google.maps.Geocoder();

          var devicePixelRatio = window.devicePixelRatio;

          if(devicePixelRatio>2){
            marker_icon = `' . $icon_url_2 . '`;
          }else{
            marker_icon = `' . $icon_url_1 . '`;
          }

          for (i = 0; i < marcadores.length; i++) {
            if(marcadores[i][0]!=0 && marcadores[i][1]!=0){
              marker_wovax[count_aux] = new google.maps.Marker({
                position: new google.maps.LatLng(marcadores[i][0], marcadores[i][1]),
                map: map,
                icon: marker_icon
              });
              marker_wovax[count_aux].price = marcadores[i][3];
              marker_wovax[count_aux].id_post = marcadores[i][4];
              marker_wovax[count_aux].id_class = marcadores[i][5];
              marker_wovax[count_aux].setDraggable(false);

              marker_wovax[count_aux].addListener("click", function() {
                wovax_idx_click_marker(this,ajaxurl,map);
              });
              bounds_wovax.extend(marker_wovax[count_aux].position);
              count_aux++;
            }
          }

          var markerCluster = new MarkerClusterer(map, marker_wovax, {imagePath: "https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m"});

          map.fitBounds(bounds_wovax);

          google.maps.event.addListener(map, "click", function(event) {
            wovax_idx_close_all_markers();
          });

        }

        function wovax_idx_click_marker(marker, ajaxurl, mapa){
          //INFOBOX CON AJAX
          var id_post  = marker.id_post;
          var price    = marker.price;
          var id_class = marker.id_class;
          var propiedades = '.json_encode($user_fav_properties).';
          var fav_avail = "' . $fav_avail . '";

          if(typeof marker.infobox === "undefined"){

            var data = {action:"get_infobox_marker", id:id_post, price:price, class:id_class, prop:propiedades, fav_avail:fav_avail };
            jQuery.post(ajaxurl, data, function(response) {

              var boxText = document.createElement("div");
              boxText.innerHTML = response ;

              var myOptions_info = {
                content: boxText,
                disableAutoPan: false,
                maxWidth: 0,
                pixelOffset: new google.maps.Size(-175, -410),
                zIndex: null,
                boxStyle: {
                  opacity: 1,
                  width: "340px",
                  height: "95px"
                },
                closeBoxMargin: "0px",
                closeBoxURL: "",
                isHidden: false,
                pane: "floatPane",
                enableEventPropagation: false
              };
              marker.infobox = new InfoBox(myOptions_info);
              wovax_idx_close_all_markers();
              marker.infobox.open(mapa, marker);
              markers_open_wovax_filter.push(marker);

            });
          }else{
            wovax_idx_close_all_markers();
            marker.infobox.open(mapa, marker);
            markers_open_wovax_filter.push(marker);
          }
        }

        function wovax_idx_close_all_markers(){
          jQuery.each(markers_open_wovax_filter,function(key,value){
            if(typeof value.infobox !== "undefined"){
              value.infobox.close();
            }
          });
        }

        function wovax_idx_go_listing_details(e, url){
          if(jQuery(e.target).attr("class") == "toggle-heart" || jQuery(e.target).attr("id") == "toggle-heart-section"){
            return;
          }
          location.href = url;
        }

      </script>

      <script
                src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js">
      </script>

      <script async defer
                src="https://maps.googleapis.com/maps/api/js?key=' . $map_api_key . '&callback=wovax_idx_init_map"></script>
      <style>
        #'.$instance_id.' {
          height: 600px;
          width: 100%;
          margin: 30px 0 0 0;
        }
        .wovax-idx-listing {
          background: white;
        }
      </style>';

      return $output;

  } else {
    if (is_array($data)) {
      $data = "0 results returned";
    }
    return '<br />
        <div class=warning>
          <p> '.esc_html($data).'</p>
        </div>
        <style media=screen type=text/css>
          .warning {
            font-size: 13px;
            color: #333;
            padding-top: 20px;
            padding-bottom: 1px;
          }
        </style>';

  }
}
?>
