<?php

if (!defined('ABSPATH')) exit; ?>

<?php
add_action('wp_network_dashboard_setup', function() {
    add_screen_option('layout_columns', ['default' => 2]);
});
$user = get_current_user_id();
$feed_meta = get_user_meta( $user, 'managewovax-idx_page_wovax_idx_feedscolumnshidden', true );
$feed_meta = ( is_array($feed_meta) )?$feed_meta:array();  
$columns_count = 7 - ( count($feed_meta) / 2 );

?>
<div class="wrap">
  <h1 class="wp-heading-inline">Wovax IDX Feeds</h1>
  <a href="https://wovax.com/products/idx/add-feed/" target="_blank" class="page-title-action">Add New</a>
  <hr class="wp-header-end">

  <p class="search-box">
    <label class="screen-reader-text" for="wovax-idx-feeds-search-input">Search IDX Feeds</label>
    <input type="search" id="wovax-idx-feeds-search-input" name="s" value="">
    <input type="submit" id="wovax-idx-feeds-search-submit" class="button" value="Search IDX Feeds">
  </p>

  <div class="tablenav top">
    <div class="alignleft actions">
      <label class="screen-reader-text" for="filter-by-comment-type">Filter by comment type</label>
      <select id="filter-by-wovax-idx-feeds" name="comment_type" >
        <option value="">All IDX feed types</option>
        <option value="Production">Production</option>
        <option value="Development">Development</option>
      </select>
      <input type="submit" name="filter_action" id="wovax-idx-feeds-query-submit" class="button" value="Filter">
    </div>
    <div id="wovax-idx-feeds-pagination">
    <!-- Here wil display the pagination -->
    </div>
    <br class="clear">
  </div>
  
  <table class="wp-list-table widefat fixed striped posts" id="wovax-idx-feeds-table">

    <thead>
      <tr>
        <th scope="col" id="class" class="manage-column column-class column-primary sortable asc">
          <a href="#" id="wovax-idx-feeds-class-search"><span>Class</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="resource" class="manage-column column-resource sortable asc<?php echo in_array('resource', $feed_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-feeds-resource-search"><span>Resource</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="feed" class="manage-column column-feed sortable asc<?php echo in_array('feed', $feed_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-feeds-feed-search"><span>Feed</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="board" class="manage-column column-board sortable desc<?php echo in_array('board', $feed_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-feeds-board-search"><span>Board</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="environment" class="manage-column column-environment sortable desc<?php echo in_array('environment', $feed_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-feeds-environment-search"><span>Environment</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="status" class="manage-column column-status sortable desc<?php echo in_array('status', $feed_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-feeds-status-search"><span>Status</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="updated" class="manage-column column-updated sortable desc<?php echo in_array('updated', $feed_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-feeds-updated-search"><span>Updated</span><span class="sorting-indicator"></span></a>
        </th>
      </tr>
    </thead>
    <tbody id="the-list" data-wp-lists="list:post">
      <tr class="wovax-idx-table-loading">
        <td id = "wovax-idx-feed-colspan-qty-loading" colspan="<?php echo $columns_count?>" style="text-align: center;">
          Loading 
        </td>
      </tr>
    </tbody>

    <tfoot>
      <tr>
        <th scope="col" id="class-tfoot" class="manage-column column-class column-primary sortable asc">
          <a href="#" id="wovax-idx-feeds-class-search-tfoot"><span>Class</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="resource-tfoot" class="manage-column column-resource sortable asc<?php echo (in_array('resource', $feed_meta))?' hidden':'';?>">
          <a href="#" id="wovax-idx-feeds-resource-search-tfoot"><span>Resource</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="feed-tfoot" class="manage-column column-feed sortable asc<?php echo (in_array('feed', $feed_meta))?' hidden':'';?>">
          <a href="#" id="wovax-idx-feeds-feed-search-tfoot"><span>Feed</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="board-tfoot" class="manage-column column-board sortable desc<?php echo (in_array('board', $feed_meta))?' hidden':'';?>">
          <a href="#" id="wovax-idx-feeds-board-search-tfoot"><span>Board</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="environment-tfoot" class="manage-column column-environment sortable desc<?php echo (in_array('environment', $feed_meta))?' hidden':'';?>">
          <a href="#" id="wovax-idx-feeds-environment-search-tfoot"><span>Environment</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="status-tfoot" class="manage-column column-status sortable desc<?php echo (in_array('status', $feed_meta))?' hidden':'';?>">
          <a href="#" id="wovax-idx-feeds-status-search-tfoot"><span>Status</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="updated-tfoot" class="manage-column column-updated sortable desc<?php echo (in_array('updated', $feed_meta))?' hidden':'';?>">
          <a href="#" id="wovax-idx-feeds-updated-search-tfoot"><span>Updated</span><span class="sorting-indicator"></span></a>
        </th>
      </tr>
    </tfoot>

  </table>