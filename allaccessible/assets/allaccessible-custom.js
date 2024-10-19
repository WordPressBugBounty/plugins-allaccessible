jQuery(document).ready(function ($) {

    $('#AACB_optionsForm').on('submit', function(event){

        event.preventDefault();

        $('#aacb-save-opt-btn').prop('disabled', true);
        var $this = $(this);
        // $this.after('<span class="aacb_loader"></span>');
        $('.aacb_message').after('<span class="aacb_loader"></span>');
        var id = $('#siteDetails').attr('data-siteid');
        var obj = {};
        var formData = new FormData($('#AACB_optionsForm')[0]);
        for (var key of formData.keys()) {
            if (key === 'isWhiteLabel') {
                obj[key] = (formData.get(key) === 'true');
            } else {
                obj[key] = formData.get(key);
            }
        }
        var xhr = new XMLHttpRequest();
        xhr.open("POST", 'https://app.allaccessible.org/api/save-site-options/'+id);

        xhr.setRequestHeader("Accept", "application/json");
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                $('.aacb_loader').hide();
                // $this.after('<span class="aacb_success"> Saved<div alt="f147" class="dashicons dashicons-yes"></div></a>');
                $('.aacb_message').after('<span class="aacb_success"> Saved<div alt="f147" class="dashicons dashicons-yes"></div></a>');
                $('.aacb_success').delay(3000).fadeOut(300);
                $('#aacb-save-opt-btn').prop('disabled', false);
            } else {
                $('.aacb_loader').hide();
                $('#aacb-save-opt-btn').prop('disabled', false);
            }};
        xhr.send(JSON.stringify(obj));

    });

    /* update option meta */
    $('#AACB_accountForm').on('submit', function() {

        var accountID = $( "#aacb_accountID" ).val();

        $.ajax({
            url      : ajaxurl,
            type     : 'POST',
            dataType : 'json',
            data : {
                action:'AllAccessible_save_settings',
                opt_name: 'aacb_accountID',
                opt_value: accountID
            },
            beforeSend: function (xhr) {

            },
            success: function (data, textStatus, jqXHR) {

                location.reload();

                if (data.status === 'OK') {
                    $('.error').html(data.message);

                } else if (data.status === 'KO') {
                    $('.error').html(data.v_error);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log('Something went wrong!');
            }
        });
    });

    // process the form
    $('#AACB_trialForm').on('submit', function(event) {

        $('#aacb-trial-btn').prop('disabled', true);

        // process the form
        $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : 'https://app.allaccessible.org/api/add-site', // the url where we want to POST
            data : JSON.stringify({
                email: $( "#aacb_email" ).val(),
                url: $( "#aacb_url" ).val(),
                source: 'wordpress'
            }),
            dataType    : 'text',
            encode          : true,
            contentType: 'application/json; charset=utf-8',
            success: function(data){
                if(data.error){
                    //show error message here
                    data.errors
                    $('#aacb-trial-btn').prop('disabled', false);
                }else{
                    //handle success part
                    data.message

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'AllAccessible_save_settings',
                            opt_name: 'aacb_accountID',
                            opt_value: data
                        },
                        success: function (data, textStatus, jqXHR) {

                            location.reload();

                        }
                    });

                }
            },
            error: function(jqXHR, textStatus, errorThrown){
                //request error
                $('#aacb-trial-btn').prop('disabled', false);
                console.log('jqXHR:');
                console.log(jqXHR);
                console.log('textStatus:');
                console.log(textStatus);
                console.log('errorThrown:');
                console.log(errorThrown);
            }
        });

        // stop the form from submitting the normal way and refreshing the page
        event.preventDefault();
    });

    $(document).on('click', '#aacb-premium-notice .notice-dismiss', function() {

        //console.log('dismiss clicked');

        var data = {
            'action': 'aacb_dismiss_notice'
        };

        $.post(ajax_object.ajax_url, data, function (response) {
            //console.log('Notice dismissed: ' + response);
        });
    });

});