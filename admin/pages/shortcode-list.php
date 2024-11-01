<?php
namespace Wovax\IDX\Admin\Pages;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

use WP_List_Table;

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

// It says this class is marked as private, but because its commonly used
// by plugins it's kinda become defactor public. They also say if your worrdied
// about compatibility you can just copy the class source code. Changes to the
// WP_List_Table I suspect will be small and easy to check for. If major we can
// always copy the source code from before what ever majore change.
// https://codex.wordpress.org/Class_Reference/WP_List_Table

class ShortcodeList extends WP_List_Table {
    private $types = array(
        array('All Types',       ''),
        array('Search Form',     'search_form'),
        array('Listing Embed',   'listings'),
        array('User Favourites', 'user_favorites'),
        array('User Profile',    'user_profile'),
    );

    public function __construct() {
        parent::__construct(array(
            'singular' => 'wovax_idx_shortcode',
            'plural'   => 'wovax_idx_shortcodes',
            'ajax'     => false
        ));
    }

    public function get_columns() {
        return array(
            'cb'     => '<input type="checkbox" />',
            'title'  => __('Title'),
            'id'     => __('Shortcode'),
            'type'   => __('Type'),
            'author' => __('Author'),
            'date'   => __('Created'),
        );
    }

    public function get_sortable_columns() {
        return array(
            'title'  => array('title',  true),
            'id'     => array('id',     true),
            'type'   => array('type',   true),
            'author' => array('author', true),
            'date'   => array('date',   true)
        );
    }

    function get_bulk_actions() {
        if($this->getStatus() === 'trash') {
            return array(
                'restore'  => 'Restore',
                'perm_del' => 'Delete Permanently',
            );
        }
        return array(
          'trash' => 'Trash'
        );
      }
    // set default column
    protected function get_default_primary_column_name() {
        return 'title';
    }

    public function no_items() {
        _e('No shortcodes found.');
    }

    public function extra_tablenav( $which ) {
        if ($which == "top" ) {
            ?>
                <label for="shortcode-type" class="screen-reader-text"><?php _e( 'Filter by Type' ); ?></label>
                <select name="shortcode-type">
                    <?php
                        $sel = $this->getFilterType();
                        foreach($this->types as $index => $val) {
                            $sel_str = $index == $sel ? 'selected="selected"' : '';
                            echo sprintf('<option %s value="%d">%s</option>', $sel_str, $index, $val[0]);
                        }
                    ?>
                </select>
            <?php
            submit_button('filter', '', 'filter_action', false, array( 'id' => 'filter-submit' ) );
        }
        /*
        if ($which == "bottom") {
        }
        // $this->screen->shortcode_types*/

        // Both

    }

    public function prepare_items() {
		$this->_column_headers = $this->get_column_info();
        global $wpdb;
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE";
        // Add ordering info
        $orderby = 'id';
        if( array_key_exists('s', $_REQUEST)) {
            $search  = trim($_REQUEST['s']);
        } else {
            $search = '';
        }
        if( array_key_exists('order', $_REQUEST) && trim(strtolower($_REQUEST['order'])) !== 'desc') {
            $order   = 'ASC';
        } else {
            $order = 'DESC';
        }
        if( array_key_exists('orderby', $_REQUEST) ) {
            $tmp     = trim(strtolower($_REQUEST['orderby']));
        } else  {
            $tmp = '';
        }
        $status  = $this->getStatus();
        $type    = $this->getFilterType();
        if(array_key_exists($tmp, $this->get_columns())) {
            $orderby = $tmp;
        }
        // Add status to query
        $val = $status == 'trash' ? 'trash' : 'published';
        $sql .= "  `status` = '$val'";
        // Add filter type to query
        if($type > 0) {
            $val = $this->types[$type][1];
            $sql .= " AND `type` = '$val'";
        }
        // Add search to query
        if(strlen($search) > 0) {
            $sql .= ' AND `title` LIKE \'%'.esc_sql($search).'%\'';
        }
        // Add ordering to query
        $sql .= ' ORDER BY '.esc_sql($orderby).' '.$order;
        // Setup pagination
        $per_page = $this->get_items_per_page('wovax_idx_shortcodes_per_pg', 20);
        $page     = $this->get_pagenum();
        $start    = ($page - 1) * $per_page;
        $sql     .= ' LIMIT '.$start.', '.$per_page;
        // Perform the query
        $results  = $wpdb->get_results($sql, ARRAY_A);
        $total    = intval($wpdb->get_var('SELECT FOUND_ROWS();'));
        $last_pg  = intdiv($total, $per_page);
        if($total % $per_page > 0) {
            $last_pg += 1;
        }
        // An impossible page number redo query at last page.
        if($start > $total) {
            $_GET['paged']     = $last_pg;
            $_POST['paged']    = $last_pg;
            $_REQUEST['paged'] = $last_pg;
            $this->prepare_items();
            return;
        }
        // set pagination information
        $this->set_pagination_args(array(
            'total_items' => $total,
            'total_pages' => $last_pg,
            'per_page'    => $per_page,
        ));
        //$_wp_column_headers[$screen->id] = $this->get_columns()
        // Give the Table class the results
        $this->items = $results;
    }

