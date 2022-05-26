// CITY OF CULTURE STYLES
function city_of_culture_landing_css() {
	if(is_page_template('cov-city-of-culture-21.php')) {
    //wp_enqueue_style( 'city-of-culture', get_stylesheet_directory_uri() . '/assets/pages/city-culture-21/css/city-of-culture.css',false,'1.2.4','all');
	wp_enqueue_style( 'city-of-culture', 'https://cdn.jsdelivr.net/gh/ellodave/Coventry-College/city-of-culture21/css/city-of-culture.min.css',false,'1.2.4','all');
	}
}
add_action( 'wp_enqueue_scripts', 'city_of_culture_landing_css' );

// CITY OF CULTURE JS
function city_of_culture_landing_js() {
	if(is_page_template('cov-city-of-culture-21.php')) {
   // wp_enqueue_script( 'cov2021-bundle', get_stylesheet_directory_uri() . '/assets/js/cov2021-bundle.js', array(), '1.0.3', true );
	  wp_enqueue_script( 'cov2021-bundle', 'https://cdn.jsdelivr.net/gh/ellodave/Coventry-College@latest/city-of-culture21/js/cov21-bundle.min.js', array(), '1.0.3', true );
	}
}
add_action( 'wp_enqueue_scripts', 'city_of_culture_landing_js' );
