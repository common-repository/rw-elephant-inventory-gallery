<?php
/*
Plugin Name: R.W. Elephant Inventory Gallery
Plugin URI: https://www.rwelephant.com/
Description: Gallery displays R.W. Elephant rental inventory on your website.
Version: 1.5.1
Author: R.W. Elephant
Author URI: https://www.rwelephant.com/
License: GPL2 - https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

$rwe_gallery_data = array();

function rwe_get_data($key) {
	global $rwe_gallery_data;
	return (isset($rwe_gallery_data[$key])) ? $rwe_gallery_data[$key] : '';
}

function rwe_set_data($key,$data) {
	global $rwe_gallery_data;
	$rwe_gallery_data[$key] = $data;
}

class RWEgallery {

	function rwe_gallery_core() {

		// Program defaults
		$default_options = array(
			'rwelephant_id' => 'coolrentals',
			'api_key' => '854572e22dd949a11a5b3719308196fd',
			'template' => 'greybox',
			'gallery_name' => 'Gallery',
			'category_thumbnail_size' => 100,
			'item_thumbnail_size' => 100,
			'wishlist_thumbnail_size' => 100,
			'related_thumbnail_size' => 100,
			'kit_thumbnail_size' => 100,
			'facebook' => false,
			'twitter' => false,
			'pinterest' => false,
			'google' => false,
			'page_id' => 2,
			'title_format' => "[title] [separator] [gallery_name] [separator] ",
			'enable_wishlist' => 'yes',
			'wishlist_quantity' => 'yes',
			'wishlist_prices' => 'no',
			'view_wishlist_page' => 'no',
			'wishlist_continue_button' => 'Continue shopping',
			'wishlist_submit_button' => 'Submit your wishlist',
			'wishlist_message' => 'Complete the following form to submit your wishlist.',
			'seo_urls' => 'no',
			'show_related_items' => 'yes',
			'related_items_label' => 'You Might Also Like',
			'show_kit_items' => 'yes',
			'kit_items_label' => 'Contains',
		);

		// Read stored options from options table
		$defined_options = get_option('rwe_gallery_options');

		// Merge set options with defaults
		$options = wp_parse_args( $defined_options, $default_options );

		global $wp_query;

		// Get current page ID
		$location = $wp_query->post->ID;

		rwe_set_data('location',$location);

		// If current page is the gallery location
		if ( is_page($location) && $location == $options['page_id']) {

			// Store options for use in other functions

			rwe_set_data('gallery_name',$options['gallery_name']);
			rwe_set_data('api_key',$options['api_key']);
			rwe_set_data('rwelephant_id',$options['rwelephant_id']);
			rwe_set_data('title_format',$options['title_format']);
			rwe_set_data('enable_wishlist',$options['enable_wishlist']);
			rwe_set_data('view_wishlist_page',$options['view_wishlist_page']);
			rwe_set_data('wishlist_prices',$options['wishlist_prices']);
			rwe_set_data('wishlist_quantity',$options['wishlist_quantity']);
			rwe_set_data('wishlist_submit_button',$options['wishlist_submit_button']);
			rwe_set_data('wishlist_continue_button',$options['wishlist_continue_button']);
			rwe_set_data('wishlist_thumbnail_size',$options['wishlist_thumbnail_size']);
			rwe_set_data('seo_urls',$options['seo_urls']);

			// Define API calls

			$api_base = 'https://galleryapi.rwelephant.com/api/public_api?tenant=' . $options['rwelephant_id'] . '&';
			$wishlist_api = 'https://' . $options['rwelephant_id'] . '.rwelephant.com/perl/wishlist?';

			$category_list_url = $api_base . 'action=list_inventory_types';
			$tag_list_url = $api_base . 'action=list_tags';
			$items_in_category_url = $api_base . 'action=list_items_for_type&inclusion_mask=main_hash&inventory_type_id=';
			$items_by_tag_url = $api_base . 'action=list_items_for_tag&inclusion_mask=main_hash&inventory_tag_type_id=';
			$api_search_url = $api_base . 'action=list_items_for_search&inclusion_mask=main_hash&search_term=';
			$item_detail_url = $api_base . 'action=item_info&inventory_item_id=';
			$item_tags_url = $api_base . 'action=list_tags_for_item&inventory_item_id=';
			$submit_wishlist_url = $wishlist_api . 'action=finalize_wishlist';

			// Templates

			$custom_template_directory = 'rw-elephant-templates';


			// check wp-content location if not already defined
			if ( ! defined( 'WP_CONTENT_DIR' ) )
				define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

			rwe_set_data('content_dir', WP_CONTENT_DIR );
			rwe_set_data('custom_template_directory', $custom_template_directory );


			$custom_template = WP_CONTENT_DIR . '/' . $custom_template_directory . '/';
			$standard_template = plugin_dir_path(__FILE__) . 'templates/' . $options['template'] . '/' ;
			$common_template = plugin_dir_path(__FILE__) . 'templates/common/' ;

			// Register the style sheet for chosen template

			$rwe_stylesheet_location = (file_exists($custom_template.'style.css'))? content_url( $custom_template_directory ). '/style.css' : plugins_url('templates/'.$options['template'].'/style.css', __FILE__);
			wp_register_style( 'rwe-stylesheet', $rwe_stylesheet_location );


			//   Sub-templates used for lists of items and categories

			$category_list_template_file = (file_exists($custom_template.'category-list.php'))? $custom_template . 'category-list.php' : $common_template . 'category-list.php';
			$item_list_template_file = (file_exists($custom_template.'item-list.php'))? $custom_template . 'item-list.php' : $common_template . 'item-list.php';
			$kit_list_template_file = (file_exists($custom_template.'kit-list.php'))? $custom_template . 'kit-list.php' : $common_template . 'kit-list.php';
			$related_list_template_file = (file_exists($custom_template.'related-list.php'))? $custom_template . 'related-list.php' : $common_template . 'related-list.php';


			//   Wishlist
			if (rwe_get_data('enable_wishlist')=='yes') {

				// Wishlist template

				$wishlist_template_file = (file_exists($custom_template.'wishlist.php'))? $custom_template . 'wishlist.php' : $common_template . 'wishlist.php';
				$wishlist_template = file_get_contents($wishlist_template_file);

				// Wishlist messaging
				$wishlist_notice = '<div id="wishlist-result"></div>';

				// View wishlist button
				$view_wishlist = '<button class="view-wishlist">Wishlist</button>';
			}
			

			// Parse search form and wishlist for use in other templates

			$search_form_template_file = (file_exists($custom_template.'search-form.php'))? $custom_template . 'search-form.php' : $common_template . 'search-form.php';

			$search_form_template = file_get_contents($search_form_template_file);
			$search_form_placeholders = array(
				'[gallery_name]' => $options['gallery_name'],
				'[gallery_url]' => get_permalink($options['page_id'])
			);
			$search_form = RWEgallery::parse_template( $search_form_template, $search_form_placeholders );

			// Images

			$no_thumbnail_image = (file_exists($custom_template.'no-thumbnail-image.png'))? content_url( $custom_template_directory ). '/no-thumbnail-image.png' : plugin_dir_url(__FILE__) . 'images/no-thumbnail-image.png';
			$no_item_image = (file_exists($custom_template.'no-item-image.png'))? content_url( $custom_template_directory ). '/no-item-image.png' : plugin_dir_url(__FILE__) . 'images/no-item-image.png';

			rwe_set_data('no-thumbnail-image', $no_thumbnail_image );
			rwe_set_data('no-item-image', $no_item_image );

			// Request var names mapped to permalink names

			$request_vars_list = array ('category'=>'rwecat', 'tag'=>'rwetag', 'item'=>'rweitem', 'search'=>'rwe-search', 'wishlist'=>'rwewishlist', 'viewwishlist'=>'rweviewwishlist');
			rwe_set_data('request_vars_list',$request_vars_list); // store for use in other functions

			// Process the URL 

			if (get_query_var('rwegallery')) {

				// permalink - format: type/##

				$rwegallery_request_url = get_query_var('rwegallery');

				$page_request = array();
				$url = explode('/',$rwegallery_request_url);
				$i = 0;
				while( $i < count($url) ) {
					// strip off seo friendly urls
					$page_request[$url[$i]] = intval(end(explode('-',$url[$i+1])));
					$i+=2;
				}
			}

			// process get requests:

				$get_request = array();
				foreach ($request_vars_list as $key=>$var) {
					if ($var=='rwe-search')
						$get_request['search'] = get_query_var('rwe-search');
					elseif ($var=='rwewishlist')
						$get_request['wishlist'] = get_query_var('rwewishlist');
					elseif ($var=='rweviewwishlist')
						$get_request['viewwishlist'] = get_query_var('rweviewwishlist');
					else
						$get_request["$key"] = intval(get_query_var("$var"));
				}
				// remove null requests
				$get_request = array_filter($get_request);


			// merge URL (permalink) with GET request (non-permalink)

			if ( is_array( $page_request ) && is_array( $get_request ) )
				$page_request = array_merge($page_request, $get_request);
			elseif ( is_array( $get_request))
					$page_request = $get_request;
			else {
				// nothing 
			}

			// Process the URL. Can contain multiple requests -- order matters! 

			if ($page_request['search']) {
				$search_terms = $page_request['search'];
			}
			elseif ($page_request['wishlist']) {
				$wishlist_submit = 1;
			}
			elseif ($page_request['viewwishlist']) {
				$wishlist_view = 1;
			}
			elseif ($page_request['item']) {
				$item_id = $page_request['item'];
			}
			elseif ($page_request['category']) {
				$category_id = $page_request['category'];
			}
			elseif ($page_request['tag']) {
				$tag_id = $page_request['tag'];
			}
			else {
				// nothing else, show top level
				$location = 'main';
			}

			if ($search_terms) {

				$search_words = htmlspecialchars($search_terms, ENT_QUOTES); // make search terms safe for display

				$search_result = RWEgallery::rwe_api($api_search_url, urlencode($search_terms) );

				if ($search_result) {

					// variables to extract
					$search_variables_to_extract = array ('name', 'inventory_item_id', 'description', 'inventory_type_name',
						'quantity', 'rental_price', 'frac_length', 'frac_width', 'frac_height');

					$search_items = array();

					foreach ($search_result as $key=>$search_item) {
						foreach ($search_variables_to_extract as $item_variable) {
							if ( is_numeric( $search_item["$item_variable"] ))
								$search_items["$key"]["$item_variable"] =  $search_item["$item_variable"];
							else
								$search_items["$key"]["$item_variable"] =  htmlspecialchars( $search_item["$item_variable"] , ENT_QUOTES);
						}
						$search_items["$key"]['dimensions'] = RWEgallery::format_item_dimensions( $search_item['frac_length'],
									 $search_item['frac_width'], $search_item['frac_height'] );
						$search_items["$key"]['images'] = RWEgallery::process_item_in_category_image_links( $search_item['image_links'], $search_item['name'], $options['category_thumbnail_size'], $options['rwelephant_id'] );
					}

					$item_list_template = file_get_contents( $item_list_template_file );

					foreach ($search_items as $item) {
				
						$item_list_placeholders = array(
							'[item_name]' => $item['name'],
							'[item_url]' => RWEgallery::rwe_link('item',$item['inventory_item_id'],htmlspecialchars_decode($item['name'])),
							'[item_quantity]' => $item['quantity'],
							'[item_price]' => $item['rental_price'],
							'[item_dimensions]' => $item['dimensions'],
							'[item_photo]' => $item['images']['photo'],
							'[item_photo_url]' => $item['images']['photo_url']
						);

						// Parse item list sub-template and store content for use in main template
						$search_items_content .= RWEgallery::parse_template( $item_list_template, $item_list_placeholders );
					}

				}

				else {
					// empty result or error

					$error = '<p class="error">Nothing found.</p>';
				}

				$search_results_template_file = (file_exists($custom_template.'search-results.php'))? $custom_template . 'search-results.php' : $standard_template . 'search-results.php';

				$search_results_template = file_get_contents( $search_results_template_file );

				$url_connector = (get_option('permalink_structure'))? '?':'&'; // if permalinks are enabled use '?' else '&'
				$search_url = get_permalink($options['page_id']) . $url_connector . 'rwe-search=' . urlencode($search_words);

				$search_placeholders = array(
					'[gallery_name]' => $options['gallery_name'],
					'[gallery_url]' => get_permalink($options['page_id']),
					'[search_form]' => $search_form,
					'[search_terms]' => $search_words,
					'[search_items]' => $search_items_content,
					'[category_thumbnail_size]' => $options['category_thumbnail_size'],
					'[error]' => $error,
					'[page_url]' => $search_url,
					'[view_wishlist]' => $view_wishlist,
					'[wishlist]' => $wishlist_template
				);

				rwe_set_data('content', RWEgallery::parse_template( $search_results_template, $search_placeholders ));
				rwe_set_data('page_title', 'Search: ' . $search_words);
				rwe_set_data('page_link', $search_url);
				rwe_set_data('page_heading', 'Search: ' . $search_words);

			// end search

			}

			if ($wishlist_submit) {


				$cartid = $_COOKIE['cartid'];
				$rwe_sid = $_COOKIE['rwe_sid'];

				$wishlist_id = htmlspecialchars(stripslashes($_REQUEST['rwewishlist']));
				$form_rwe_sid = htmlspecialchars(stripslashes($_REQUEST['rwesid']));
				$first_name = htmlspecialchars(stripslashes($_REQUEST['first_name']));
				$last_name =  htmlspecialchars(stripslashes($_REQUEST['last_name']));
				$email_address =  htmlspecialchars(stripslashes($_REQUEST['email_address']));
				$phone_number =  htmlspecialchars(stripslashes($_REQUEST['phone_number']));
				$event_date =  htmlspecialchars(stripslashes($_REQUEST['event_date']));

				$extra_fields = '';
				if (file_exists($custom_template.'wishlist-extra-fields.php')) {

					$extra_fields = '&wishlist_custom_fields=';

					// check for extra fields input

					$default_fields = array('rwewishlist','rwesid','first_name','last_name','email_address','phone_number','event_date');

					foreach ($_REQUEST as $key => $value) {
						if (!in_array($key,$default_fields)) {
							$value = htmlspecialchars(stripslashes($value));
							$extra_fields .= urlencode("$key: $value\n\n");
						}
					}

				}


				// error checking

				if ($_POST) {

					if (!$first_name) {
						$wishlist_error = 1;
						$error_firstname = 'error';
					}
					if (!$last_name) {
						$wishlist_error = 1;
						$error_lastname = 'error';
					}
					if (!$email_address || !filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
						$wishlist_error = 1;
						$error_email = 'error';
					}
					if (!$phone_number) {
						$wishlist_error = 1;
						$error_phone = 'error';
					}
					if (!$event_date) {
						$wishlist_error = 1;
						$error_event = 'error';
					}
					else {
						// we have a date entry, check it
						$date = date_parse_from_format("m-d-Y", $event_date);
						if (checkdate($date['month'], $date['day'], $date['year'])) {
							// Valid date
							$formatted_date = $date['year'] . '-' . $date['month'] . '-' . $date['day'];
						}
						else {
							// Invalid date
							$wishlist_error = 1;
							$error_event = 'error';
						}
					}

					if ($wishlist_error)
						$error_message = '<p class="error">Required information missing or invalid. Please complete all fields.</p>';

				}

				if ($_POST && !$wishlist_error) {

					// submit to wishlist api

					$wishlist_submit_string = $submit_wishlist_url . '&wishlist_id=' . $wishlist_id
							. '&sid=' . urlencode($form_rwe_sid)
							. '&first_name=' . urlencode($first_name)
							. '&last_name=' . urlencode($last_name)
							. '&email_address=' . urlencode($email_address)
							. '&phone_number=' . urlencode($phone_number)
							. '&event_date=' . urlencode($formatted_date)
							. $extra_fields;

					$wishlist_submit_result = RWEgallery::rwe_api($wishlist_submit_string);


					if ($wishlist_submit_result['response_status'] == "Error") {

						$content = '<p>Error: ' . $wishlist_submit_result['response_message'] . '</p><p><a href="'. get_permalink($options['page_id']) .'">Return to '. $options['gallery_name'] .'</a></p>';

						// clear cookies
						setcookie('cartid', '', time()-3600, '/');
						setcookie('rwe_sid', '', time()-3600, '/');
					}

					elseif ($wishlist_submit_result['message'] == "Wishlist finalized") {

						$content = '<p>Your wishlist has been submitted. Check your inbox for a confirmation email from R.W. Elephant.</p><p><a href="'. get_permalink($options['page_id']) .'">Return to '. $options['gallery_name'] .'</a></p>';

						// clear cookies
						setcookie('cartid', '', time()-3600, '/');
						setcookie('rwe_sid', '', time()-3600, '/');

					}
					else {
						$content = '<p>Unknown error submitting wishlist.</p><p><a href="'. get_permalink($options['page_id']) .'">Return to '. $options['gallery_name'] .'</a></p>';

						$content .= '<p>' . $wishlist_submit_string . '</p>';

					}

				}

				if ($wishlist_error || !$_POST) {

					// if error or first page load


					// get extra fields template

					if (file_exists($custom_template.'wishlist-extra-fields.php')) {
						$wishlist_extra_fields = file_get_contents($custom_template.'wishlist-extra-fields.php');
					}

					$wishlist_message = '<p>' . $options['wishlist_message'] . '</p>';

					$content = <<<EOF

	<h2>Submit Wishlist</h2>
	$error_message
	$wishlist_message
	<form id="wishlistsubmit" method="post">
		<input type="hidden" id="rwewishlist" name="rwewishlist" value="$cartid"/>
		<input type="hidden" id="rwesid" name="rwesid" value="$rwe_sid"/>
		<p class="$error_firstname"><label for="first_name">First name</label><input name="first_name" type="text" value="$first_name" /></p>
		<p class="$error_lastname"><label for="last_name">Last name</label><input name="last_name" type="text" value="$last_name" /></p>
		<p class="$error_email"><label for="email_address">Email address</label><input name="email_address" type="text" value="$email_address" /></p>
		<p class="$error_phone"><label for="phone_number">Phone number</label><input name="phone_number" type="text" value="$phone_number" /></p>
		<p class="$error_event"><label for="event_date">Event date (mm-dd-yyyy)</label><input id="event_date" name="event_date" type="text" value="$event_date" /></p>

		$wishlist_extra_fields

<script type="text/javascript">
jQuery(document).ready(function() {
    jQuery('#event_date').datepicker({
        dateFormat : 'mm-dd-yy',
        minDate: 0
    });
    var rwe_sid = getCookie('rwe_sid');
    var cartid = getCookie('cartid');
    jQuery('#rwewishlist').val(cartid);
    jQuery('#rwesid').val(rwe_sid);
});
function getCookie(cname)
{
var name = cname + "=";
var ca = document.cookie.split(';');
for(var i=0; i<ca.length; i++) 
  {
  var c = ca[i].trim();
  if (c.indexOf(name)==0) return c.substring(name.length,c.length);
}
return "";
}
</script>
		<p><input type="submit" value="Submit" /></p>
	</form>


EOF;

				}

				rwe_set_data('content', $content);

			}	// end of wishlist submit


			if ($wishlist_view) {

				$content = <<<EOF

	<h2>Your Wishlist</h2>

	<div class="view-wishlist-page">

		<form id="wishlistpageform">
		<div id="wishlist-page-contents"></div>
		</form>

	</div>

EOF;

				rwe_set_data('content', $content);

			}

			if ($location == 'main') {

				$category_list_result = RWEgallery::rwe_api($category_list_url);

				if ($category_list_result) {

					// variables to extract
					$category_variables_to_extract = array ('inventory_type_name', 'inventory_type_id');

					$category_list = array();

					foreach ($category_list_result as $key=>$category) {
						foreach ($category_variables_to_extract as $item_variable) {
							if ( is_numeric( $category["$item_variable"] ))
								$category_list["$key"]["$item_variable"] =  $category["$item_variable"];
							else
								$category_list["$key"]["$item_variable"] =  htmlspecialchars( $category["$item_variable"] , ENT_QUOTES);
	
							$category_image_links[0] = array ( 'photo_hash' => $category["photo_hash"] );
							$category_list["$key"]['images'] = RWEgallery::process_item_in_category_image_links( $category_image_links, $category_list["$key"]['inventory_type_name'], $options['category_thumbnail_size'], $options['rwelephant_id'] );
						}
					}

					$category_list_template = file_get_contents( $category_list_template_file );
				
					foreach ($category_list as $category) {
				
						$category_list_placeholders = array(
							'[category_name]' => $category['inventory_type_name'],
							'[category_id]' => $category['inventory_type_id'],
							'[category_url]' => RWEgallery::rwe_link('category',$category['inventory_type_id'],htmlspecialchars_decode($category['inventory_type_name'])),
							'[category_thumbnail]' => $category['images']['photo'],
							'[category_thumbnail_url]' => $category['images']['photo_url']
						);

						// Parse category list sub-template and store content for use in main template
						$category_list_content .= RWEgallery::parse_template( $category_list_template, $category_list_placeholders );
					}
				}

				else {
					// empty result or error

					$error = '<p class="error">No categories found.</p>';
				}


	
				$categories_template_file = (file_exists($custom_template.'categories.php'))? $custom_template . 'categories.php' : $standard_template . 'categories.php';

				$categories_template = file_get_contents( $categories_template_file );

				$categories_placeholders = array(
					'[gallery_name]' => $options['gallery_name'],
					'[gallery_url]' => get_permalink($options['page_id']),
					'[search_form]' => $search_form,
					'[category_list]' => $category_list_content,
					'[category_thumbnail_size]' => $options['category_thumbnail_size'],
					'[error]' => $error,
					'[view_wishlist]' => $view_wishlist,
					'[wishlist]' => $wishlist_template
				);

				rwe_set_data('content', RWEgallery::parse_template( $categories_template, $categories_placeholders ));
				// rwe_set_data('page_title', null );
				rwe_set_data('page_heading', $options['gallery_name']);


			// end main

			}

			if ($tag_id) {

				// list items by tag

				$items_by_tag_result = RWEgallery::rwe_api($items_by_tag_url, $tag_id);

				if ($items_by_tag_result) {

					// variables to extract
					$tag_variables_to_extract = array ('name', 'inventory_item_id', 'description', 'inventory_type_name',
						'quantity', 'rental_price', 'frac_length', 'frac_width', 'frac_height', 'inventory_tag_name');
					$items_by_tag = array();

					foreach ($items_by_tag_result as $key=>$item_by_tag) {
						foreach ($tag_variables_to_extract as $item_variable) {
							if ( is_numeric( $item_by_tag["$item_variable"] ))
								$items_by_tag["$key"]["$item_variable"] =  $item_by_tag["$item_variable"];
							else
								$items_by_tag["$key"]["$item_variable"] =  htmlspecialchars( $item_by_tag["$item_variable"] , ENT_QUOTES);
						}
						$items_by_tag["$key"]['dimensions'] = RWEgallery::format_item_dimensions( $item_by_tag['frac_length'],
									 $item_by_tag['frac_width'], $item_by_tag['frac_height'] );
						$items_by_tag["$key"]['images'] = RWEgallery::process_item_in_category_image_links( $item_by_tag['image_links'], $item_by_tag['name'], $options['category_thumbnail_size'], $options['rwelephant_id'] );
					}

					// get tag name

					$tag_name = $items_by_tag[0]['inventory_tag_name']; // each result should have the tag name
					if(!$tag_name)
						$tag_name = 'Unknown Tag';
	
					$item_list_template = file_get_contents( $item_list_template_file );
				
					foreach ($items_by_tag as $item) {
				
						$item_list_placeholders = array(
							'[item_name]' => $item['name'],
							'[item_url]' => RWEgallery::rwe_link('item',$item['inventory_item_id'], htmlspecialchars_decode($item['name'])),
							'[item_quantity]' => $item['quantity'],
							'[item_price]' => $item['rental_price'],
							'[item_dimensions]' => $item['dimensions'],
							'[item_photo]' => $item['images']['photo'],
							'[item_photo_url]' => $item['images']['photo_url']
						);

						// Parse item list sub-template and store content for use in main template
						$items_by_tag_content .= RWEgallery::parse_template( $item_list_template, $item_list_placeholders );
					}

				}

				else {
					// empty response or error

					$error = 'Could not find items for tag.';

				}

				$tag_template_file = (file_exists($custom_template.'tag.php'))? $custom_template . 'tag.php' : $standard_template . 'tag.php';

				$tag_template = file_get_contents( $tag_template_file );

				$tag_url = RWEgallery::rwe_link('tag',$tag_id,htmlspecialchars_decode($tag_name));

				$tag_placeholders = array(
					'[gallery_name]' => $options['gallery_name'],
					'[gallery_url]' => get_permalink($options['page_id']),
					'[search_form]' => $search_form,
					'[tag_name]' => $tag_name,
					'[tag_items]' => $items_by_tag_content,
					'[category_thumbnail_size]' => $options['category_thumbnail_size'],
					'[error]' => $error,
					'[page_url]' => $tag_url,
					'[view_wishlist]' => $view_wishlist,
					'[wishlist]' => $wishlist_template
				);

				rwe_set_data('content', RWEgallery::parse_template( $tag_template, $tag_placeholders ));
				rwe_set_data('page_title', $tag_name);
				rwe_set_data('page_heading',  $tag_name);
				rwe_set_data('page_link',  $tag_url);


			// end items by tag
			}


			if ($category_id) {

				$items_in_category_result = RWEgallery::rwe_api($items_in_category_url, $category_id);

				if ($items_in_category_result) {

					// variables to extract
					$category_variables_to_extract = array ('name', 'inventory_item_id', 'description', 'inventory_type_name',
						'quantity', 'rental_price', 'frac_length', 'frac_width', 'frac_height');

					$items_in_category = array();
					foreach ($items_in_category_result as $key=>$item_in_category) {
						foreach ($category_variables_to_extract as $item_variable) {
							if ( is_numeric( $item_in_category["$item_variable"] ))
								$items_in_category["$key"]["$item_variable"] =  $item_in_category["$item_variable"];
							else
								$items_in_category["$key"]["$item_variable"] =  htmlspecialchars( $item_in_category["$item_variable"] , ENT_QUOTES);
						}
						$items_in_category["$key"]['dimensions'] = RWEgallery::format_item_dimensions( $item_in_category['frac_length'],
									 $item_in_category['frac_width'], $item_in_category['frac_height'] );
						$items_in_category["$key"]['images'] = RWEgallery::process_item_in_category_image_links( $item_in_category['image_links'], $item_in_category['name'], $options['category_thumbnail_size'], $options['rwelephant_id'] );
					}

					if ($items_in_category[0]['inventory_type_name'])
						$category_name = $items_in_category[0]['inventory_type_name'];
					else
						$category_name = 'Empty category';

					$item_list_template = file_get_contents( $item_list_template_file );
				
					foreach ($items_in_category as $item) {
				
						$item_list_placeholders = array(
							'[item_name]' => $item['name'],
							'[item_url]' => RWEgallery::rwe_link('item',$item['inventory_item_id'],htmlspecialchars_decode($item['name'])),
							'[item_quantity]' => $item['quantity'],
							'[item_price]' => $item['rental_price'],
							'[item_dimensions]' => $item['dimensions'],
							'[item_photo]' => $item['images']['photo'],
							'[item_photo_url]' => $item['images']['photo_url']
						);

						// Parse item list sub-template and store content for use in main template
						$items_in_category_content .= RWEgallery::parse_template( $item_list_template, $item_list_placeholders );
					}

				}

				else {
					// empty result or error

					$error = 'Nothing found in category.';

				}

				$category_template_file = (file_exists($custom_template.'category.php'))? $custom_template . 'category.php' : $standard_template . 'category.php';

				$category_template = file_get_contents( $category_template_file );

				$category_url = RWEgallery::rwe_link('category',$category_id,htmlspecialchars_decode($category_name));

				$category_placeholders = array(
					'[gallery_name]' => $options['gallery_name'],
					'[gallery_url]' => get_permalink($options['page_id']),
					'[search_form]' => $search_form,
					'[category_name]' => $category_name,
					'[category_id]' => $category_id,
					'[category_items]' => $items_in_category_content,
					'[category_thumbnail_size]' => $options['category_thumbnail_size'],
					'[error]' => $error,
					'[page_url]' => $category_url,
					'[view_wishlist]' => $view_wishlist,
					'[wishlist]' => $wishlist_template
				);

				rwe_set_data('content', RWEgallery::parse_template( $category_template, $category_placeholders ));
				rwe_set_data('page_title', $category_name);
				rwe_set_data('page_heading',  $category_name);
				rwe_set_data('page_link',  $category_url);


			// end items in category
			}

			if ($item_id) {

				$item_detail_result = RWEgallery::rwe_api($item_detail_url, $item_id);

				if ($item_detail_result) {

					$item_detail_result = $item_detail_result[0]; // result is the first item

					// variables to extract
					$item_detail_variables_to_extract = array ('name', 'description', 'inventory_type_name',
						'inventory_type_id', 'quantity', 'rental_price', 'frac_length', 'frac_width', 'frac_height');

					// create $item array, encode characters as html entities for web safe display
					$item = array();
					foreach ($item_detail_variables_to_extract as $item_variable) {
						if ( is_numeric( $item_detail_result["$item_variable"] ))
							$item["$item_variable"] =  $item_detail_result["$item_variable"];
						else
							$item["$item_variable"] =  htmlspecialchars( $item_detail_result["$item_variable"] , ENT_QUOTES);
					}

					$item_url = RWEgallery::rwe_link('item',$item_id, htmlspecialchars_decode($item['name']));


					// get tags for item
					$item_tags_result = RWEgallery::rwe_api($item_tags_url, $item_id);
					$item['tags'] = RWEgallery::format_item_tags($item_tags_result);
	
					// format item dimensions
					$item['dimensions'] = RWEgallery::format_item_dimensions( $item['frac_length'],
								 $item['frac_width'], $item['frac_height'] );
	
					// process images -- main image and thumbnails
					$item_images = RWEgallery::process_image_links( $item_detail_result['image_links'],
								 $item['name'], $options['item_thumbnail_size'], $options['rwelephant_id'] );

					// related items
					// filter out empty values
					if (is_array($item_detail_result['related_items'])) {
						$related = array_filter($item_detail_result['related_items']);
						if (!empty($related) && $options['show_related_items']=='yes') {
							$related_items = RWEgallery::process_related_items($related, $options['related_thumbnail_size'], $options['related_items_label'], $related_list_template_file, $options['rwelephant_id']);
						}
					}

					// kit items
					// filter out empty values
					if (is_array($item_detail_result['inventory_kit_line_items'])) {
						$kit = array_filter($item_detail_result['inventory_kit_line_items']);
						if (!empty($kit) && $options['show_kit_items']=='yes') {
							$kit_items = RWEgallery::process_kit_items($kit, $options['kit_thumbnail_size'], $options['kit_items_label'], $kit_list_template_file, $options['rwelephant_id']);
						}
					}


					// add to wishlist button
					if (rwe_get_data('enable_wishlist')=='yes') {
						$add_to_wishlist = '<button class="add-to-wishlist" value="'. $item_id .'">Add to wishlist</button>';
					}

					// add Open Graph meta tags

					add_action('wp_head', array('RWEgallery', 'add_meta_og_tags'), 5);

					// process selected social links

					if($options['facebook']) {
						$social_link_script .= <<<EOF
(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

EOF;
						$social_links .= '<div class="rwe-share rwe-button-facebook" style="width:77px;"><div id="fb-root"></div><div class="fb-like" data-href="'.$item_url.'" data-send="false" data-layout="button_count" data-width="100" data-show-faces="false"></div></div>';
					}
					if($options['twitter']) {
						$social_link_script .= <<<EOF
!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");

EOF;
						$social_links .= '<div class="rwe-share rwe-button-twitter" style="width:83px;"><a href="https://twitter.com/share" class="twitter-share-button" data-text="'.$item['name'].'" data-url="'.$item_url.'" rel="nofollow"></a></div>';
					}
					if($options['google']) {
						$social_link_script .= <<<EOF
window.___gcfg = {lang: '<?php echo $lang_g; ?>'};
(function() {
   var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
   po.src = 'https://apis.google.com/js/plusone.js';
   var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
})();

EOF;
						$social_links .= '<div class="rwe-share rwe-button-googleplus" style="width:56px;"><div class="g-plusone" data-size="medium" data-href="'.$item_url.'"></div></div>';
					}
					if($options['pinterest']) {
						$social_script_pinterest = '<script type="text/javascript" src="//assets.pinterest.com/js/pinit.js"></script>';
						$social_links .= '<div class="rwe-share rwe-button-pinterest" style="width: 75px;"><a href="https://pinterest.com/pin/create/button/?url='.urlencode($item_url).'&media='.urlencode($item_images['main_photo_url']).'&description='.urlencode($item['name']).': '.urlencode($item['description']).'" class="pin-it-button" count-layout="horizontal" always-show-count="1"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a></div>';
					}

					// format social links
					if ($social_links) {
						$social_links = '<div class="rwe-share-box">'.$social_links.'<div style="clear:both;"></div></div>';
					}

					// include javascript for social links
					if ($social_link_script) {
						$social_links = '<script type="text/javascript">' ."\n".'//<![CDATA['."\n" . $social_link_script . '// ]]>'."\n".'</script>'."\n"  . $social_links;
					}


				}
				else {
					//empty result or error

					$error = 'Item could not be found.';
					$item_url = RWEgallery::rwe_link('item',$item_id, 'not found');
				}

				if ($item['rental_price']) {
					$price_template_file = (file_exists($custom_template.'price.php'))? $custom_template . 'price.php' : $common_template . 'price.php';
					$price_template = file_get_contents( $price_template_file );

					$price_placeholders = array(
						'[price]' => $item['rental_price']
					);

					$formatted_price = RWEgallery::parse_template( $price_template, $price_placeholders );
				}
				else {
					$formatted_price = '';
				}

				$item_detail_template_file = (file_exists($custom_template.'item-detail.php'))? $custom_template . 'item-detail.php' : $standard_template . 'item-detail.php';
				$item_detail_template = file_get_contents( $item_detail_template_file );

				$item_detail_placeholders = array(
					'[gallery_name]' => $options['gallery_name'],
					'[gallery_url]' => get_permalink($options['page_id']),
					'[search_form]' => $search_form,
					'[item_name]' => $item['name'],
					'[item_id]' => $item_id,
					'[item_description]' => $item['description'],
					'[item_quantity]' => $item['quantity'],
					'[item_category_name]' => $item['inventory_type_name'],
					'[item_category_id]' => $item['inventory_type_id'],
					'[item_category_url]' => RWEgallery::rwe_link('category',$item['inventory_type_id'],htmlspecialchars_decode($item['inventory_type_name'])),
					'[item_photo]' => $item_images['main_photo'],
					'[item_photo_url]' => $item_images['main_photo_url'],
					'[item_thumbnails]' => $item_images['thumbnails'],
					'[item_thumbnails_url]' => $item_images['thumbnails_url'],
					'[item_thumbnail_size]' => $options['item_thumbnail_size'],
					'[item_tags]' => $item['tags'],
					'[item_dimensions]' => $item['dimensions'],
					'[item_price]' => $item['rental_price'],
					'[formatted_price]' => $formatted_price,
					'[related_items]' => $related_items,
					'[kit_items]' => $kit_items,
					'[error]' => $error,
					'[page_url]' => $item_url,
					'[social_links]' => $social_links,
					'[add_to_wishlist]' => $add_to_wishlist,
					'[view_wishlist]' => $view_wishlist,
					'[wishlist]' => $wishlist_template,
					'[wishlist_notice]' => $wishlist_notice
				);

				// Parse template and set the content
				rwe_set_data('content', RWEgallery::parse_template( $item_detail_template, $item_detail_placeholders ).$social_script_pinterest);
				rwe_set_data('page_title', $item['name']);
				rwe_set_data('page_heading', $item['name']);
				rwe_set_data('page_link',  $item_url);
				rwe_set_data('item_image', $item_images['main_photo_url']);
				rwe_set_data('item_description', $item['description']);

			// end of item request
			}

			// Add hooks
			add_filter('wp_title', array('RWEgallery', 'change_page_title'), 1, 3);
			add_filter('the_title', array('RWEgallery', 'change_page_heading'), 100, 2);
			add_filter('page_link', array('RWEgallery', 'change_page_link'), 100, 2);
			add_filter('the_content', array('RWEgallery', 'rwe_gallery_display'), 10, 2);
			add_action('wp_enqueue_scripts', array('RWEgallery', 'include_scripts'));
			if (rwe_get_data('enable_wishlist')=='yes') {
				// Add hook to define RWE data in <head> javascript
				add_action('wp_head', array('RWEgallery', 'rwe_wishlist_head_js'));
			}
		}
	}

	function rwesidaction_callback() {
		global $wpdb; // this is how you get access to the database

		// check for session id for wishlist
		$rwe_sid = $_COOKIE['rwe_sid'];
		if ($rwe_sid=='' || !$rwe_sid) {

			$plugin_options = get_option('rwe_gallery_options');

			$rwe_id = $plugin_options['rwelephant_id'];
			$api_key = $plugin_options['api_key'];
			$api_call = 'https://' . $rwe_id . '.rwelephant.com/perl/wishlist?action=obtain_session_id';
			$args = array('timeout'=>10);
			$result = wp_remote_get( $api_call . '&api_key=' . $api_key . '&callback=myJsonpCallback' , $args );

			if ( is_wp_error( $result ) ) {
				// WP_HTTP returned an error -- $error_string = $result->get_error_message();
			}
			else {
				$result = jsonp_decode($result['body']);
				if($result["response_status"]=="Error") {
					// API response contains an error.
				}
			}

			$rwe_sid = $result['sid'];
			echo $rwe_sid;
		}
		die(); // this is required to return a proper result
	}


	function rwe_api($api_call, $extra=null ) {
		$api_key = rwe_get_data('api_key');
		$args = array('timeout'=>10);
		$result = wp_remote_get( $api_call . $extra . '&api_key=' . $api_key . '&callback=myJsonpCallback' , $args );
		if ( is_wp_error( $result ) ) {
			// WP_HTTP returned an error -- $error_string = $result->get_error_message();
			return;
		}
		else {
			$data = jsonp_decode($result['body']);
			if($data["response_status"]=="Error") {
				// API response contains an error.
				return;
			}
			else
				return $data;
		}
	}

	function add_meta_og_tags() {
		echo '<meta property="og:title" content="' . rwe_get_data('page_title') . '" />';
		echo '<meta property="og:type" content="website" />';
		echo '<meta property="og:url" content="' . get_permalink() . '" />';
		echo '<meta property="og:image" content="' . rwe_get_data('item_image') . '" />';
		echo '<meta property="og:description" content="' . rwe_get_data('item_description') . '" />';
	}

	function format_item_tags($tags) {
		if ( is_array( $tags )) {
			foreach($tags as $tag) {
				$tag_link = RWEgallery::rwe_link('tag',$tag['inventory_tag_type_id'],htmlspecialchars_decode($tag['inventory_tag_name']));
				$tag_list .= '<li><a href="'. $tag_link . '">' . $tag['inventory_tag_name'] . '</a></li>';
			}
			return $tag_list;
		}
	}

	function rwe_wishlist_head_js() {

		$getsid = admin_url( 'admin-ajax.php' );

		$rwe_gallery_options = get_option('rwe_gallery_options');
		$rweid = $rwe_gallery_options['rwelephant_id'];
		$galleryurl = get_permalink( $rwe_gallery_options['page_id'] );

		// $rweid = rwe_get_data('rwelephant_id');

		echo <<<EOF
<script type="text/javascript">
var rweID = '$rweid';
var imageURL = 'https://$rweid.rwelephant.com/';
var rweURL = 'https://$rweid.rwelephant.com/perl/wishlist?callback=?';
var galleryURL = '$galleryurl';
jQuery(document).ready(function( $ ) {
	var rwe_sid = getCookie('rwe_sid');
	if (rwe_sid=="") {
	   // session not set, check if browser accepts cookies
	   var tmpcookie = new Date();
	   chkcookie = (tmpcookie.getTime() + '');
	   document.cookie = "chkcookie=" + chkcookie + "; path=/";
	    if (document.cookie.indexOf(chkcookie,0) < 0) {
	     		// cookies disabled
	      }
	    else {
		     // cookies enabled
			var data =  {
				action: 'rwesidaction'
			}
			$.post('$getsid', data, function(response){
				setCookie("rwe_sid",response,1);
				// get cartid
				jQuery.getJSON(rweURL,{sid: response, action: 'create_new_wishlist'}, function(result){
					var cartid = result.wishlist_id; 
					if (cartid!="" && cartid!=null)
					{
						setCookie("cartid",cartid,1);
					}
				});


			});
	    }
	
	}
});
EOF;
		if (rwe_get_data("wishlist_prices") == 'yes') {
	     		echo 'var showprice = false;';
		}
		else echo 'var showprice = false;';
		if (rwe_get_data("wishlist_quantity") == 'yes') {
	     		echo 'var showquantity = true;';
		}
		else echo 'var showquantity = false;';
		if (rwe_get_data("view_wishlist_page") == 'yes') {
	     		echo 'var viewwishlistpage = true;';
		}
		else echo 'var viewwishlistpage = false;';
		if (rwe_get_data('seo_urls') == 'yes')
			echo 'var seoURLs = true;';
		else
			echo 'var seoURLs = false;';

		echo 'var permalinks = ';
		echo (get_option('permalink_structure'))? 'true;':'false;'; // if permalinks are enabled

		echo 'var wishlistthumbnailsize = "' . rwe_get_data("wishlist_thumbnail_size") . '";';
		echo 'var wishlistcontinuebuttontext = "' . rwe_get_data("wishlist_continue_button") . '";';
		echo 'var wishlistsubmitbuttontext = "' . rwe_get_data("wishlist_submit_button") . '";';


echo <<<EOF
function setCookie(cname,cvalue,exdays)
{
var d = new Date();
d.setTime(d.getTime()+(exdays*24*60*60*1000));
var expires = "expires="+d.toGMTString();
document.cookie = cname + "=" + cvalue + "; " + expires + "; path=/";
}
function getCookie(cname)
{
var name = cname + "=";
var ca = document.cookie.split(';');
for(var i=0; i<ca.length; i++) 
  {
  var c = ca[i].trim();
  if (c.indexOf(name)==0) return c.substring(name.length,c.length);
}
return "";
}
EOF;

		echo '</script>';
	}

	function include_scripts() {

		$custom_template_directory = rwe_get_data('custom_template_directory');

		$custom_template = rwe_get_data('content_dir') . '/'. $custom_template_directory . '/';
		$custom_template_url = content_url( $custom_template_directory );

		$script_js_location = (file_exists($custom_template.'script.js'))? $custom_template_url . '/script.js' : plugins_url('templates/common/script.js', __FILE__);

		wp_enqueue_script(
			'rwe_gallery_script',
			$script_js_location,
			array('jquery'),
			'1.1'
		);
		if (rwe_get_data('enable_wishlist')=='yes') {

			$wishlist_js_location = (file_exists($custom_template.'wishlist.js'))? $custom_template_url . '/wishlist.js' : plugins_url('templates/common/wishlist.js', __FILE__);

			wp_enqueue_script(
				'rwe_wishlist_script',
				$wishlist_js_location,
				array('jquery','jquery-ui-datepicker'),
				'1.1'
			);
			wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		}
		// load the stylesheet previously registered for the chosen template
		wp_enqueue_style( 'rwe-stylesheet' );
	}

	function rwe_link ($type, $id, $name) {
		if (get_option('permalink_structure')) {
			
			if (rwe_get_data('seo_urls')=='yes') {
				// add seo friendly name
				if (!empty($name)) {
					$seo = create_slug($name) . '-';
				} else {
					$seo = '';
				}
			} else {
					$seo = '';
			}

			// permalink
			return get_permalink($options['page_id'])."$type/$seo$id/";
		}
		else {
			// non-permalink direct request link
			$rwe_get_vars = rwe_get_data('request_vars_list');
			$type = $rwe_get_vars["$type"];
			return get_permalink($options['page_id'])."&$type=$id";
		}
	}


	function process_item_in_category_image_links ($image_links, $item_name, $thumbnail_size, $rwe_id) {

		if ($thumbnail_size == 300)
			$thumb_base = '_300_';
		elseif ($thumbnail_size == 200)
			$thumb_base = '_large_thumbnail_';
		else
			$thumb_base = '_public_thumbnail_';

		if ($image_links[0]['photo_hash']) {
			// array item 0 contains the main image
			$item_images['photo_url'] = 'https://images.rwelephant.com/' . $rwe_id . $thumb_base . $image_links[0]['photo_hash'];
			$item_images['photo'] = '<img src="' . $item_images['photo_url'] . '" class="rwe-category-photo" alt="' . $item_name . '" />';
		}
		else {
			$item_images['photo_url'] = rwe_get_data('no-thumbnail-image');
			$item_images['photo'] = '<img src="' . $item_images['photo_url'] . '" class="rwe-category-photo" alt="' . $item_name . '" />';
		}

		return $item_images;
	}

	function process_image_links ($image_links, $item_name, $thumbnail_size, $rwe_id) {

		if ($image_links[0]['photo_hash']) {
			// array item 0 contains the main image

			$item_images['original_photo_url'] = $image_links[0]['photo_link'];
			$item_images['main_photo_url'] = $image_links[0]['600_link'];
			$item_images['main_photo'] = '<a href="'. $item_images['original_photo_url'] .'"><img src="' . $item_images['main_photo_url'] . '" class="rwe-item-photo" alt="' . $item_name . '" /></a>';
		}
		else {
			$item_images['main_photo_url'] = rwe_get_data('no-item-image');
			$item_images['main_photo'] = '<img src="' . $item_images['main_photo_url'] . '" class="rwe-item-photo" alt="' . $item_name . '" />';
		}


		
		if ($thumbnail_size == 300)
			$thumb_key = '300_link';
		elseif ($thumbnail_size == 200)
			$thumb_key = 'large_thumbnail_link';
		else
			$thumb_key = 'thumbnail_link';

		$thumbnails = array();
		$thumbnail_list = array();

		if ($image_links) {
			foreach ( $image_links as $image ) {
				if ( $image['photo_hash'] ) {
					$thumbnail_url = $image[$thumb_key];
					$full_image_url = $image['600_link'];
					$original_image =  $image['photo_link'];


					$thumbnails[] = $thumbnail_url;
					$thumbnail_list[] = '<li><a href="' . $full_image_url . '" data-original="' . $original_image . '"><img src="' . $thumbnail_url . '" /></a></li>';
				}
			}
		}

		// if we have more than the main photo, create thumbnail list
		if ( count($thumbnails) > 1 ) {

			// format thumbnails list

			$item_images['thumbnails'] = implode( '', $thumbnail_list );

			// create csv of thumbnails url

			$item_images['thumbnails_url'] = implode( ',', $thumbnails );

		}

		return $item_images;
	}

	function process_related_items($related, $thumbnail_size, $label, $template_file, $rwe_id) {
		$list_template = file_get_contents( $template_file );
		$related_items = "<h3>$label</h3>" . '<ul class="related-items">';
		foreach ($related as $item) {

			$photos = RWEgallery::process_item_in_category_image_links ($item['image_links'], $item['name'], $thumbnail_size, $rwe_id);

			$placeholders = array(
				'[item_name]' => $item['name'],
				'[item_id]' => $item['inventory_item_id'],
				'[item_category_id]' => $item['inventory_type_id'],
				'[item_category_name]' => $item['inventory_type_name'],
				'[item_price]' => $item['rental_price'],
				'[item_url]' => RWEgallery::rwe_link('item',$item['inventory_item_id'],htmlspecialchars_decode($item['name'])),
				'[item_photo]' => $photos['photo'],
				'[item_photo_url]' => $photos['photo_url']
			);

			$related_items .= RWEgallery::parse_template( $list_template, $placeholders );

		}
		$related_items .= '</ul>';
		return $related_items;
	}

	function process_kit_items($kit, $thumbnail_size, $label, $template_file, $rwe_id) {
		$list_template = file_get_contents( $template_file );
		$kit_items = "<h3>$label</h3>" . '<ul class="kit-items">';
		foreach ($kit as $item) {

			$photos = RWEgallery::process_item_in_category_image_links ($item['image_links'], $item['name'], $thumbnail_size, $rwe_id);

			$placeholders = array(
				'[item_quantity]' => $item['quantity'],
				'[item_name]' => $item['name'],
				'[item_id]' => $item['inventory_item_id'],
				'[item_category_id]' => $item['inventory_type_id'],
				'[item_category_name]' => $item['inventory_type_name'],
				'[item_price]' => $item['rental_price'],
				'[item_url]' => RWEgallery::rwe_link('item',$item['inventory_item_id'],htmlspecialchars_decode($item['name'])),
				'[item_photo]' => $photos['photo'],
				'[item_photo_url]' => $photos['photo_url']
			);

			$kit_items .= RWEgallery::parse_template( $list_template, $placeholders );

		}
		$kit_items .= '</ul>';
		return $kit_items;
	}


	function format_item_dimensions( $length, $width, $height ) {
		if ( $length || $width || $height ) {
			$dimensions = array();
			if ($length)
				$dimensions[] = $length;
			if ($width)
				$dimensions[] = $width;
			if ($height)
				$dimensions[] = $height;
			
			$dim_total = count($dimensions);

			foreach ($dimensions as $dim) {
				$dim_total-=1;
				$formatted_dimensions .= $dim;
				if ($dim_total > 0)
					$formatted_dimensions .= ' x ';
			}
			return $formatted_dimensions;
		}
		else return;
	}

	function change_page_title($title, $sep = "|", $seplocation = 'right') { 

		$custom_title = rwe_get_data('page_title');
		$gallery_name = rwe_get_data('gallery_name');

		if ( $custom_title ) {

			$title_placeholders = array(
				'[title]' => $custom_title,
				'[separator]' => $sep,
				'[gallery_name]' => $gallery_name
			);
	
			$new_title = RWEgallery::parse_template( rwe_get_data('title_format') , $title_placeholders );
			return $new_title;
		}
		else {
			// no title is set (on the main gallery page) so use the page title from WordPress
			return $title;
		}
	}

	function change_page_heading($title, $id=null) { 
		$location = rwe_get_data('location');
		if ( $location == $id && in_the_loop() )
			return rwe_get_data('page_heading');
		else
			return $title;
	}

	function change_page_link($url, $id) { 
		$new_url = rwe_get_data('page_link');
		if ($new_url && in_the_loop())
			return $new_url;
		else
			return $url;
	}

	function parse_template ($template, $placeholders) {
		return str_replace(array_keys($placeholders), $placeholders, $template);
	}

	function rwe_gallery_display($content) { 
		$rwe_content = rwe_get_data('content');
		$new_content = $rwe_content . $content;
		return $new_content;
	}
	function add_admin_menu() {
		$hook_suffix = add_options_page(
			'R.W. Elephant Inventory Gallery', //page title
			'R.W. Elephant', //menu-title
			'manage_options', //access/capability
			'rw-elephant-inventory-gallery', //slug name
			'rwe_admin_options' //function
		);
		add_action( 'load-' . $hook_suffix , 'rwe_load_function' );
		add_action( 'admin_print_styles-' . $hook_suffix, 'rwe_admin_styles' );
	}
}
function create_slug($phrase, $maxLength=100)
{
    $result = strtolower($phrase);

    $result = preg_replace("/[^A-Za-z0-9\s-._\/]/", "", $result);
    $result = preg_replace("/[\/._]+/", " ", $result);
    $result = trim(preg_replace("/[\s-]+/", " ", $result));
    $result = trim(substr($result, 0, $maxLength));
    $result = preg_replace("/\s/", "-", $result);
    
    return $result;
}
function rwe_admin_init() {
	wp_register_style( 'rwe-admin-stylesheet', plugins_url('admin.css', __FILE__) );
}
function rwe_admin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$options = array (
		array(
		    'name' => 'Settings',
		    'desc' => 'These settings are required to use the R.W. Elephant Inventory Gallery on your site.',
		    'id'   => 'main-settings',
		    'type' => 'section'),
		array(
		    'name' => 'R.W. Elephant ID',
		    'desc' => 'Example: coolrentals',
		    'id'   => 'rwelephant_id',
		    'type' => 'text',
		    'std'  => 'coolrentals'),
		array(
		    'name' => 'R.W. Elephant API Key',
		    'desc' => '(32 character code)',
		    'id'   => 'api_key',
		    'type' => 'text',
		    'std'  => '854572e22dd949a11a5b3719308196fd'),
		array(
		    'name' => 'Page for Gallery',
		    'desc' => 'Gallery is displayed at this location on your website.',
		    'id'   => 'page_id',
		    'type' => 'dropdown_pages'),
		array(
		    'name' => 'Options',
		    'desc' => 'You can use these options to customize the appearance of your gallery.',
		    'id'   => 'extra-options',
		    'type' => 'section'),
		array(
		    'name' => 'Gallery Name',
		    'desc' => 'Default: Gallery',
		    'id'   => 'gallery_name',
		    'type' => 'text',
		    'std'  => 'Gallery'),
		array(
		    'name' => 'Title Format',
		    'desc' => 'Placeholders available: [title] [separator] [gallery_name]',
		    'id'   => 'title_format',
		    'type' => 'text',
		    'std'  => '[title] [separator] [gallery_name] [separator] '),
		array(
		    'name' => 'Template',
		    'desc' => '',
		    'id'   => 'template',
		    'type' => 'select',
		    'options'  => array('greybox','simple') ),
		array(
		    'name' => 'Thumbnail Size: Item List',
		    'desc' => 'pixels. Item thumbnails on category, tag and search pages',
		    'id'   => 'category_thumbnail_size',
		    'type' => 'select',
		    'options' => array(100,200,300) ),
		array(
		    'name' => 'Thumbnail Size: Alternate Images',
		    'desc' => 'pixels. Alternate thumbnails on item detail pages',
		    'id'   => 'item_thumbnail_size',
		    'type' => 'select',
		    'options' => array(100,200,300) ),
		array(
		    'name' => 'Use item and category names in URLs?',
		    'desc' => '&quot;SEO-friendly&quot; URLs',
		    'id'   => 'seo_urls',
		    'type' => 'select',
		    'options' => array('yes','no'),
		    'default' => 'no' ),
		array(
		    'name' => 'Kits',
		    'desc' => '',
		    'id'   => 'kits',
		    'type' => 'section'),
		array(
		    'name' => 'Expanded display of kits?',
		    'desc' => 'Show the individual items in a kit on the detail page',
		    'id'   => 'show_kit_items',
		    'type' => 'select',
		    'options' => array('yes','no'),
		    'default' => 'yes' ),
		array(
		    'name' => 'Kit item thumbnail size',
		    'desc' => 'pixels',
		    'id'   => 'kit_thumbnail_size',
		    'type' => 'select',
		    'options' => array(100,200) ),
		array(
		    'name' => 'Title for kit items',
		    'desc' => '',
		    'id'   => 'kit_items_label',
		    'type' => 'text',
		    'std'  => 'Contains'),
		array(
		    'name' => 'Related Items',
		    'desc' => '',
		    'id'   => 'related-items',
		    'type' => 'section'),
		array(
		    'name' => 'Show related items?',
		    'desc' => '',
		    'id'   => 'show_related_items',
		    'type' => 'select',
		    'options' => array('yes','no'),
		    'default' => 'yes' ),
		array(
		    'name' => 'Related item thumbnail size',
		    'desc' => 'pixels',
		    'id'   => 'related_thumbnail_size',
		    'type' => 'select',
		    'options' => array(100,200) ),
		array(
		    'name' => 'Title for related items',
		    'desc' => '',
		    'id'   => 'related_items_label',
		    'type' => 'text',
		    'std'  => 'You Might Also Like'),
		array(
		    'name' => 'Wishlist',
		    'desc' => 'Allow visitors to create and submit a wishlist of items from your gallery.',
		    'id'   => 'wishlist-options',
		    'type' => 'section'),
		array(
		    'name' => 'Enable wishlist?',
		    'desc' => '',
		    'id'   => 'enable_wishlist',
		    'type' => 'select',
		    'options' => array('yes','no'),
		    'default' => 'yes' ),
		array(
		    'name' => 'View wishlist on a separate page?',
		    'desc' => '',
		    'id'   => 'view_wishlist_page',
		    'type' => 'select',
		    'options' => array('yes','no'),
		    'default' => 'no' ),
		array(
		    'name' => 'Wishlist thumbnail size',
		    'desc' => 'pixels. Shown on separate wishlist page only',
		    'id'   => 'wishlist_thumbnail_size',
		    'type' => 'select',
		    'options' => array(100,200) ),
		array(
		    'name' => 'Show quantity on wishlist?',
		    'desc' => '',
		    'id'   => 'wishlist_quantity',
		    'type' => 'select',
		    'options' => array('yes','no'),
		    'default' => 'yes' ),
		array(
		    'name' => 'Continue shopping button',
		    'desc' => 'shown on the wishlist page',
		    'id'   => 'wishlist_continue_button',
		    'type' => 'text',
		    'std'  => 'Continue shopping'),
		array(
		    'name' => 'Wishlist submit button',
		    'desc' => 'shown on the wishlist page',
		    'id'   => 'wishlist_submit_button',
		    'type' => 'text',
		    'std'  => 'Submit your wishlist'),
		array(
		    'name' => 'Wishlist Message',
		    'desc' => 'Message to show on wishlist submit page',
		    'id'   => 'wishlist_message',
		    'type' => 'text',
		    'std'  => 'Complete the following form to submit your wishlist.'),
		array(
		    'name' => 'Social Sharing Links',
		    'desc' => 'Select which social sharing links to display on item detail pages.',
		    'id'   => 'social-sharing-settings',
		    'type' => 'section'),
		array(
		    'name' => 'Facebook',
		    'id'   => 'facebook',
		    'type' => 'checkbox',
		    'std' => null ),
		array(
		    'name' => 'Twitter',
		    'id'   => 'twitter',
		    'type' => 'checkbox',
		    'std' => null ),
		array(
		    'name' => 'Pinterest',
		    'id'   => 'pinterest',
		    'type' => 'checkbox',
		    'std' => null ),
		array(
		    'name' => 'Google',
		    'id'   => 'google',
		    'type' => 'checkbox',
		    'std' => null )
	);
	if ( 'save' == $_REQUEST['action'] ) {
		$options_to_update = array();
		foreach ($options as $value) {
			if( isset( $_REQUEST[ $value['id'] ] ) ) {
				$options_to_update[ $value['id'] ] = $_REQUEST[ $value['id'] ] ;
			}
		}
		update_option('rwe_gallery_options',$options_to_update);
		$saved = true;
	}
	$options_array = get_option('rwe_gallery_options');
	echo '<div class="rwe-admin-head"><h2><img src="'. plugin_dir_url(__FILE__) . 'images/rwe-logo.png" class="rwe-logo" alt="R.W. Elephant" />';
	echo 'R.W. Elephant Inventory Gallery</h2></div>';
	if ( $saved == true ) echo '<div id="message" class="updated fade"><p><strong>R.W. Elephant Inventory Gallery settings saved.</strong></p></div>';
	echo '<div class="rwe-settings">';
	echo '<form method="post">';
	foreach ($options as $value) {
		switch ( $value['type'] ) {
		case 'section':
			if ($value['name']) echo '<h3>'.$value['name'].'</h3>';
			if ($value['desc']) echo '<p>'.$value['desc'].'</p>';
		break;
		case 'text':
			echo '<div><label class="" for="'.$value['id'].'">'. $value['name'].'</label>'
				. '<input name="'. $value['id'] .'" id="'. $value['id'] .'" type="'. $value['type'] .'" value="';
				if ( $options_array[ $value['id'] ] != "") {
					echo stripslashes( $options_array[ $value['id'] ] );
				}
				else {
					echo $value['std'];
				}
			echo '" />';
			if ( $value['desc'] ) echo '<small class="rwe-note">'. $value['desc'] .'</small>';
			echo '</div>';
		break;
		case 'select':
			echo '<div><label class="" for="'.$value['id'].'">'. $value['name'] .'</label>'
				.'<select name="'. $value['id'] .'" id="'. $value['id'] .'">';
			foreach ( $value['options'] as $opt ) {
				echo '<option value="' . $opt .'"';
				if ( $options_array[ $value['id'] ] == $opt) echo ' selected="selected"';
				elseif ( empty($options_array[ $value['id'] ]) && $value['default'] == $opt ) echo ' selected="selected"';
				echo '>';
				echo $opt . '</option>';
			}
			echo '</select>';
			echo ' <small class="rwe-note">' . $value['desc'] . '</small></div>';
		break;
		case 'dropdown_pages':
			echo '<div><label class="" for="'.$value['id'].'">'. $value['name'];
			echo '</label>';
			$args = array(
			    'depth'    => 0,
			    'child_of' => 0,
			    'selected' => $options_array[ $value['id'] ],
			    'echo'     => 1,
			    'name'     => $value['id']
			);
			wp_dropdown_pages( $args );
			echo '<small class="rwe-note">' . $value['desc'] . '</small>';
			echo '</div>';
		break;
		case 'checkbox':
			echo '<input type="checkbox" name="'.$value['id'].'" id="'.$value['id'].'" value="true"';
			if ( $options_array[ $value['id'] ] == true ) echo ' checked="true"';
			echo ' />';
			echo '<label for="'.$value['id'].'" class="social-checkbox">'.$value['name'].'</label>';
		break;
		}
	}
	echo '<input name="save" class="rwe-submit" type="submit" value="Save changes" />';
	echo '<input type="hidden" name="action" value="save" />';
	echo '</form>';
	echo '</div>';
}
if (!get_option('rwe_gallery_options')) {
	add_action( 'admin_notices', 'rwe_admin_notices' );
}

function rwe_load_function() {
	remove_action( 'admin_notices', 'rwe_admin_notices' );
}

function rwe_admin_notices() {
	echo "<div id='notice' class='updated fade'><p>R.W. Elephant Inventory Gallery is not configured yet. Please edit the settings now.</p></div>\n";
}

function rwe_admin_styles() {
	wp_enqueue_style( 'rwe-admin-stylesheet' );
}

function jsonp_decode($jsonp, $assoc = true) { // PHP 5.3 adds depth as third parameter to json_decode
	if($jsonp[0] !== '[' && $jsonp[0] !== '{') { // we have JSONP
		$jsonp = substr($jsonp, strpos($jsonp, '('));
	}
	return json_decode(trim($jsonp,'();'), $assoc);
}

class RWE_Widget extends WP_Widget {

	// Set up the RW Elephant 

	public function __construct() {
		parent::__construct(
			'rwe_widget', // Base ID
			__( 'RW Elephant Wishlist Button', 'text_domain' ), // Name
			array( 'description' => __( 'A button to view RW Elephant wishlist', 'text_domain' ), ) // Args
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		// load RW Elephant plugin options

		$rwe_gallery_options = get_option('rwe_gallery_options');

		if ($rwe_gallery_options['enable_wishlist']=='yes'){ 

			echo $args['before_widget'];
			if ( ! empty( $instance['title'] ) ) {
				echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
			}

			$handle = 'rwe_wishlist_script';
			$list = 'enqueued';
			if (wp_script_is( $handle, $list )) {
				// nothing
			} else {
				// wishlist js not loaded
	
				// Add hook to define RWE data javascript in footer
				add_action('wp_footer', array('RWEgallery', 'rwe_wishlist_head_js'));
	
				// Register and load script for counter
				wp_register_script( 'rwe_wishlist_counter', plugin_dir_url(__FILE__).'templates/common/wishlist-counter.js');
				wp_enqueue_script( 'rwe_wishlist_counter' );
	
			}

			// get permalink for gallery page
		
			if ( $rwe_gallery_options['page_id'] ) {
				$rwe_gallery_page = get_permalink( $rwe_gallery_options['page_id'] );
			}
	
			$url_connector = (get_option('permalink_structure'))? '?':'&'; // if permalinks are enabled use '?' else '&'
	
			echo ( '<a href="'. $rwe_gallery_page . $url_connector .'rweviewwishlist=1" class="view-wishlist">Wishlist</a>');
			echo $args['after_widget'];

		}

	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}

add_action('widgets_init',
     create_function('', 'return register_widget("RWE_Widget");')
);

// get the permalink for the gallery
function get_rwe_gallery_permalink() {
	// get permalink for gallery page
	$rwe_gallery_options = get_option('rwe_gallery_options');

	if ( $rwe_gallery_options['page_id'] ) {
		$rwe_gallery_page = substr(get_permalink( $rwe_gallery_options['page_id'] ), strlen(get_settings('home'))+1, -1 );
		return $rwe_gallery_page;
	}
}

// flush rewrite rules if our rules are included
function rwe_gallery_flush_rewrite_rules() {

	$rwe_gallery_page = get_rwe_gallery_permalink();

	$rules = get_option( 'rewrite_rules' );
	if ( ! isset( $rules['(' . $rwe_gallery_page . ')/(.+)$'] ) ) {
		global $wp_rewrite;
	   	$wp_rewrite->flush_rules();
	}
}

// Add rewrite rule
function rwe_gallery_rewrite_rules( $rules ) {

	$rwe_gallery_page = get_rwe_gallery_permalink();

	$newrules = array();
	$newrules['(' . $rwe_gallery_page . ')/(.+)$'] = 'index.php?pagename=$matches[1]&rwegallery=$matches[2]';
	return $newrules + $rules;
}

// Add variables to use in query
function rwe_gallery_query_vars( $vars ) {
	array_push($vars, 'rwegallery', 'rwecat', 'rwetag', 'rweitem', 'rwewishlist', 'rweviewwishlist', 'rwe-search');
	return $vars;
}

if ( get_option('permalink_structure') ) {
	// permalinks are enabled
	// add rewrite rules
	add_action( 'wp_loaded','rwe_gallery_flush_rewrite_rules' );
	add_filter( 'rewrite_rules_array','rwe_gallery_rewrite_rules' );
}


// add AJAX for wishlist session request
add_action( 'wp_ajax_rwesidaction', array('RWEgallery', 'rwesidaction_callback'));
add_action( 'wp_ajax_nopriv_rwesidaction', array('RWEgallery', 'rwesidaction_callback'));


function rwe_gallery_plugin_action_links( $links ) {
 	return array_merge(
		$links, 
		array(
			sprintf(
				'<a href="%s">%s</a>',
				add_query_arg(
					array(
						'page' => 'rw-elephant-inventory-gallery'
					),
					admin_url('options-general.php')
				),
				__('Settings')
			)
		)

	);
 
}

add_filter( 'query_vars','rwe_gallery_query_vars' );
add_action('wp', array('RWEgallery', 'rwe_gallery_core'));
add_action( 'admin_init', 'rwe_admin_init' );
add_action('admin_menu', array('RWEgallery', 'add_admin_menu'));
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'rwe_gallery_plugin_action_links' );

?>