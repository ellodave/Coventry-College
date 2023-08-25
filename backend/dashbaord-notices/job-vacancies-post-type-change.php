function jobVacanciesNotice() {
    $user = wp_get_current_user();
    
    // Check if the user has the required roles
    if (in_array('editor', $user->roles) || in_array('hr-staff', $user->roles)) {
        
        // Check if the current page is 'edit.php'
        if (basename($_SERVER['PHP_SELF']) === 'edit.php') {
            echo '<div class="notice notice-info" style="padding:1.5rem 1rem">
             <h3 class="mb-10"><strong>Job Vacancies have changed location</strong></h3>
			 <p>Vacancy posts are now managed from a dedicated page. New vacancies should be posted under Job Vacancies in your dashboard.</p>
			 <a class="button button-primary" role="button" href="/wp-admin/edit.php?post_type=vacancy">Take me there</a>
         </div>';
        }
    }
}
add_action('admin_notices', 'jobVacanciesNotice');
