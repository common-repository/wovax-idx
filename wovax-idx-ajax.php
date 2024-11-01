<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}
use Wovax\IDX\API\WovaxConnect;
use Wovax\IDX\Settings\FeedDisplay;
use Wovax\IDX\Utilities\ListingLoader;

/* This file will contain all the AJAX functions */
add_action('wp_ajax_search_page_post_title', 'wovax_idx_search_page_post_title');
add_action('wp_ajax_create_page_post', 'wovax_idx_create_page_post');
add_action('wp_ajax_create_listing_page_post', 'wovax_idx_create_listing_page_post');
add_action('wp_ajax_get_wovax_idx_get_result_api', 'wovax_idx_get_result_api');
add_action('wp_ajax_get_wovax_idx_get_result_api_feed_details', 'wovax_idx_get_result_api_feed_details');
add_action('wp_ajax_get_wovax_idx_get_result_api_feed_for_search_content', 'wovax_idx_get_result_api_feed_for_search_content');
add_action('wp_ajax_set_order_section', 'wovax_idx_set_order_section');
add_action('wp_ajax_get_wovax_idx_sort_shortcode', 'wovax_idx_get_wovax_idx_sort_shortcode');
add_action('wp_ajax_get_wovax_idx_sort_user_activity', 'wovax_idx_get_wovax_idx_sort_user_activity');
add_action('wp_ajax_save_favorite', 'wovax_idx_save_favorite');
add_action('wp_ajax_delete_favorite', 'wovax_idx_delete_favorite');
add_action('wp_ajax_update_profile', 'wovax_idx_update_profile');
add_action('wp_ajax_get_infobox_marker', 'wovax_idx_get_infobox_marker');
add_action('wp_ajax_nopriv_get_infobox_marker', 'wovax_idx_get_infobox_marker');
add_action('wp_ajax_wovax_idx_settings_autocomplete', 'wovax_idx_settings_autocomplete');

//Just in case someone is able to access the save search interface without logging in somehow.
add_action('wp_ajax_nopriv_save_search', 'wovax_idx_please_login');
add_action('wp_ajax_nopriv_delete_saved_search', 'wovax_idx_please_login');

add_action('wp_ajax_wovax_idx_save_search', 'wovax_idx_save_search');
add_action('wp_ajax_wovax_idx_delete_saved_search', 'wovax_idx_delete_saved_search');

add_action('wp_ajax_nopriv_save_favorite', 'wovax_idx_please_login');
add_action('wp_ajax_nopriv_delete_favorite', 'wovax_idx_please_login');
function wovax_idx_please_login() {
    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => false,
        'reason'  => 'no_login',
        'msg'     => 'Please sign in to add favorites.'
    ));
    die();
}

/* function to sort the shortcode table */
function wovax_idx_get_wovax_idx_sort_user_activity() {
  if (!isset($_POST['attr'])) {
    echo json_encode(array());
    die();
  }

  $orderby = filter_var($_POST['attr']['orderby'], FILTER_SANITIZE_STRING);
  $ordername = filter_var($_POST['attr']['ordername'], FILTER_SANITIZE_STRING);
  $search = filter_var($_POST['attr']['search'], FILTER_SANITIZE_STRING);
  $paged = filter_var($_POST['attr']['paged'], FILTER_SANITIZE_STRING);

  $per_page = get_option('wovax-idx-users-per-page');
  $result = wovax_idx_user_table_sort($per_page, $paged, $orderby, $ordername, $search);
  $result = json_decode($result);
  if ($result->count_total && $result->data && !empty($result->data) && !empty($result->count_total)) {

    $total = $result->count_total;
    $pag_pages = ceil($total / $per_page);
    $table_html = '';
    $paginate_html = '';

    $user = get_current_user_id();
    $user_meta = get_user_meta( $user, 'managewovax-idx_page_wovax_idx_user_activitycolumnshidden', true );
    $user_meta = ( is_array($user_meta) )?$user_meta:array();

    $columns_count = 7 - ( count($user_meta) / 2 );

    $fullname_hidden  = in_array('fullname', $user_meta)?' hidden':'';
    $phone_hidden     = in_array('phone', $user_meta)?' hidden':'';
    $email_hidden     = in_array('email', $user_meta)?' hidden':'';
    $favorites_hidden = in_array('favorites', $user_meta)?' hidden':'';

    foreach ($result->data as $key => $item) {
      $item = ( object )$item;

      $table_html .= '<tr>
                           <td class="title column-title has-row-actions column-primary" data-colname="Class">
                            ' . $item->picture . '<strong><a class="row-title" href="' . esc_url('admin.php?page=wovax_idx_users&amp;tab=general&amp;action=update&amp;iduser=' . $item->id . '') . '" title="Edit ' . esc_attr($item->nickname) . '">' . esc_html($item->nickname) . '</a></strong>
                            <div class="row-actions"><span class="edit"><a href="' . esc_url('admin.php?page=wovax_idx_users&amp;tab=general&amp;action=update&amp;iduser=' . $item->id . '') . '">Details</a></span></div>
                            <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                           </td>
                           <td class="fullname column-fullname' . esc_attr($fullname_hidden) . '" data-colname="Fullname">
                             ' . esc_html($item->username) . '
                           </td>
                           <td class="phone column-phone' . esc_attr($phone_hidden) . '" data-colname="Phone">
                             ' . esc_html($item->phone) . '
                           </td>
                           <td class="email column-email' . esc_attr($email_hidden) . '" data-colname="Email">
                             ' . esc_html($item->email) . '
                           </td>
                           <td class="favorites column-favorites' . esc_attr($favorites_hidden) . '" data-colname="Favorites">
                             ' . esc_html(($item->qty_prop>1?$item->qty_prop . ' Properties': ($item->qty_prop==0?'None':$item->qty_prop . ' Property'))) . '
                           </td>
                         </tr>';
    }


    // PAGINATION
    if ($paged < $pag_pages && $paged == '1') {
      $wovax_idx_pag_prev = '<span class="tablenav-pages-navspan" aria-hidden="true">«</span>
                   <span class="tablenav-pages-navspan" aria-hidden="true">‹</span>';
      $wovax_idx_pag_next = '<a href="#" id="wovax-idx-users-next-search" class="next-page"><span class="screen-reader-text" >Next page</span><span aria-hidden="true">›</span></a>
                   <a class="last-page" href="#" id="wovax-idx-users-last-search"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>';
    }
    elseif ($paged < $pag_pages) {
      $wovax_idx_pag_prev = '<a class="first-page" href="#" id="wovax-idx-users-first-search"><span class="screen-reader-text">First page</span><span aria-hidden="true">«</span></a>
                  <a class="prev-page" href="#" id="wovax-idx-users-prev-search"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>';
      $wovax_idx_pag_next = '<a href="#" id="wovax-idx-users-next-search" class="next-page"><span class="screen-reader-text" >Next page</span><span aria-hidden="true">›</span></a>
                   <a class="last-page" href="#" id="wovax-idx-users-last-search"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>';
    }
    else {
      $wovax_idx_pag_prev = '<a class="first-page" href="#" id="wovax-idx-users-first-search"><span class="screen-reader-text">First page</span><span aria-hidden="true">«</span></a>
                    <a class="prev-page" href="#" id="wovax-idx-users-prev-search"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>';
      $wovax_idx_pag_next = '<span class="tablenav-pages-navspan" aria-hidden="true">›</span>
                  <span class="tablenav-pages-navspan" aria-hidden="true">»</span>';
    }

    if ($total <= $per_page) {
      $paginate_html.= '
                <div class="tablenav-pages">
                  <span class="displaying-num">' . esc_html($total) . ' items</span>
                </div>';
    }
    else {
      $paginate_html.= '
                <div class="tablenav-pages">
                  <span class="displaying-num">' . esc_html($total) . ' items</span>
                    <span class="pagination-links">
                      ' . $wovax_idx_pag_prev . '
                      <span class="paging-input">
                        <label for="wovax-idx-current-users-page-selector" class="screen-reader-text">Actual page</label>
                        <input class="current-page" id="wovax-idx-current-users-page-selector" type="text" name="paged" value="' . esc_attr($paged) . '" size="1" aria-describedby="table-paging">
                        <span class="tablenav-paging-text"> of <span class="total-pages" id="wovax-idx-users-pag-pages">' . esc_html($pag_pages) . '</span></span>
                      </span>
                        ' . $wovax_idx_pag_next . '
                    </span>
                </div>';
    }

    echo json_encode(array(
      'table_html' => $table_html,
      'paginate_html' => $paginate_html
    ));
    die();
  }
  else {
    $table_html.= '<tr><td class="colspanchange" colspan="' . esc_html($columns_count) . '">No users found.</td></tr>';
  }

  echo json_encode(array(
    'table_html' => $table_html
  ));
  die();
}

