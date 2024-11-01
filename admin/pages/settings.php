<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}
use Wovax\IDX\Admin\Pages\SearchAppearanceAdmin;
use Wovax\IDX\Admin\Pages\ListingCardAdmin;

$tabs     = array('initial-setup', 'services', 'users', 'styling', 'search-appearance', 'listing-card');
$sel_tab  = isset($_GET['tab']) ? strtolower(trim($_GET['tab'])) : '';
if(in_array($sel_tab, $tabs, TRUE) === FALSE) {
    $sel_tab = 'initial-setup';
}
?>
<div class="wrap">
  <h1>Wovax IDX Settings</h1>
  <h2 class="nav-tab-wrapper">
<?php
    foreach($tabs as $tab) {
        $html  = "<a href=\"?page=wovax_idx_settings&tab=$tab\" ";
        $html .= 'class="nav-tab';
        if($tab === $sel_tab) {
            $html .= ' nav-tab-active';
        }
        $html .= '">'.ucwords(str_replace('-', ' ', $tab))."</a>\n";
        echo $html;
    }
?></h2>
<?php

switch($sel_tab) {
    case 'services':
        wovax_idx_display_services();
        break;
    case 'users':
        wovax_idx_display_users();
        break;
    case 'styling':
        wovax_idx_display_styling();
        break;
    case 'search-appearance':
        (new SearchAppearanceAdmin())->renderPage();
        break;
    case 'listing-card':
        //wovax_idx_edit_listing_card();
        (new ListingCardAdmin())->renderPage();
        break;
    default:
        wovax_idx_display_initial_setup();
        break;
}

?>
  </form>
</div>
  <?php
/* Display HTML Initial Setup Tab */

function wovax_idx_display_initial_setup() {
    global $wpdb;
    $opts = new Wovax\IDX\Settings\InitialSetup;
    $search = $opts->searchPage();
    $search =  $search > 0 ? $search : '';
    $detail = $opts->detailPage();
    $detail = $detail > 0 ? $detail : '';
    $default_search = $opts->defaultSearch();
    $search_shortcodes_sql = $wpdb->prepare("SELECT `id`, `title` FROM `{$wpdb->prefix}wovax_idx_shortcode` WHERE `type` = %s AND `status` = %s", "search_form", "published");
		$search_shortcodes = $wpdb->get_results($search_shortcodes_sql);
		$search_options = array();
		foreach($search_shortcodes as $shortcode) {
      if($shortcode->id == $default_search) {
        $selected = " selected";
      } else {
        $selected = "";
      }
			$search_options[] = "<option value=$shortcode->id $selected>$shortcode->title</option>";
		}
?>
  <form method="post" action="options.php">
  <?php
  settings_fields("settings_menu_initial_setup");
  do_settings_sections("settings_menu_initial_setup");
?>
  <section id="initial_setup"> <!-- Initial Setup tab content -->
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">
            <label for="email">Email</label>
          </th>
          <td>
            <input name="wovax-idx-settings-webmaster-email" type="email" class="regular-text" id="email" value="<?php echo esc_attr( $opts->email() ); ?>">
            <p class="description" id="tagline-description">Must be a valid email address for IDX feed setup.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label>Environment</label>
          </th>
          <td>
            <fieldset>
              <label for="environment">
                <input name="wovax-idx-settings-environment" type="radio" id="development" value="development" <?php checked(FALSE, $opts->inProduction()); ?> > Development (sample MLS data)
              </label>
              <br />
              <label for="production">
                <input name="wovax-idx-settings-environment" type="radio" id="production" value="production" <?php checked(TRUE, $opts->inProduction()); ?> > Production (live MLS data)
              </label>
              <p class="description" id="tagline-description">Production requires an active <a href="https://wovax.com/wordpress-idx/" title="Wovax IDX" target="_blank">Wovax IDX</a> subscription.</p>
            </fieldset>
          </td>
        </tr>
        <tr>
            <th scope="row">Page Builder</th>
            <td>
                <fieldset>
                <label for="wovax-idx-settings-details-block-mode">
                  <select id="block-mode" name="wovax-idx-settings-details-block-mode">
                    <option value="blocks" <?php echo esc_attr($opts->inDetailsBlockMode() == 'blocks' ? 'selected' : ''); ?>>Gutenberg Blocks</option>
                    <option value="elementor" <?php echo esc_attr($opts->inDetailsBlockMode() == 'elementor' ? 'selected' : ''); ?> >Elementor</option>
                    <option value="legacy" <?php echo esc_attr($opts->inDetailsBlockMode() == 'legacy' ? 'selected' : ''); ?>>Legacy (Not Recommended)</option>
                  </select>
                  Use Gutenberg Blocks setting for Pre-built Property Listing Details Page.
                </label>
                <br>
                </fieldset>
            </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="wovax-idx-settings-search-results-page">Search Results Page ID</label>
          </th>
          <td>
            <input type="text" name="wovax-idx-settings-search-results-page" id="wovax-idx-settings-search-results-page" placeholder="ID Search Results Page" value="<?php echo esc_attr($search); ?>" />
            <input type="button" id="wovax-idx-settings-search-results-button" class="button button-secondary" value="Create Search Results Page">
            <div id="wovax_idx_message" class="wovax-idx-settings-color-green wovax-idx-settings-display-none">The page was created.</div>
            <p class="description" id="tagline-description">Creates a page called Search Results containing the [wovax-idx-search-results] shortcode.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="wovax-idx-settings-listing-details-page">Listing Details Page ID</label>
          </th>
          <td>
            <input type="text" name="wovax-idx-settings-listing-details-page" id="wovax-idx-settings-listing-details-page" placeholder="ID Listing Details Page" value="<?php echo esc_attr($detail); ?>" />
            <input type="button" id="wovax-idx-settings-listing-details-button" class="button button-secondary" value="Create Listing Details Page">
            <div id="wovax_idx_message_listing" class="wovax-idx-settings-color-green wovax-idx-settings-display-none">The page was created.</div>
            <p class="description" id="tagline-description">Creates a page called Listing Details containing the [wovax-idx-listing-details] shortcode.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
						<label for="wovax-idx-default-search">Default Search Form</label>
					</th>
					<td>
						<select id="wovax-idx-settings-default-search" name="wovax-idx-settings-default-search">
							<?php echo implode($search_options); ?>
						</select>	
            <p class="description" id="tagline-description">Selects which Search Form shortcode displays on the Search Results page if no search is performed.</p>
					</td>
        </tr>
        </tbody>
      </table>
        <?php submit_button(); ?>
    </section>
    <?php
}

