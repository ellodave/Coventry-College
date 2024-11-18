<?php
/**
 * Plugin Name: Open Event Attendance Sign-in
 * Description: A plugin to handle CSV imports of attendees, search functionality, and marking attendance for an event.
 * Version: 1.3
 * Author: Richard Henney
 */

defined('ABSPATH') or die('Direct access not allowed');

global $open_event_attendance_db_version;
$open_event_attendance_db_version = '1.3';

function open_event_debug_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG === true) {
        $log_message = '[Open Event Attendance] ' . $message;
        if ($data !== null) {
            $log_message .= ': ' . print_r($data, true);
        }
        error_log($log_message);
    }
}

// Extend nonce lifetime for this specific action
function open_event_modify_nonce_lifecycle() {
    add_filter('nonce_life', function($life, $action) {
        if ($action === 'public_event_attendance_nonce') {
            return DAY_IN_SECONDS;
        }
        return $life;
    }, 10, 2);
}
add_action('init', 'open_event_modify_nonce_lifecycle');

function open_event_attendance_install_or_update() {
    global $wpdb;
    global $open_event_attendance_db_version;

    $table_name = $wpdb->prefix . 'event_attendance';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        first_name varchar(100) NOT NULL,
        last_name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        age_range varchar(10) NOT NULL,
        registration_type ENUM('pre-registered', 'walk-in') DEFAULT 'pre-registered',
        attended BOOLEAN DEFAULT FALSE,
        date_attended datetime DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Add columns if they don't exist
    $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '$table_name' AND COLUMN_NAME = 'attended'");
    if(empty($row)){
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN attended BOOLEAN DEFAULT FALSE");
    }

    $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '$table_name' AND COLUMN_NAME = 'registration_type'");
    if(empty($row)){
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN registration_type ENUM('pre-registered', 'walk-in') DEFAULT 'pre-registered'");
    }

    // Handle status column migration
    $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '$table_name' AND COLUMN_NAME = 'status'");
    if(!empty($row)){
        $wpdb->query("UPDATE $table_name SET attended = TRUE, registration_type = 'walk-in' WHERE status = 'attended'");
        $wpdb->query("UPDATE $table_name SET registration_type = 'pre-registered' WHERE status = 'pre-registered'");
        $wpdb->query("ALTER TABLE $table_name DROP COLUMN status");
    }

    update_option('open_event_attendance_db_version', $open_event_attendance_db_version);
}

register_activation_hook(__FILE__, 'open_event_attendance_install_or_update');

function open_event_attendance_update_db_check() {
    global $open_event_attendance_db_version;
    if (get_site_option('open_event_attendance_db_version') != $open_event_attendance_db_version) {
        open_event_attendance_install_or_update();
    }
}
add_action('plugins_loaded', 'open_event_attendance_update_db_check');