function wovax_idx_get_wovax_idx_sort_shortcode() {
  $orderby = filter_var($_POST['attr']['orderby'], FILTER_SANITIZE_STRING);
  $ordername = filter_var($_POST['attr']['ordername'], FILTER_SANITIZE_STRING);
  $search = filter_var($_POST['attr']['search'], FILTER_SANITIZE_STRING);
  $filter = filter_var($_POST['attr']['filter'], FILTER_SANITIZE_STRING);
  $section = filter_var($_POST['attr']['section'], FILTER_SANITIZE_STRING);
  $result = wovax_idx_shortcode_table_sort($orderby, $ordername, $search, $filter, $section);
  $shortcode_table = '';

  $user = get_current_user_id();
  $shortcode_meta = get_user_meta( $user, 'managewovax-idx_page_wovax_idx_shortcodescolumnshidden', true );
  $shortcode_meta = ( is_array($shortcode_meta) )?$shortcode_meta:array();

  $shortcode_hidden = in_array('shortcode', $shortcode_meta)?' hidden':'';
  $type_hidden      = in_array('type', $shortcode_meta)?' hidden':'';
  $author_hidden    = in_array('author', $shortcode_meta)?' hidden':'';
  $created_hidden   = in_array('created', $shortcode_meta)?' hidden':'';

  if ($section == 'trash') {
    foreach($result as $key => $item) {
      $count++;
      $date_mys = mysql2date('U', $item->date);
      $last_updated = esc_html(human_time_diff($date_mys, current_time('timestamp')));
      $filter_type = ($item->type == 'listings' ? 'Listings Embed' : ($item->type == 'search_form' ? 'Search Form' : ($item->type == 'user_favorites' ? 'User Favorites' : 'User Profile')));
      $shortcode_table.= '<tr>
				                    <th scope="row" class="check-column">
				                      <input type="checkbox" name="post[]" value="' . esc_attr($item->id) . '">
				                    </th>
				                    <td class="title column-title has-row-actions column-primary" data-colname="Title">
				                      <strong><a class="row-title" "' . esc_url('admin.php?page=wovax_idx_shortcodes&action=update&id=' . $item->id . '') . '" title="Edit “Main site search”">' . esc_html($item->title) . '</a></strong>
				                      <div class="row-actions"><span class="untrash"><a  href="' . esc_url('admin.php?page=wovax_idx_shortcodes&action=untrash&t=t&id=' . $item->id . '') . '" aria-label="Restore “dfg” from the Trash">Restore</a> | </span><span class="delete"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&action=delete&t=t&id=' . $item->id . '') . '" class="submitdelete" aria-label="Delete “dfg” permanently">Delete Permanently</a></span></div>
				                      <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
				                    </td>
				                    <td class="shortcode column-shortcode' . esc_attr($shortcode_hidden) . '" data-colname="Shortcode">
				                      [wovax-idx id="' . esc_html($item->id) . '"]
				                    </td>
				                    <td class="type column-type' . esc_attr($type_hidden) . '" data-colname="Type">
				                     ' . esc_html($filter_type) . '
				                    </td>
				                    <td class="author column-author' . esc_attr($author_hidden) . '" data-colname="Author">
				                       ' . esc_html($item->author) . '
				                    </td>
				                    <td class="created column-created' . esc_attr($created_hidden) . '" data-colname="Created">
				                      <abbr title="' . esc_attr($item->date) . '">
				                       ' . esc_html($last_updated) . '
				                      </abbr>
				                    </td>
				                  </tr>';
    }
  }
  else {
    foreach($result as $key => $item) {
      $count++;
      $date_mys = mysql2date('U', $item->date);
      $last_updated = esc_html(human_time_diff($date_mys, current_time('timestamp')));
      $filter_type = ($item->type == 'listings' ? 'Listings Embed' : ($item->type == 'search_form' ? 'Search Form' : ($item->type == 'user_favorites' ? 'User Favorites' : 'User Profile')));
      $shortcode_table.= '<tr>
			                    <th scope="row" class="check-column">
			                      <input type="checkbox" name="post[]" value="' . esc_attr($item->id) . '">
			                    </th>
			                    <td class="title column-title has-row-actions column-primary" data-colname="Title">
			                      <strong><a class="row-title" "' . esc_url('admin.php?page=wovax_idx_shortcodes&action=update&id=' . $item->id . '') . '" title="Edit “Main site search”">' . esc_html($item->title) . '</a></strong>
			                      <div class="row-actions"><span class="edit"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&action=update&id=' . $item->id . '') . '">Edit</a> | </span><span class="copy"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&id=' . $item->id . '&action=duplicate') . '">Duplicate</a> | </span><span class="trash"><a href="' . esc_url('admin.php?page=wovax_idx_shortcodes&id=' . $item->id . '&action=trash') . '" class="submitdelete" aria-label="Move “Main site search” to the Trash">Trash</a></span></div>
			                      <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
			                    </td>
			                    <td class="shortcode column-shortcode' . esc_attr($shortcode_hidden) . '" data-colname="Shortcode">
			                      [wovax-idx id="' . esc_html($item->id) . '"]
			                    </td>
			                    <td class="type column-type' . esc_attr($type_hidden) . '" data-colname="Type">
			                     ' . esc_html($filter_type) . '
			                    </td>
			                    <td class="author column-author' . esc_attr($author_hidden) . '" data-colname="Author">
			                       ' . esc_html($item->author) . '
			                    </td>
			                    <td class="created column-created' . esc_attr($created_hidden) . '" data-colname="Created">
			                      <abbr title="' . esc_attr($item->date) . '">
			                       ' . esc_html($last_updated) . '
			                      </abbr>
			                    </td>
			                  </tr>';
    }
  }

  if (empty($result)) {
    $shortcode_table = '<tr><td class="colspanchange" colspan="6">No shortcodes found.</td></tr>';;
  }

  $wovax_table = array(
    'table' => $shortcode_table
  );
  echo json_encode($wovax_table);
  die();
}

/* function to retrieve the page or post list */
function wovax_idx_search_page_post_title() {
  $wovax_idx_name = filter_var($_POST['wovax_idx_name'], FILTER_SANITIZE_STRING);
  $list_titles = wovax_idx_get_all_post_page($wovax_idx_name);
  echo json_encode(array(
    'page_post' => $list_titles
  ));
  die();
}

/* function to create a new page and retrieves post id */
function wovax_idx_create_page_post() {
  $title_post = "Search Results";
  $id_post = wovax_idx_create_post_page($title_post, '[wovax-idx-search-results]');
  echo json_encode(array(
    'id_post' => $id_post
  ));
  die();
}

