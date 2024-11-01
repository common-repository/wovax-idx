=== Wovax IDX ===
Contributors: wovax, joeharby, petenyhus
Tags: IDX, MLS, multiple listing service, IDX plugin, IDX WordPress, IDX WordPress plugin, integrated IDX, real estate, real estate WordPress, RETS, WordPress IDX, WordPress MLS, realtor, Gutenberg,
Stable tag: 1.2.2
Requires PHP: 7.0
Requires at least: 4.3
Tested up to: 6.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Wovax IDX brings real estate listings into your website. The clean, modern aesthetic integrates well with most themes.

== Description ==

Wovax IDX for WordPress brings live real estate listings directly into your website. The clean, modern aesthetic integrates well with most WordPress themes and is easy to modify to fit your branding. MLS feeds, listing fields, search filters, and search forms are all managed in the WordPress dashboard. Pair [Wovax IDX](https://wovax.com/products/idx/ "Wovax IDX WordPress plugin") with [Wovax CRM](https://wovax.com/products/crm/ "Wovax CRM WordPress plugin") to see what your leads are looking for on your website, and to track and manage deals.

Download the free plugin with test properties, then sign up for a [subscription to Wovax IDX](https://wovax.com/products/idx/ "Wovax IDX WordPress Plugin"), to integrate live listing data from your preferred MLS board.

https://www.youtube.com/watch?v=AXNiYe-kQ_k

Questions? Don’t hesitate to [reach out to us](https://wovax.com/support/ "Wovax Support").

**Specs & Features**

* Elementor widgets! Design and build your property listing details page layout using Wovax IDX Elementor widgets. Widgets include listing field data, photo carousel, and maps (Google Maps, Apple Maps, LocationIQ, and MapQuest).

* Gutenberg blocks! Design and build your property listing details page layout using Wovax IDX Gutenberg blocks.

* Automatically displays property updates from your MLS within minutes.

* Optimized images served via S3 with image alt tags for improved SEO.

* Customizable search forms, property pages, search results pages, etc.

* Force website visitors to register user accounts after viewing a specified number of listing details.

* Website users can favorite property listings to view later.

* Integrates with analytics tools (Google Analytics, Jetpack Stats, etc.).

* CSS & JS hosted on your website.

* Custom permalink builder for property listings URL structure.

* Scores over 96% on GTmetrix.

* Works with most hosting providers.

* Supported by real people in the Pacific Northwest.

* Integrates with the [Wovax App](https://wovax.com/wordpress-app/ "Wovax App") (for iOS and Android) and [Wovax CRM](https://wovax.com/crm/ "Wovax CRM").

**Coming Soon**

* WordPress REST API controller. This will allow you to build your own custom apps using the WordPress JSON API.

* Divi modules! Design and build your property listing details page layout using Wovax IDX Divi modules.

* Customizable search results sort options.

* Omnisearch search form filter type. Allow your website visitors to search multiple fields from a single text input.

== Installation ==
1. Download and extract the Wovax IDX plugin. You may also install the plugin directly by going to your WordPress Dashboard and then navigating to Plugins > Add New. Search for 'Wovax IDX' under new plugins and click to install directly.
2. Upload the uncompressed folder to your `/wp-content/plugins/` directory.
3. Activate the Wovax IDX plugin through the plugins menu in WordPress.

== Frequently Asked Questions ==
= How do I add my MLS boards listings to my website? =
Go to [https://wovax.com/products/idx/](https://wovax.com/products/idx/ "Wovax IDX WordPress Plugin") and sign up for Wovax IDX. Typically there is a paperwork process with your MLS board to obtain access to the live data feed. A Wovax representative will guide you through that process, then live real estate listings will be added to your site.

= What is a RETS feed? =
RETS stands for “Real Estate Transaction Standard.” A RETS feed is a raw data feed that and IDX provider translates into readable information on your website. Most MLS boards provide MLS data in the RETS format, and Wovax will work with any RETS feed. Feel free to reach out with questions about your particular MLS board’s rules, requirements, and/or fees.

= What does IDX stand for? =
IDX stands for Internet Data Exchange. This is the process of translating raw data into a visual real estate listing search on your website.

= Can I customize the design of Wovax IDX? =
Yes. You can write your own custom variations of the existing/bundled CSS of Wovax IDX, or you can dequeue the Wovax IDX CSS and include your own custom CSS in your WordPress theme.

= Do you provide IDX to my local MLS board? =
Please [contact us here](https://wovax.com/support/ "Wovax Support") to request information about your MLS board.

= Is this an iframe? =
No, Wovax IDX integrates MLS property listings directly into your site via the Wovax JSON API in real time. Rather than embedding the listings into a frame, we integrate each property into your website.

= Can I add neighborhood pages to my website? =
Yes. You can use the shortcode builder in the Wovax IDX plugin to create neighborhood pages, MLS search forms, real estate agent pages, etc.

== Screenshots ==
1. Search results grid view.
2. Search results map view.
3. Search form shortcode builder.
4. Search form filters shortcode builder.
5. MLS/IDX feeds.
6. MLS/IDX feed settings.

== Changelog ==
= 1.2.2 =
* Fixed some bugs related to shortcode filters.

= 1.2.1 =
* Added preset value to search filter types.

= 1.2.0 =
* Added preset value filter to search shortcodes that allows searching within a pre-defined set of listings.
* Fixed some WordPress warnings.

= 1.1.9 =
* Added exclude rule feature to the listing embed shortcode builder.

= 1.1.8 =
* Adjusted initial setup page text.

= 1.1.7 =
* Fixed a bug that prevented the listing card favorite icon from displaying on listing embed shortcodes.

= 1.1.6 =
* Removed legacy icon font assets. The plugin now uses inline SVG icons.
* Removed the saved search button from listing embed shortcodes.
* Added new idx controller functionality - Pass in additional fields to return values in the search and listing embed endpoints (wovax-idx-extra-fields and a comma separated list of field names) - The listing permalink is now attached to each listing in the search and listing embed endpoints.
* Added the ability to change the status display element style on the listing card layout (new setting in the WordPress dashboard).
* Fixed several PHP warnings and notices.

= 1.1.5 =
* Added an error message if the IDX setup is not complete.
* Added initial support for getting extra fields in property search and listing requests.
* Added more site meta tags to improve the user experience for listings shared on social media, including Twitter support.
* Updated the default listing details page layout to include more fields and formatting styles for the Gutenberg block editor.
* Fixed an issue with the Elementor widgets where non-default fields would incorrectly fail to display values.
* Fixed several PHP warnings and notices.

= 1.1.4 =
* Fixed an issue with special characters in shortcode rule creation.
* Added the ability for users to save searches if enabled in Settings.
* Updated the User Profile shortcode to allow users to delete saved searches.

= 1.1.3 =
* Fixed a bug with the Wovax IDX Elementor image widget.

= 1.1.2 =
* Added display for boolean (Y/N) fields for listing details.
* Added boolean (Y/N) search filter type to the search form shortcode builder.

= 1.1.1 =
* Added numeric value range filter type to the search form shortcode builder.
* Tweaked the listing favorite icon to improve compatibility across browsers and platforms.
* Fixed a conflict with Elementor that could cause issues with image paths.
* Removed PHP short tag for broader server compatibility.

= 1.1.0 =
* Updated Wovax IDX WordPress REST API controller paths.
* Updated short PHP tags with regular tags for broader server compatibility.

= 1.0.9 =
* Fixed an issue that affected pagination in certain instances when multiple shortcodes were on the same page.
* Added maps widget for Elementor. This maps widget is for the listings details page, it includes map services from Google, Apple, LocationIQ, and MapQuest 🎉. LocationIQ maps also support places of interest pins (POI).

= 1.0.8 =
* Added initial [Apple MapKit JS](https://developer.apple.com/maps/web/ "Apple MapKit JS") token settings. Apple Maps support coming soon.
* Fixed a bug that prevented Wovax IDX Elementor widgets appearing for websites running on PHP 7.2 or older.

= 1.0.7 =
* Added image carousel widget for Elementor. This will allow you to design and build the listing details page with the incredible Elementor page builder 🎉. Map and POI widget coming soon.

= 1.0.6 =
* Fixed some warnings for PHP 8.

= 1.0.5 =
* Fixed a bug that would echo the path to our upcoming Elementor default CSS file path in the footer of some websites.

= 1.0.4 =
* Added field data widget for Elementor. This will allow you to design and build the listing details page with Elementor 🎉. Image carousel, map and POI widgets coming soon.
* Updated property listings URL path to include MLS and feed number by default.

= 1.0.3 =
* Modified the path for our upcoming WordPress REST API controller.

= 1.0.2 =
* Added get_search_results method to our upcoming WordPress REST API controller.

= 1.0.1 =
* Added get_search_form and get_listings_embed methods to our upcoming WordPress REST API controller.

= 1.0.0 =
* Fixed an issue that would prevent maps rendering correctly.

= 0.9.9 =
* Added fallback in the event wp-transient fails to clear.
* Fixed a bug that prevented shortcodes from rendering if there were multiple on a single page.

= 0.9.8 =
* Added default image alt tags to grid view listings.

= 0.9.7 =
* Improved action bar responsive layout CSS.

= 0.9.6 =
* Updated add new feed link.
* Added rough draft for our upcoming WordPress REST API controller.

= 0.9.5 =
* Adjusted pagintation to work with WordPress 5.5.
* Bumped the WordPress compatibility version number to 5.5.
* Added HTTPS to blocks where field type is set to 'Link' if HTTPS is not present in the data.

= 0.9.4 =
* Added parameter to the Wovax image cache so that images are rotated to match the EXIF data.
* Updated and moved the Slick Slider resources to the plugin. This allows for better caching and reduces HTTP requests.

= 0.9.3 =
* Reduced HTTP requests for shortcodes and the listing details page.
* Improved listing details slider arrow icons.

= 0.9.2 =
* Fixed a bug that would prevent a search form from submitting filters if more than one search form shortcode was on the same page.
* Added default search results to the search results page if accessed directly. This will greatly improve search engine crawlability for property listings.

= 0.9.1 =
* Added the option to hide the WordPress admin toolbar on the frontend for non admin users.

= 0.9.0 =
* Adjusted initial setup page.

= 0.8.9 =
* Bumped the WordPress compatibility version number to 5.4.
* Fixed a bug with map error messages.

= 0.8.8 =
* Improved image alt tags to include listing address if no image description available from the MLS provider.
* Added a default search form shortcode to help speed up IDX setup.
* Fixed an issue that could break search forms when switching feeds.

= 0.8.7 =
* Fixed an issue with sort selects.

= 0.8.6 =
* Fixed a bug that caused a non-responsive map view button in some shortcodes.

= 0.8.5 =
* Added the option to disable the action bar (result count, sort options, etc...) in the shortcode generator.
* Fixed a bug with the listing description output, where fields could get stacked up on the right side in some themes.

= 0.8.4 =
* Added user phone number input/display to WordPress user profiles in the dashboard.

= 0.8.3 =
* Fixed a bug that affected listings without geolocation data.
* Changed the Wovax IDX admin menu label to Wovax. All future Wovax WordPress plugins will reside under one admin menu.

= 0.8.2 =
* Fixed a bug with search form select option dropdown filters.

= 0.8.1 =
* Added photo gallery slider options to the Wovax IDX Gutenberg Photo Gallery block.

= 0.8.0 =
* Improved default Gutenberg layout for the listing details page.
* Fixed a bug with shortcode rules and filters. If you're still experiencing issues with certain shortcodes, try recreating the shortcode from scratch.

= 0.7.9 =
* Added custom color picker in 'Settings' > 'Styling' that affects the colored UI elements.

= 0.7.8 =
* Added custom placeholder image option for listings without photos in 'Settings' > 'Styling'.

= 0.7.7 =
* Fixed a bug with the description meta tag.

= 0.7.6 =
* Improved OG meta tags for listing images.
* Fixed a bug that affected availability of fields in the shortcode builder.

= 0.7.5 =
* Added Wovax IDX Gutenberg Blocks! Build your listing details pages with the new Gutenberg blocks editor.
* New Point of Interest 'POI' Gutenberg Block, display the location of schools, colleges, restaurants, hotels, and many other types of services on maps within your listings.
* Added Favorites button to the action bar for search results, grid view embed, and maps view embed.
* Changed number inputs to type="number" from type="text" on search forms.

= 0.7.4 =
* Added Contact Form 7 support, so that listing URL and listing title can be included in contact forms.

= 0.7.3 =
* Fixed a bug that affected listing URL's on map views.

= 0.7.2 =
* Fixed a bug that prevented the Search Appearance URL path settings from saving correctly.

= 0.7.1 =
* Fixed a bug with breakpoints on listing results CSS grids.

= 0.7.0 =
* Fixed pagination link position on listing grid view.
* Improved CSS layout of the search form.

= 0.6.9 =
* Fixed a bug where the Map View button was not displaying in the correct position.

= 0.6.8 =
* Fixed a bug with listing permalinks.

= 0.6.7 =
* Adjusted CSS to avoid conflicts with Bootstrap 3.
* Improved URL structure to fix missing MLS numbers.
* Replaced CSS column system on search forms and listing grids.
* Fixed a bug that caused MLS feed environments to spontaneously switch.

= 0.6.6 =
* Fixed a bug that would output a PHP warning on listing details pages with certain versions of PHP.

= 0.6.5 =
* Added Open Graph meta data to listing details pages, price, description, image URL's, etc...

= 0.6.4 =
* Fixed a bug with CSS for the toolbar columns height on smartphones.

= 0.6.3 =
* Fixed a bug where fields were displaying duplicates in shortcode filters in certain scenarios.
* Fixed a bug where search form shortcodes that did not contain a select/dropdown wouldn't work as expected.
* Added dynamic formatting to square footage decimal values.

= 0.6.2 =
* Added a div element wrapper around listing details fields, to allow for better customization of the layout.

= 0.6.1 =
* Fixed a bug where a rogue line break <br> tag was causing issues with listing preview layouts.
* Added dynamic formatting to lot size decimal values.
* Added dynamic formatting to singular/plural bedroom and bathroom label depending on field value.
* Added min-height to the bedrooms/bathrooms/square footage/lot size container on listing previews to prevent layout issues.

= 0.6.0 =
* Added dynamic removal of listing field containers when data is blank on listing previews.

= 0.5.9 =
* Added autocomplete to the Initial Setup Search Results and Listing Details page ID text inputs.
* Fixed a syntax error that affected listing embed rules.

= 0.5.8 =
* Fixed a syntax error that affected listing embed rules.

= 0.5.7 =
* Added alert message for failed login attempts.
* Fixed a bug with CSS on the description field output on listing detail views.

= 0.5.6 =
* Fixed a syntax error that affected search form filters.

= 0.5.5 =
* Improvements made to the JSON API interaction/submission for search form shortcodes.
* Updated Wovax logo assets.
* Alphabetized the field select options in shortcodes builder.

= 0.5.4 =
* Added dynamic MLS board logos to listing previews.
* Added ability to change the default listings sort order per shortcode.

= 0.5.3 =
* Adjusted property listings custom H1 title, meta title, and meta description to override the blank data caused by the Yoast SEO plugin.
* Fixed the property listings virtual tour link formatting.

= 0.5.2 =
* Added custom H1 title, meta title, and meta description builder.

= 0.5.1 =
* Fixed a bug with listing preview permalinks on the map view.

= 0.5.0 =
* Added custom permalinks builder for listing details URL's.
* Added meta description to the listing details pages.
* Fixed a pagination bug on search results pages.

= 0.4.9 =
* Fixed acres output in the property listing preview.

= 0.4.8 =
* Clarified a feature within the shortcodes builder.

= 0.4.7 =
* Fixed a misspelling in the IDX Feed rule type display name.

= 0.4.6 =
* Added new Include rule type to the IDX Feed details rules section.

= 0.4.5 =
* Added Wovax/AWS caching service to listing details images.

= 0.4.4 =
* Fixed an issue with the search-form-columns parameter in the search results shortcode.

= 0.4.3 =
* Added the ability to specify the column count in the search form, and search results shortcodes. Example: [wovax-idx id="1" search-form-columns="3"], and [wovax-idx-search-results search-form-columns="3"].

= 0.4.2 =
* Improvements to the Wovax API interactions.

= 0.4.1 =
* Fixed an issue with Wovax API interactions.

= 0.4.0 =
* Improvements to the Wovax API interactions.
* Fixed a bug that prevented complete duplication of shortcodes.
* Fixed a bug that stopped text input placeholders in the search form from displaying the correct value.
* Added Rules section to the IDX feed configuration page.

= 0.3.9 =
* Fixed a bug that stopped field values displaying correctly on the listing details page.

= 0.3.8 =
* Added Screen Options to the User Activity page.
* Added Rules section to the IDX feed configuration page. Optimizations still to be completed. Not yet fully complete.

= 0.3.7 =
* Added initial styling tools to the IDX feed fields layout builder. More styling tools to come in the near future.

= 0.3.6 =
* Added ability to change the Google Map display height in the listing details view. The map height can be adjusted in the Fields Layout builder.
* Added Screen Options to the Shortcodes and IDX Feeds pages. Now you can decide how many items to display per page in the dashboard, and also show/hide columns.

= 0.3.5 =
* Listings in search results and listings pages now dynamically show the status from the MLS board.

= 0.3.4 =
* Improvements to the IDX feed fields layout builder.

= 0.3.3 =
* Improvements to the IDX feed fields layout builder. Works, but optimizations still to be completed.

= 0.3.2 =
* Fixed a bug that created incorrect search filter formats in version 0.3.1.
* Improvements to the IDX feed fields layout builder. Optimizations still to be completed. Not yet fully complete.

= 0.3.1 =
* Added CSS background color to listing previews in the grid view.
* WordPress 4.9.4 support.

= 0.3.0 =
* Fixed a bug that prevented the property listing favorites feature from being disabled.
* Added Omnisearch filter type. Optimizations still to be completed. Not yet fully complete.

= 0.2.9 =
* WordPress 4.9.3 support.

= 0.2.8 =
* Fixed shortcodes database table creation/update bug.

= 0.2.7 =
* Fixed database table update problems.

= 0.2.6 =
* Fixed some poor grammar, and a few small PHP changes in the shortcodes builder.

= 0.2.5 =
* Fixed a bug with the search results shortcode.

= 0.2.4 =
* Fixed a UX issue with shortcodes to auto populate view options with default values if non were set.

= 0.2.3 =
* Fixed extra closing div that should not have been output below shortcodes.

= 0.2.2 =
* Fixed a bug with shortcodes outputting more property listings than specified in the max posts per page setting.

= 0.2.1 =
* Fixed a bug with CSS where the listings container div height would not represent the content height.

= 0.2.0 =
* Updated listing image cache API strings.

= 0.1.9 =
* Improvements to the Favorites shortcode feature.

= 0.1.8 =
* Added User Favorites shortcode feature. Display favorited listings of the current signed in user via a shortcode. Optimizations still to be completed.
* Added User Profile shortcode feature. Display editable user profile fields of the current signed in user via a shortcode. Optimizations still to be completed.

= 0.1.7 =
* Fixed a bug with CSS where around 10px of line-height would appear beneath images.

= 0.1.6 =
* Improvements to the Listing Embed shortcode feature.

= 0.1.5 =
* Improvements to the Listing Embed shortcode type feature.
* Fixed a bug with shortcodes related to PHP version 5.6. Thanks to [@mailtopaul8](https://wordpress.org/support/users/mailtopaul8/) for finding it.

= 0.1.4 =
* Improvements to the User Activity feature. Optimizations still to be completed.
* Added alt tags to images feature. If your MLS board provides image descriptions, these will now be used as alt tags to improve listing SEO.
* Added Listing Embed shortcode type feature. Now you can display listings that match a specific criteria in posts and pages via the shortcode generator. Optimizations still to be completed.

= 0.1.3 =
* Improvements to the User Activity feature. Optimizations still to be completed.

= 0.1.2 =
* Improvements to the User Activity feature. Optimizations still to be completed. Wovax IDX initial stable release almost complete.

= 0.1.1 =
* Improvements to the User Activity feature.

= 0.1.0 =
* Added User Activity feature. Now you can view favorited listings of users. Optimizations still to be completed.

= 0.0.9 =
* Added Force User Registration feature.  Now you have the possibility to force a website visitor to register a user account after a specified number of listing views.

= 0.0.8 =
* Fixed a bug with shortcodes where they would always print at the top of the page contents.

= 0.0.7 =
* Improvements to the Map View feature. Getting closer to initial Wovax IDX stable release.

= 0.0.6 =
* Improvements to the Map View feature. Improved the map search interface with listing previews. Optimizations still to be completed.

= 0.0.5 =
* Improvements to the Map View feature. Optimizations still to be completed.

= 0.0.4 =
* Added Map View feature. Allow home buyers to search for real estate listings with an interactive Google Map. Not yet fully complete.

= 0.0.3 =
* Fixed a bug that stopped certain production IDX feeds displaying correctly.

= 0.0.2 =
* Added free demo development IDX listing data feeds.

= 0.0.1 =
* Initial Wovax IDX Beta release.
