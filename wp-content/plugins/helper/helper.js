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
                    console.log('Processing event:', events[i]);
                    eventsHtml += '<div class="event" id="event-' + events[i].event_id + '">';
                    if (events[i].matches) {
                        eventsHtml += '<div style="float: right;"><input type="checkbox" id="commit-' + i + '" class="commit-checkbox" data-event-id="' + events[i].event_id + '" data-volunteer-id="' + helperAjax.volunteer_id + '"' + (events[i].is_registered ? ' checked disabled' : '') + '><label for="commit-' + i + '" class="commit-label">' + (events[i].is_registered ? 'Committed' : 'Commit') + '</label></div>';
                    }
                    eventsHtml += '<h4>' + events[i].event_name + '</h4>';
                    var eventDate = new Date(events[i].event_date);
                    var formattedDate = eventDate.toLocaleString('en-US', {
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric',
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: true
                    });
                    eventsHtml += '<p>Date: ' + formattedDate + '</p>';
                    eventsHtml += '<button class="show-address-button" data-event-id="' + events[i].event_id + '">Show Address</button>';
                    eventsHtml += '<div class="event-address" id="address-' + events[i].event_id + '" style="display: none;">';
                    eventsHtml += '<p>Street: ' + events[i].street + '</p>';
                    eventsHtml += '<p>City: ' + events[i].city + '</p>';
                    eventsHtml += '<p>State: ' + events[i].state + '</p>';
                    eventsHtml += '<p>Zip Code: ' + events[i].zip_code + '</p>';
                    eventsHtml += '</div>';
                    eventsHtml += '<p>Organization: ' + (events[i].organization_name ? events[i].organization_name : 'N/A') + '</p>';
                    eventsHtml += '<p>Needs: ' + events[i].event_needs + '</p>';
                    eventsHtml += '<p>Positions: ' + events[i].positions + '</p>';

                    if (events[i].committed_volunteers.length > 0) {
                        eventsHtml += '<p>Committed Volunteers: ' + events[i].committed_volunteers.join(', ') + '</p>';
                    }
                    
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

                    console.log('Checkbox volunteer ID:', volunteerId);
                    console.log('Checkbox event ID:', eventId);

                    if (checkbox.is(':checked')) {
                        // Confirm before proceeding
                        var confirmed = confirm("Are you sure you want to commit to this event?");
                        if (!confirmed) {
                            checkbox.prop('checked', false);
                            return;
                        }

                        console.log('Checkbox is checked, sending AJAX request to register volunteer.');
                        $.ajax({
                            url: helperAjax.ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'register_volunteer_to_event',
                                volunteer_id: volunteerId,
                                event_id: eventId
                            },
                            success: function(response) {
                                console.log('Registration response:', response);
                                if (response.success) {
                                    label.text('Committed');
                                    eventDiv.css({
                                        'background-color': '#d4edda', // Light green background
                                        'border-color': '#c3e6cb' // Green border
                                    });
                                    checkbox.prop('disabled', true); // Disable the checkbox
                                } else {
                                    console.log('Failed to fetch titles:', response.data);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX error:', error);
                                console.log('XHR:', xhr);
                                console.log('Status:', status);
                                console.log('Error:', error);
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

                // Add event listeners for show address buttons
                $('.show-address-button').click(function() {
                    var eventId = $(this).data('event-id');
                    var addressDiv = $('#address-' + eventId);
                    addressDiv.slideToggle();
                    var button = $(this);
                    if (button.text() === 'Show Address') {
                        button.text('Hide Address');
                    } else {
                        button.text('Show Address');
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