function open_event_enqueue_scripts() {
    $version = time(); // Use timestamp for cache busting
    wp_enqueue_script(
        'open-event-scripts', 
        plugins_url('/js/script.js', __FILE__), 
        array('jquery'), 
        $version, 
        true
    );
    
    wp_localize_script('open-event-scripts', 'ajax_object', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('public_event_attendance_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'open_event_enqueue_scripts');

function open_event_enqueue_styles() {
    $css_file = plugin_dir_path( __FILE__ ) . 'css/style.css';
    $version = file_exists( $css_file ) ? filemtime( $css_file ) : '1.0.0';

    wp_enqueue_style( 
        'open-event-styles',
        plugins_url( 'css/style.css', __FILE__ ),
        array(),
        $version,
        'all'
    );
}
add_action('wp_enqueue_scripts', 'open_event_enqueue_styles');

function format_name($name) {
    $name = strtolower($name);
    
    if (strpos($name, '-') !== false) {
        return implode('-', array_map('ucfirst', explode('-', $name)));
    }
    
    return implode(' ', array_map('ucfirst', explode(' ', $name)));
}

// AJAX Handlers with Updated Security
function open_event_search_attendee() {
    open_event_debug_log('Search attendee request received', $_POST);
    check_ajax_referer('public_event_attendance_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'event_attendance';
    $search_type = sanitize_text_field($_POST['search_type']);
    $search_value = sanitize_text_field($_POST['search_value']);

    if ($search_type === 'email') {
        $attendees = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE email = %s", $search_value));
    } else {
        $attendees = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE last_name LIKE %s", '%' . $wpdb->esc_like($search_value) . '%'));
    }

    ob_start();
    if ($attendees) {
        echo '<h3>Matching Registrations:</h3>';
        echo '<ul class="matching-registration-list">';
        foreach ($attendees as $attendee) {
            echo '<li>';
            echo '<input type="radio" name="selected_attendee" value="' . esc_attr($attendee->id) . '" required>';
            echo esc_html($attendee->first_name . ' ' . $attendee->last_name . ' (' . $attendee->email . ')');
            echo '</li>';
        }
        echo '</ul>';
        echo '<button class="btn--primary" type="submit" name="confirm_attendance">I have Attended</button>';
    } else {
        echo '<p class="error">No registrations matching this email were found.</p>';
    }
    $html = ob_get_clean();

    wp_send_json([
        'success' => true,
        'html' => $html,
        'count' => count($attendees)
    ]);
}
add_action('wp_ajax_search_attendee', 'open_event_search_attendee');
add_action('wp_ajax_nopriv_search_attendee', 'open_event_search_attendee');

function open_event_process_attendance() {
    open_event_debug_log('Process attendance request received', $_POST);
    check_ajax_referer('public_event_attendance_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'event_attendance';

    if (isset($_POST['selected_attendee'])) {
        // Pre-registered attendee
        $attendee_id = intval($_POST['selected_attendee']);
        $current_time = current_time('mysql');
        
        $result = $wpdb->update(
            $table_name,
            ['attended' => true, 'date_attended' => $current_time],
            ['id' => $attendee_id]
        );
        
        if ($result === false) {
            wp_send_json(['success' => false, 'message' => 'Database update failed.']);
            return;
        }
        
        $message = '<div class="confirmation-message success">
            <h2 class="h3">Thank You!</h2>
            <p>Attendance confirmed successfully.</p>
            <button class="btn--white" type="button" id="reset-form-button" style="margin-top: 20px;">Start New Sign-in</button>
        </div>';
    } elseif (isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) && isset($_POST['age_range'])) {
        // Walk-in registration
        $result = $wpdb->insert(
            $table_name,
            [
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'email' => sanitize_email($_POST['email']),
                'age_range' => sanitize_text_field($_POST['age_range']),
                'registration_type' => 'walk-in',
                'attended' => true,
                'date_attended' => current_time('mysql')
            ]
        );
        
        if ($result === false) {
            wp_send_json(['success' => false, 'message' => 'Database insert failed.']);
            return;
        }
        
        $message = '<div class="confirmation-message success">
            <h2 class="h3">Thank You!</h2>
            <p>Walk-in attendance confirmed successfully.</p>
            <button class="btn--white" type="button" id="reset-form-button" style="margin-top: 20px;">Start New Sign-in</button>
        </div>';
    } else {
        wp_send_json(['success' => false, 'message' => 'Invalid request.']);
        return;
    }
    wp_send_json(['success' => true, 'message' => $message]);
}
add_action('wp_ajax_process_attendance', 'open_event_process_attendance');
add_action('wp_ajax_nopriv_process_attendance', 'open_event_process_attendance');

function open_event_check_email_exists() {
    open_event_debug_log('Check email exists request received', $_POST);
    check_ajax_referer('public_event_attendance_nonce', 'nonce');

    $email = sanitize_email($_POST['email']);
    
    if (!$email) {
        wp_send_json([
            'success' => false,
            'message' => 'Invalid email address provided.'
        ]);
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'event_attendance';
    
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE email = %s AND registration_type = 'pre-registered'",
        $email
    ));

    wp_send_json([
        'success' => true,
        'exists' => (bool)$exists
    ]);
}
add_action('wp_ajax_check_email_exists', 'open_event_check_email_exists');
add_action('wp_ajax_nopriv_check_email_exists', 'open_event_check_email_exists');

function open_event_upload_csv() {
    if (!current_user_can('editor') && !current_user_can('administrator')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    ?>
    <div class="wrap">
        <h2>Upload CSV of Pre-Registrations</h2>
        <p>
            Upload a CSV of open event pre-registrations.
        </p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('open_event_csv_upload', 'open_event_csv_nonce'); ?>
            <input type="file" name="csv_file" accept=".csv" />
            <input type="submit" name="upload_csv" value="Upload CSV" class="button-primary" />
        </form>
    </div>
    <div>
        <h3>Format of CSV</h3>
        <p>Ensure that the column headers are titled the same as the below example:</p>
        <aside class="notice notice-warning" style="display: inline-block;margin-left: 0;margin-block: 2em;">
            <p>Remove any other columns from the CSV before uploading.</p>
        </aside>
        <table>
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>
    <style>
        table {
            border:1px solid #b3adad;
            border-collapse:collapse;
            padding:8px;
        }
        table th {
            border:1px solid #b3adad;
            padding:8px;
            background: #f0f0f0;
            color: #313030;
        }
        table td {
            border:1px solid #b3adad;
            text-align:center;
            padding:8px;
            background: #ffffff;
            color: #313030;
        }
    </style>
    <?php

    if (isset($_POST['upload_csv']) && !empty($_FILES['csv_file']['tmp_name'])) {
        if (!wp_verify_nonce($_POST['open_event_csv_nonce'], 'open_event_csv_upload')) {
            die('Security check failed');
        }

        $csv_file = $_FILES['csv_file']['tmp_name'];
        $duplicates = array();
        $imported = 0;
        $csv_records = array();
        $existing_records = array();
        $formatted_count = 0;

        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance';

        $existing_data = $wpdb->get_results("
            SELECT LOWER(first_name) as first_name, 
                   LOWER(last_name) as last_name, 
                   LOWER(email) as email,
                   age_range
            FROM $table_name
        ");

        foreach ($existing_data as $record) {
            $key = $record->first_name . '|' . $record->last_name . '|' . $record->email;
            $existing_records[$key] = true;
        }

        if (($handle = fopen($csv_file, "r")) !== FALSE) {
            fgetcsv($handle); // Skip header row

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (empty($data[0]) || empty($data[1]) || empty($data[2])) {
                    continue;
                }

                $original_first_name = trim($data[0]);
                $original_last_name = trim($data[1]);
                $formatted_first_name = format_name($original_first_name);
                $formatted_last_name = format_name($original_last_name);

                if ($original_first_name !== $formatted_first_name || 
                    $original_last_name !== $formatted_last_name) {
                    $formatted_count++;
                }

                $first_name_lower = strtolower($formatted_first_name);
                $last_name_lower = strtolower($formatted_last_name);
                $email = strtolower(trim(sanitize_email($data[2])));
                $age_range = isset($data[3]) ? sanitize_text_field($data[3]) : '';

                $key = $first_name_lower . '|' . $last_name_lower . '|' . $email;

                if (isset($csv_records[$key])) {
                    if (!isset($duplicates[$key])) {
                        $duplicates[$key] = array(
                            'first_name' => $formatted_first_name,
                            'last_name' => $formatted_last_name,
                            'email' => $email,
                            'age_range' => $age_range,
                            'original_name' => $original_first_name . ' ' . $original_last_name,
                            'count' => 2
                        );
                    } else {
                        $duplicates[$key]['count']++;
                    }
                    continue;
                }

                if (isset($existing_records[$key])) {
                    if (!isset($duplicates[$key])) {
                        $duplicates[$key] = array(
                            'first_name' => $formatted_first_name,
                            'last_name' => $formatted_last_name,
                            'email' => $email,
                            'age_range' => $age_range,
                            'original_name' => $original_first_name . ' ' . $original_last_name,
                            'count' => 1,
                            'in_db' => true
                        );
                    }
                    continue;
                }

                $csv_records[$key] = array(
                    'first_name' => $formatted_first_name,
                    'last_name' => $formatted_last_name,
                    'email' => $email,
                    'age_range' => $age_range,
                    'original_name' => $original_first_name . ' ' . $original_last_name
                );
            }
            fclose($handle);

            foreach ($csv_records as $record) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'first_name' => $record['first_name'],
                        'last_name' => $record['last_name'],
                        'email' => $record['email'],
                        'age_range' => $record['age_range'],
                        'registration_type' => 'pre-registered',
                        'attended' => false
                    )
                );
                $imported++;
            }

            echo '<div class="updated">';
            echo '<p>CSV import completed:</p>';
            echo '<ul>';
            echo '<li>Successfully imported: ' . $imported . ' records</li>';
            echo '<li>Duplicates found: ' . count($duplicates) . ' records</li>';
            echo '<li>Names reformatted: ' . $formatted_count . ' records</li>';
            echo '</ul>';

            if (!empty($duplicates)) {
                echo '<div class="duplicate-details" style="margin-top: 15px;">';
                echo '<h3>Details of Duplicate Records:</h3>';
                echo '<table class="widefat" style="margin-top: 10px;">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Original Name</th>';
                echo '<th>Formatted Name</th>';
                echo '<th>Email</th>';
                echo '<th>Age Range</th>';
                echo '<th>Occurrences</th>';
                echo '<th>Status</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($duplicates as $duplicate) {
                    echo '<tr>';
                    echo '<td>' . esc_html($duplicate['original_name']) . '</td>';
                    echo '<td>' . esc_html($duplicate['first_name'] . ' ' . $duplicate['last_name']) . '</td>';
                    echo '<td>' . esc_html($duplicate['email']) . '</td>';
                    echo '<td>' . esc_html($duplicate['age_range']) . '</td>';
                    echo '<td>' . esc_html($duplicate['count']) . '</td>';
                    echo '<td>' . (isset($duplicate['in_db']) ? 'Already in database' : 'Multiple in CSV') . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
            }
            
            if ($formatted_count > 0) {
                echo '<div class="name-formatting-details" style="margin-top: 15px;">';
                echo '<h3>Name Formatting Applied</h3>';
                echo '<p>Names were automatically formatted to ensure consistent capitalization.</p>';
                echo '</div>';
            }
            
            echo '</div>';
        } else {
            echo '<div class="error"><p>Failed to upload CSV file.</p></div>';
        }
    }
}

function open_event_attendance_menu() {
    add_menu_page('Event Attendance', 'Event Attendance', 'edit_pages', 'open-event-attendance', 'open_event_view_attendance');
    add_submenu_page('open-event-attendance', 'Upload CSV', 'Upload CSV', 'edit_pages', 'upload-csv', 'open_event_upload_csv');
}
add_action('admin_menu', 'open_event_attendance_menu');

function open_event_clear_database() {
    if (!current_user_can('editor') && !current_user_can('administrator')) {
        wp_die('You do not have sufficient permissions to perform this action.');
    }

    check_admin_referer('clear_event_attendance_nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'event_attendance';
    
    $result = $wpdb->query("TRUNCATE TABLE $table_name");
    
    if ($result !== false) {
        add_settings_error(
            'open_event_messages',
            'open_event_message',
            'Database cleared successfully.',
            'updated'
        );
    } else {
        add_settings_error(
            'open_event_messages',
            'open_event_message',
            'Error clearing database: ' . $wpdb->last_error,
            'error'
        );
    }
}

function open_event_view_attendance() {
    if (!current_user_can('editor') && !current_user_can('administrator')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Handle clear database action
    if (isset($_POST['clear_database']) && check_admin_referer('clear_event_attendance_nonce')) {
        open_event_clear_database();
    }

    // Display any error/update messages
    settings_errors('open_event_messages');

    global $wpdb;
    $table_name = $wpdb->prefix . 'event_attendance';
    
    // Get total records count
    $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    
    // Get the attendees list
    $attendees = $wpdb->get_results("SELECT * FROM $table_name ORDER BY first_name ASC, last_name ASC");

    ?>
    <div class="wrap">
        <h2>Attendees List</h2>
        
        <div class="tablenav top">
            <div class="alignleft actions" style="display: flex;">
                <!-- Export CSV Button -->
                <form method="post" style="display: inline-block; margin-right: 10px;">
                    <input type="submit" name="export_csv" value="Export as CSV" class="button-primary" />
                </form>

                <!-- Clear Database Button -->
                <form method="post" style="display: inline-block;" onsubmit="return confirm('WARNING: This will permanently delete all registration data. This action cannot be undone. Are you sure you want to continue?');">
                    <?php wp_nonce_field('clear_event_attendance_nonce'); ?>
                    <input type="submit" name="clear_database" value="Clear All Data" class="button-secondary" />
                </form>
            </div>
            <br class="clear">
        </div>

        <!-- Records Count Section -->
        <div class="records-summary" style="margin: 20px 0; padding: 10px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
            <strong>Total Records: </strong><?php echo number_format($total_records); ?>
        </div>

        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Age Range</th>
                    <th>Registration Type</th>
                    <th>Attended</th>
                    <th>Date Attended</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendees as $attendee): ?>
                    <tr>
                        <td><?php echo esc_html($attendee->first_name); ?></td>
                        <td><?php echo esc_html($attendee->last_name); ?></td>
                        <td><?php echo esc_html($attendee->email); ?></td>
                        <td><?php echo esc_html($attendee->age_range); ?></td>
                        <td><?php echo esc_html($attendee->registration_type); ?></td>
                        <td><?php echo $attendee->attended ? 'Yes' : 'No'; ?></td>
                        <td><?php echo !empty($attendee->date_attended) ? esc_html($attendee->date_attended) : 'Not yet attended'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php

    // Handle export CSV if requested
    if (isset($_POST['export_csv'])) {
        open_event_export_csv();
    }
}

function open_event_export_csv() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'event_attendance';
    $attendees = $wpdb->get_results("SELECT * FROM $table_name ORDER BY first_name ASC, last_name ASC");

    $csv_data = "First Name,Last Name,Email,Age Range,Registration Type,Attended,Date Attended\n";
    foreach ($attendees as $attendee) {
        $csv_data .= '"' . $attendee->first_name . '","' . $attendee->last_name . '","' . $attendee->email . '","' . $attendee->age_range . '","' . $attendee->registration_type . '","' . ($attendee->attended ? 'Yes' : 'No') . '","' . (!empty($attendee->date_attended) ? $attendee->date_attended : 'Not yet attended') . "\"\n";
    }

    $current_month = date('F');
    $file_name = 'cov-' . $current_month . '-open-event-signin-data.csv';

    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=" . $file_name);
    header("Pragma: no-cache");
    header("Expires: 0");

    echo $csv_data;
    exit;
}

function open_event_export_csv_handler() {
    if (isset($_POST['export_csv'])) {
        open_event_export_csv();
    }
}
add_action('admin_init', 'open_event_export_csv_handler');

function open_event_render_attendance_form() {
    ob_start();
    ?>
    <div class="open-event-container">
        <!-- Back button - initially hidden -->
        <div class="back-button-container" style="display: none;">
            <a href="#" class="back-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Previous Step
            </a>
        </div>

        <form method="POST" id="open-event-attendance-form" class="form--light">
            <div id="first_page">
                <h1 class="h2">Did you book a place for this open event via our website?</h1>
                <fieldset>
                    <label><input type="radio" name="pre_registered" value="yes" required> Yes</label>
                    <label><input type="radio" name="pre_registered" value="no" required> No</label>
                </fieldset>
                <button class="btn--primary" type="button" id="next-button">Next</button>
            </div>

            <div id="pre_registered_yes" style="display: none;" class="smart-spacing">
                <label for="search_email">Search by Email</label>
                <p>
                    Please enter the email you used to register for this Open Event.
                </p>
                <input type="email" name="search_email" id="search_email" placeholder="Enter your email">
                <button class="btn--base" type="button" id="search-email-button">Search</button>
                
                <div id="email-search-results"></div>
                
                <div id="surname-search" style="display: none;">
                    <p>Try searching by surname:</p>
                    <div>
                        <label for="search_surname">Search by Surname</label>
                        <input type="text" name="search_surname" id="search_surname" placeholder="Enter your surname" required>
                    </div>
                    <button class="btn--base" type="button" id="search-surname-button">Search</button>
                </div>
                
                <div id="surname-search-results"></div>
            </div>

            <div id="pre_registered_no" style="display: none;">
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" id="first_name_no" placeholder="Enter your first name" required>
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" id="last_name_no" placeholder="Enter your last name" required>
                <label for="email">Email</label>
                <input type="email" name="email" id="email_no" placeholder="Enter your email" required>
                <label for="age_range">Age Range</label>
                <select name="age_range" id="age_range_no" required>
                    <option value="16-18">16-18</option>
                    <option value="19+">19+</option>
                </select>
                <button class="btn--primary" type="submit" name="register_walkin">I have Attended</button>
            </div>
        </form>
    </div>

    <style>
        .open-event-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-button-container {
            margin-bottom: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            transition: all 0.2s ease;
        }

        .back-button:hover {
            background-color: #e5e5e5;
            text-decoration: none;
            color: #000;
        }

        .back-button svg {
            width: 16px;
            height: 16px;
        }

        @media (max-width: 768px) {
            .open-event-container {
                padding: 10px;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}

// Shortcode to display the attendance form
function open_event_attendance_shortcode() {
    return open_event_render_attendance_form();
}
add_shortcode('open_event_attendance', 'open_event_attendance_shortcode');
