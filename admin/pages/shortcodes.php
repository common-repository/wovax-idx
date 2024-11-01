<?php

if (!defined('ABSPATH')) exit;

if (isset($_GET['t'])) {
  if ($_GET['t'] == 'a') {
    /* tab All */
    $current_all = 'current';
  }
  elseif ($_GET['t'] == 'p') {
    /* tab Published */
    $current_published = 'current';
  }
  elseif ($_GET['t'] == 't') {
    /* tab Trash */
    $current_trash = 'current';
  }
  else {
    /* tab All */
    $current_all = 'current';
  }
}
else {
  /* If no tab is selected, the "All" tab is shown */
  $current_all = 'current';
}

$id_shortcode = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
$action_shortcode = filter_var($_GET['action'], FILTER_SANITIZE_STRING);
$filter = filter_var($_GET['f'], FILTER_SANITIZE_STRING);
$search = filter_var($_GET['s'], FILTER_SANITIZE_STRING);
/* $_GET['t']='t'  means the trash page is active */
$page = ($_GET['t'] == 't') ? 'trash_page' : '';

if (!isset($_GET['action']) && !isset($_GET['id']) && !isset($_GET['f']) && !isset($_GET['s']) && !isset($_GET['a'])) {
  $wovax_idx_table_shortcode = wovax_idx_shortcode_list_table('', '', '', '', '');
}
elseif (isset($_GET['action']) && isset($_GET['id'])) {
  if ($_GET['action'] == 'trash') {
    /* Send a shortcode to trash tab */
    $wovax_idx_table_shortcode = wovax_idx_shortcode_list_table($id_shortcode, $action_shortcode, '', '', '');
  }
  elseif ($_GET['action'] == 'duplicate') {
    $wovax_idx_table_shortcode = wovax_idx_shortcode_list_table('', '', '', '', '');
  }
  elseif ($_GET['action'] == 'untrash' && $_GET['t'] == 't') {
    /* Restore a shortcode to published from trash tab */
    $wovax_idx_table_shortcode = wovax_idx_shortcode_list_table($id_shortcode, $action_shortcode, '', '', 'trash_page');
  }
  elseif ($_GET['action'] == 'delete' && $_GET['t'] == 't') {
    /* Delete a shortcode permanently from trash tab */
    $wovax_idx_table_shortcode = wovax_idx_shortcode_list_table($id_shortcode, $action_shortcode, '', '', 'trash_page');
  }
}
elseif (isset($_GET['f']) && isset($_GET['s'])) {
  /* filter and search */
  $wovax_idx_table_shortcode = wovax_idx_shortcode_list_table('', '', $filter, $search, $page);
}
elseif (!isset($_GET['f']) && isset($_GET['s'])) {
  /* search  */
  $wovax_idx_table_shortcode = wovax_idx_shortcode_list_table('', '', $filter, $search, $page);
}
elseif (isset($_GET['f']) && !isset($_GET['s'])) {
  /* filter  */
  $wovax_idx_table_shortcode = wovax_idx_shortcode_list_table('', '', $filter, '', $page);
}
elseif (isset($_GET['a']) && $_GET['a'] == 'trash') {
  /* Trash tab is selected */
  $wovax_idx_table_shortcode = wovax_idx_shortcode_list_table('', '', '', '', 'trash_page');
}

$wovax_idx_items = (isset($_GET['t']) && $_GET['t'] == 't') ? $wovax_idx_table_shortcode['count_trash'] : $wovax_idx_table_shortcode['count_published'];
$wovax_idx_button_trash = (isset($_GET['t']) && $_GET['t'] == 't') ? '<input type="submit" name="delete_all" id="delete_all" class="button apply" value="Empty Trash">' : '';
$wovax_idx_select = (isset($_GET['t']) && $_GET['t'] == 't') ? '<select name="apply_action" id="bulk-action-selector-top"><option value="t">Bulk Actions</option><option value="untrash">Restore</option><option value="delete_permanently">Delete Permanently</option></select>' : '<select name="apply_action" id="bulk-action-selector-top"><option value="p">Bulk Actions</option><option value="delete">Delete</option></select>';

$user = get_current_user_id();
$shortcode_meta = get_user_meta( $user, 'managewovax-idx_page_wovax_idx_shortcodescolumnshidden', true );
$shortcode_meta = ( is_array($shortcode_meta) )?$shortcode_meta:array();

?>
<div class="wrap">
  <h1 class="wp-heading-inline">Wovax IDX Shortcodes</h1>
  <a href="admin.php?page=wovax_idx_shortcodes&action=update" class="page-title-action">Add New</a>
  <hr class="wp-header-end">