// Display HTML Services Tab
function wovax_idx_display_services() {
  $opts = new Wovax\IDX\Settings\InitialSetup;
  ?>
    <form method="post" action="options.php">
    <?php
    settings_fields("settings_menu_services");
    do_settings_sections("settings_menu_services");
  ?>
  <section id="services"> <!-- Services tab content -->
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">
            <label for="google_maps_api_key">Google Maps API Key</label>
          </th>
          <td>
            <input name="wovax-idx-settings-google-maps-api-key" type="text" class="regular-text" id="google_maps_api_key" value="<?php echo esc_attr($opts->googleMapsKey()); ?>">
            <p class="description" id="tagline-description">Maps requires an active <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" title="Google Maps API key" target="_blank">Google Maps API key</a>.</p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="apple_maps_api_key">Apple MapKit JS API Token</label>
          </th>
          <td>
            <textarea name="wovax-idx-settings-apple-mapkit-token" rows="4" class="regular-text" id="apple_mapkit_token"><?php echo esc_attr($opts->appleMapsToken()); ?></textarea>
            <p class="description" id="tagline-description">Apple Maps requires an active <a href="https://developer.apple.com/maps/web/" title="Apple MapKit JS API Token" target="_blank">Apple MapKit JS API Token</a>.</p>
          </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="location_iq_key">Location IQ API Key</label>
            </th>
            <td>
                <input name="wovax-idx-settings-location-iq-api-key" type="text" class="regular-text" id="location_iq_key" value="<?php echo esc_attr($opts->locationIqKey()); ?>">
                <p class="description" id="tagline-description">Maps requires an active <a href="https://locationiq.com/#register" title="Location IQ API key" target="_blank">Location IQ API key</a>.</p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="map_quest_key">MapQuest API Key</label>
            </th>
            <td>
                <input name="wovax-idx-settings-map-quest-api-key" type="text" class="regular-text" id="map_quest_key" value="<?php echo esc_attr($opts->mapQuestKey()); ?>">
                <p class="description" id="tagline-description">Maps requires an active <a href="https://developer.mapquest.com" title="Location IQ API key" target="_blank">MapQuest API key</a>.</p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="list_trac_id">ListTrac ID</label>
            </th>
            <td>
                <input name="wovax-idx-settings-list-trac-id" type="text" class="regular-text" id="list_trac_id" value="<?php echo esc_attr($opts->listTracId()); ?>">
                <p class="description" id="tagline-description">If your board supports ListTrac, there should be an ID provided.</p>
            </td>
        </tr>
      </tbody>
    </table>
    <?php submit_button(); ?>
  </section>

  <?php
}

