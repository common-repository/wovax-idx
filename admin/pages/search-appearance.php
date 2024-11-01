<?php
namespace Wovax\IDX\Admin\Pages;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

use Wovax\IDX\Settings\FeedDisplay;
use Wovax\IDX\Settings\SearchAppearance;

class SearchAppearanceAdmin {
    private static $url_bad_fields = array('Description');
    public function __construct() {
        //add_action('admin_enqueue_scripts', array($this, 'enqueueSelectize'));
    }
    public function renderPage() {
        $opts        = new SearchAppearance();
        $base_select = array(
            'plugins'     => array('drag_drop'),
            'persist'     => FALSE,
            'maxItems'    => NULL,
            'valueField'  => 'id',
            'labelField'  => 'title',
            'searchField' => 'title',
            'sortField'   => array(
                'field'     => 'title',
                'direction' => 'asc',
            ),
            'placeholder' => 'Please select an option below',
            'options'     => array(),
            'items'       => array()
        );
        $des_select          = $base_select;
        $des_select['items'] = $opts->getGlobalDescriptionFormat();
        $url_select          = $base_select;
        $url_select['items'] = $opts->getGlobalUrlFormat();
        // Build Choice array/s
        foreach(FeedDisplay::getDefaultFields() as $field) {
            $choice = array(
                'id'    => $field,
                'title' => $field
            );
            if(!in_array($field, self::$url_bad_fields)) {
                $url_select['options'][] = $choice;
            }
            $des_select['options'][] = $choice;
        }
        ?>
		<form method="POST" action="<?php echo admin_url('admin-post.php') ?>">
        <input type="hidden" name="action" value="wovax_idx_search_appearance">
		<?php wp_nonce_field('wovax-search-appr'); ?>
			<section id="search_appearance">
			<table class="form-table">
      		<tbody>
			<!-- URL Structure form part -->
			<tr>
				<th scope="row">
					<label>URL Structure</label>
				</th>
				<td>
					<select name="url-struct[]" id="wovax-idx-url-struct"></select>
					<p class="description">URL structure will be prefixed with {detail-page}/wovax-idx/{resource-id}/ this can not be changed.</p>
				</td>
			</tr>
			<!-- Title -->
			<tr>
				<th scope="row">
					<label>Title Structure</label>
				</th>
				<td>
                    <select name="title-struct[]" id="wovax-idx-title-struct"></select>
					<p class="description">The fields and separators to use for the H1 and meta titles on property listings.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label>Meta Description Structure</label>
				</th>
				<td>
                    <select name="description-struct[]" id="wovax-idx-description-struct"></select>
					<p class="description">The fields to use for the meta description on property listings.</p>
				</td>
			</tr>
			</tbody>
      		</table>
			</section>
			<?php submit_button(); ?>
        </form>
        <script>
            jQuery(document).ready( function($) {
                $('#wovax-idx-url-struct').selectize(<?php echo json_encode($url_select, JSON_PRETTY_PRINT); ?>);
                $('#wovax-idx-description-struct').selectize(<?php echo json_encode($des_select, JSON_PRETTY_PRINT); ?>);
                $('#wovax-idx-title-struct').selectize(<?php echo $this->generateTitleSelectize($base_select) ?>);
            });
        </script>
    <?php
    }
    private function generateTitleSelectize($base) {
        $opts      = new SearchAppearance();
        $counter   = 0;
        $sel_items = array();
        foreach($opts->getGlobalTitleFormat() as $item) {
            $entry = array(
                'id'    => '{'.substr($item['type'], 0, 3).'}{'.$counter.'}{'.$item['value'].'}',
                'title' => $item['value']
            );
            if($item['type'] == 'divider') {
                switch($item['value']) {
                    case 'comma':
                        $entry['title'] = ',';
                        break;
                    case 'dash':
                        $entry['title'] = '-';
                        break;
                    case 'forward-slash':
                        $entry['title'] = '/';
                        break;
                    case 'line-break':
                        $entry['title'] = '[Line Break]';
                        break;
                }
            }
            $sel_items[]     = $entry;
            $base['items'][] = $entry['id'];
            $counter++;
        }
        $base['options'] = array_merge($sel_items, array(
            array(
                'id'    => '{div}{'.($counter+0).'}{comma}',
                'title' => ','
            ),
            array(
                'id'    => '{div}{'.($counter+1).'}{dash}',
                'title' => '-'
            ),
            array(
                'id'    => '{div}{'.($counter+2).'}{forward-slash}',
                'title' => '/'
            ),
            array(
                'id'    => '{div}{'.($counter+3).'}{line-break}',
                'title' => '[Line Break]'
            )
        ));
        $counter += 4;
        foreach(FeedDisplay::getDefaultFields() as $field) {
            $base['options'][] = array(
                'id'    => '{val}{'.$counter.'}{'.$field.'}',
                'title' => $field
            );
            $counter++;
        }
        $init  = substr(json_encode($base, JSON_PRETTY_PRINT), 0, -1);
        $init .= ',
                    onItemAdd: function replaceItem(value, item) {
                        if(typeof replaceItem.counter == \'undefined\') {
                            replaceItem.counter = '.$counter.';
                        }
                        type = value.substr(0, 5);
                        name = value.replace(/{(val|div)}{[0-9]+}/, "");
                        console.log(type);
                        console.log(name);
                        this.addOption({
                            "id"   : type + "{" + replaceItem.counter + "}" + name,
                            "title": item[0].innerText
                        })
                        this.refreshItems();
                        this.refreshOptions(true);
                        replaceItem.counter++;
                    },
                    onItemRemove: function(value, item) {
                        this.removeOption(value);
                    }}';
        return $init;
    }
	public static function enqueueSelectize($hook) {
        if($hook !== 'toplevel_page_wovax_idx_settings') {
            return;
        }
		$base = plugin_dir_url(__FILE__).'../../assets/libraries/selectize/';
		wp_enqueue_script(
			'selectize_js',
			$base.'selectize.min.js',
			array('jquery')
		);
		wp_enqueue_style(
			'selectizen_css',
			$base.'selectize.default.css'
		);
    }
    // Static methods
    public static function updateSettings() {
		if (!current_user_can('manage_options')) {
			wp_die('You can not manage options.', 'Unauthorized User', 401);
		}
        check_admin_referer('wovax-search-appr');
        // Begin updating settings
        $opts       = new SearchAppearance();
        $def_fields = FeedDisplay::getDefaultFields();
        foreach($_POST as $key => $value) {
            if(!is_array($value)) {
                continue;
            }
            switch($key) {
                case 'title-struct':
                    $format = array_map(function($value) {
                        $item = array();
                        $name = substr(preg_replace('/^{(div|val)}{[0-9]+}{/', '', $value), 0, -1);
                        if(substr($value, 0, 5) == '{div}') {
                            $item['type'] = 'divider';
                        } else {
                            $item['type'] = 'value';
                        }
                        $item['value'] = $name;
                        return $item;
                    }, $value);
                    $opts->setGlobalTitleFormat($format);
                    break;
                case 'url-struct':
                    $value = array_map('trim', $value);
                    $value = array_intersect($value, $def_fields);
                    $value = array_diff($value, self::$url_bad_fields);
                    $opts->setGlobalUrlFormat($value);
                    break;
                case 'description-struct':
                    $value = array_map('trim', $value);
                    $value = array_intersect($value, $def_fields);
                    $opts->setGlobalDescriptionFormat($value);
                    break;
            }
        }
		wp_redirect(admin_url('admin.php?page=wovax_idx_settings&tab=search-appearance'), 303);
	}
}
add_action('admin_post_wovax_idx_search_appearance', __NAMESPACE__.'\SearchAppearanceAdmin::updateSettings');
add_action('admin_enqueue_scripts', __NAMESPACE__.'\SearchAppearanceAdmin::enqueueSelectize');
