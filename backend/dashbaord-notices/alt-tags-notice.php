//DASH MESSAGE
function alttxt_admin_notice(){
    global $pagenow;
    if ( $pagenow == 'index.php' ) {
         echo '<div class="notice notice-warning is-dismissible">
             <h3 class="mb-10"><strong>Please add Alt text to images</strong></h3>
			 <p>Please ensure to include Alt text when <a href="https://ibb.co/cXFdDvy" target="_blank">uploading images to the Media Library</a>. Alt text is used as an alternative to an image for people who use assistive technology, like screen reading software.<br/><strong>We are legally required to make our <a href="https://www.gov.uk/guidance/content-design/images#:~:text=in%20easy%20reads.-,Alt,-text" target="_blank">content as accessible as possible</a></strong>.</p>
         </div>';
    }
}
add_action('admin_notices', 'alttxt_admin_notice');

function alttxt_admin_notice_upload(){
    global $pagenow;
    if ( $pagenow == 'upload.php' ) {
         echo '<div class="notice notice-warning is-dismissible">
             <h3 class="mb-10"><strong>Please add Alt text to images once uploaded</strong></h3>
			 <p>Please ensure to include Alt text when uploading images to the Media Library. Alt text is used as an alternative to an image for people who use assistive technology, like screen reading software.<br/><strong>We are legally required to make our <a href="https://www.gov.uk/guidance/content-design/images#:~:text=in%20easy%20reads.-,Alt,-text" target="_blank">content as accessible as possible</a></strong>.</p>
         </div>';
    }
}
add_action('admin_notices', 'alttxt_admin_notice_upload');