/* function to create a new page for Listing Details and retrieves post id */
function wovax_idx_create_listing_page_post() {
    $opts       = new Wovax\IDX\Settings\InitialSetup;
    $title_post = "Listing Details";
    ob_start();
    ?>
    <!-- wp:wovax-idx-wordpress/image-gallery {"displayNumbers":true,"showMainArrows":true} /-->
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column">
    <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>MLS Number</strong>","listingField":"MLS Number","className":"wovax-idx-field"} /-->
    <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Listing Agent</strong>","listingField":"Listing Agent","className":"wovax-idx-field"} /-->
    <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Listing Office</strong>","listingField":"Listing Office","className":"wovax-idx-field"} /-->
    </div>
    <!-- /wp:column -->
    <!-- wp:column -->
    <div class="wp-block-column">
    <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Price</strong>","listingField":"Price","fieldType":{"link":{"label":"Click Here"},"numeric":{"commas":true,"decimals":2},"price":{"left":true,"symbol":"$"},"type":"price","boolean":{}},"className":"wovax-idx-field"} /-->
    <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Status</strong>","listingField":"Status","className":"wovax-idx-field"} /-->
    <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Date Listed</strong>","listingField":"Date Listed","className":"wovax-idx-field"} /-->
    </div>
    <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
    <!-- wp:wovax-idx-wordpress/field-data {"listingField":"Description"} /-->
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column">
    <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Exterior Features</strong>","listingField":"Exterior Features","className":"wovax-idx-field"} /-->
    <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Interior Features</strong>","listingField":"Interior Features","className":"wovax-idx-field"} /-->
    </div>
    <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column">
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Acres</strong>","listingField":"Acres","fieldType":{"link":{"label":"Click Here"},"numeric":{"commas":true,"decimals":2},"price":{"left":true,"symbol":"$"},"type":"numeric","boolean":{}},"className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Architectural Style</strong>","listingField":"Architectural Style","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Area</strong>","listingField":"Area","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Bathrooms</strong>","listingField":"Bathrooms","fieldType":{"link":{"label":"Click Here"},"numeric":{"commas":true,"decimals":2},"price":{"left":true,"symbol":"$"},"type":"numeric","boolean":{}},"className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Bedrooms</strong>","listingField":"Bedrooms","fieldType":{"link":{"label":"Click Here"},"numeric":{"commas":true,"decimals":2},"price":{"left":true,"symbol":"$"},"type":"numeric","boolean":{}},"className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>City</strong>","listingField":"City","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>County</strong>","listingField":"County","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Flooring</strong>","listingField":"Flooring","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Full Bathrooms</strong>","listingField":"Full Bathrooms","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Half Bathrooms</strong>","listingField":"Half Bathrooms","className":"wovax-idx-field"} /-->
    </div>
    <!-- /wp:column -->
    <!-- wp:column -->
    <div class="wp-block-column">
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Heating</strong>","listingField":"Heating","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Lot Size</strong>","listingField":"Lot Size","fieldType":{"link":{"label":"Click Here"},"numeric":{"commas":true,"decimals":2},"price":{"left":true,"symbol":"$"},"type":"numeric","boolean":{}},"className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Neighborhood</strong>","listingField":"Neighborhood","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Property Type</strong>","listingField":"Property Type","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Property Sub Type</strong>","listingField":"Property Sub Type","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Roofing</strong>","listingField":"Roofing","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Square Footage</strong>","listingField":"Square Footage","fieldType":{"link":{"label":"Click Here"},"numeric":{"commas":true,"decimals":2},"price":{"left":true,"symbol":"$"},"type":"numeric","boolean":{}},"className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>State</strong>","listingField":"State","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Subdivision</strong>","listingField":"Subdivision","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Virtual Tour</strong>","listingField":"Virtual Tour","fieldType":{"link":{"label":"Click Here"},"numeric":{"commas":true,"decimals":2},"price":{"left":true,"symbol":"$"},"type":"link","boolean":{}},"className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Year Built</strong>","listingField":"Year Built","className":"wovax-idx-field"} /-->
      <!-- wp:wovax-idx-wordpress/labeled-field {"label":"<strong>Zip Code</strong>","listingField":"Zip Code","className":"wovax-idx-field"} /-->
    </div>
    <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
    <?php
    $blocks = ob_get_clean();
    $id_post = wovax_idx_create_post_page($title_post, '[wovax-idx-listing-details]');
    if($id_post !== FALSE) {
        global $wpdb;
        $table = $wpdb->posts;
        $wpdb->query("UPDATE $table SET post_content = '$blocks' WHERE ID = $id_post");
    }
    echo json_encode(array(
        'id_post' => $id_post
    ));
    die();
}

/* function to retrieve all rows in the table and the pagination */
function wovax_idx_get_result_api() {
  if (!isset($_POST['attr'])) {
    echo json_encode(array());
    die();
  }

  $orderby = filter_var($_POST['attr']['orderby'], FILTER_SANITIZE_STRING);
  $ordername = filter_var($_POST['attr']['ordername'], FILTER_SANITIZE_STRING);
  $s = filter_var($_POST['attr']['s'], FILTER_SANITIZE_STRING);
  $paged = filter_var($_POST['attr']['paged'], FILTER_SANITIZE_STRING);
  $filter = filter_var($_POST['attr']['filter'], FILTER_SANITIZE_STRING);

  $per_page = get_option('wovax-idx-feeds-per-page');
  $result = wovax_idx_feed_request_data($per_page, $paged, $orderby, $ordername, $s, $filter);
  $result = json_decode($result);
  if ($result->count_total && $result->data && !empty($result->data) && !empty($result->count_total)) {

    $total = $result->count_total;
    $pag_pages = ceil($total / $per_page);
    $table_html = '';
    $paginate_html = '';

    $lista_option = array();
    $lista_complete = wovax_idx_merge_list_feeds($lista_option, $result->data);
    update_option('wovax_idx_feeds_list', json_encode($lista_complete));

    $user = get_current_user_id();
    $feed_meta = get_user_meta( $user, 'managewovax-idx_page_wovax_idx_feedscolumnshidden', true );
    $feed_meta = ( is_array($feed_meta) )?$feed_meta:array();

    $columns_count = 7 - ( count($feed_meta) / 2 );

    $resource_hidden    = in_array('resource', $feed_meta)?' hidden':'';
    $feed_hidden        = in_array('feed', $feed_meta)?' hidden':'';
    $board_hidden       = in_array('board', $feed_meta)?' hidden':'';
    $environment_hidden = in_array('environment', $feed_meta)?' hidden':'';
    $status_hidden      = in_array('status', $feed_meta)?' hidden':'';
    $updated_hidden     = in_array('updated', $feed_meta)?' hidden':'';
    // TABLE HTML
    foreach($result->data as $key => $item) {


      $item = ( object )$item;
      $status = ($item->status == '1') ? 'Active' : 'Inactive';
      $table_html.= '
				<tr>
					<td class="title column-title has-row-actions column-primary" data-colname="Class">
			          <strong><a class="row-title" href="' . esc_url('admin.php?page=wovax_idx_feeds&tab=general&action=update&idfeed=' . $item->class_id . '') . '" title="Edit Residential" >' . esc_html($item->class_visible_name) . '</a></strong>
			          <div class="row-actions"><span class="edit"><a href="' . esc_url('admin.php?page=wovax_idx_feeds&tab=general&action=update&idfeed=' . $item->class_id . '') . '" >Edit</a></span></div>
			          <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
			        </td>
			        <td class="resource column-resource' . esc_attr($resource_hidden) . '" data-colname="Resource">
			          ' . esc_html($item->resource) . '
			        </td>
			        <td class="feed column-feed' . esc_attr($feed_hidden) . '" data-colname="Feed">
			         ' . esc_html($item->feed_description) . '
			        </td>
			        <td class="board column-board' . esc_attr($board_hidden) . '" data-colname="Board">
			          ' . esc_html($item->board_acronym) . '
			        </td>
			        <td class="environment column-environment' . esc_attr($environment_hidden) . '" data-colname="Environment">
			          ' . esc_html($item->environment) . '
			        </td>
			        <td class="status column-status' . esc_attr($status_hidden) . '" data-colname="Status">
			          ' . esc_html($status) . '
			        </td>
			        <td class="updated column-updated' . esc_attr($updated_hidden) . '" data-colname="Updated">
			          <abbr title="' . esc_attr($item->updated) . '">' . esc_html($item->updated) . '</abbr>
			        </td>
			      </tr>
			      ';
    }

    // PAGINATION
    if ($paged < $pag_pages && $paged == '1') {
      $wovax_idx_pag_prev = '<span class="tablenav-pages-navspan" aria-hidden="true">«</span>
								   <span class="tablenav-pages-navspan" aria-hidden="true">‹</span>';
      $wovax_idx_pag_next = '<a href="#" id="wovax-idx-feeds-next-search" class="next-page"><span class="screen-reader-text" >Next page</span><span aria-hidden="true">›</span></a>
								   <a class="last-page" href="#" id="wovax-idx-feeds-last-search"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>';
    }
    elseif ($paged < $pag_pages) {
      $wovax_idx_pag_prev = '<a class="first-page" href="#" id="wovax-idx-feeds-first-search"><span class="screen-reader-text">First page</span><span aria-hidden="true">«</span></a>
									<a class="prev-page" href="#" id="wovax-idx-feeds-prev-search"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>';
      $wovax_idx_pag_next = '<a href="#" id="wovax-idx-feeds-next-search" class="next-page"><span class="screen-reader-text" >Next page</span><span aria-hidden="true">›</span></a>
								   <a class="last-page" href="#" id="wovax-idx-feeds-last-search"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>';
    }
    else {
      $wovax_idx_pag_prev = '<a class="first-page" href="#" id="wovax-idx-feeds-first-search"><span class="screen-reader-text">First page</span><span aria-hidden="true">«</span></a>
								    <a class="prev-page" href="#" id="wovax-idx-feeds-prev-search"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>';
      $wovax_idx_pag_next = '<span class="tablenav-pages-navspan" aria-hidden="true">›</span>
									<span class="tablenav-pages-navspan" aria-hidden="true">»</span>';
    }

    if ($total <= $per_page) {
      $paginate_html.= '
								<div class="tablenav-pages">
									<span class="displaying-num">' . esc_html($total) . ' items</span>
								</div>';
    }
    else {
      $paginate_html.= '
								<div class="tablenav-pages">
									<span class="displaying-num">' . esc_html($total) . ' items</span>
										<span class="pagination-links">
											' . $wovax_idx_pag_prev . '
											<span class="paging-input">
												<label for="wovax-idx-current-feeds-page-selector" class="screen-reader-text">Actual page</label>
												<input class="current-page" id="wovax-idx-current-feeds-page-selector" type="text" name="paged" value="' . esc_attr($paged) . '" size="1" aria-describedby="table-paging">
												<span class="tablenav-paging-text"> of <span class="total-pages" id="wovax-idx-feeds-pag-pages">' . esc_html($pag_pages) . '</span></span>
											</span>
											 	' . $wovax_idx_pag_next . '
										</span>
								</div>';
    }

    echo json_encode(array(
      'table_html' => $table_html,
      'paginate_html' => $paginate_html
    ));
    die();
  }
  else {
    $table_html.= '<tr><td class="colspanchange" colspan="' . esc_html($columns_count) . '">No feeds found.</td></tr>';
  }

  echo json_encode(array(
    'table_html' => $table_html
  ));
  die();
}

