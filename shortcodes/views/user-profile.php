<?php

if (!defined('ABSPATH')) exit;

function wovax_idx_get_content_user_profile() {

	global $current_user;

	if (is_user_logged_in()) {
		get_currentuserinfo();
		$user_firstname = $current_user->user_firstname;
		$user_lastname  = $current_user->user_lastname;
		$user_email     = $current_user->user_email;
		$user_phone     = get_user_meta( $current_user->data->ID, 'phone', true );
		$user_login     = $current_user->user_login;
		$user_ID        = $current_user->data->ID;
		$user_searches  = get_user_meta($user_ID, 'wovax-idx-searches');
    	$saved_searches = json_decode($user_searches[0]);
  	} else {
		return;
	}

	$saved_search_rows = '';
	foreach($saved_searches as $id => $search) {
		$saved_search_rows .= "<tr><td><a target='_blank' href='".$search[1]."'>".$search[0]."</a></td><td><a class='wovax-idx-saved-search-delete' href='#'>Delete</a></td></tr>";
	}
	
	ob_start();
	?>
<div class="wrap">
	<h2 class="wovax-idx-nav-wrapper nav-tab-wrapper">
		<a class="wovax-nav-tab nav-tab-active" href="#">User Details</a>
		<a class="wovax-nav-tab" href="#">Saved Searches</a>
		<a class="wovax-nav-tab" href="#">Edit User</a>
	</h2>
	<div id="wovax-idx-user-sections">
		<section id="wovax-idx-user-details">
			<table>
				<tr>
					<th>Name</th>
					<td><?php echo $user_firstname. ' ' . $user_lastname; ?></td>
				</tr>
				<tr>
					<th>Email</th>
					<td><?php echo $user_email; ?></td>
				</tr>
				<tr>
					<th>Phone</th>
					<td><?php echo $user_phone; ?></td>
				</tr>
				<tr>
					<th>Username</th>
					<td><?php echo $user_login; ?></td>
				</tr>
			</table>	
		</section>
		<section id="wovax-idx-user-saved-searches" hidden>
			<table class="wovax-idx-saved-search-display">
				<?php echo $saved_search_rows; ?>
			</table>
		</section>
		<section id="wovax-idx-user-change" hidden>
			<div class="col-4">
				<form class="wovax-idx-form" id="wovax-idx-form-1">

					<div class="wovax-idx-section">
						<label for="first-name">First Name</label>
								<input type="text" id="first-name" value="<?php echo esc_attr($user_firstname); ?>">
					</div>

					<div class="wovax-idx-section">
						<label for="last-name">Last Name</label>
								<input type="text" id="last-name" value="<?php echo esc_attr($user_lastname); ?>">
					</div>

					<div class="wovax-idx-section">
						<label for="email">Email</label>
								<input type="email" id="email" value="<?php echo esc_attr($user_email); ?>">
					</div>

					<div class="wovax-idx-section">
						<label for="phone">Phone</label>
								<input type="tel" id="phone" value="<?php echo esc_attr($user_phone); ?>">
					</div>

					<div class="wovax-idx-section">
						<label for="password">New Password</label>
								<input type="password" id="password">
					</div>

					<div class="wovax-idx-section">
						<label for="verify-password">Verify New Password</label>
								<input type="password" id="verify-password">
					</div>

					<div class="wovax-idx-section">
						<label for="username"></label>
								<input type="text" id="username" value="<?php echo esc_attr($user_login); ?>" disabled="">
					</div>

					<div class="wovax-idx-section">
						<div class="wovax-idx-form-button">
						<a href="#" id="update_profile_button" class="wovax-idx-button wovax-idx-button-highlight">Update</a>
						</div>
					</div>
				</form>
			</div>
		</section>		
	</div>
</div>
  	<script>
		jQuery("a#update_profile_button").on("click",function(){
			var id = '<?php echo esc_html($user_ID); ?>';
			var url = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
			var firstname = jQuery("input#first-name").val();
			var lastname = jQuery("input#last-name").val();
			var email = jQuery("input#email").val();
			var password = jQuery("input#password").val();
			var verify_password = jQuery("input#verify-password").val();
			var phone = jQuery("input#phone").val();
			var username = jQuery("input#username").val();

			var request = jQuery.ajax({ url: url,
									method: "POST",
									dataType: "application/json",
									data: {   id : id,
											firstname : firstname,
											lastname : lastname,
											email : email,
											password : password,
											verify_password : verify_password,
											phone : phone,
											username : username,
											action : "update_profile" }
									});
			request.done(function( response ) {

			});
			request.fail(function( responseText ) {
				window.location.reload();
			});
		});

		jQuery('.wovax-idx-nav-wrapper.nav-tab-wrapper a').click(function() {
			jQuery('section').hide();
			jQuery('section').eq(jQuery(this).index()).show();
			jQuery('.nav-tab-wrapper a').removeClass("nav-tab-active");
			jQuery(this).addClass("nav-tab-active");
			return;
		});

		jQuery('.wovax-idx-saved-search-delete').click(function(event) {
			event.preventDefault();

			var url = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
			var search = jQuery(this).closest('tr').find(':first-child a').text();
			var search_url = jQuery(this).closest('tr').find('a').attr('href');

			jQuery(this).closest('tr').hide('slow');

			let data = {
                    action: 'wovax_idx_delete_saved_search',
                    data: {
                        name: search,
						search_url: search_url,
                    }
            }

			jQuery.ajax({ 
				url: url,
				method: "POST",
				dataType: "application/json",
				data: data,
				success: function(response) {
                        console.log(response);
                }
			});
			
		});
	</script>
	<?php
	$output = ob_get_clean();			
	return $output;

}

?>
