<?php
/**
 * GeneratePress child theme functions and definitions.
 *
 * Add your custom PHP in this file. 
 * Only edit this file if you have direct access to it on your server (to fix errors if they happen).
 */

function generatepress_child_enqueue_scripts() {
	if ( is_rtl() ) {
		wp_enqueue_style( 'generatepress-rtl', trailingslashit( get_template_directory_uri() ) . 'rtl.css' );
	}

	wp_enqueue_style('fontawesome-all', get_stylesheet_directory_uri() .'/css/all.min.css');


	wp_enqueue_script('custom-js', get_stylesheet_directory_uri() .'/js/custom.js', array('jquery'), null, true);

	if (current_user_can( 'update_core' )) {
	          return;
	      }
	      wp_deregister_style('dashicons');
}
add_action( 'wp_enqueue_scripts', 'generatepress_child_enqueue_scripts', 100 );

//remove related products
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );


//customize woo - remove breadcrumbs, remove image zoom and lightbox, remove product categories

function woo_remove_wc_breadcrumbs() {
	remove_theme_support( 'wc-product-gallery-zoom' );
	remove_theme_support( 'wc-product-gallery-lightbox' );
	remove_theme_support( 'wc-product-gallery-slider' );
    remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

   
}
add_action( 'wp', 'woo_remove_wc_breadcrumbs' );


//remove link on product image
function custom_single_product_image_html( $html, $post_id ) {
	$post_thumbnail_id = get_post_thumbnail_id( $post_id );
	
    return get_the_post_thumbnail( $post_thumbnail_id, apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ) );
}
add_filter('woocommerce_single_product_image_thumbnail_html', 'custom_single_product_image_html', 10, 2);

   
//change permalink structure of view profile
function change_the_gravityview_directory_endpoint( $endpoint ) {      
    return 'certified';  
}
add_filter('gravityview_directory_endpoint', 'change_the_gravityview_directory_endpoint'); 


//new endpoint for "edit your profile" tab in my account page
function hcc_add_edit_profile_endpoint() {
	add_rewrite_endpoint('edit-profile', EP_ROOT | EP_PAGES);
}
add_action('init', 'hcc_add_edit_profile_endpoint');

//add new query var
function hcc_edit_profile_vars($vars) {
	$vars[] = 'edit-profile';
	return $vars;
}
add_filter('query_vars', 'hcc_edit_profile_vars');


//new endpoint for "create profile" in my account 
function hcc_edit_profile_content() {
	$hcc_current_user = wp_get_current_user();

	if(!wcs_user_has_subscription($hcc_current_user->ID, '', 'active') && !current_user_can('edit_others_posts')) {
		?>
		<p>You must have an active Profile Subscription to access your profile. Please <a href="/my-account/subscriptions/">check your Profile Subscription status here.</a></p>
		<?php
	} else {
	//get entries from the logged in user
	$form_criteria = array (
		'status' => 'active',
		'field_filters' => array (
			array (
				'key' => 'created_by',
				'value' => $hcc_current_user->ID,
			)
		)

	);

	$hcc_form_id = 1;

	$entry_count = GFAPI::count_entries( $hcc_form_id, $form_criteria );
	if($entry_count > 0) {
		$userEntries = GFAPI::get_entries( $hcc_form_id, $form_criteria );
		foreach ($userEntries as $userEntry) {
			$userEntryID = $userEntry['id'];
		}

		$approved = gform_get_meta($userEntryID, 'is_approved');
		if($approved == '3') {
			?>
			<p>Your profile is being reviewed. Once your profile is approved you will be able to view and edit.</p>
			
			<?php
		} elseif($approved == '2') {
			
			$not_approved_info = gform_get_meta($userEntryID, 28);
				
				?>

				<p>We're sorry. Your profile is not approved.</p>
				<p>Reason for not approving profile: <?php echo $not_approved_info;?></p>
				<?php
		
		} else {
	
		?>

		<p>Profile Actions:</p>
		<div class="profile-actions-wrap">
			<div class="hcc-button hcc-view-button"><?php
			echo do_shortcode('[gv_entry_link action="read" entry_id="'.$userEntryID.'" view_id="20362"]<i class="fas fa-eye"></i> View Your Profile[/gv_entry_link]');
			?>
			</div>
			<div class="hcc-button hcc-edit-button">
			<?php
			echo do_shortcode('[gv_entry_link action="edit" entry_id="'.$userEntryID.'" view_id="20362"]<i class="fas fa-edit"></i> Edit Your Profile[/gv_entry_link]');
			?>
			</div>
		</div>
		<?php
		}


		} else {
			?>
			<p>Please create your new Health Coach Profile.<p>
			<a class="button" href="/create-a-profile/">Create Profile</a>
		<?php
	}
	
 }
	
}
add_action('woocommerce_account_edit-profile_endpoint', 'hcc_edit_profile_content');