function wovax_idx_get_infobox_marker() {
    $list_id  = filter_var( $_POST['id'], FILTER_SANITIZE_STRING);
    $class_id = filter_var( $_POST['class'], FILTER_SANITIZE_STRING);
    $user_fav_properties = filter_var_array( $_POST['prop'], FILTER_SANITIZE_STRING);
    $fav_avail = filter_var( $_POST['fav_avail'], FILTER_SANITIZE_STRING);

    $form_curren_id = json_decode(wovax_idx_get_feed_attr_by_id_feed($class_id)[0]->attributes);
    $format = $form_curren_id->format;
    $currency = $form_curren_id->currency;
    $idx_api    = WovaxConnect::createFromOptions();
    $fields     = new FeedDisplay($class_id);
    $rest       = $idx_api->getListingDetails(
        $class_id,
        $list_id,
        $fields->getRequiredFields(),
        'single'
    );
    $rest = (object) $rest;
    $price = ($format === 'entire'?number_format($rest->Price, 0, '', '') : ($format === 'miles'?number_format($rest->Price, 0, '', ',') : ($format === 'decimals'?number_format($rest->Price, 2, '.', '') : number_format($rest->Price, 2, '.', ',') )));
    $price = $currency === 'right'?$price . "$" : "$" . $price;

    $photo_source = '';
    $photo_alt    = '';
    if(count($rest->photos_list) > 0) {
        $photo_source = $rest->photos_list[0]['location'];
        if(strlen($rest->photos_list[0]['description']) > 0) {
            $photo_alt = $rest->photos_list[0]['description'];
        }
    }
    $zip_code = 'Zip Code';
    $sq_ft = "Square Footage";
    $ac_lt = "Lot Size";
    $street = 'Street Address';
    $mls = 'MLS Number';
    // Get logo
    $item_arr = (array)$rest;
    $mls_logo = "https://cache.wovax.com/images/mls-board-logo.jpg";
    if(array_key_exists('MLS Logo URL', $item_arr) && filter_var($item_arr['MLS Logo URL'], FILTER_VALIDATE_URL)) {
        $mls_logo = $item_arr['MLS Logo URL'];
    }
    if(!empty($rest->Bedrooms) || !empty($rest->Bathrooms)) {
      if(!empty($rest->Bedrooms)) {
        $bedrooms = $rest->Bedrooms . ' Bed';
        if($rest->Bedrooms > 1) {
          $bedrooms .= 's';
        }
      } else {
        $bedrooms = '';
      }
      if(!empty($rest->Bathrooms)) {
        $bathrooms = floatval(number_format($rest->Bathrooms, 2)) . ' Bath';
        if(floatval(floatval(number_format($rest->Bathrooms, 2))) > 1) {
          $bathrooms .= 's';
        }
      } else {
        $bathrooms = '';
      }
      if(!empty($bedrooms) && !empty($bathrooms)) {
        $rooms = $bedrooms . ' · ' . $bathrooms . '<br />';
      } else {
        $rooms = $bedrooms . $bathrooms . '<br />';
      }
    } else {
      $rooms = '';
    }
    if(!empty($rest->$sq_ft) || !empty($rest->$ac_lt)) {
      if(!empty($rest->$sq_ft)) {
        $square_footage = number_format($rest->$sq_ft) . ' sqft';
      } else {
        $square_footage = '';
      }
      if(!empty($rest->$ac_lt)) {
        $acreage = floatval(number_format($rest->$ac_lt, 2)) . ' acres';
      } else {
        $acreage = '';
      }
      if(!empty($square_footage) && !empty($acreage)) {
        $lot_details = $square_footage . ' · ' . $acreage;
      } else {
        $lot_details = $square_footage . $acreage;
      }
    } else {
      $lot_details = '';
    }

    if( $fav_avail == 'yes' && is_user_logged_in() ){
      if( in_array(array($class_id, $rest->$mls), $user_fav_properties) ) {
        $checked = ' checked';
        $label_value = '<svg width="32px" height="29px" viewBox="0 0 32 29" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
            <g id="Search-Results---Grid-View" transform="translate(-470.000000, -637.000000)" fill="#FF5C5C" fill-rule="nonzero">
                <g id="Listings" transform="translate(199.000000, 419.000000)">
                    <g id="Listing" transform="translate(0.665296, 0.000000)">
                        <g id="Favorite" transform="translate(270.334704, 218.000000)">
                            <path d="M31.1790033,5.0756545 C30.1901794,2.84299593 28.2900863,1.12841284 26.0758963,0.400640156 C23.8966058,-0.318909105 21.4730178,-0.0516479513 19.4721034,1.10785429 C18.1148941,1.89730262 17.0717818,3.14726248 15.9937698,4.29854129 C15.7378389,4.02716843 15.481908,3.75579557 15.2259771,3.4844227 C14.7606482,2.99101749 14.3069525,2.46883032 13.7950907,2.02887734 C11.7011106,0.227948333 8.88974839,-0.434036987 6.27227325,0.285512274 C3.80990774,0.963944434 1.69653893,2.85944277 0.692204024,5.34291565 C-0.238453804,7.64547329 -0.226820581,10.2399623 0.703837247,12.5384083 C1.19631035,13.7595861 1.96410305,14.7710668 2.84047251,15.7003133 L4.88791973,17.8712962 C5.9271543,18.9732345 6.96638888,20.0710611 8.00174571,21.1729994 C9.09139091,22.3283899 10.1810361,23.4837804 11.2706813,24.639171 C12.1043956,25.5231886 12.9419877,26.411318 13.7795797,27.2994474 C14.2720528,27.8216346 14.7994256,28.5411838 15.5012967,28.7344342 C16.1605127,28.9153494 16.8468728,28.7138756 17.3393459,28.2286939 C17.3471014,28.2204704 17.3548569,28.212247 17.3626124,28.2040236 C17.3703678,28.1958002 17.3781233,28.1875768 17.3820011,28.183465 C17.7309977,27.8134111 18.0799944,27.4433572 18.4251134,27.077415 C19.2937273,26.156392 20.1623413,25.2353689 21.0309553,24.3143459 C22.1206005,23.1589553 23.206368,22.0076765 24.2960132,20.852286 C25.3081036,19.7791297 26.3201939,18.7059734 27.3361621,17.6287053 L29.2711548,15.576962 C29.3797315,15.4618341 29.492186,15.3467062 29.6007628,15.2274666 C30.4267216,14.3352256 31.0549156,13.2702927 31.4698339,12.102567 C32.2802818,9.83290304 32.1445608,7.25897254 31.1790033,5.0756545 Z" id="filled"></path>
                        </g>
                    </g>
                </g>
            </g>
        </g>
    </svg>';
      } else {
        $checked = '';
        $label_value = '<svg width="32px" height="29px" viewBox="0 0 32 29" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
            <g id="Search-Results---Grid-View" transform="translate(-470.000000, -637.000000)" fill="#FF5C5C" fill-rule="nonzero">
                <g id="Listings" transform="translate(199.000000, 419.000000)">
                    <g id="Listing" transform="translate(0.665296, 0.000000)">
                        <g id="Favorite" transform="translate(270.334704, 218.000000)">
                            <path d="M16.2560915,26.695487 C16.2333822,26.7194748 16.1879636,26.7514585 16.1728241,26.7754463 C16.1728241,26.7754463 16.3393588,26.6595054 16.2560915,26.7074809 C16.206888,26.7354667 16.1387602,26.7714483 16.0933417,26.80743 C16.2295973,26.695487 16.2598763,26.7434626 16.1728241,26.7714483 C16.1387602,26.7794443 16.1084812,26.7954361 16.0744173,26.803432 C16.0479232,26.811428 16.021429,26.8154259 15.9987197,26.8234219 C15.8889583,26.8554056 16.2333822,26.8154259 16.0706324,26.811428 C16.0100744,26.811428 15.9495163,26.811428 15.8889583,26.80743 C15.7678422,26.80743 16.1009115,26.8554056 15.9873651,26.8194239 C15.960871,26.811428 15.9343768,26.80743 15.9116675,26.7994341 C15.8700339,26.7874402 15.6732202,26.7154768 15.8284002,26.7754463 C15.9684407,26.8314178 15.8586793,26.7834422 15.8208305,26.7634524 C15.7867666,26.7434626 15.7451329,26.703483 15.7072841,26.6874911 C15.711069,26.6874911 15.8548944,26.8274198 15.7905514,26.7554565 C15.8094758,26.7794443 15.6656505,26.6315196 15.7299934,26.695487 C15.7299934,26.695487 15.7299934,26.695487 15.7299934,26.695487 C15.7262085,26.6914891 15.7224236,26.6874911 15.7186388,26.6834931 C15.7034992,26.6675013 15.6883597,26.6515094 15.6732202,26.6355176 C15.5104705,26.4636052 15.3477207,26.2916927 15.1849709,26.1197803 C14.469629,25.3641652 13.754287,24.6085501 13.0389451,23.852935 C12.0321675,22.7894768 11.0291748,21.7300164 10.0223972,20.6665582 C8.99669525,19.5831101 7.97477816,18.5036599 6.9490762,17.4202119 C6.18074594,16.6086253 5.40863081,15.7930407 4.64030055,14.9814542 C4.43591714,14.7655641 4.23153372,14.5496741 4.0271503,14.3337841 C3.98551664,14.2898065 3.94388298,14.2458289 3.90224932,14.2018513 C3.67515664,13.9619735 3.48969761,13.6781181 3.26260493,13.4422382 C3.27395956,13.4542321 3.40264542,13.6381384 3.31180834,13.5062057 C3.29666883,13.4822179 3.27774444,13.4582301 3.26260493,13.4382403 C3.22475614,13.3822688 3.18690736,13.3302953 3.15284346,13.2743238 C3.06957614,13.1503869 2.9900937,13.0224521 2.91439614,12.8905193 C2.77435565,12.6506415 2.64566979,12.4027677 2.52833857,12.1468981 C2.50184443,12.0869286 2.47535028,12.0269592 2.44885613,11.9669897 C2.41857711,11.8990244 2.34287955,11.6871323 2.44885613,11.9749857 C2.39965272,11.8430529 2.3504493,11.715118 2.30503077,11.5831852 C2.21797857,11.3193197 2.13849613,11.0554541 2.07793808,10.7835926 C2.01359515,10.515729 1.96817661,10.2438675 1.92654296,9.96800801 C1.91140344,9.85606504 1.94546735,10.1119347 1.94168247,10.0959428 C1.93789759,10.0599612 1.93411271,10.0239795 1.93032783,9.98799783 C1.92275808,9.92403042 1.9189732,9.860063 1.91140344,9.79609559 C1.90004881,9.66016483 1.89247905,9.52023611 1.88869417,9.38430535 C1.87733954,9.10844588 1.88112442,8.8325864 1.90004881,8.55672692 C1.90761856,8.42879209 1.9189732,8.30085726 1.93032783,8.17292243 C1.93411271,8.13694076 1.94168247,8.10095909 1.94168247,8.06497742 C1.94168247,8.07297335 1.90383369,8.32884301 1.93032783,8.1649265 C1.94168247,8.09296316 1.9530371,8.02099982 1.96439174,7.94503852 C2.05522881,7.39731752 2.1952693,6.86159042 2.39208296,6.34585314 C2.4034376,6.32186536 2.41100735,6.29387962 2.42236199,6.26989184 C2.46778052,6.1539509 2.33909467,6.46979001 2.38829808,6.35384907 C2.41479223,6.29387962 2.4375015,6.23391016 2.46399565,6.17394071 C2.52076882,6.04600588 2.57754199,5.91807105 2.64188492,5.79413419 C2.76678589,5.54226249 2.90682638,5.29838672 3.05822151,5.06250688 C3.13013419,4.9505639 3.20583175,4.84261889 3.27774444,4.73467388 C3.34965712,4.63072683 3.15284346,4.89859038 3.22854102,4.79864129 C3.24746541,4.77465351 3.26260493,4.75466369 3.28152932,4.73067591 C3.33451761,4.66271053 3.3875059,4.59474516 3.4404942,4.53077774 C3.62595322,4.30289383 3.82276688,4.08300584 4.03093518,3.8791097 C4.14448152,3.76716672 4.26559762,3.65522375 4.38671372,3.5512767 C4.40942299,3.53128688 4.43591714,3.51129706 4.45862641,3.48730928 C4.58731226,3.37536631 4.4245625,3.5072991 4.41320787,3.52329096 C4.45862641,3.4633215 4.54946348,3.41534594 4.61380641,3.36737038 C4.86360836,3.1794661 5.12476495,3.00755367 5.3934913,2.8516331 C5.51839227,2.77966976 5.64329325,2.71170438 5.77197911,2.64773696 C5.83632204,2.61575326 5.90066496,2.58776751 5.96500789,2.5557838 C6.07855423,2.49981232 5.7909035,2.63174511 5.90444984,2.57977158 C5.94986838,2.55978177 5.99907179,2.53979195 6.04449033,2.51980213 C6.32457131,2.40386119 6.61222205,2.30791007 6.90365766,2.2279508 C7.18373864,2.1519895 7.4676045,2.09601801 7.75525523,2.04804245 C7.88015621,2.02805263 7.55465669,2.07203023 7.67955767,2.06003634 C7.7098367,2.05603837 7.74011572,2.05204041 7.76660987,2.04804245 C7.84609231,2.04004652 7.92557475,2.03205059 8.00505719,2.02405467 C8.15266743,2.01206078 8.30406256,2.00406485 8.4516728,2.00006689 C8.75067817,1.99207096 9.05346842,2.00806281 9.35247379,2.03604856 C9.41681672,2.04404448 9.50008404,2.07203023 9.56064209,2.06003634 C9.56442697,2.06003634 9.35247379,2.02805263 9.45845038,2.04404448 C9.50008404,2.05204041 9.53793282,2.05603837 9.57956648,2.0640343 C9.72717673,2.08802208 9.87857185,2.12000579 10.0261821,2.1519895 C10.3214026,2.21995488 10.6166231,2.30791007 10.9004889,2.41585508 C11.0329597,2.46383064 11.1654304,2.52779806 11.2979011,2.57577362 C11.0405294,2.48382046 11.2941163,2.57577362 11.3508894,2.60375936 C11.426587,2.63974104 11.4984997,2.67572271 11.5741972,2.71570234 C11.8504933,2.86362699 12.1192197,3.02754349 12.3765914,3.20745184 C12.4977075,3.29140908 12.6150387,3.38336223 12.7361548,3.47531539 C12.7891431,3.51529503 12.8307768,3.59125633 12.6945212,3.43933372 C12.7172304,3.4633215 12.7437246,3.48331132 12.7702187,3.50330114 C12.8459163,3.56726855 12.917829,3.63123597 12.9897416,3.69920135 C13.1865553,3.88310766 13.3720143,4.07900787 13.5612582,4.27490808 C14.034368,4.77465351 14.5112626,5.2783969 14.9843724,5.77814233 C15.0979188,5.89808124 15.2076802,6.01402218 15.3212266,6.13396108 C15.68079,6.51376761 16.2977251,6.51776557 16.6610734,6.13396108 C17.0282066,5.74615863 17.3915549,5.36235414 17.7586881,4.97455168 C18.1598851,4.55076756 18.5497276,4.10699362 18.9774188,3.71119524 C19.0796105,3.61524411 19.1855871,3.52728892 19.2915637,3.43533576 C19.3710461,3.36737038 19.2007266,3.51129706 19.2082964,3.50330114 C19.2385754,3.48331132 19.2650695,3.45532558 19.2953486,3.43533576 C19.3521217,3.39135816 19.4126798,3.34738056 19.469453,3.30340297 C19.6889759,3.14348443 19.9160686,2.99555978 20.150731,2.85962902 C20.2604925,2.79566161 20.370254,2.73569216 20.4800154,2.67972067 C20.5443583,2.64773696 20.6087013,2.61575326 20.6692593,2.58376955 C20.6919686,2.57177566 20.7184627,2.55978177 20.741172,2.55178584 C20.8812125,2.48781843 20.7449569,2.5557838 20.6843988,2.57577362 C20.9342008,2.49581435 21.1726481,2.37987341 21.4262349,2.29991414 C21.6760369,2.21995488 21.9296237,2.15598746 22.1869954,2.10401394 C22.3043267,2.08002615 22.4254428,2.06803226 22.5465589,2.04004652 C22.5389891,2.04004652 22.292972,2.07203023 22.448152,2.05603837 C22.4746462,2.05204041 22.5011403,2.04804245 22.5238496,2.04804245 C22.5919774,2.04004652 22.6601052,2.03205059 22.7320179,2.02805263 C23.2505462,1.98407503 23.7690745,1.99207096 24.2838179,2.05603837 C24.4919862,2.08002615 24.0983589,2.02405467 24.2648935,2.05204041 C24.3254516,2.0640343 24.3860096,2.07203023 24.4427828,2.08402412 C24.5790384,2.1080119 24.715294,2.13599764 24.8515496,2.17197931 C25.0975667,2.23194877 25.3397989,2.30391211 25.5782462,2.38786934 C25.7031472,2.43184694 25.8242633,2.4798225 25.9491643,2.52779806 C25.9718735,2.53979195 25.9983677,2.54778788 26.021077,2.55978177 C26.1649023,2.61975122 25.9491643,2.52779806 25.9415945,2.5238001 C26.0059374,2.5557838 26.0702804,2.58376955 26.1346233,2.61575326 C26.3768555,2.73169419 26.6115179,2.86362699 26.8386106,3.00755367 C27.0694882,3.15148036 27.2890111,3.31539686 27.5085341,3.47931336 C27.6410048,3.57926244 27.3495692,3.3433826 27.5085341,3.47931336 C27.5577375,3.51929299 27.6069409,3.56327059 27.6561443,3.60724819 C27.7696906,3.70719727 27.8794521,3.81114432 27.9854287,3.91509137 C28.1860272,4.11498954 28.3752711,4.32688161 28.5569453,4.54676959 C28.5985789,4.59874312 28.6402126,4.65071664 28.6780614,4.70269017 C28.719695,4.75866166 28.8408111,4.94656594 28.6818463,4.70269017 C28.7575438,4.81863111 28.8408111,4.93057408 28.9165087,5.05051299 C29.0679038,5.28239487 29.2041594,5.52627064 29.3290604,5.77414437 C29.3858336,5.89008531 29.4426068,6.00602625 29.4955951,6.12196719 C29.5220892,6.18193664 29.5485833,6.23790813 29.5712926,6.29787758 C29.5864321,6.32986129 29.5977868,6.36584296 29.6129263,6.39782667 C29.5826473,6.33385925 29.529659,6.18993257 29.5864321,6.33785721 C29.775676,6.84160061 29.9195014,7.36533382 30.0103385,7.89706295 C30.0216931,7.9690263 30.0330477,8.04098964 30.0444024,8.11295298 C30.0481873,8.14093872 30.0519721,8.16892447 30.055757,8.19291225 C30.0746814,8.32084708 30.0292629,7.96103037 30.0444024,8.0889652 C30.0595419,8.22489596 30.0746814,8.36082671 30.0822512,8.49675747 C30.1011756,8.76861898 30.1087453,9.04447846 30.1011756,9.31633997 C30.0936058,9.59219945 30.0595419,9.860063 30.0406175,10.1359225 C30.0406175,10.1279266 30.0784663,9.86805893 30.0519721,10.0319754 C30.0481873,10.0599612 30.0444024,10.0879469 30.0406175,10.1119347 C30.0330477,10.1759021 30.0216931,10.2398695 30.0103385,10.299839 C29.9838443,10.4437657 29.9573502,10.5876923 29.9232863,10.731619 C29.8627282,10.9994826 29.7908155,11.2633482 29.7037634,11.5232158 C29.6621297,11.6471527 29.620496,11.7670916 29.5750775,11.8870305 C29.5145194,12.046949 29.6696994,11.6591465 29.5788624,11.8790345 C29.5523682,11.9469999 29.5220892,12.0149653 29.4918102,12.0829307 C29.3782638,12.3348024 29.2533629,12.5826761 29.1133224,12.8225539 C29.0489794,12.9344969 28.9808516,13.0464399 28.908939,13.1543849 C28.874875,13.2103564 28.8370263,13.2623299 28.8029624,13.3183014 C28.7802531,13.3502851 28.7613287,13.3782708 28.7386194,13.4102545 C28.7159102,13.4422382 28.6175033,13.578169 28.7159102,13.4462362 C28.8105321,13.3143034 28.6818463,13.4902138 28.6553521,13.5261955 C28.6137185,13.578169 28.5758697,13.6301425 28.534236,13.682116 C28.4396141,13.798057 28.3412072,13.9139979 28.2390155,14.0259409 C28.2049516,14.0659205 28.1671028,14.1059002 28.1292541,14.1418818 C28.0876204,14.1858594 28.0459867,14.229837 28.0043531,14.2738146 C27.4669004,14.8415254 26.9332326,15.4052383 26.3957799,15.9729491 C25.4646999,16.9564481 24.5336199,17.9399471 23.598755,18.9274441 C22.5503437,20.0348799 21.5019325,21.1423158 20.4535213,22.2497517 C19.5527203,23.201267 18.6481344,24.1567802 17.7473334,25.1082955 C17.2704388,25.6120389 16.7935441,26.1157823 16.3166495,26.6195257 C16.30151,26.6515094 16.2788007,26.6714992 16.2560915,26.695487 C15.9040978,27.0672976 15.8813885,27.7429534 16.2560915,28.1107661 C16.6345793,28.4785787 17.2212354,28.5065645 17.5959383,28.1107661 C17.9441471,27.7429534 18.288571,27.3791388 18.6367798,27.0113261 C19.4845925,26.1157823 20.3324052,25.2202385 21.1802179,24.3246947 C22.2437686,23.201267 23.3035345,22.0818372 24.3670852,20.9584095 C25.3549384,19.914941 26.3427916,18.8714726 27.3344297,17.8240062 C27.9627194,17.1603442 28.5947941,16.4926843 29.2230838,15.8290224 C29.3290604,15.7170794 29.4388219,15.6051365 29.5447985,15.4891955 C30.396396,14.5696639 31.0360404,13.4742219 31.4675165,12.2748329 C32.2964049,9.96800801 32.1336551,7.35733789 31.1458019,5.13447022 C30.1390243,2.87162292 28.1898121,1.14450271 25.9378096,0.404879475 C23.7160862,-0.326747834 21.236991,-0.046890393 19.2007266,1.13250882 C17.823031,1.92810354 16.7708349,3.19545795 15.6732202,4.35486735 C15.5596739,4.47480625 15.4423427,4.59874312 15.3287963,4.71868202 C15.7754119,4.71868202 16.2220275,4.71868202 16.6686432,4.71868202 C16.2863705,4.31488772 15.9040978,3.91109341 15.5180402,3.50330114 C15.0638549,3.02354553 14.6210241,2.51580417 14.1214202,2.08802208 C13.0427299,1.16449253 11.7861504,0.492834671 10.4235943,0.196985377 C9.09888696,-0.0908679908 7.72497621,-0.0668802102 6.41162351,0.292936499 C3.92495859,0.976588247 1.75622344,2.89161273 0.738091231,5.39433784 C0.26119659,6.57373706 -0.00374487715,7.82110165 0,9.10844588 C0.00382487906,10.3877942 0.272551224,11.6271628 0.749445865,12.7985661 C1.24148002,14.013947 2.01738003,15.0254318 2.89168687,15.9529593 C3.55782542,16.6566008 4.22396396,17.3602424 4.89010251,18.063884 C5.90444984,19.1353382 6.91879717,20.2067924 7.92935963,21.2742486 C8.99291037,22.3976763 10.0564611,23.5211041 11.1200119,24.6445318 C11.9337607,25.5040939 12.7512943,26.367654 13.5650431,27.2272162 C14.1024958,27.794927 14.6475183,28.5145604 15.4120636,28.7224545 C16.1993183,28.9383445 17.016852,28.6944687 17.5997232,28.1027702 C17.9592866,27.7389555 17.9706412,27.0473078 17.5997232,26.6874911 C17.2098807,26.3236764 16.6383641,26.3076846 16.2560915,26.695487 Z" id="empty"></path>
                        </g>
                    </g>
                </g>
            </g>
        </g>
    </svg>';
      }
      ob_start()
      ?>
        <div class="wovax-idx-listing-favorite-container" id="<?php echo esc_attr($rest->id); ?>">
          <div class="wovax-idx-listing-favorite">
            <input type="hidden" class="<?php echo esc_attr($list_id) . '_feed'; ?>" name="feed-id" value="<?php echo esc_attr($class_id); ?>">
            <input type="hidden" class="<?php echo esc_attr($list_id) . '_mls'; ?>" name="feed-id" value="<?php echo esc_attr($rest->$mls); ?>">
            <input class="toggle-heart" id="<?php echo esc_attr($rest->id) . '_heart'; ?>" type="checkbox" <?php echo $checked; ?> onclick="wovax_idx_save_favorite_click(this)">
            <label id="toggle-heart-section" for="<?php echo esc_attr($rest->id) . '_heart'; ?>" onclick="wovax_idx_change_heart(this)"><?php echo $label_value; ?></label>
          </div>
        </div>
      <?php
      $favorite_html = ob_get_clean();
    } else if ( $fav_avail == 'yes' && !is_user_logged_in() ) {
      ob_start()
      ?>
        <div class="wovax-idx-listing-favorite-container" id="<?php echo esc_attr($rest->id); ?>">
          <div class="wovax-idx-listing-favorite">
            <input type="hidden" class="<?php echo esc_attr($list_id) . '_feed'; ?>" name="feed-id" value="<?php echo esc_attr($class_id); ?>">
            <input type="hidden" class="<?php echo esc_attr($list_id) . '_mls'; ?>" name="feed-id" value="<?php echo esc_attr($rest->$mls); ?>">
            <input class="toggle-heart" id="<?php echo esc_attr($rest->id) . '_heart'; ?>" type="checkbox" onclick="wovax_idx_save_favorite_click(this)">
            <div href="#wovax-idx-login-registration-modal" onclick="wovax_idx_open_modal(this)">
              <label id="toggle-heart-section" for="<?php echo esc_attr($rest->id) . '_heart'; ?>" onclick="wovax_idx_change_heart(this)"><?php echo $label_value; ?></label>
            </div>
          </div>
        </div>
      <?php
      $favorite_html = ob_get_clean();
    } else {
      $favorite_html = '';
    }
    ob_start();
    ?>
    <div class="wovax-idx-section">
      <div class="wovax-idx-listing-container">
        <div class="wovax-idx-listing">
            <a href="<?php echo ListingLoader::buildURL($class_id, $list_id, $rest); ?>">
              <div class="wovax-idx-listing-image">
                <img class="wovax-idx-listing-featured-image" src="<?php echo esc_url('https://cache.wovax.com/image/?src=' . $photo_source . '&width=480&height=300&crop-to-fit&aro'); ?>" <?php echo $photo_alt; ?> >
                <div class="wovax-idx-listing-status"><?php echo esc_html($rest->Status); ?></div>
              </div>
            </a>
            <a href="<?php echo ListingLoader::buildURL($class_id, $list_id, $rest); ?>">
              <div class="wovax-idx-listing-content">
                <div class="wovax-idx-listing-title">
                  <?php echo esc_html($rest->$street); ?>
                  <div class="wovax-idx-listing-subtitle">
                    <?php echo esc_html($rest->City) . ' ' . esc_html($rest->State) . ' ' . esc_html($rest->$zip_code); ?>
                  </div>
                </div>
                <?php echo $favorite_html; ?>
                <div class="wovax-idx-listing-details"><?php echo $rooms . $lot_details; ?></div>
                <div class="wovax-idx-listing-price"><?php echo esc_html($price); ?></div>
                <div class="wovax-idx-listing-mls-logo"><img src="https://cache.wovax.com/image/?src=<?php echo $mls_logo; ?>"></div>
              </div>
            </a>
          </div>
        </div>
      </div>
    <?php
    $output .= ob_get_clean();
    echo $output;
    die();
}

