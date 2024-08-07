<?php
/*
Plugin Name: Helper
Description: A plugin to connect volunteers and organizations in need.
Version: 1.0
Author: Ernest Juchheim
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add shortcode to check login status and display forms
add_action( 'init', 'register_helper_shortcodes' );

function register_helper_shortcodes() {
    add_shortcode( 'helper_login_check', 'helper_login_check_shortcode' );
}

function helper_login_check_shortcode( $atts, $content = null ) {
    if ( is_user_logged_in() ) {
        ob_start();
        include plugin_dir_path( __FILE__ ) . 'protected-content.php';
        return ob_get_clean();
    } else {
        ob_start();
        include plugin_dir_path( __FILE__ ) . 'html.php';
        return ob_get_clean();
    }
}

function helper_process_registration() {
    if ( isset( $_POST['helper_register_nonce'] ) && wp_verify_nonce( $_POST['helper_register_nonce'], 'helper_register' ) ) {
        $username = sanitize_text_field( $_POST['user_login'] );
        $email = sanitize_email( $_POST['user_email'] );
        $password = $_POST['user_pass'];
        $role = sanitize_text_field( $_POST['user_role'] );
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $skills = isset($_POST['skills']) ? sanitize_text_field($_POST['skills']) : '';
        $organization_name = isset($_POST['organization_name']) ? sanitize_text_field($_POST['organization_name']) : '';
        $needs = isset($_POST['needs']) ? sanitize_text_field($_POST['needs']) : '';

        if ( username_exists( $username ) || ! validate_username( $username ) ) {
            echo '<p>Invalid username.</p>';
            return;
        }

        if ( ! is_email( $email ) || email_exists( $email ) ) {
            echo '<p>Invalid email.</p>';
            return;
        }

        if ( empty( $password ) ) {
            echo '<p>Password cannot be empty.</p>';
            return;
        }

        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            echo '<p>Registration failed: ' . $user_id->get_error_message() . '</p>';
            return;
        }

        // Assign role and create Pod item
        $user = new WP_User( $user_id );
        if ($role === 'volunteer') {
            $user->set_role('volunteer');
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);
            update_user_meta($user_id, 'skills', $skills);
            $pod_id = pods('volunteer')->add([
                'post_title' => $username,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'skills' => $skills,
                'post_author' => $user_id,
                'post_status' => 'publish'
            ]);
        } elseif ($role === 'organization') {
            $user->set_role('organization');
            update_user_meta($user_id, 'organization_name', $organization_name);
            update_user_meta($user_id, 'needs', $needs);
            $pod_id = pods('organization')->add([
                'post_title' => $username,
                'organization_name' => $organization_name,
                'needs' => $needs,
                'post_author' => $user_id,
                'post_status' => 'publish'
            ]);
        }

        // Log the user in
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );

        // Reload the current page
        wp_redirect( $_SERVER['REQUEST_URI'] );
        exit;
    }
}
add_action( 'init', 'helper_process_registration' );

// Handle custom login
function helper_custom_login() {
    if ( isset( $_POST['helper_login_nonce'] ) && wp_verify_nonce( $_POST['helper_login_nonce'], 'helper_login' ) ) {
        $username_or_email = sanitize_text_field( $_POST['log'] );
        $password = $_POST['pwd'];

        if ( is_email( $username_or_email ) ) {
            $user = get_user_by( 'email', $username_or_email );
        } else {
            $user = get_user_by( 'login', $username_or_email );
        }

        if ( $user && wp_check_password( $password, $user->data->user_pass, $user->ID ) ) {
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID );
            wp_redirect( $_SERVER['REQUEST_URI'] );
            exit;
        } else {
            echo '<p>Invalid username, email, or password.</p>';
        }
    }
}
add_action( 'template_redirect', 'helper_custom_login' );

// Handle updating the linked pod item
function helper_process_update() {
    if ( isset( $_POST['helper_update_nonce'] ) && wp_verify_nonce( $_POST['helper_update_nonce'], 'helper_update' ) ) {
        $user_id = get_current_user_id();
        $role = sanitize_text_field( $_POST['user_role'] );
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $skills = isset($_POST['skills']) ? sanitize_text_field($_POST['skills']) : '';
        $organization_name = isset($_POST['organization_name']) ? sanitize_text_field($_POST['organization_name']) : '';
        $needs = isset($_POST['needs']) ? sanitize_text_field($_POST['needs']) : '';

        if ($role === 'volunteer') {
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);
            update_user_meta($user_id, 'skills', $skills);
            $pod = pods('volunteer')->find(array('where' => 'post_author = ' . $user_id));
            if ($pod->total() > 0) {
                $pod_item = $pod->fetch();
                $pod->save(array(
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'skills' => $skills,
                ));
            }
        } elseif ($role === 'organization') {
            update_user_meta($user_id, 'organization_name', $organization_name);
            update_user_meta($user_id, 'needs', $needs);
            $pod = pods('organization')->find(array('where' => 'post_author = ' . $user_id));
            if ($pod->total() > 0) {
                $pod_item = $pod->fetch();
                $pod->save(array(
                    'organization_name' => $organization_name,
                    'needs' => $needs,
                ));
            }
        }

        // Reload the current page
        wp_redirect( $_SERVER['REQUEST_URI'] );
        exit;
    }
}
add_action( 'init', 'helper_process_update' );

function helper_process_create_event() {
    if (isset($_POST['helper_create_event_nonce']) && wp_verify_nonce($_POST['helper_create_event_nonce'], 'helper_create_event')) {
        $user_id = get_current_user_id();
        $event_name = sanitize_text_field($_POST['event_name']);
        $event_date = sanitize_text_field($_POST['event_date']);
        $event_needs = sanitize_textarea_field($_POST['event_needs']);
        $positions = intval($_POST['positions']); // Sanitize and retrieve the positions field
        $street = sanitize_text_field($_POST['street']);
        $city = sanitize_text_field($_POST['city']);
        $state = sanitize_text_field($_POST['state']);
        $zip_code = sanitize_text_field($_POST['zip_code']);

        // Fetch organization ID (this should be the post ID of the organization)
        $organization_pod = pods('organization', array('where' => array('post_author' => $user_id)));
        $organization_id = $organization_pod->field('id');

        $pod_id = pods('event')->add([
            'post_title' => $event_name,
            'event_name' => $event_name,
            'event_date' => $event_date,
            'event_needs' => $event_needs,
            'positions' => $positions, // Add positions to the event
            'street' => $street,
            'city' => $city,
            'state' => $state,
            'zip_code' => $zip_code,
            'organization' => $organization_id, // Set the organization ID
            'post_author' => $user_id,
            'post_status' => 'publish'
        ]);

        if ($pod_id) {
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        } else {
            error_log('Error creating event');
        }
    }
}
add_action('init', 'helper_process_create_event');





// Handle event deletion
function helper_process_delete_event() {
    error_log('AJAX delete_event called');
    if (isset($_POST['event_id']) && isset($_POST['nonce'])) {
        $event_id = intval($_POST['event_id']);
        $nonce = $_POST['nonce'];
        error_log('Received event_id: ' . $event_id);
        error_log('Received nonce: ' . $nonce);

        if (!wp_verify_nonce($nonce, 'delete_event_' . $event_id)) {
            error_log('Nonce verification failed for event_id: ' . $event_id);
            echo 'error: nonce verification failed';
            wp_die();
        }

        if (!current_user_can('delete_posts')) {
            error_log('Current user cannot delete posts');
            echo 'error: current user cannot delete posts';
            wp_die();
        }

        $event = pods('event', $event_id);
        error_log('Event exists: ' . $event->exists());
        error_log('Event post author: ' . $event->field('post_author'));

        if ($event->exists() && get_current_user_id() === (int) $event->field('post_author')) {
            $deleted = pods('event', $event_id)->delete();
            if ($deleted) {
                error_log('Event deleted successfully: ' . $event_id);
                echo 'success';
            } else {

                echo 'error: could not delete event';
            }
        } else {
            error_log('Unauthorized or non-existent event: ' . $event_id);
            echo 'error: unauthorized or non-existent event';
        }
    } else {
        error_log('Invalid request: ' . print_r($_POST, true));
        echo 'error: invalid request';
    }

    wp_die();
}
add_action('wp_ajax_delete_event', 'helper_process_delete_event');

function helper_fetch_related_events() {
    global $wpdb;
    $user_id = get_current_user_id();
    $show_related = isset($_POST['show_related']) ? filter_var($_POST['show_related'], FILTER_VALIDATE_BOOLEAN) : false;

    $skills = get_user_meta($user_id, 'skills', true);
    $skills_array = array_map('trim', explode(',', $skills));

    if ($show_related) {
        $where_clauses = [];
        foreach ($skills_array as $skill) {
            $where_clauses[] = $wpdb->prepare("pm.meta_value LIKE %s", '%' . $wpdb->esc_like($skill) . '%');
        }
        $where = '(' . implode(' OR ', $where_clauses) . ')';

        $query = "
            SELECT DISTINCT p.*
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = 'event_needs'
            AND $where
            AND p.post_type = 'event'
            AND p.post_status IN ('publish', 'future', 'draft', 'pending', 'private')
        ";
    } else {
        $query = "
            SELECT DISTINCT p.*
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'event'
            AND p.post_status IN ('publish', 'future', 'draft', 'pending', 'private')
        ";
    }

    $events = $wpdb->get_results($query);

    $event_list = [];

    foreach ($events as $event) {
        setup_postdata($event);

        $event_needs = get_post_meta($event->ID, 'event_needs', true);
        $event_needs_array = array_map('trim', explode(',', $event_needs));
        $matches = array_intersect($skills_array, $event_needs_array);

        // Check if the volunteer is already registered for the event
        $is_registered_query = pods('registration')->find([
            'where' => [
                'volunteer_id' => $user_id,
                'event_id' => $event->ID
            ]
        ]);

        $is_registered = $is_registered_query->total() > 0;

        // Fetch organization name from Pods
        $organization_id = get_post_meta($event->ID, 'organization', true);
        $organization_name = '';
        if ($organization_id) {
            $organization_pod = pods('organization', $organization_id);
            if ($organization_pod->exists()) {
                $organization_name = $organization_pod->field('organization_name');
            }
        }

        // Fetch the number of positions available
        $positions = get_post_meta($event->ID, 'positions', true);

        // Fetch the names of committed volunteers
        $committed_volunteers_query = pods('registration')->find([
            'where' => [
                'event_id' => $event->ID
            ]
        ]);

        $committed_volunteers = [];
        if ($committed_volunteers_query->total() > 0) {
            while ($committed_volunteers_query->fetch()) {
                $volunteer_id = $committed_volunteers_query->field('volunteer_id');
                $volunteer = get_userdata($volunteer_id);
                if ($volunteer) {
                    $committed_volunteers[] = $volunteer->first_name . ' ' . $volunteer->last_name;
                }
            }
        }

        // Add the address fields to the event data
        $street = get_post_meta($event->ID, 'street', true);
        $city = get_post_meta($event->ID, 'city', true);
        $state = get_post_meta($event->ID, 'state', true);
        $zip_code = get_post_meta($event->ID, 'zip_code', true);

        // Log the committed volunteers
        error_log('Event ID: ' . $event->ID . ', Committed Volunteers: ' . implode(', ', $committed_volunteers));

        error_log('Event ID: ' . $event->ID . ', Organization ID: ' . $organization_id . ', Organization Name: ' . $organization_name . ', Positions: ' . $positions);

        // Only add the event to the list if the positions are greater than 0 or the volunteer is already registered
        if ($positions > 0 || $is_registered) {
            $event_list[] = [
                'event_id' => $event->ID,
                'event_name' => $event->post_title,
                'event_date' => get_post_meta($event->ID, 'event_date', true),
                'event_needs' => $event_needs,
                'positions' => $positions,
                'matches' => !empty($matches),
                'is_registered' => $is_registered,
                'organization_name' => $organization_name,
                'committed_volunteers' => $committed_volunteers,
                'street' => $street,
                'city' => $city,
                'state' => $state,
                'zip_code' => $zip_code // Add address fields to the event data
            ];
        }
    }
    wp_reset_postdata();

    echo json_encode($event_list);
    wp_die();
}
add_action('wp_ajax_fetch_related_events', 'helper_fetch_related_events');
add_action('wp_ajax_nopriv_fetch_related_events', 'helper_fetch_related_events');







function helper_ajax_fetch_related_content() {
    $user_id = get_current_user_id();
    $user_meta = get_userdata($user_id);
    $user_roles = $user_meta->roles;
    $skills = get_user_meta($user_id, 'skills', true);
    $needs = get_user_meta($user_id, 'needs', true);

    function match_content($field, $keywords) {
        $field_keywords = array_map('trim', explode(',', $field));
        foreach ($keywords as $keyword) {
            foreach ($field_keywords as $field_keyword) {
                if (stripos($field_keyword, $keyword) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    $response = array();
    $keywords = array_map('trim', explode(',', $skills ? $skills : $needs));
    
    if (in_array('volunteer', $user_roles)) {
        // Fetch matched organizations
        $organizations = pods('organization')->find();
        $matched_organizations = array();
        if ($organizations->total() > 0) {
            while ($organizations->fetch()) {
                $organization_name = $organizations->display('organization_name');
                $organization_needs = $organizations->display('needs');
                if (!isset($_POST['show_related']) || $_POST['show_related'] === 'false' || match_content($organization_needs, $keywords)) {
                    $matched_organizations[] = $organization_name;
                }
            }
        }
        $response['organizations'] = $matched_organizations;

        // Fetch matched events
        $events = pods('event')->find();
        $matched_events = array();
        if ($events->total() > 0) {
            while ($events->fetch()) {
                $event_name = $events->display('event_name');
                $event_needs = $events->display('event_needs');
                if (!isset($_POST['show_related']) || $_POST['show_related'] === 'false' || match_content($event_needs, $keywords)) {
                    $matched_events[] = array(
                        'event_name' => $event_name,
                        'event_date' => $events->display('event_date'),
                        'event_needs' => $event_needs,
                    );
                }
            }
        }
        $response['events'] = $matched_events;
    } elseif (in_array('organization', $user_roles)) {
        // Fetch matched volunteers
        $volunteers = pods('volunteer')->find();
        $matched_volunteers = array();
        if ($volunteers->total() > 0) {
            while ($volunteers->fetch()) {
                $volunteer_name = $volunteers->display('first_name') . ' ' . $volunteers->display('last_name');
                $volunteer_skills = $volunteers->display('skills');
                if (!isset($_POST['show_related']) || $_POST['show_related'] === 'false' || match_content($volunteer_skills, $keywords)) {
                    $matched_volunteers[] = $volunteer_name;
                }
            }
        }
        $response['volunteers'] = $matched_volunteers;
    }
    
    echo json_encode($response);
    wp_die();
}
add_action( 'wp_ajax_fetch_related_content', 'helper_ajax_fetch_related_content' );
add_action( 'wp_ajax_nopriv_fetch_related_content', 'helper_ajax_fetch_related_content' );




function helper_enqueue_scripts() {
    wp_enqueue_script( 'jquery' );
    wp_enqueue_style( 'helper-styles', plugin_dir_url( __FILE__ ) . 'html.css' );
    wp_enqueue_script('helper-scripts', plugin_dir_url(__FILE__) . 'helper.js', array('jquery'), null, true);

    // Pass the volunteer ID to JavaScript
    wp_localize_script('helper-scripts', 'helperAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'volunteer_id' => get_current_user_id(), // Ensure this is correctly set
    ));
}
add_action('wp_enqueue_scripts', 'helper_enqueue_scripts');

function register_volunteer_to_event() {
    $volunteer_id = isset($_POST['volunteer_id']) ? intval($_POST['volunteer_id']) : 0;
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

    if (!$volunteer_id || !$event_id) {
        wp_send_json_error("Missing volunteer_id or event_id");
    }

    // Fetch the volunteer Pod item using the user ID
    $volunteer_pod = pods('volunteer', array('where' => array('post_author' => $volunteer_id)));
    $volunteer_pod_id = $volunteer_pod->field('id');

    // Check if the volunteer Pod item exists
    if (!$volunteer_pod_id) {
        wp_send_json_error("Invalid volunteer Pod item ID");
    }

    // Fetch volunteer title from Pods
    $volunteer_title = $volunteer_pod->field('post_title');

    // Fetch event title from WordPress
    $event_post = get_post($event_id);
    $event_title = $event_post ? $event_post->post_title : '';

    if (empty($volunteer_title) || empty($event_title)) {
        wp_send_json_error("Failed to fetch titles: Volunteer or Event title is empty");
    }

    $registration_title = $volunteer_title . ' - ' . $event_title;

    // Set the registration status to "Registered"
    $registration_status = 'Registered';

    // Create the registration post with Pods and set all fields at once
    $registration_pod = pods('registration');
    $params = array(
        'post_title' => $registration_title,
        'post_status' => 'publish',
        'volunteer' => $volunteer_pod_id,
        'event' => $event_id,
        'registration_status' => $registration_status,
        'registration_date' => current_time('mysql'),
        'event_id' => $event_id // New field for event ID
    );

    $registration_id = $registration_pod->add($params);

    if ($registration_id) {
        // Decrement the number of positions available for the event
        $event_pod = pods('event', $event_id);
        $positions_available = (int) $event_pod->field('positions');
        if ($positions_available > 0) {
            $event_pod->save('positions', $positions_available - 1);
        }

        wp_send_json_success("Registration created successfully");
    } else {
        wp_send_json_error("Failed to create registration");
    }
}
add_action('wp_ajax_register_volunteer_to_event', 'register_volunteer_to_event');
add_action('wp_ajax_nopriv_register_volunteer_to_event', 'register_volunteer_to_event');





?>