//get current user and populate hidden field. Used in next function to check if user already has profile
function fill_field_with_current_user($value) {
	$hcc_current_user_object = wp_get_current_user();
	$hcc_current_user = $hcc_current_user_object->ID;
	return $hcc_current_user;
}
add_filter( 'gform_field_value_hccid', 'fill_field_with_current_user' );

//sets the notification on the "Contact Coach" form to go to the current profile email address
function hcc_email_coach_notification($notification, $form, $entry) {
	$currentUrl = $_SERVER['REQUEST_URI'];
	$currentParse = parse_url($currentUrl);
	$currentPath = $currentParse['path'];
	$currentPath_parts = explode('/', $currentPath);
	$currentProfile = $currentPath_parts[count($currentPath_parts)-2];
	$hccEntry = GFAPI::get_entry( $currentProfile );
	$hcc_email = $hccEntry[22];
	if($notification['name'] == 'Coach Notification') {
		$notification['toType'] = 'email';
		$notification['to'] = $hcc_email;
		
	}

	return $notification;
	
}

add_filter('gform_notification_2', 'hcc_email_coach_notification', 20, 3);

//prevent user from creating more than one profile
function hcc_prevent_multiple_profiles($validation_result) {
	$hcc_current_user_object = wp_get_current_user();
	$hcc_current_user = $hcc_current_user_object->ID;

	$form_criteria = array (
			'status' => 'active',
			'field_filters' => array (
				array (
					'key' => 'created_by',
					'value' => $hcc_current_user,
				)
			)

		);

		$hcc_form_id = 1;
		$entry_count = GFAPI::count_entries( $hcc_form_id, $form_criteria );

	//if user is on the edit screen, don't invalidate
	if(isset($_GET['edit']))  {
		if(rgpost('input_8') == $hcc_current_user ) {
			$validation_result['is_valid'] = true;
		}
		return $validation_result;
		//if user is tryng top create a new profile, invalidate
	} else {
		
		if(rgpost('input_8') == $hcc_current_user && current_user_can('edit_others_pages')) {
			$validation_result['is_valid'] = true;

		} elseif(rgpost('input_8') == $hcc_current_user && $entry_count > 0) {
			$validation_result['is_valid'] = false;
			$validation_result['message'] = "You have already created a Health Coach Profile. Only one profile per account is allowed.";
		}
		return $validation_result;
		
	}

	
}
add_action('gform_field_validation_1_8', 'hcc_prevent_multiple_profiles');

//change my account login page
function hcc_login_page() {
	?>
	<p class="medium-font bold">If you are a Dr. Sears Wellness Institute Certified Health Coach, login to signup, manage your coach profile, or get support.<p>
	<p>Not certified yet? You can become a health coach through our accredited program. <a href="https://www.drsearswellnessinstitute.org/health-coach-certification" rel="nofollow noopener">Become a Health Coach today.</a></p>
	<?php
}
add_action('woocommerce_before_customer_login_form', 'hcc_login_page');

//change my account dashboard  - check if user has purchased a profile and it's active. If not direct the user to buy it.
function hcc_change_account_dashboard() {
	//changed
	//$productID = 430;
	$productIDBeta = 2683;
	$productID = 19748;
	$product = wc_get_product($productID);
	if ( ! is_user_logged_in() ) return;
	$current_user = wp_get_current_user();
	
	if ( wc_customer_bought_product( $current_user->user_email, $current_user->ID, $productID) || wc_customer_bought_product( $current_user->user_email, $current_user->ID, $productIDBeta) ) { 
		if(wcs_user_has_subscription($current_user->ID, '', 'active')) {
				?>
				<p>Your Health Coach Profile subscription is active. You can <a href="/my-account/edit-profile/">create or edit your profile</a>.</p>
				<?php
			} else {
				
				?>
				<p>Your Health Coach Profile subscription is not active. <a href="/my-account/subscriptions/">Please check your subscription status here.</a></p>
				<?php
			}
			
			
		} else {
			//changed
			?>
			<p>Welcome to HealthCoachConnection.com. Please purchase your profile to get started.</p>
			<!-- <p>Welcome to HealthCoachConnection.com. Please get a test profile to get started (no purchase necessary for testing).</p> -->
			<a class="button" href="/product/health-coach-connection-profile-bundle/">Purchase Profile</a>
			<!-- <a class="button" href="/product/health-coach-connection-profile-bundle-beta/">Get Profile</a> -->
			<?php
		}

}
add_action('woocommerce_account_dashboard', 'hcc_change_account_dashboard');

 //change tabs on my account page
