<?php
/**
 * Helper functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Helper
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function helper_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on Helper, use a find and replace
		* to change 'helper' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'helper', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'helper' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'helper_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'helper_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function helper_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'helper_content_width', 640 );
}
add_action( 'after_setup_theme', 'helper_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function helper_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'helper' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'helper' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'helper_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function helper_scripts() {
	wp_enqueue_style( 'helper-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'helper-style', 'rtl', 'replace' );

	wp_enqueue_script( 'helper-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'helper_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

function add_custom_roles() {
    add_role(
        'volunteer',
        __( 'Volunteer' ),
        array(
            'read' => true,
        )
    );

    add_role(
        'organization',
        __( 'Organization' ),
        array(
            'read' => true,
        )
    );
}
add_action( 'init', 'add_custom_roles' );


// Shortcode to display events
function display_events() {
    ob_start();
    ?>
    <div id="events-container">
        <!-- Events will be loaded here by JavaScript -->
    </div>
    <script>
        jQuery(document).ready(function($) {
            function fetchEvents(showRelated) {
                console.log('Fetching events, showRelated:', showRelated);
                $.ajax({
                    url: helperAjax.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'fetch_related_events',
                        show_related: showRelated
                    },
                    success: function(response) {
                        console.log('Related events response:', response);
                        var events = JSON.parse(response);
                        var eventsHtml = '';
                        for (var i = 0; i < events.length; i++) {
                            console.log('Event:', events[i].event_name, 'Matches:', events[i].matches);
                            eventsHtml += '<div class="event" id="event-' + i + '">';
                            if (events[i].matches) {
                                eventsHtml += '<div style="float: right;"><input type="checkbox" id="commit-' + i + '" class="commit-checkbox" data-event-id="' + events[i].event_id + '" data-volunteer-id="' + <?php echo get_current_user_id(); ?> + '"><label for="commit-' + i + '"> Commit</label></div>';
                            }
                            eventsHtml += '<h4>' + events[i].event_name + '</h4>';
                            eventsHtml += '<p>Date: ' + events[i].event_date + '</p>';
                            eventsHtml += '<p>Needs: ' + events[i].event_needs + '</p>';
                            eventsHtml += '</div>';
                        }
                        $('#events-container').html(eventsHtml);

                        // Add event listeners for commit checkboxes
                        $('.commit-checkbox').change(function() {
                            var checkbox = $(this);
                            var label = checkbox.next('label');
                            var eventDiv = checkbox.closest('.event');

                            if (checkbox.is(':checked')) {
                                $.ajax({
                                    url: helperAjax.ajaxurl,
                                    method: 'POST',
                                    data: {
                                        action: 'register_volunteer_to_event',
                                        volunteer_id: checkbox.data('volunteer-id'),
                                        event_id: checkbox.data('event-id')
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            label.text('Committed');
                                            eventDiv.css({
                                                'background-color': '#d4edda', // Light green background
                                                'border-color': '#c3e6cb' // Green border
                                            });
                                        } else {
                                            alert(response.data);
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('AJAX error:', error);
                                    }
                                });
                            } else {
                                label.text('Commit');
                                eventDiv.css({
                                    'background-color': '',
                                    'border-color': ''
                                });
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                        console.log('XHR:', xhr);
                        console.log('Status:', status);
                        console.log('Error:', error);
                    }
                });
            }

            $('#show_related').change(function() {
                fetchEvents($(this).is(':checked'));
            });

            // Initial fetch with show_related as false
            fetchEvents(false);
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('display_events', 'display_events');

function hide_admin_bar_for_roles() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if (in_array('volunteer', $user->roles) || in_array('organization', $user->roles)) {
            add_filter('show_admin_bar', '__return_false');
        }
    }
}
add_action('after_setup_theme', 'hide_admin_bar_for_roles');