/* function to retrieve all fields into the wovax_IDX_detail form */
function wovax_idx_get_result_api_feed_details() {
  if (!isset($_POST['attr'])) {
    echo json_encode(array());
    die();
  }

  $id_feed = filter_var($_POST['attr']['idfeed'], FILTER_SANITIZE_STRING);
  $api_data = wovax_idx_api_data_feed_details($id_feed);

  // $result  	 = json_decode( $api_data );
  $all = '';
  $default = '';
  $values_array = array('City', 'State', 'Zip Code', 'Bedrooms', 'Bathrooms', 'Price', 'Description');
  $list_result = wovax_idx_list_feeds_field_by_id($id_feed);

  if ($api_data[0][0] == 'No data found') {
    $all.= '';
    $default.= '';
  }
  else {
    $all.='<li>
              <label class="menu-item-title">
                <input type="checkbox" class="menu-item-checkbox" name="wovax_idx_map_option" value="Map"> Map
              </label>
              <input type="hidden" name="wovax_idx_feed_id" value="' . esc_attr($id_feed) . '" >
              <input type="hidden" name="wovax_idx_map_state" value="1" >
              <input type="hidden" name="wovax_idx_map_order" value="0" >
              <input type="hidden" name="wovax_idx_map_title" value="Map" >
            </li>
            <li>
              <label class="menu-item-title">
                <input type="checkbox" class="menu-item-checkbox" name="wovax_idx_map_option" value="URL"> Virtual Tour URL
              </label>
              <input type="hidden" name="wovax_idx_feed_id" value="' . esc_attr($id_feed) . '" >
              <input type="hidden" name="wovax_idx_link_state" value="1" >
              <input type="hidden" name="wovax_idx_link_order" value="0" >
              <input type="hidden" name="wovax_idx_link_title" value="Virtual Tour URL" >
            </li>';
    foreach($api_data[0] as $key => $item) {
      $new_value = (!empty($item->field_alias)) ? $item->field_alias : $item->default_alias;
      $old_value = (!empty($item->field_alias)) ? $item->field_alias : $item->default_alias;
      $label_val = (empty($item->default_alias)) ? $item->field_alias : $item->default_alias;
      if ($object = wovax_idx_find_by_name_feed($list_result, $item->id)) {
        if (!empty($object->alias_update)) {
          $new_value = $object->alias_update;
        }
      }
      $txt_value = ($new_value == $item->field_alias || $new_value == $item->default_alias) ? '' : $new_value;

      $container .='<input type="hidden" name="wovax_idx_old_field_' . esc_attr($item->id) . '"  value="' . esc_attr($old_value) . '" >
                    <input type="hidden" name="wovax_idx_id_field_' . esc_attr($item->id) . '" value="' . esc_attr($item->id) . '" >
                    <input type="hidden" name="wovax_idx_field_name_' . esc_attr($item->id) . '" value="' . esc_attr($item->mls_field_name) . '" >
                    <input type="hidden" name="wovax_idx_status_alias_' . esc_attr($item->id) . '" value="' . esc_attr($item->status_alias) . '" >
                    <input type="hidden" name="wovax_idx_default_alias_' . esc_attr($item->id) . '" value="' . esc_attr($item->default_alias) . '" >
                    <input type="hidden" name="wovax_idx_field_alias_' . esc_attr($item->id) . '" value="' . esc_attr($item->field_alias) . '" >';

      $all.='<li>
                  <label class="menu-item-title">
                    <input type="checkbox" class="menu-item-checkbox" name="wovax_idx_id_field_' . esc_attr($item->id) . '" value="' . esc_attr($item->mls_field_name) . '"> ' . esc_html($label_val) . '
                  </label>
                  <input type="hidden" name="wovax_idx_id"  value="' . esc_attr($item->id) . '" >
                  <input type="hidden" name="wovax_idx_old_field"  value="' . esc_attr($old_value) . '" >
                  <input type="hidden" name="wovax_idx_field_name" value="' . esc_attr($item->mls_field_name) . '" >
                </li>';

      if(in_array($label_val, $values_array)){
        $default.='<li>
                  <label class="menu-item-title">
                    <input type="checkbox" class="menu-item-checkbox" name="wovax_idx_id_field_' . esc_attr($item->id) . '" value="' . esc_attr($item->mls_field_name) . '"> ' . esc_html($label_val) . '
                  </label>
                  <input type="hidden" name="wovax_idx_id"  value="' . esc_attr($item->id) . '" >
                  <input type="hidden" name="wovax_idx_old_field"  value="' . esc_attr($old_value) . '" >
                  <input type="hidden" name="wovax_idx_field_name" value="' . esc_attr($item->mls_field_name) . '" >
                </li>';
      }
    }
  }

  echo json_encode(array(
    'all' => $all,
    'default' => $default,
    'container' => $container
  ));
  die();
}