function hcc_change_my_account_tabs() {
//unset($items['downloads'],$items['edit-address'], $items['members-area'], $items['orders']);
	$my_account_tabs = array(
		'dashboard' => __('Dashboard'),
		'edit-profile' => __('Create or Edit Profile'),
		'subscriptions' => __('Subscription'),
		'payment-methods' => __('Payment Methods'),
		'customer-logout' => __('Logout')
	);

return $my_account_tabs;
}
add_filter( 'woocommerce_account_menu_items', 'hcc_change_my_account_tabs', 999 );


//remove membership page link on thankyou page
function hcc_remove_membership_thankyou() {
	if ( function_exists( 'wc_memberships' ) ) {
		remove_action( 'woocommerce_thankyou', array( wc_memberships()->get_frontend_instance(), 'maybe_render_thank_you_content' ), 9 );
	}
}

add_filter('init', 'hcc_remove_membership_thankyou');

// Removes Memberships "thank you" message from emails
function hcc_remove_email_thankyou() {
	if ( function_exists( 'wc_memberships' ) ) {
		remove_action( 'woocommerce_email_order_meta', array( wc_memberships()->get_emails_instance(), 'maybe_render_thank_you_content' ), 5, 2 );
	}
}
add_action( 'init', 'hcc_remove_email_thankyou' );

function hcc_change_subscription_thankyou_message($order) {
	
	       $thank_you_message = sprintf( __( '%sThank you for signing up! Next step: %sSetup your profile%s.%s%sYou can also %sContinue Shopping%s on the Coach Portal and setup your profile at a later time.%s', 'woocommerce-subscriptions' ), '<p>', '<a href="' . get_permalink( wc_get_page_id( 'myaccount' ) ) . '">', '</a>','</p>', '<p>', '<a href="https://drsearscoachportal.org/" target="_blank">', '</a>', '</p>' );

	       return $thank_you_message;
	    
	   
}
add_filter( 'woocommerce_subscriptions_thank_you_message', 'hcc_change_subscription_thankyou_message');


// function hcc_thankyou_coach_portal($order) {
// 	$thank_you_message = sprintf( __( '%sYou can also %scontinue shopping on the Coach Portal%s.%s', 'woocommerce-subscriptions' ), '<p>', '<a href="https://drsearscoachportal.org/">', '</a>','</p>' );

// 	return $thank_you_message;
// }
// add_filter( 'woocommerce_subscriptions_thank_you_message', 'hcc_thankyou_coach_portal');

//add sidebar and shortcode for featured area
function hcc_featured_sidebar() {
	register_sidebar(array(
		'name' => 'Featured Coaches',
		'id' => 'featured-coaches-sidebar',
		'before_widget' => '<div>',
		'after_widget' => '</div>'
	));
}
add_action('widgets_init', 'hcc_featured_sidebar');

//prevent profile from showing if subscription isnt active
function hcc_prevent_non_active_profile($subscription, $new_status, $old_status) {
	$hcc_user = $subscription->get_user_id();
	$hcc_form_id = 1;
	//get entries from the user
		$form_criteria = array (
			'status' => 'active',
			'field_filters' => array (
				array (
					'key' => 'created_by',
					'value' => $hcc_user,
				)
			)

		);
	$entry_count = GFAPI::count_entries( $hcc_form_id, $form_criteria );
	if($entry_count > 0) {
			$userEntries = GFAPI::get_entries( $hcc_form_id, $form_criteria );
			foreach ($userEntries as $userEntry) {

				$userEntryID = $userEntry['id'];
				if($new_status !== 'active' && $new_status !=='pending-cancel') {
					gform_update_meta($userEntryID, 'is_approved', '2');
				
					
				} else if($new_status == 'active' || $new_status == 'pending-cancel') {
					gform_update_meta($userEntryID, 'is_approved', '1');
					
				}
				
			}
		}
}
add_action('woocommerce_subscription_status_updated', 'hcc_prevent_non_active_profile', 10, 3);

