jQuery( function ( $ ) {

    // For Tab options.
    $('#_bpptc_tab_is_existing').on('click', function () {
        toggle_tab_option($(this));
    });

    // on dom ready
    $(document).ready(function () {
        toggle_tab_option($('#_bpptc_tab_is_existing'));
        $('.bpptc-existing-subnav input').each(function () {
            var $this = $(this);
            var $group = $this.parents('.cmb-repeatable-grouping');
            toggle_subnav($this, $group);
        });
    });

    // on click handler
    $(document).on('click', '.bpptc-existing-subnav input', function () {
        var $this = $(this);
        var $group = $this.parents('.cmb-repeatable-grouping');
        toggle_subnav($this, $group);
    });

    // fo sub tabs.
    function toggle_tab_option($element) {
        if ($element.is(':checked')) {
            $('.bpptc-existing-tab-hide').hide();
            $('.bpptc-existing-tab-show').show();
            //  $(existing_tab_option_selectors).show();
            // $('.bpptc-new-tab-show').hide();
        } else {
            // it's not our existing tab, so make sure to show these settings.
            $('.bpptc-existing-tab-hide').show();
            $('.bpptc-existing-tab-show').hide();
            //$('.bpptc-new-tab-show').show();

        }
    }
    // Toggle subnav options.
    function toggle_subnav($element, $container) {

        if ($element.is(':checked')) {
            $('.bpptc-existing-subnav-hide', $container).hide();
            $('.bpptc-existing-subnav-show', $container).show();
            //  $(existing_tab_option_selectors).show();
            // $('.bpptc-new-tab-show').hide();
        } else {
            // it's not our existing tab, so make sure to show these settings.
            $('.bpptc-existing-subnav-hide', $container).show();
            $('.bpptc-existing-subnav-show', $container).hide();
            //$('.bpptc-new-tab-show').show();

        }
    }
});