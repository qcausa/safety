jQuery(function ($) {

    // For Tab options.
    $('#_bpgtc_tab_is_existing').on('click', function () {
        var $this = $(this);
        toggle_tab_option($this);
        show_hide_subnavs( $this );
    });

    // on dom ready
    $(document).ready(function () {
        toggle_tab_option($('#_bpgtc_tab_is_existing'));
        $('.bpgtc-existing-subnav input').each(function () {
            var $this = $(this);
            var $group = $this.parents('.cmb-repeatable-grouping');
            toggle_subnav($this, $group);
        });

        show_hide_subnavs( $('#_bpgtc_tab_is_existing') );
    });

    // on click handler
    $(document).on('click', '.bpgtc-existing-subnav input', function () {
        var $this = $(this);
        var $group = $this.parents('.cmb-repeatable-grouping');
        toggle_subnav($this, $group);
    });

    // show hide sub navs.
    function show_hide_subnavs($option) {
        if ($option.is(':checked')) {
            $('#_bpgtc_subnav_items_repeat').hide();
        } else {
            $('#_bpgtc_subnav_items_repeat').show();
        }
    }

    // fo sub tabs.
    function toggle_tab_option($element) {
        if ($element.is(':checked')) {
            $('.bpgtc-existing-tab-hide').hide();
            $('.bpgtc-existing-tab-show').show();
            //  $(existing_tab_option_selectors).show();
            // $('.bpgtc-new-tab-show').hide();
        } else {
            // it's not our existing tab, so make sure to show these settings.
            $('.bpgtc-existing-tab-hide').show();
            $('.bpgtc-existing-tab-show').hide();
            //$('.bpgtc-new-tab-show').show();

        }
    }

    // Toggle subnav options.
    function toggle_subnav($element, $container) {

        if ($element.is(':checked')) {
            $('.bpgtc-existing-subnav-hide', $container).hide();
            $('.bpgtc-existing-subnav-show', $container).show();
            //  $(existing_tab_option_selectors).show();
            // $('.bpgtc-new-tab-show').hide();
        } else {
            // it's not our existing tab, so make sure to show these settings.
            $('.bpgtc-existing-subnav-hide', $container).show();
            $('.bpgtc-existing-subnav-show', $container).hide();
            //$('.bpgtc-new-tab-show').show();
        }
    }
});