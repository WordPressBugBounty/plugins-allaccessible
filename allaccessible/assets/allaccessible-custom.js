jQuery(document).ready(function ($) {
    // Options Form Handler
    $('#AACB_optionsForm').on('submit', function(event) {
        var $messageArea = $('#aacb_message');
        event.preventDefault();

        var $btn = $('#aacb-save-opt-btn').prop('disabled', true);
        var $loader = $('<span class="aacb_loader"></span>').insertAfter('.aacb_message');
        var id = $('#siteDetails').attr('data-siteid');

        var formData = new FormData($(this)[0]);
        var obj = {};
        for (var key of formData.keys()) {
            obj[key] = key === 'isWhiteLabel' ?
                (formData.get(key) === 'true') :
                formData.get(key);
        }

        $.ajax({
            type: "POST",
            url: 'https://app.allaccessible.org/api/save-site-options/' + id,
            data: JSON.stringify(obj),
            contentType: "application/json",
            success: function() {
                $messageArea.html('<div class="notice notice-success"><p><span class="aacb_success">Settings saved successfully <span class="dashicons dashicons-yes"></span></span></p></div>')
                    .attr('aria-live', 'polite')
                    .attr('role', 'status')
                    .delay(3000).fadeOut(300);
            },
            error: function() {
                alert('Failed to save options. Please try again.');
            },
            complete: function() {
                $loader.hide();
                $btn.prop('disabled', false);
            }
        });
    });

    // Account Form Handler
    $('#AACB_accountForm').on('submit', function(event) {
        event.preventDefault();

        var accountID = $("#aacb_accountID").val();
        if (!accountID) {
            alert('Please enter an account ID');
            return;
        }

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'AllAccessible_save_settings',
                nonce: ajax_object.nonce,
                opt_name: 'aacb_accountID',
                opt_value: accountID
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Failed to save settings');
                }
            },
            error: function() {
                alert('Failed to save settings. Please try again.');
            }
        });
    });

    // Trial Form Handler
    $('#AACB_trialForm').on('submit', function(event) {
        event.preventDefault();
        var $messageArea = $('#aacb_message');

        var $btn = $('#aacb-trial-btn').prop('disabled', true);
        var email = $("#aacb_email").val();
        var url = $("#aacb_url").val();

        if (!email || !url) {
            alert('Please fill in all required fields');
            $btn.prop('disabled', false);
            return;
        }

        // Add loading indicator
        var loadingIcon = $('<span class="dashicons dashicons-update-alt" style="animation: rotation 2s infinite linear; margin-left: 5px;"></span>');
        $btn.after(loadingIcon);

        $messageArea.html('<div class="notice notice-info"><p>Processing your request...</p></div>').attr('aria-live', 'polite');

        $.ajax({
            type: 'POST',
            url: 'https://app.allaccessible.org/api/add-site',
            data: JSON.stringify({
                email: email,
                url: url,
                source: 'wordpress'
            }),
            contentType: 'application/json; charset=utf-8',
            success: function(data) {
                if (data.error) {
                    alert('Error: ' + (data.errors || 'Failed to create trial'));
                    loadingIcon.remove();
                    $btn.prop('disabled', false);
                    return;
                }

                $.ajax({
                    url: ajax_object.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'AllAccessible_save_settings',
                        nonce: ajax_object.nonce,
                        opt_name: 'aacb_accountID',
                        opt_value: data
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || 'Failed to save settings');
                            loadingIcon.remove();
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function() {
                        loadingIcon.remove();
                        alert('Failed to save settings. Please try again.');
                        $btn.prop('disabled', false);
                    }
                });
            },
            error: function() {
                alert('Failed to create trial. Please try again.');
                loadingIcon.remove();
                $btn.prop('disabled', false);
            }
        });
    });

    // Notice Dismiss Handler
    $(document).on('click', '#aacb-premium-notice .notice-dismiss', function() {
        $.post(ajax_object.ajax_url, { 'action': 'aacb_dismiss_notice' });
    });

    // Improve keyboard navigation for settings form
    $('#AACB_optionsForm, #AACB_trialForm').on('keydown', 'input, select, button', function(e) {
        // If Enter key is pressed on any form element except textarea and submit button
        if (e.key === 'Enter' && !$(this).is('textarea') && !$(this).is('[type="submit"]')) {
            e.preventDefault();

            // Find the next focusable element
            var focusables = $('#AACB_optionsForm, #AACB_trialForm').find('input, select, textarea, button, a').filter(':visible:not([disabled])');
            var currentIndex = focusables.index(this);
            var nextElement = focusables.eq(currentIndex + 1);

            if (nextElement.length) {
                nextElement.focus();
            } else {
                // If we're at the last element, focus the submit button
                $('#aacb-save-opt-btn, #aacb-trial-btn').focus();
            }
        }
    });

    // Add ARIA attributes to the color picker
    if ($('#colorPicker').length) {
        $('.sp-replacer').attr({
            'role': 'button',
            'aria-label': 'Open color picker',
            'tabindex': '0'
        });

        // Make color picker accessible via keyboard
        $('.sp-replacer').on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                $(this).click();
            }
        });
    }
});
