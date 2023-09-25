//ENROLMENT CROWDHANDLER
function enrolmentQueue() {
	if(is_page_template('enrolment.php')) {
	wp_enqueue_script( 'enrolment-crowdhandler', 'https://wait.crowdhandler.com/js/latest/main.js?id=c2802edb5090c33d91bbdd7435de71387abca97147f599b18d14a6474bc0698e', array(), '1.0.0', false );
	}
}
add_action( 'wp_enqueue_scripts', 'enrolmentQueue' );
