$(function () {
    // -----------------------------------------------------------------------------------------------------------------
    // All AJAX requests will include the CSRF token
    // -----------------------------------------------------------------------------------------------------------------

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // -----------------------------------------------------------------------------------------------------------------
    // Ambassador Operations
    // -----------------------------------------------------------------------------------------------------------------

    // Close modals
    $('.modal-close-button').on('click', function () {
        $.modal.close();
    });

    var target_timeslot = null;
    var target_reservation_id = null;
    var target_script_id = null;
    var target_button = null;
    var target_reservation = null;
    var target_url = null;

    // -- Cancellation modal --------------------------------------------------------------------------------------------

    // Open cancellation modal
    $('.timeslot__reservation__cancel-initiate-button').on('click', function () {
        target_timeslot = $(this).closest('.timeslot');
        target_button = $(this);
        target_reservation_id = $(this).data('reservation');
        target_reservation = $(this).closest('.timeslot__reservation');

        $('#reservation-cancellation-modal').modal({
            fadeDuration: 100,
            fadeDelay: 1,
            showClose: false
        });
    });

    // Commit the deletion
    $('.timeslot__reservation__cancel-commit-button').on('click', function () {
        target_url = $(this).data('url');

        $.post(target_url, {reservation_id: target_reservation_id}, function () {
            target_reservation.remove();
            target_timeslot.find('.timeslot__time__dots img').first().remove();
        }).fail(function () {
            alert("The server reported an error when performing this operation. Unfortunately no more information is available.");
        }).always(function () {
            $.modal.close();
        });
    });

    // -- Checkin button -----------------------------------------------------------------------------------------------

    $(document).delegate('.timeslot__reservation--pending .timeslot__reservation__checkin-commit-button', 'click', function () {
        target_button = $(this);
        target_reservation_id = $(this).data('reservation');
        target_reservation = $(this).closest('.timeslot__reservation');
        target_url = $(this).data('url');

        $.post(target_url, {reservation_id: target_reservation_id}, function () {
            target_reservation.removeClass('timeslot__reservation--pending');
            target_reservation.addClass('timeslot__reservation--checked_in');
            target_reservation.removeClass('timeslot__reservation--complete');
        }).fail(function () {
            alert("The server reported an error when performing this operation. Unfortunately no more information is available.");
        }).always(function () {
            // Always
        });
    });

    // -- Demo button --------------------------------------------------------------------------------------------------

    $('.timeslot__reservation__demo-commit-button').on('click', function () {
        target_button = $(this);
        target_reservation_id = $(this).data('reservation');
        target_reservation = $(this).closest('.timeslot__reservation');
        target_url = $(this).data('url');

        $.post(target_url, {reservation_id: target_reservation_id}, function () {
            target_reservation.removeClass('timeslot__reservation--pending');
            target_reservation.removeClass('timeslot__reservation--checked_in');
            target_reservation.addClass('timeslot__reservation--complete');
        }).fail(function () {
            alert("The server reported an error when performing this operation. Unfortunately no more information is available.");
        }).always(function () {
            // Always
        });
    });

    // -- Open scripts modal -------------------------------------------------------------------------------------------

    $('.timeslot__reservation__script-initiate-button').on('click', function () {
        target_reservation_id = $(this).data('reservation');
        target_button = $(this);

        console.log('aadad');

        $('#reservation-script-modal-1').modal({
            fadeDuration: 100,
            fadeDelay: 1,
            showClose: false
        });
    });

    $('.timeslot__reservation__script-commit-button').on('click', function () {
        target_script_id = $(this).data('script');

        $.modal.close();

        $('#reservation-script-modal-' + target_script_id).modal({
            fadeDuration: 100,
            fadeDelay: 1,
            showClose: false
        });
    });

    // -- Open scripts modal -------------------------------------------------------------------------------------------

    $('.health-and-safety-modal-link').on('click', function () {
        $('#health-and-safety-modal').modal({
            fadeDuration: 100,
            fadeDelay: 1,
            showClose: false
        });
    });

    // -----------------------------------------------------------------------------------------------------------------
    // WYSIWYG
    // -----------------------------------------------------------------------------------------------------------------

    $('.wysiwyg').trumbowyg({
        fullscreenable: false,
        btns: [
            'viewHTML',
            'formatting',
            'btnGrp-design',
            'btnGrp-lists',
            'horizontalRule']
    });

    // -----------------------------------------------------------------------------------------------------------------
    // Flash message fading
    // -----------------------------------------------------------------------------------------------------------------

    setTimeout(function () {
        $(".flash-message").fadeOut("fast");
    }, 1000)

    // -----------------------------------------------------------------------------------------------------------------
    // Row Deleting
    // -----------------------------------------------------------------------------------------------------------------

    $('.row-delete-button').on('click', function () {
        $(this).closest('tr').remove();
    });

    // -----------------------------------------------------------------------------------------------------------------
    // Tabs
    // -----------------------------------------------------------------------------------------------------------------

    $('.tab-controls.tab-controls--clickable .tab-controls__tab').on('click', function () {
        $('.tab-controls__tab[data-tabset="' + $(this).data('tabset') + '"]').removeClass('active');
        $(this).addClass('active');

        $('.tab-content[data-tabset="' + $(this).data('tabset') + '"]').removeClass('active');
        $('.tab-content[data-tabset="' + $(this).data('tabset') + '"][data-tab="' + $(this).data('tab') + '"]').addClass('active');
    });

    // -----------------------------------------------------------------------------------------------------------------
    // Demo total calculations
    // -----------------------------------------------------------------------------------------------------------------

    $('.demo-total-trigger').on('change', function () {
        for (i = 1; i <= 7; i++) {
            var start = parseInt($('[name="day_' + i + '_start"]').val());
            var end = parseInt($('[name="day_' + i + '_end"]').val());
            var stations = parseInt($('[name="stations"]').val());

            if (isNaN(start) || isNaN(end)) {
                $('.demo-total--' + i).html('');
            } else {
                if (end <= start) {
                    $('.demo-total--' + i).html('(Invalid range)');
                } else if (isNaN(stations) || stations == 0) {
                    $('.demo-total--' + i).html('(No stations)');
                } else {
                    $('.demo-total--' + i).html(demo_total_text = ((end - start) / 30) * stations + ' demos');
                }
            }
        }
    });

    $('.demo-total-trigger').change();

    // -----------------------------------------------------------------------------------------------------------------
    // Ambassador controls
    // -----------------------------------------------------------------------------------------------------------------

    $('.timeslot__time').on('click', function () {
        $(this).siblings().toggle();
    });

    // -----------------------------------------------------------------------------------------------------------------
    // Field incrementing
    // -----------------------------------------------------------------------------------------------------------------

    $('.field-incrementer').on('click', function () {
        crementer($(this), 1);
    });

    $('.field-decrementer').on('click', function () {
        crementer($(this), -1);
    });

    function crementer($element, adjust) {
        var $sibling = $element.siblings('input');
        var value = parseInt($sibling.val());
        var min = parseInt($sibling.attr('min'));
        var max = parseInt($sibling.attr('max'));

        if ($sibling.attr('disabled')) {
            return;
        }

        if (typeof value != 'number' || isNaN(value)) {
            value = 0;
        }

        var newValue = value + adjust;

        if (typeof min == 'number' && newValue < min) {
            newValue = min;
        }

        if (typeof max == 'number' && newValue > max) {
            newValue = max;
        }

        $sibling.val(newValue);

        $sibling.trigger('change');
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Navigation
    // -----------------------------------------------------------------------------------------------------------------

    //$('.hamburger').on('mouseenter', function () {
    //    $('.navigation').toggle();
    //});

    $('.hamburger').on('click', function () {
        $('.navigation').toggle();
    });

    $('.navigation').on('mouseleave', function () {
        $('.navigation').hide();
    });

    $('.navigation a').on('click', function () {
        $(this).siblings('ul').toggle();
    });

    // -----------------------------------------------------------------------------------------------------------------
    // Location Filters
    // -----------------------------------------------------------------------------------------------------------------

    function queryStringUrlReplacement(url, param, value) {
        var re = new RegExp("[\\?&]" + param + "=([^&#]*)"), match = re.exec(url), delimiter, newString;

        if (match === null) {
            var hasQuestionMark = /\?/.test(url);
            delimiter = hasQuestionMark ? "&" : "?";
            newString = url + delimiter + param + "=" + value;
        } else {
            delimiter = match[0].charAt(0);
            newString = url.replace(re, delimiter + param + "=" + value);
        }

        return newString;
    }

    $('[name="location_region_filter"]').on('change', function () {
        window.location.href = queryStringUrlReplacement(window.location.href, 'region', this.value)
    });

    $('[name="location_brand_filter"]').on('change', function () {
        window.location.href = queryStringUrlReplacement(window.location.href, 'brand_id', this.value)
    });

    // -----------------------------------------------------------------------------------------------------------------
    // Fancy Select Boxes
    // -----------------------------------------------------------------------------------------------------------------

    if (typeof location_list_options !== 'undefined') {
        location_list_options = JSON.parse(location_list_options);
        location_list_data = JSON.parse(location_list_data);

        function initialize_magic_location_select($element) {
            $element.select2({
                placeholder: "Select location",
                data: location_list_options,
                escapeMarkup: function (markup) {
                    return markup;
                },
                templateResult: function (option) {
                    if (!option.id) {
                        return option.text;
                    }

                    return location_list_data[option.id].name + ' ' + '<br>' + location_list_data[option.id].city + ', ' + location_list_data[option.id].region;
                }
            });
        }

        $(".magic-location-select").each(function (key, element) {
            initialize_magic_location_select($(element));
        });
    }

    // -----------------------------------------------------------------------------------------------------------------

    if (typeof manager_list_options !== 'undefined') {
        manager_list_options = JSON.parse(manager_list_options);
        manager_list_data = JSON.parse(manager_list_data);

        function initialize_magic_manager_select($element) {
            $element.select2({
                placeholder: "Select user",
                data: manager_list_options,
                escapeMarkup: function (markup) {
                    return markup;
                },
                templateResult: function (option) {
                    if (!option.id) {
                        return option.text;
                    }

                    return manager_list_data[option.id].first_name + ' ' + manager_list_data[option.id].last_name + '<br>' + manager_list_data[option.id].email;
                }
            });
        }

        $(".magic-manager-select").each(function (key, element) {
            initialize_magic_manager_select($(element));
        });
    }

    // -----------------------------------------------------------------------------------------------------------------

    if (typeof ambassador_list_options !== 'undefined') {
        ambassador_list_options = JSON.parse(ambassador_list_options);
        ambassador_list_data = JSON.parse(ambassador_list_data);

        function initialize_magic_ambassador_select($element) {
            $element.select2({
                placeholder: "Select user",
                data: ambassador_list_options,
                escapeMarkup: function (markup) {
                    return markup;
                },
                templateResult: function (option) {
                    if (!option.id) {
                        return option.text;
                    }

                    return ambassador_list_data[option.id].first_name + ' ' + ambassador_list_data[option.id].last_name + '<br>' + ambassador_list_data[option.id].email;
                }
            });
        }

        $(".magic-ambassador-select").each(function (key, element) {
            initialize_magic_ambassador_select($(element));
        });
    }
});