    public function column_default($item, $column_name) {
        if(array_key_exists($column_name, $item)) {
            return $item[ $column_name ];
        }
        return print_r($item, true);
    }

    protected function get_views() {
        global $wpdb;
        $publish   = intval($wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = 'published'"));
        $trash     = intval($wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = 'trash'"));
        $all       = $publish;
        $status    = $this->getStatus();
        $current   = 'class="current"';
        // Make sure there is no active if search or filter present.
        if($this->getFilterType() > 0) {
            $current = '';
        }
        if(strlen(trim($_REQUEST['s'])) > 0) {
            $current = '';
        }
        // Select Active
        $all_cls   = $status == 'all'     ? $current : '';
        $pub_cls   = $status == 'publish' ? $current : '';
        $trash_cls = $status == 'trash'   ? $current : '';
        $args      = array(
            'page' => $_REQUEST['page']
        );
        $status_links = array();
        $args['status'] = 'all';
        $status_links['all'] = sprintf("<a %s href='?%s'>All</a> (%d)", $all_cls, http_build_query($args), $all);
        $args['status'] = 'publish';
        $status_links['published'] = sprintf("<a %s href='?%s'>Published</a> (%d)", $pub_cls, http_build_query($args), $publish);
        $args['status'] = 'trash';
        $status_links['trashed'] = sprintf("<a %s href='?%s'>Trashed</a> (%d)", $trash_cls, http_build_query($args), $trash);
        return $status_links;
    }

    protected function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="shortcodes[]" value="%s" />', $item['id']
        );
    }
    protected function column_date($item) {
        return human_time_diff( mysql2date('U', $item['date']), current_time('timestamp') );
    }
    protected function column_id($item) {
        return sprintf('[wovax-idx id="%s"]', $item['id']);
    }

    protected function column_title($item) {
        $always_visible = false;
        $edit_url = '#'.$item['id'];
        $actions  = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
        $args     = ShortcodeList::getArgsArray();
        $args['shortcodes[0]'] = $item['id'];
        if($this->getStatus() === 'trash') {
            $args['action'] = 'restore';
            $actions .= sprintf('<span class="edit"><a href="?%s">Restore</a> | </span>', http_build_query($args));
            $args['action'] = 'perm_del';
            $actions .= sprintf('<span class="delete"><a href="?%s">Delete Permanently</a></span>', http_build_query($args));
        } else {
            $edit_url = sprintf("?page=wovax_idx_shortcodes&tab=general&id=%s&action=update", $item['id']);
            $actions .= '<span class="edit"><a href="'.$edit_url.'">Edit</a> | </span>';
            $args['action'] = 'duplicate';
            $actions .= sprintf('<span class="duplicate"><a href="?%s">Duplicate</a> | </span>', http_build_query($args));
            $args['action'] = 'trash';
            $actions .= sprintf('<span class="trash"><a href="?%s">Trash</a></span>', http_build_query($args));
        }
        $actions .= '<div>';
        $text  = "<a id=\"{$item['id']}\"class=\"row-title\" href=\"$edit_url\">{$item['title']}</a>";
        $text .= $actions;
        return $text;
    }

    private function getFilterType() {
        $type = 0;
        if(array_key_exists('shortcode-type', $_REQUEST)) {
            $type = intval($_REQUEST['shortcode-type']);
            if($type < 0 || $type >= count($this->types)) {
                $type = 0;
            }
        }
        return $type;
    }

    private function getStatus() {
        $status = 'all';
        $var   = '';
        if(array_key_exists('status', $_REQUEST)) {
            $var = strtolower(trim($_REQUEST['status']));
        }
        switch($var) {
            case 'publish':
                $status = 'publish';
                break;
            case 'trash':
                $status = 'trash';
                break;
            default:
                break;
        }
        return $status;
    }

    public static function getArgsArray() {
        $order = trim(strtolower($_REQUEST['order']));
        return array(
            'page'           => $_REQUEST['page'],
            'paged'          => isset($_REQUEST['paged']) ? $_REQUEST['paged'] : '1',
            's'              => isset($_REQUEST['s']) ? trim($_REQUEST['s']) : '',
            'status'         => isset($_REQUEST['status']) ? $_REQUEST['status'] : 'all',
            'shortcode-type' => isset($_REQUEST['shortcode-type']) ? $_REQUEST['shortcode-type'] : '0',
            'order'          => $order !== 'desc' ? 'asc' : 'desc'
        );
    }
}

