jQuery(document).ready(function ($) {
    // Options Form Handler
    $('#AACB_optionsForm').on('submit', function(event) {
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
                var $success = $('<span class="aacb_success"> Saved<div alt="f147" class="dashicons dashicons-yes"></div></span>')
                    .insertAfter('.aacb_message')
                    .delay(3000)
                    .fadeOut(300);
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

        var $btn = $('#aacb-trial-btn').prop('disabled', true);
        var email = $("#aacb_email").val();
        var url = $("#aacb_url").val();

        if (!email || !url) {
            alert('Please fill in all required fields');
            $btn.prop('disabled', false);
            return;
        }

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
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Failed to save settings. Please try again.');
                        $btn.prop('disabled', false);
                    }
                });
            },
            error: function() {
                alert('Failed to create trial. Please try again.');
                $btn.prop('disabled', false);
            }
        });
    });

    // Notice Dismiss Handler
    $(document).on('click', '#aacb-premium-notice .notice-dismiss', function() {
        $.post(ajax_object.ajax_url, { 'action': 'aacb_dismiss_notice' });
    });
});
