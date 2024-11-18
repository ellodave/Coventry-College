jQuery(document).ready(function ($) {
    const $form = $('#open-event-attendance-form');
    const $firstPage = $('#first_page');
    const $preRegisteredYes = $('#pre_registered_yes');
    const $preRegisteredNo = $('#pre_registered_no');
    const $surnameSearch = $('#surname-search');
    const $emailSearchResults = $('#email-search-results');
    const $surnameSearchResults = $('#surname-search-results');
    const $backButton = $('.back-button-container');

    // Next button handler
    $('#next-button').on('click', function() {
        const preRegistered = $('input[name="pre_registered"]:checked').val();
        if (!preRegistered) {
            alert('Please select whether you have pre-registered or not.');
            return;
        }
        $firstPage.hide();
        $backButton.show();
        
        if (preRegistered === 'yes') {
            $preRegisteredYes.show();
            $preRegisteredNo.find('input, select').prop('required', false);
        } else {
            $preRegisteredNo.show();
            $preRegisteredYes.find('input').prop('required', false);
        }
    });

    // Back button handler
    $('.back-button').on('click', function(e) {
        e.preventDefault();
        resetForm();
    });

    // Search button handlers
    $('#search-email-button').on('click', function() {
        const email = $('#search_email').val();
        if (!email) {
            alert('Please enter an email address.');
            return;
        }
        searchAttendee('email', email);
    });

    $('#search-surname-button').on('click', function() {
        const surname = $('#search_surname').val();
        if (!surname) {
            alert('Please enter a surname.');
            return;
        }
        searchAttendee('surname', surname);
    });

    // Form submission handler
    $form.on('submit', function(e) {
        e.preventDefault();

        if ($(this).find('#pre_registered_no').is(':visible')) {
            const email = $('#email_no').val();
            checkEmailAndSubmit(email);
        } else {
            submitForm();
        }
    });

    // Function to handle form submission
    function submitForm() {
        const formData = new FormData($form[0]);
        formData.append('action', 'process_attendance');
        formData.append('nonce', ajax_object.nonce);

        $.ajax({
            type: 'POST',
            url: ajax_object.ajaxurl,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Response:', response);
                if (response.success) {
                    $form.html(response.message);
                    
                    // Add click handler for the reset button after it's added to the DOM
                    $(document).on('click', '#reset-form-button', function() {
                        window.location.reload();
                    });
                } else {
                    alert(response.message || 'An error occurred. Please try again.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                console.log('Response Text:', jqXHR.responseText);
                alert('An error occurred while submitting the form. Please try again.');
            }
        });
    }

    // Function to check email existence and submit form
    function checkEmailAndSubmit(email) {
        $.ajax({
            type: 'POST',
            url: ajax_object.ajaxurl,
            data: {
                action: 'check_email_exists',
                email: email,
                nonce: ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.exists) {
                        alert('It looks like you have already pre-registered for this Open Event. This email address already exists. Please go back and choose the pre-registered option.');
                    } else {
                        submitForm();
                    }
                } else {
                    alert(response.message || 'An error occurred while checking the email. Please try again.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('An error occurred while checking the email. Please try again.');
            }
        });
    }

    // Function to search for attendees
    function searchAttendee(type, value) {
        $.ajax({
            type: 'POST',
            url: ajax_object.ajaxurl,
            data: {
                action: 'search_attendee',
                search_type: type,
                search_value: value,
                nonce: ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (type === 'email') {
                        $emailSearchResults.html(response.html);
                        obfuscateEmails($emailSearchResults);
                        if (response.count === 0) {
                            $surnameSearch.show();
                            $('#search_surname').prop('required', true);
                        } else {
                            $surnameSearch.hide();
                            $('#search_surname').prop('required', false);
                        }
                    } else {
                        $surnameSearchResults.html(response.html);
                        obfuscateEmails($surnameSearchResults);
                    }
                } else {
                    alert(response.message || 'An error occurred while searching. Please try again.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('An error occurred while searching. Please try again.');
            }
        });
    }

    // Function to obfuscate emails in search results
    function obfuscateEmails($container) {
        $container.find('li').each(function() {
            const $li = $(this);
            const $radio = $li.find('input[type="radio"]');
            const $label = $('<label>').append($li.contents().not($radio));
            const email = $label.text().match(/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/);
            if (email) {
                const obfuscatedEmail = email[0].replace(/(.{3}).*(@.*)/, '$1***$2');
                $label.text($label.text().replace(email[0], obfuscatedEmail));
            }
            $li.empty().append($radio).append($label);
        });
    }

    // Function to reset form state
    function resetForm() {
        $emailSearchResults.empty();
        $surnameSearchResults.empty();
        $backButton.hide();
        $form[0].reset();
        $preRegisteredYes.hide();
        $preRegisteredNo.hide();
        $surnameSearch.hide();
        $firstPage.show();
        $preRegisteredYes.find('input').prop('required', true);
        $preRegisteredNo.find('input, select').prop('required', true);
    }
});

// Function to hide the back button container
function hideBackButtonOnConfirmation() {
    const backButtonContainer = document.querySelector('.back-button-container');
    
    // Ensure the back button exists
    if (backButtonContainer) {
        const observer = new MutationObserver((mutationsList, observer) => {
            mutationsList.forEach(mutation => {
                // Check if a new node with class confirmation-message was added
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && node.classList.contains('confirmation-message')) {
                            // Hide the back button container when confirmation-message enters the DOM
                            backButtonContainer.style.display = 'none';
                        }
                    });
                }
            });
        });

        // Start observing the document body for child list mutations
        observer.observe(document.body, { childList: true, subtree: true });
    }
}

// Run the function to initiate the observer
hideBackButtonOnConfirmation();