class ShortcodeListPage {
    private $table = NULL;
    public function __construct() {
        add_filter('wovax_idx_submenus', array($this, 'addMenu'));
    }
    public function getTable() {
        if($this->table == NULL) {
            $this->table = new ShortcodeList();
        }
        return $this->table;
    }
    public function addMenu($menus) {
        $menus[] = array(
            'order'       => 2,
            'page_title'  => 'Wovax IDX Shortcodes',
            'menu_title'  => 'Shortcodes',
            'slug'        => 'shortcode_list',
            'init'        => array($this, 'init'),
            'before_call' => array($this, 'addScreen'),
            'call'        => array($this, 'display')
        );
        return $menus;
    }
    public function addScreen() {
        $args = array(
            'label'   => 'Number of shortcodes per page.',
            'default' => 20,
            'option'  => 'wovax_idx_shortcodes_per_pg'
        );
        add_screen_option('per_page', $args);
        $this->getTable(); // Create the list
    }
    public function init() {
        $ran_action = FALSE;
        $action  = isset($_REQUEST['action'])  ? $_REQUEST['action']  : '';
        $action2 = isset($_REQUEST['action2']) ? $_REQUEST['action2'] : '';
        if(strlen($action) < 1 || $action == '-1') {
            $action = $action2;
        }
        switch($action) {
            case 'duplicate':
                $this->duplicate();
                $ran_action = TRUE;
                break;
            case 'perm_del':
                $this->delete();
                $ran_action = TRUE;
                break;
            case 'restore':
                $this->changeStatus('trash', 'published');
                $ran_action = TRUE;
                break;
            case 'trash':
                $this->changeStatus('published', 'trash');
                $ran_action = TRUE;
                break;
            default:
                break;
        }
        if($ran_action || ($_SERVER['REQUEST_METHOD'] !== 'GET' && isset($_REQUEST['filter_action'])) ) {
            wp_redirect('?'.http_build_query(ShortcodeList::getArgsArray()));
            exit;
        }
    }
    private function delete() {
        $ids = $this->getIDs();
        if(count($ids) < 1) {
            return;
        }
        global $wpdb;
        $sql  = "DELETE FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = 'trash'";
        $sql .= " AND `id` IN(".implode(', ', $ids).")";
        $wpdb->query($sql);
    }
    private function duplicate() {
        $ids = $this->getIDs();
        if(count($ids) < 1) {
            return;
        }
        global $wpdb;
        $id = $ids[0];
        $sql  = "SELECT * FROM `{$wpdb->base_prefix}wovax_idx_shortcode` WHERE `status` = 'published'";
        $sql .= " AND `id` = $id";
        $item = $wpdb->get_row($sql, ARRAY_A);
        if(!is_array($item)) {
            return;
        }
        $item['title'] = $item['title'].' (Copy)';
        unset($item['id']);
        unset($item['date']);
        $cols = array();
        $vals = array();
        foreach($item as $key => $val) {
            $cols[] = "`$key`";
            $vals[] = "'".esc_sql($val)."'";
        }
        $sql  = "INSERT INTO `{$wpdb->base_prefix}wovax_idx_shortcode` (".implode(', ', $cols).") VALUES ";
        $sql .= "(".implode(', ', $vals).")";
        $wpdb->query($sql);
    }
    private function changeStatus($old_status, $new_status) {
        $ids = $this->getIDs();
        if(count($ids) < 1) {
            return;
        }
        global $wpdb;
        $old  = esc_sql($old_status);
        $new  = esc_sql($new_status);
        $sql  = "UPDATE `{$wpdb->base_prefix}wovax_idx_shortcode` SET `status` = '$new' WHERE `status` = '$old'";
        $sql .= " AND `id` IN(".implode(', ', $ids).")";
        $wpdb->query($sql);
    }
    public function display() {
        $table = $this->getTable();
        $table->prepare_items();
        ?>
            <div class="wrap">
                <h1 class="wp-heading-inline">Wovax IDX Shortcodes</h1>
                <a href="admin.php?page=wovax_idx_shortcodes&action=update" class="page-title-action">Add New</a>
                <hr class="wp-header-end">
                <form id="wovax-idx-shortcode-list" method="POST">
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                    <?php
                        $table->search_box('Search Shortcodes', 'shortcode_search');
                        $table->views();
                        $table->display();
                    ?>
                </form>
            </div>
        <?php
    }
    private function getIDs() {
        $ids = array();
        if(isset($_REQUEST['shortcodes']) && is_array($_REQUEST['shortcodes'])) {
            foreach($_REQUEST['shortcodes'] as $id) {
                $ids[] = intval($id);
            }
        }
        return $ids;
    }
}

// Create the page
new ShortcodeListPage();