//remove "continue shopping" button from added to cart notice
function hcc_remove_continue_button($message, $product_id) {
	

	foreach($product_id as $productId => $productValue) {
		$product = wc_get_product($productId);
		$name = $product->get_name();
	}
	
	$added_text = sprintf('%s has been added to your cart.', $name );
	 $message = sprintf(esc_html($added_text));
	return $message;
}
add_filter( 'wc_add_to_cart_message_html', 'hcc_remove_continue_button', 10, 2 );

// Removes Order Notes Title - Additional Information & Notes Field
add_filter( 'woocommerce_enable_order_notes_field', '__return_false', 9999 );

// Remove Order Notes Field
function remove_order_notes( $fields ) {
     unset($fields['order']['order_comments']);
     return $fields;
}
add_filter( 'woocommerce_checkout_fields' , 'remove_order_notes' );



//change text on Profile Page when profile is not approved
function gv_modify_text() {
	
	$hcc_current_user = wp_get_current_user();

	//wait for current user to become available
	if(!($hcc_current_user))
		return;
	
	
	$current_page_id = get_the_ID();
	
	//run only on single profile page
	if($current_page_id == 20362 && is_single()) {
	
	
	//get entries from the logged in user
	$form_criteria = array (
		'status' => 'active',
		'field_filters' => array (
			array (
				'key' => 'created_by',
				'value' => $hcc_current_user->ID,
			)
		)
	);

	$hcc_form_id = 1;

	$entry_count = GFAPI::count_entries( $hcc_form_id, $form_criteria );
	if($entry_count > 0) {
		$userEntries = GFAPI::get_entries( $hcc_form_id, $form_criteria );
		foreach ($userEntries as $userEntry) {
			$userEntryID = $userEntry['id'];
		}

		$approved = gform_get_meta($userEntryID, 'is_approved');
		if($approved == '3') {

			add_filter( 'gettext', 'pending_approval_filter', 20, 3 );
			
			} elseif($approved == '2') {
				global $not_approved_info;
				$not_approved_info = gform_get_meta($userEntryID, 28);
				if(!empty($not_approved_info)) {
					add_filter( 'gettext', 'not_approved_filter', 20, 3 );
					add_action('generate_after_entry_content', function() {
						global $not_approved_info;
						echo "<p>Reason for not approving profile: ".$not_approved_info."</p>";
						echo "<p>Go To Your Account Page: <a href='/my-account'>My Account</a></p>";
					});
				} else {
					add_filter( 'gettext', 'not_approved_public', 20, 3 );
					add_action('generate_after_entry_content', function() {
						//global $not_approved_info;
						
						echo '<p>This profile is not active. <a href="https://healthcoachconnection.com/find-a-health-coach/">Please try another search</a>.</p>';
					});
				}
				

			} else {
  	  }
	}
  }
	
}
add_action('wp', 'gv_modify_text');

//filter to change text when profile is not approved
function not_approved_filter($translated_text, $text, $domain) {
	if ( $translated_text === 'You are not allowed to view this content.') {
	   
	    $translated_text = __( "Your profile is not approved.", 'gravityview' );

	}
	return $translated_text;
}

//filter to show to public when profile is not approved
function not_approved_public($translated_text, $domain) {
	if ( $translated_text === 'You are not allowed to view this content.') {
	   
	    $translated_text = __( " ", 'gravityview' );

	}
	return $translated_text;
}

//filter to change text when profile is pending
function pending_approval_filter($translated_text, $text, $domain) {
	if ( $translated_text === 'You are not allowed to view this content.') {
	    
	    $translated_text = __( 'Your profile is being reviewed. Once your profile is approved you will be able to view and edit.<div class="hcc-button"><a href="/my-account/">Dashboard</a></div>', 'gravityview' );
	}
	return $translated_text;
}


//add custom checkbox to checkout page
function hcc_custom_checkbox() {
	woocommerce_form_field('hcc_communicate', array (
		'type' => 'checkbox',
		'class' => array('hcc-field form-row-wide'),
		'label' => 'You agree to respond to any inquiries with in 3 business days.',
		'required' => true,
	), WC()->checkout->get_value('hcc_communicate'));
	woocommerce_form_field('hcc_promote', array(
		'type' => 'checkbox',
		'class' => array('hcc-field form-row-wide'),
		'label' => 'You agree to not use this directory as a platform for promoting products. This includes offering free services for products purchased.',
		'required' => true,
	), WC()->checkout->get_value('hcc_promote'));
	
}

