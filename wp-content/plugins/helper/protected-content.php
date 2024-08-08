<div class="main-content">
    <div class="full-width">
        <div class="checkbox-container">
            <label for="show_related">
                <input type="checkbox" id="show_related" name="show_related">
                Match skills and needs
            </label>
        </div>
    </div>
    <div class="three-columns">
        <div class="column" id="left-column">
            <?php
            $user_id = get_current_user_id();
            $user_meta = get_userdata($user_id);
            $user_roles = $user_meta->roles;
            $first_name = '';
            $last_name = '';
            $organization_name = '';

            if (in_array('volunteer', $user_roles)) {
                $pod = pods('volunteer', array('where' => 'post_author = ' . $user_id));
                if ($pod->total() > 0) {
                    $pod->fetch();
                    $first_name = $pod->display('first_name');
                    $last_name = $pod->display('last_name');
                }
            } elseif (in_array('organization', $user_roles)) {
                $pod = pods('organization', array('where' => 'post_author = ' . $user_id));
                if ($pod->total() > 0) {
                    $pod->fetch();
                    $organization_name = $pod->display('organization_name');
                }
            }
            ?>
            <p><?php echo esc_html($first_name) . ' ' . esc_html($last_name) . esc_html($organization_name); ?></p>
            <button id="edit-pod-item" class="edit-button">My Information</button>
            <form id="edit-pod-form" style="display: none;" method="post">
                <?php
                $skills = get_user_meta($user_id, 'skills', true);
                $needs = get_user_meta($user_id, 'needs', true);

                if (in_array('volunteer', $user_roles)) {
                    ?>
                    <p>
                        <label for="first_name"><?php _e('First Name'); ?><br/>
                        <input type="text" name="first_name" id="first_name" class="input" value="<?php echo esc_attr($first_name); ?>" size="25" /></label>
                    </p>
                    <p>
                        <label for="last_name"><?php _e('Last Name'); ?><br/>
                        <input type="text" name="last_name" id="last_name" class="input" value="<?php echo esc_attr($last_name); ?>" size="25" /></label>
                    </p>
                    <p>
                        <label for="skills"><?php _e('Skills'); ?><br/>
                        <textarea name="skills" id="skills" class="input" rows="5"><?php echo esc_attr($skills); ?></textarea></label>
                    </p>
                    <input type="hidden" name="user_role" value="volunteer" />
                    <?php
                } elseif (in_array('organization', $user_roles)) {
                    ?>
                    <p>
                        <label for="organization_name"><?php _e('Organization Name'); ?><br/>
                        <input type="text" name="organization_name" id="organization_name" class="input" value="<?php echo esc_attr($organization_name); ?>" size="25" /></label>
                    </p>
                    <p>
                        <label for="needs"><?php _e('Needs'); ?><br/>
                        <textarea name="needs" id="needs" class="input" rows="5"><?php echo esc_attr($needs); ?></textarea></label>
                    </p>
                    <input type="hidden" name="user_role" value="organization" />
                    <?php
                }
                wp_nonce_field('helper_update', 'helper_update_nonce');
                ?>
                <p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Update'); ?>" /></p>
            </form>
        </div>
        <div class="column" id="middle-column">
            <?php
            if (in_array('organization', $user_roles)) {
                ?>
                <h3>My Events</h3>
                <button id="create-event-button" class="edit-button">Create Event</button>
                <form id="create-event-form" style="display: none;" method="post">
                    <p>
                        <label for="event_name"><?php _e('Event Name'); ?><br/>
                        <input type="text" name="event_name" id="event_name" class="input" size="25" /></label>
                    </p>
                    <p>
                        <label for="event_date"><?php _e('Event Date'); ?><br/>
                        <input type="datetime-local" name="event_date" id="event_date" class="input" size="25" /></label>
                    </p>
                    <p>
                        <label for="event_needs"><?php _e('Event Needs'); ?><br/>
                        <textarea name="event_needs" id="event_needs" class="input" rows="5"></textarea></label>
                    </p>
                    <p>
                        <label for="positions"><?php _e('Positions Available'); ?><br/>
                        <input type="number" name="positions" id="positions" class="input" size="25" /></label>
                    </p>
                    <p>
                        <label for="street"><?php _e('Street'); ?><br/>
                        <input type="text" name="street" id="street" class="input" size="25" /></label>
                    </p>
                    <p>
                        <label for="city"><?php _e('City'); ?><br/>
                        <input type="text" name="city" id="city" class="input" size="25" /></label>
                    </p>
                    <p>
                        <label for="state"><?php _e('State'); ?><br/>
                        <input type="text" name="state" id="state" class="input" size="25" /></label>
                    </p>
                    <p>
                        <label for="zip_code"><?php _e('Zip Code'); ?><br/>
                        <input type="text" name="zip_code" id="zip_code" class="input" size="25" /></label>
                    </p>
                    <input type="hidden" name="user_role" value="organization" />
                    <?php wp_nonce_field('helper_create_event', 'helper_create_event_nonce'); ?>
                    <p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Create Event'); ?>" /></p>
                </form>

                <?php
                // Fetch events linked to the current organization
                $events = pods('event', array(
                    'where' => 't.post_author = ' . $user_id
                ));
                error_log('Total events found: ' . $events->total());
                if ($events->total() > 0) {
                    while ($events->fetch()) {
                        $event_id = $events->id();
                        $nonce = wp_create_nonce('delete_event_' . $event_id);
                        error_log('Event ID: ' . $event_id . ' Post Author: ' . $events->field('post_author'));
                        
                        // Fetch the committed volunteers
                        $committed_volunteers_query = pods('registration')->find([
                            'where' => [
                                'event_id' => $event_id
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

                        error_log('Event ID: ' . $event_id . ', Committed Volunteers: ' . implode(', ', $committed_volunteers));
                        ?>
                        <div class="event" data-event-id="<?php echo esc_attr($event_id); ?>">
                            <h4><?php echo esc_html($events->display('event_name')); ?></h4>
                            <p><?php echo esc_html(date('F j, Y \a\t g:i a', strtotime($events->display('event_date')))); ?></p>
                            <button class="show-address-button" data-event-id="<?php echo esc_attr($event_id); ?>">Show Address</button>
                            <div class="event-address" id="address-<?php echo esc_attr($event_id); ?>" style="display: none;">
                                <p>Street: <?php echo esc_html($events->field('street')); ?></p>
                                <p>City: <?php echo esc_html($events->field('city')); ?></p>
                                <p>State: <?php echo esc_html($events->field('state')); ?></p>
                                <p>Zip Code: <?php echo esc_html($events->field('zip_code')); ?></p>
                            </div>
                            <p>Needs: <?php echo esc_html($events->display('event_needs')); ?></p>
                            <p>Positions available: <?php echo esc_html($events->display('positions')); ?></p>
                            <?php if (!empty($committed_volunteers)) { ?>
                                <p>Committed Volunteers: <?php echo esc_html(implode(', ', $committed_volunteers)); ?></p>
                            <?php } ?>
                            <button class="delete-event-button" data-event-id="<?php echo esc_attr($event_id); ?>" data-nonce="<?php echo $nonce; ?>">Delete</button>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p>No events found.</p>';
                }
            } elseif (in_array('volunteer', $user_roles)) {
                ?>
                <h3>All Events</h3>
                <div id="events-container">
                    <?php
                    // Initial fetch of all events
                    $events = pods('event');
                    $events->find();
                    if ($events->total() > 0) {
                        while ($events->fetch()) {
                            ?>
                            <div class="event">
                                <h4><?php echo esc_html($events->display('event_name')); ?></h4>
                                <p><?php echo esc_html(date('F j, Y \a\t g:i a', strtotime($events->display('event_date')))); ?></p>
                                <button class="show-address-button" data-event-id="<?php echo $events->id(); ?>">Show Address</button>
                                <div class="event-address" id="address-<?php echo $events->id(); ?>" style="display: none;">
                                    <p>Street: <?php echo esc_html($events->field('street')); ?></p>
                                    <p>City: <?php echo esc_html($events->field('city')); ?></p>
                                    <p>State: <?php echo esc_html($events->field('state')); ?></p>
                                    <p>Zip Code: <?php echo esc_html($events->field('zip_code')); ?></p>
                                </div>
                                <p>Needs: <?php echo esc_html($events->display('event_needs')); ?></p>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p>No events found.</p>';
                    }
                    ?>
                </div>
                <?php
            } else {
                echo '<p>Middle column content</p>';
            }
            ?>
        </div>
        <div class="column" id="right-column">
            <h3><?php echo in_array('volunteer', $user_roles) ? 'All Organizations' : 'All Volunteers'; ?></h3>
            <div id="related-content">
                <!-- AJAX-loaded content will be inserted here -->
            </div>
        </div>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    $('.commit-checkbox').change(function() {
        var checkbox = $(this);
        var event_id = checkbox.data('event-id');
        var volunteer_id = checkbox.data('volunteer-id');

        if (checkbox.is(':checked')) {
            $.ajax({
                url: helperAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'register_volunteer_to_event',
                    volunteer_id: volunteer_id,
                    event_id: event_id
                },
                success: function(response) {
                    if (response.success) {
                        checkbox.next('label').text('Committed');
                        checkbox.closest('.event').css({
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
            checkbox.next('label').text('Commit');
            checkbox.closest('.event').css({
                'background-color': '',
                'border-color': ''
            });
        }
    });

    $('#edit-pod-item').click(function(event) {
        event.preventDefault();
        var button = $(this);
        console.log('My Information button clicked');
        $('#edit-pod-form').slideToggle(function() {
            if ($('#edit-pod-form').is(':visible')) {
                button.text('Close');
            } else {
                button.text('My Information');
            }
        });
    });

    $('#create-event-button').click(function(event) {
        event.preventDefault();
        console.log('Create Event button clicked');
        $('#create-event-form').slideToggle();
    });


// Add event listeners for show address buttons
$('.show-address-button').off('click').on('click', function(event) {
    event.preventDefault();
    console.log('Show Address button clicked');
    var eventId = $(this).data('event-id');
    var addressDiv = $('#address-' + eventId);
    var button = $(this);

    // Slide toggle and update button text based on visibility
    addressDiv.stop(true, true).slideToggle(400, function() {
        if (addressDiv.is(':visible')) {
            button.text('Hide Address');
        } else {
            button.text('Show Address');
        }
    });
});




    $('.delete-event-button').click(function(event) {
        event.preventDefault();
        var eventId = $(this).data('event-id');
        var nonce = $(this).data('nonce');
        console.log('Delete button clicked for event ID:', eventId);
        if (confirm('Are you sure you want to delete this event?')) {
            $.ajax({
                url: helperAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'delete_event',
                    event_id: eventId,
                    nonce: nonce
                },
                success: function(response) {
                    console.log('Server response:', response);
                    if (response === 'success') {
                        $('[data-event-id="' + eventId + '"]').remove();
                    } else {
                        alert('Error deleting event: ' + response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('AJAX error: ' + error);
                }
            });
        }
    });

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
                var events = JSON.parse(response);
                var eventsHtml = '';
                for (var i = 0; i < events.length; i++) {
                    console.log('Event:', events[i].event_name, 'Matches:', events[i].matches);
                    eventsHtml += '<div class="event">';
                    if (events[i].matches) {
                        eventsHtml += '<div style="float: right;"><input type="checkbox" id="commit-' + i + '" class="commit-checkbox" data-event-id="' + events[i].event_id + '" data-volunteer-id="' + helperAjax.volunteer_id + '"' + (events[i].is_registered ? ' checked disabled' : '') + '><label for="commit-' + i + '" class="commit-label">' + (events[i].is_registered ? 'Committed' : 'Commit') + '</label></div>';
                    }
                    eventsHtml += '<h4>' + events[i].event_name + '</h4>';
                    eventsHtml += '<p>' + new Date(events[i].event_date).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true }) + '</p>';
                    eventsHtml += '<button class="show-address-button" data-event-id="' + events[i].event_id + '">Show Address</button>';
                    eventsHtml += '<div class="event-address" id="address-' + events[i].event_id + '" style="display: none;">';
                    eventsHtml += '<p>Street: ' + events[i].street + '</p>';
                    eventsHtml += '<p>City: ' + events[i].city + '</p>';
                    eventsHtml += '<p>State: ' + events[i].state + '</p>';
                    eventsHtml += '<p>Zip Code: ' + events[i].zip_code + '</p>';
                    eventsHtml += '</div>';
                    eventsHtml += '<p>Needs: ' + events[i].event_needs + '</p>';
                    eventsHtml += '</div>';
                }
                $('#events-container').html(eventsHtml);

                // Add event listeners for commit checkboxes
                $('.commit-checkbox').change(function() {
                    var checkbox = $(this);
                    var label = checkbox.next('label.commit-label');
                    var eventDiv = checkbox.closest('.event');

                    var volunteerId = checkbox.data('volunteer-id');
                    var eventId = checkbox.data('event-id');

                    if (checkbox.is(':checked')) {
                        // Confirm before proceeding
                        var confirmed = confirm("Are you sure you want to commit to this event?");
                        if (!confirmed) {
                            checkbox.prop('checked', false);
                            return;
                        }

                        $.ajax({
                            url: helperAjax.ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'register_volunteer_to_event',
                                volunteer_id: volunteerId,
                                event_id: eventId
                            },
                            success: function(response) {
                                if (response.success) {
                                    label.text('Committed');
                                    eventDiv.css({
                                        'background-color': '#d4edda', // Light green background
                                        'border-color': '#c3e6cb' // Green border
                                    });
                                    checkbox.prop('disabled', true); // Disable the checkbox
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
            }
        });
    }

    $('#show_related').change(function() {
        fetchEvents($(this).is(':checked'));
    });

    // Initial fetch with show_related as false
    fetchEvents(false);

    function fetchRelatedContent() {
        var showRelated = $('#show_related').is(':checked');
        $.ajax({
            url: helperAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'fetch_related_content',
                show_related: showRelated
            },
            success: function(response) {
                var content = JSON.parse(response);

                var relatedContentHtml = '';
                if (content.organizations) {
                    for (var i = 0; i < content.organizations.length; i++) {
                        relatedContentHtml += '<p>' + content.organizations[i] + '</p>';
                    }
                } else if (content.volunteers) {
                    for (var i = 0; i < content.volunteers.length; i++) {
                        relatedContentHtml += '<p>' + content.volunteers[i] + '</p>';
                    }
                }
                $('#related-content').html(relatedContentHtml);

                var eventContentHtml = '';
                if (content.events) {
                    for (var j = 0; j < content.events.length; j++) {
                        eventContentHtml += '<div class="event">';
                        eventContentHtml += '<h4>' + content.events[j].event_name + '</h4>';
                        eventContentHtml += '<p>' + new Date(content.events[j].event_date).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true }) + '</p>';
                        eventContentHtml += '<button class="show-address-button" data-event-id="' + content.events[j].event_id + '">Show Address</button>';
                        eventContentHtml += '<div class="event-address" id="address-' + content.events[j].event_id + '" style="display: none;">';
                        eventContentHtml += '<p>Street: ' + content.events[j].street + '</p>';
                        eventContentHtml += '<p>City: ' + content.events[j].city + '</p>';
                        eventContentHtml += '<p>State: ' + content.events[j].state + '</p>';
                        eventContentHtml += '<p>Zip Code: ' + content.events[j].zip_code + '</p>';
                        eventContentHtml += '</div>';
                        eventContentHtml += '<p>Needs: ' + content.events[j].event_needs + '</p>';
                        eventContentHtml += '</div>';
                    }
                }
                $('#event-content').html(eventContentHtml);
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }

    $('#show_related').change(fetchRelatedContent);

    // Initial fetch with show_related as false
    fetchRelatedContent();
});
</script>
