<?php

if (!defined('ABSPATH')) exit;

if (array_key_exists('s', $_GET)) {
  $search = filter_var($_GET['s'], FILTER_SANITIZE_STRING);
  $wovax_idx_table_user_activity = wovax_idx_user_activity_list_table($search);
} else {
  $wovax_idx_table_user_activity = wovax_idx_user_activity_list_table('');
}

$user = get_current_user_id();
$user_meta = get_user_meta( $user, 'managewovax-idx_page_wovax_idx_user_activitycolumnshidden', true );
$user_meta = ( is_array($user_meta) )?$user_meta:array();
$columns_count = 5 - ( count($user_meta) / 2 );

?>
<div class="wrap">
  <h1 class="wp-heading-inline">Wovax IDX User Activity</h1>
  <hr class="wp-header-end">

	<p class="search-box">
    <label class="screen-reader-text" for="wovax-idx-user-search-input">Search Users</label>
    <input type="search" id="wovax-idx-user-search-input" name="s" value="">
    <input type="submit" id="wovax-idx-user-submit" class="button" name="search" value="Search Users">
  </p>

  <div class="tablenav top">
    <div id="wovax-idx-users-pagination">
    <!-- Here wil display the pagination -->
    </div>
    <br class="clear">
  </div>

  <table class="wp-list-table widefat fixed striped posts" id="wovax-idx-users-table">

    <thead>
      <tr>
        <th scope="col" id="username" class="manage-column column-username column-primary sortable desc">
          <a href="#" id="wovax-idx-user-username-search"><span>Username</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="fullname" class="manage-column column-fullname sortable desc<?php echo in_array('fullname', $user_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-user-fullname-search"><span>Fullname</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="phone" class="manage-column column-phone sortable desc<?php echo in_array('phone', $user_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-user-phone-search"><span>Phone</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="email" class="manage-column column-email sortable desc<?php echo in_array('email', $user_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-user-email-search"><span>Email</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="favorites" class="manage-column column-favorites sortable desc<?php echo in_array('favorites', $user_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-user-favorites-search"><span>Favorites</span><span class="sorting-indicator"></span></a>
        </th>
      </tr>
    </thead>
    <tbody id="the-list-users" data-wp-lists="list:post">
      <tr class="wovax-idx-table-loading">
		    <td id = "wovax-idx-user-colspan-qty-loading" colspan="<?php echo $columns_count?>" style="text-align: center;">
          Loading 
        </td>
      </tr>

    </tbody>

    <tfoot>
      <tr>
        <th scope="col" id="username-tfoot" class="manage-column column-username column-primary sortable desc">
          <a href="#" id="wovax-idx-user-username-search-tfoot"><span>Username</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="fullname-tfoot" class="manage-column column-fullname sortable desc<?php echo in_array('fullname', $user_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-user-fullname-search-tfoot"><span>Fullname</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="phone-tfoot" class="manage-column column-phone sortable desc<?php echo in_array('phone', $user_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-user-phone-search-tfoot"><span>Phone</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="email-tfoot" class="manage-column column-email sortable desc<?php echo in_array('email', $user_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-user-email-search-tfoot"><span>Email</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="favorites-tfoot" class="manage-column column-favorites sortable desc<?php echo in_array('favorites', $user_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-user-favorites-search-tfoot"><span>Favorites</span><span class="sorting-indicator"></span></a>
        </th>
      </tr>
    </tfoot>

  </table>

  <style type="text/css">
    img {
      float: left;
      margin-right: 10px;
      margin-top: 1px;
    }
  </style>