add_action( 'woocommerce_checkout_before_terms_and_conditions', 'hcc_custom_checkbox' );

//add notice if custom checkbox not checked
function hcc_custom_process_checkbox() {
	global $woocommerce;
	if(!$_POST['hcc_communicate'] || !$_POST['hcc_promote']) {
		wc_add_notice(__('Please agree to all terms and select the checkboxes'), 'error');
	}

}
add_action('woocommerce_checkout_process', 'hcc_custom_process_checkbox');


//save the checkout field in the order meta
function hcc_save_custom_field($order_id) {
	if(!empty($_POST['hcc_communicate'])) {
		update_post_meta($order_id, 'hcc_communicate', $_POST['hcc_communicate']);
	}
	if(!empty($_POST['hcc_promote'])) {
		update_post_meta($order_id, 'hcc_promote', $_POST['hcc_promote']);
	}
	
}
add_action('woocommerce_checkout_update_order_meta', 'hcc_save_custom_field');


//when a coach purchases featured subscription, automatically set profile as featured
function hcc_set_entry_as_featured($entry, $form) {
	
	$entry_id = $entry['id'];
	$hcc_current_user = get_current_user_id();
	$customer_orders = wc_get_orders(array(
		'customer_id' => $hcc_current_user
	));

	$last_order_data = $customer_orders[0]->get_data();
	
	extract($last_order_data);

	//for featured subscription as part of variable product
	foreach($line_items as $item_key => $item) {
		$meta_data = $item->get_meta_data();
		if($meta_data) {
			foreach($meta_data as $meta_data_key => $meta_data_value) {
				
				if(strpos($meta_data_value->value, 'Featured') !== false) {
					
						 GFAPI::update_entry_property( $entry['id'], 'is_starred', 1 );		
				return;
				} else {
					
					GFAPI::update_entry_property( $entry['id'], 'is_starred', 0 );
				}
			}
		}
	}

	//for featured subscription add-on product
	foreach($line_items as $item_key => $item) {

		$hcc_product_id = $item->get_product_id();
		if($hcc_product_id == '19747' || $hcc_product_id == '19855') {
			GFAPI::update_entry_property( $entry['id'], 'is_starred', 1 );
			return;
		} else {
			GFAPI::update_entry_property( $entry['id'], 'is_starred', 0 );
		}
	}
	
}
add_action('gform_after_submission_1', 'hcc_set_entry_as_featured', 10, 2);


function hcc_expired_featured($subscription, $new_status, $old_status) {
	$hcc_user = $subscription->get_user_id();
	$hcc_form_id = 1;
	//get entries from the user
		$form_criteria = array (
			'status' => 'active',
			'field_filters' => array (
				array (
					'key' => 'created_by',
					'value' => $hcc_user,
				)
			)

		);

		if($entry_count > 0) {
				$userEntries = GFAPI::get_entries( $hcc_form_id, $form_criteria );
				foreach ($userEntries as $userEntry) {

					$userEntryID = $userEntry['id'];
					$userEntryFeatured = $userEntry['is_starred'];
					if($new_status == 'expired' && $userEntryFeatured == '1') {
						GFAPI::update_entry_property( $entry['id'], 'is_starred', 0 );
					
						
					} 
					
				}
			}
}
add_action('woocommerce_subscription_status_updated', 'hcc_expired_featured', 10, 3);
  
function hcc_woocommerce_quantity_changes( $args, $product ) {
  
   if ( ! is_cart() ) {
 		if($product->get_id() == '19747') {
 			$args['input_value'] = 0;
 		} else {
 			$args['input_value'] = 1; // Start from this value (default = 1) 
 			$args['max_value'] = 1; // Max quantity (default = -1)
 			$args['min_value'] = 0; // Min quantity (default = 0)
 		}

     
 
 
   } else {
 
      // Cart's "min_value" is already 0 and we don't need "input_value"
      $args['max_value'] = 1; // Max quantity (default = -1)
     
      // ONLY ADD FOLLOWING IF STEP < MIN_VALUE
      $args['min_value'] = 0; // Min quantity (default = 0)
 
   }
  
   return $args;
  
}
add_filter( 'woocommerce_quantity_input_args', 'hcc_woocommerce_quantity_changes', 10, 2 );