<form action="" method="POST">
  <ul class="subsubsub">
    <li class="all"><a href="admin.php?page=wovax_idx_shortcodes&t=a" class="<?php echo esc_attr($current_all); ?>">All <span class="count"><?php echo esc_html($wovax_idx_table_shortcode['count_published']) ?></span></a> |</li>
    <li class="publish"><a href="admin.php?page=wovax_idx_shortcodes&t=p" class="<?php echo esc_attr($current_published); ?>">Published <span class="count"><?php echo esc_html($wovax_idx_table_shortcode['count_published']) ?></span></a> |</li>
    <li class="trash"><a href="admin.php?page=wovax_idx_shortcodes&a=trash&t=t" class="<?php echo esc_attr($current_trash); ?>">Trash <span class="count"><?php echo esc_html($wovax_idx_table_shortcode['count_trash']) ?></span></a></li>
  </ul>

  <p class="search-box">
    <label class="screen-reader-text" for="wovax-idx-shortcodes-search-input">Search Shortcodes</label>
    <input type="search" id="wovax-idx-shortcodes-search-input" name="s" value="<?php echo esc_attr((isset($_GET['s']) && !empty($_GET['s'])) ? filter_var($_GET['s'], FILTER_SANITIZE_STRING) : ''); ?>">
    <input type="submit" id="search-submit" class="button" name="search" value="Search Shortcodes">
  </p>

  <div class="tablenav top">
    <div class="alignleft actions bulkactions">
      <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
      <?php echo $wovax_idx_select; ?>
      <input type="submit" id="doaction" class="button action" value="Apply">
    </div>
    <div class="alignleft actions">
      <label class="screen-reader-text" for="filter-by-shortcode-type">Filter by comment type</label>
      <select id="filter-by-wovax-idx-shortcode-type" name="shortcode_type" >
        <option value="all">All Shortcode Types</option>
        <option value="search_form" <?php echo esc_attr((isset($_GET['f']) && $_GET['f'] == 'search_form') ? 'selected' : ''); ?> >Search Form</option>
        <option value="listings" <?php echo esc_attr((isset($_GET['f']) && $_GET['f'] == 'listings') ? 'selected' : ''); ?> >Listings Embed</option>
      </select>
      <input type="submit" name="filter_action" id="wovax-idx-shortcode-query-submit" class="button" value="Filter">
      <?php echo $wovax_idx_button_trash; ?>
    </div>
    <div class="alignright actions">
      <span class="displaying-num"> <?php echo esc_html($wovax_idx_items) ?> items</span>
      <span class="pagination-links">
        <span class="tablenav-pages-navspan" aria-hidden="true">«</span>
        <span class="tablenav-pages-navspan" aria-hidden="true">‹</span>
        <span class="paging-input">
          <label for="current-page-selector" class="screen-reader-text">Current Page</label>
          <input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging">
          <span class="tablenav-paging-text"> of <span class="total-pages">1</span></span>
        </span>
        <span class="tablenav-pages-navspan" aria-hidden="true">›</span>
        <span class="tablenav-pages-navspan" aria-hidden="true">»</span>
      </span>
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
        <th scope="col" id="title" class="manage-column column-title column-primary sortable desc">
          <a href="#" id="wovax-idx-shortcode-title"><span>Title</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="shortcode" class="manage-column column-shortcode sortable desc<?php echo in_array('shortcode', $shortcode_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-shortcode-name"><span>Shortcode</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="type" class="manage-column column-type sortable desc<?php echo in_array('type', $shortcode_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-shortcode-type"><span>Type</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="author" class="manage-column column-author sortable desc<?php echo in_array('author', $shortcode_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-shortcode-author"><span>Author</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="created" class="manage-column column-created sortable desc<?php echo in_array('created', $shortcode_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-shortcode-created"><span>Created</span><span class="sorting-indicator"></span></a>
        </th>
      </tr>
    </thead>

    <tbody id="the-list-shortcode" data-wp-lists="list:post">

    <?php echo $wovax_idx_table_shortcode['table'] ?>

    </tbody>

    <tfoot>
      <tr>
        <td class="manage-column column-cb check-column">
          <label class="screen-reader-text" for="cb-select-all-2">Select All</label>
          <input id="cb-select-all-2" type="checkbox">
        </td>
        <th scope="col" id="title-tfoot" class="manage-column column-title column-primary sortable desc">
          <a href="#" id="wovax-idx-shortcode-title-foot"><span>Title</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="shortcode-tfoot" class="manage-column column-shortcode sortable desc<?php echo in_array('shortcode', $shortcode_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-shortcode-name-foot"><span>Shortcode</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="type-tfoot" class="manage-column column-type sortable desc<?php echo in_array('type', $shortcode_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-shortcode-type-foot"><span>Type</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="author-tfoot" class="manage-column column-author sortable desc<?php echo in_array('author', $shortcode_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-shortcode-author-foot"><span>Author</span><span class="sorting-indicator"></span></a>
        </th>
        <th scope="col" id="created-tfoot" class="manage-column column-created sortable desc<?php echo in_array('created', $shortcode_meta)?' hidden':'';?>">
          <a href="#" id="wovax-idx-shortcode-created-foot"><span>Created</span><span class="sorting-indicator"></span></a>
        </th>
      </tr>
    </tfoot>

  </table>
</form>