/* function to retrieve all fields into the wovax_IDX_detail form */
function wovax_idx_get_result_api_feed_for_search_content() {
  if (!isset($_POST['attr'])) {
    echo json_encode(array());
    die();
  }

  $id_feed = filter_var($_POST['attr']['idfeed'], FILTER_SANITIZE_STRING);
  $value = filter_var($_POST['attr']['value'], FILTER_SANITIZE_STRING);

  $api_data = wovax_idx_api_data_feed_details($id_feed);

  $search = [];

  $list_result = wovax_idx_list_feeds_field_by_id($id_feed);

  if ($api_data[0][0] == 'No data found') {
    $search = 'No data found';
  }
  else {

    foreach($api_data[0] as $key => $item) {
      $old_value = (!empty($item->field_alias)) ? $item->field_alias : $item->default_alias;
      $label_val = (empty($item->default_alias)) ? $item->field_alias : $item->default_alias;

      if(strpos( strtolower($label_val), strtolower($value))!== false){
        $search[$item->id] = '<li>
                  <label class="menu-item-title">
                    <input type="checkbox" class="menu-item-checkbox" name="wovax_idx_id_field_' . esc_attr($item->id) . '" value="' . esc_attr($item->mls_field_name) . '"> ' . esc_html($label_val) . '
                  </label>
                  <input type="hidden" name="wovax_idx_id"  value="' . esc_attr($item->id) . '" >
                  <input type="hidden" name="wovax_idx_old_field"  value="' . esc_attr($old_value) . '" >
                  <input type="hidden" name="wovax_idx_field_name" value="' . esc_attr($item->mls_field_name) . '" >
                </li>';
      }
    }
  }

  echo json_encode(array(
    'search' => $search
  ));
  die();
}