function hcc_featured_entries_shortcode() {
	$hcc_form_id = 1;
	$form_criteria = array (
		'status' => 'active',

	);

	$form_criteria['field_filters'][] = array('key' => 'is_starred', 'value' => '1');

	$hcc_featured_entries = GFAPI::get_entries( $hcc_form_id, $form_criteria );

	$featured_entries =  '<div class="featured-home">';
	$featured_entries .=  '<ul>';

		foreach ($hcc_featured_entries as $hcc_featured_entry) {
			$hcc_featured_entry_id = $hcc_featured_entry['id'];
			$hcc_featured_entry_image = $hcc_featured_entry['5'];
			$approved = gform_get_meta($hcc_featured_entry_id, 'is_approved');
			if($approved == '1') {
						//print_r($hcc_featured_entry);
						if($hcc_featured_entry['34'] == 'Display City, State, and Zip only') {
							$hcc_featured_city = $hcc_featured_entry['2.3'];
							$hcc_featured_state = $hcc_featured_entry['2.4'];
						} else {
							$hcc_featured_city = $hcc_featured_entry['30.3'];
							$hcc_featured_state = $hcc_featured_entry['30.4'];
						}
			$featured_entries .= '<li>';
			$featured_entries .= '<div><a href="https://healthcoachconnection.com/find-a-health-coach/certified/'.$hcc_featured_entry_id .'"><img src="'.$hcc_featured_entry_image .'"></a></div>';
			$featured_entries .= '<a href="https://healthcoachconnection.com/find-a-health-coach/certified/'.$hcc_featured_entry_id .'">';
			$featured_entries .= $hcc_featured_entry['1.3'] ." ".$hcc_featured_entry['1.6'] ." - ". $hcc_featured_city .", ". $hcc_featured_state;
			$featured_entries .= '</a>';
			$featured_entries .= '</li>';
			?>
			
			<?php
		}
		
	}
	
	$featured_entries .=  '</ul>';
	$featured_entries .= '</div>';
	?>

	
	<?php

	return $featured_entries;
}

add_shortcode('hcc-featured', 'hcc_featured_entries_shortcode');

// function hcc_footer_disclaimer() {
// 	echo '<div class="footer-disc-wrap"><p class="footer-disc">The Dr. Sears Wellness Institute does not accept any responsibility or liability for the information or services received through this website. We will not assume liability to any party for losses, costs, liabilities, expenses, personal injuries, accidents, misapplication of information, or any other loss, condition, or otherwise.</p></div>';
// }
// add_action('generate_after_footer_content', 'hcc_footer_disclaimer');

//change "in stock" to "spots available"

function hcc_customizing_stock_availability_text( $availability, $product ) {
    if ( ! $product->is_in_stock() ) {
        $availability = __( 'Sold Out', 'woocommerce' );
    }
    elseif ( $product->managing_stock() && $product->is_on_backorder( 1 ) )
    {
        $availability = $product->backorders_require_notification() ? __( 'Available on backorder', 'woocommerce' ) : '';
    }
    elseif ( $product->managing_stock() )
    {
        $availability = __( 'Available!', 'woocommerce' );
        $stock_amount = $product->get_stock_quantity();

        switch ( get_option( 'woocommerce_stock_format' ) ) {
            case 'low_amount' :
                if ( $stock_amount <= get_option( 'woocommerce_notify_low_stock_amount' ) ) {
                    /* translators: %s: stock amount */
                    $availability = sprintf( __( '%s Spots Available', 'woocommerce' ), wc_format_stock_quantity_for_display( $stock_amount, $product ) );
                }
            break;
            case '' :
                /* translators: %s: stock amount */
                $availability = sprintf( __( '%s Spots Available!', 'woocommerce' ), wc_format_stock_quantity_for_display( $stock_amount, $product ) );
            break;
        }

        if ( $product->backorders_allowed() && $product->backorders_require_notification() ) {
            $availability .= ' ' . __( '(can be backordered)', 'woocommerce' );
        }
    }
    else
    {
        $availability = '';
    }

    return $availability;
}
add_filter( 'woocommerce_get_availability_text', 'hcc_customizing_stock_availability_text', 1, 2);

//add prefix to order number


function hcc_change_woocommerce_order_number( $order_id ) {
    $prefix = 'hcc';
    $new_order_id = $prefix . $order_id;
    return $new_order_id;
}
add_filter( 'woocommerce_order_number', 'hcc_change_woocommerce_order_number' );