function maintenance_admin_notice(){
    global $pagenow;
    if ( $pagenow == 'index.php' ) {
         echo '<div class="notice notice-error is-dismissible">
             <h3 class="mb-10"><strong>Scheduled Maintenance in Progress</strong></h3>
			 <p>The College website is currently unavailable for maintenance and updates. <strong>Please do not make any content changes as they may not be applied</strong>.</p>
         </div>';
    }
}
add_action('admin_notices', 'maintenance_admin_notice');