/* function to order the filters or rules */
function wovax_idx_set_order_section() {
  global $wpdb;
  $order_number = (array)json_decode(stripslashes($_POST['order_json']));
  $order_json = json_encode($order_number);

  if ( filter_var($_POST['option'], FILTER_SANITIZE_STRING) == 'feed_rule' ) {
    $id = filter_var($_POST['id_feed'], FILTER_SANITIZE_STRING);
    $feed_attr_info = wovax_idx_get_feed_attr_by_id_feed($id);
    $feed_attr_object = json_decode($feed_attr_info[0]->attributes);
    $feed_attr_object->feed_rules_order = $order_json;

    $feed_attr_object = json_encode($feed_attr_object);
    $sql = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_feeds` (`id_feed`, `attributes`) VALUES (%d, %s) ON DUPLICATE KEY UPDATE `attributes`= %s;";
    $wpdb->query($wpdb->prepare($sql, array($id, $feed_attr_object, $feed_attr_object)));
    exit;

  }else {

    $id = filter_var($_POST['id_shortcode'], FILTER_SANITIZE_STRING);
    /* save data */
    $sql = "UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode` SET `order_section`= %s WHERE `id` = %d ;";
    $wpdb->query($wpdb->prepare($sql, $order_json, $id));
    exit;

  }
}