/* Display HTML User Tab */
function wovax_idx_display_users() {
?>
    <form method="post" action="options.php">
    <?php
  settings_fields("settings_menu_users");
  do_settings_sections("settings_menu_users");
?>
    <section id="users"> <!-- Users tab content -->
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">User Registration</th>
            <td>
              <fieldset>
                <label for="wovax-idx-settings-users-registration">
                  <input type="checkbox" name="wovax-idx-settings-users-registration" value="yes" <?php checked('yes', get_option('wovax-idx-settings-users-registration')); ?> > Allow visitors to create a user account.
                </label>
                <br />
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">Force User Registration</th>
            <td>
              <fieldset>
                <label for="wovax-idx-settings-users-registration-force">
                  <input type="checkbox" name="wovax-idx-settings-users-registration-force" value="yes"  id="wovax-idx-settings-users-registration-force" <?php checked('yes', get_option('wovax-idx-settings-users-registration-force')); ?>> Force visitors to create a user account.
                </label>

                <br />
                <label for="wovax-idx-settings-users-registration-force-count"><input name="wovax-idx-settings-users-registration-force-count" type="number" min="0" step="1" id="wovax-idx-settings-users-registration-force-count" value="<?php echo esc_attr(get_option('wovax-idx-settings-users-registration-force-count')); ?>" class="small-text"> Listings vistors can view before being forced to register a user account.</label>
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">Favorites</th>
            <td>
              <fieldset>
                <label for="wovax-idx-settings-users-favorites">
                  <input type="checkbox" name="wovax-idx-settings-users-favorites" value="yes" <?php checked('yes', get_option('wovax-idx-settings-users-favorites')); ?>> Allow users to favorite properties.
                </label>
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">Saved Searches</th>
            <td>
              <fieldset>
                <label for="wovax-idx-settings-users-saved-searches">
                  <input type="checkbox" name="wovax-idx-settings-users-saved-searches" value="yes" <?php checked('yes', get_option('wovax-idx-settings-users-saved-searches')); ?>> Allow users to save searches.
                </label>
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">Hide Admin Bar</th>
            <td>
              <fieldset>
                <label for="wovax-idx-settings-users-admin-bar">
                  <input type="checkbox" name="wovax-idx-settings-users-admin-bar" value="yes" <?php checked('yes', get_option('wovax-idx-settings-users-admin-bar')); ?>> Hide Admin Bar for non-admin users.
                </label>
              </fieldset>
            </td>
          </tr>
        </tbody>
      </table>
      <?php submit_button(); ?>
    </section>

    <?php
  }

/* Display HTML Styling Tab */
function wovax_idx_display_styling() {
?>
    <form method="post" action="options.php">
    <?php
  settings_fields("settings_menu_listing");
  do_settings_sections("settings_menu_listing");
?>
    <section id="styling"> <!-- Listings tab content -->
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">Wovax IDX CSS</th>
            <td>
              <fieldset>
                <label for="include_css">
                  <input type="checkbox" name="wovax-idx-settings-styling-css" value="yes" value="yes" <?php checked('yes', get_option('wovax-idx-settings-styling-css')); ?> > Uncheck only if you're including your own custom Wovax IDX CSS in a theme, plugin, etc...
                </label>
                <br />
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">Wovax IDX JS</th>
            <td>
              <fieldset>
                <label for="include_js">
                  <input type="checkbox" name="wovax-idx-settings-styling-js" value="yes" value="yes" <?php checked('yes', get_option('wovax-idx-settings-styling-js')); ?> > Uncheck only if you're including your own custom Wovax IDX JS in a theme, plugin, etc...
                </label>
                <br />
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">Wovax IDX Floating Labels</th>
            <td>
              <fieldset>
                <label for="include_js">
                  <input type="checkbox" name="wovax-idx-settings-styling-floating-label" value="yes" value="yes" <?php checked('yes', get_option('wovax-idx-settings-styling-floating-label')); ?> > Uncheck only if you're including your own custom Wovax IDX Floating Label in a theme, plugin, etc...
                </label>
                <br />
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">Listing Photo Placeholder</th>
            <td>
              <fieldset>
                <label for="default_photo">
                  <input type="text" class="wovax-idx-settings-styling-default-image regular-text" name="wovax-idx-settings-styling-default-image" value="<?php echo get_option('wovax-idx-settings-styling-default-image', ''); ?>" >
                  <input type='button' class='wovax-idx-styling-default-image-button button-secondary' value='Set Image'>
                </label>
								<p class="description" id="tagline-description">Allows you to upload your own custom image file for listings without images.</p>
                <br />
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">Element Color</th>
            <td>
              <fieldset>
                <label for="element_color">
                  <input type="text" class="wovax-idx-settings-styling-element-color wovax-idx-color-field" name="wovax-idx-settings-styling-element-color" value="<?php echo get_option('wovax-idx-settings-styling-element-color', ''); ?>" >
                </label>
								<p class="description" id="tagline-description">Changes the color of Wovax IDX elements like buttons, status overlays, etc.</p>
                <br />
              </fieldset>
            </td>
          </tr>
        </tbody>
      </table>
      <?php submit_button(); ?>
    </section>
    <?php
  }

  function wovax_idx_edit_listing_card() {
    ?>
    <form method="post" action="options.php">
    <?php
    settings_fields("settings_menu_listing_card");
    do_settings_sections("settings_menu_listing_card");
    include_once(__DIR__.'/listing-card.php');
    ?>
    </form>
    <?php
  }