/* function to save a favorite property */
function wovax_idx_save_favorite() {
  $fav_array = array();
  $user = wp_get_current_user();
  $propert_json = get_user_meta($user->ID) ['wovax-idx-favorites'][0];
  $new_fav = array( filter_var(stripslashes($_POST['feed_id']) , FILTER_SANITIZE_STRING),
                    filter_var(stripslashes($_POST['mls']) , FILTER_SANITIZE_STRING));
  if ($propert_json != null) {
    $fav_array = (array)json_decode($propert_json);
  }

  if (!in_array($new_fav, $fav_array)) {
    array_push($fav_array, $new_fav);
  }

  $fav_array = json_encode($fav_array);
  update_user_meta($user->ID, 'wovax-idx-favorites', $fav_array);
  echo json_encode(array(
    'success' => 'Success'
  ));
  exit;
}

/* function to delete a favorite property */
function wovax_idx_delete_favorite() {
  $fav_array = array();
  $user = wp_get_current_user();
  $propert_json = get_user_meta($user->ID) ['wovax-idx-favorites'][0];
  $del_fav = array( filter_var(stripslashes($_POST['feed_id']) , FILTER_SANITIZE_STRING),
                    filter_var(stripslashes($_POST['mls']) , FILTER_SANITIZE_STRING));
  $fav_array = (array)json_decode($propert_json);
  $key = array_search($del_fav, $fav_array);
  unset($fav_array[$key]);
  $fav_array = array_values($fav_array);
  $fav_array = json_encode($fav_array);
  update_user_meta($user->ID, 'wovax-idx-favorites', $fav_array);
  echo json_encode(array(
    'success' => 'Success'
  ));
  exit;
}

/* saves a search url and name for later recall */
function wovax_idx_save_search() {
  $current_search_array = array();
  $user = wp_get_current_user();
  $data = $_POST['data'];
  $current_searches = get_user_meta($user->ID, 'wovax-idx-searches');
  $new_search = array( 
    filter_var(stripslashes($data['name']) , FILTER_SANITIZE_STRING),
    filter_var(stripslashes($data['url']) , FILTER_SANITIZE_STRING)
  );

  if ($current_searches != null) {
    $current_search_array = (array)json_decode($current_searches[0]);
  }

  if (!in_array($new_search, $current_search_array)) {
    array_push($current_search_array, $new_search);
  }

  $current_search_array = json_encode($current_search_array);
  update_user_meta($user->ID, 'wovax-idx-searches', $current_search_array);
  echo json_encode(array(
    'success' => 'Success'
  ));
  exit;
}

/* function to delete a saved search */
function wovax_idx_delete_saved_search() {
  $current_search_array = array();
  $user = wp_get_current_user();
  $data = $_POST['data'];
  $current_searches = get_user_meta($user->ID, 'wovax-idx-searches');
  $deleted_search = array( 
    filter_var(stripslashes($data['name']) , FILTER_SANITIZE_STRING),
    filter_var(stripslashes($data['search_url']) , FILTER_SANITIZE_STRING)
  );
  $current_search_array = (array)json_decode($current_searches[0]);
  $key = array_search($deleted_search, $current_search_array);
  unset($current_search_array[$key]);
  $current_search_array = array_values($current_search_array);
  $current_search_array = json_encode($current_search_array);
  update_user_meta($user->ID, 'wovax-idx-searches', $current_search_array);
  echo json_encode(array(
    'success' => 'Success'
  ));
  exit;
}

function wovax_idx_update_profile() {
  $id              = filter_var($_POST['id'], FILTER_SANITIZE_STRING) ? filter_var($_POST['id'], FILTER_SANITIZE_STRING):'';
  $firstname       = filter_var($_POST['firstname'], FILTER_SANITIZE_STRING) ? filter_var($_POST['firstname'], FILTER_SANITIZE_STRING):'';
  $lastname        = filter_var($_POST['lastname'], FILTER_SANITIZE_STRING) ? filter_var($_POST['lastname'], FILTER_SANITIZE_STRING):'';
  $email           = filter_var($_POST['email'], FILTER_SANITIZE_STRING) ? filter_var($_POST['email'], FILTER_SANITIZE_STRING):'';
  $password        = filter_var($_POST['password'], FILTER_SANITIZE_STRING) ? filter_var($_POST['password'], FILTER_SANITIZE_STRING):'';
  $verify_password = filter_var($_POST['verify_password'], FILTER_SANITIZE_STRING) ? filter_var($_POST['verify_password'], FILTER_SANITIZE_STRING):'';
  $phone           = filter_var($_POST['phone'], FILTER_SANITIZE_STRING) ? filter_var($_POST['phone'], FILTER_SANITIZE_STRING):'';
  $username        = filter_var($_POST['username'], FILTER_SANITIZE_STRING) ? filter_var($_POST['username'], FILTER_SANITIZE_STRING):'';

  $update_values = array( 'ID' => $id);
  if($firstname != null && $firstname != ''){$update_values['first_name'] = $firstname;}
  if($lastname != null && $lastname != ''){$update_values['last_name'] = $lastname;}
  if($email != null && $email != ''){$update_values['user_email'] = $email;}
  if($password != null && $password != '' && $verify_password != null && $verify_password != '' && $password == $verify_password){$update_values['user_pass'] = $password;}
  if($username != null && $username != ''){$update_values['user_login'] = $username;}

  if($phone != null && $phone != ''){update_user_meta($id, 'phone', $phone);}

  $update_user = wp_update_user( $update_values );


  if ( is_wp_error( $update_user ) ) {
    $result = 'There was an error';
  } else {
    $result = 'Success!';
  }

  echo json_encode(array(
    'value' => $result
  ));

  exit();
}

function wovax_idx_settings_autocomplete() {
  global $wpdb;
  $sql = "SELECT `ID`,`post_title`,`post_type` FROM `".$wpdb->prefix."posts` WHERE `post_title` LIKE '%".$_POST['data']['term']."%' AND `post_type` LIKE 'page' ";
  $titles = $wpdb->get_results($sql,ARRAY_A);
  $results = array();
  foreach($titles as $title) {
    $results[] = array(
      "label" => $title['post_title'] . ' - ' . ucfirst($title['post_type']),
      "value" => $title['ID']
    );
  }
  echo json_encode($results);
  die();
}