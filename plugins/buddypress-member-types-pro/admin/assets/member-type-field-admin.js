jQuery( document).ready(function() {

    //on initial run for edit field page
    if ( jQuery('#fieldtype').val() == 'membertype' ) {
        jQuery('#membertype').show();
    }
    //for new field
    jQuery(document).on('change', '#fieldtype', function() {
        var $this = jQuery(this);
        if ( $this.val() == 'membertype' ) {
            //display tos field
            jQuery('#membertype').show();
        } else {
            jQuery('#membertype').hide();
        }
    });

    jQuery(document).on('change', '.bpmtp-member-type-list-restriction', function() {
        var $this = jQuery(this);
        if ( $this.val() == 'all' ) {
            jQuery('#bpmtp-selected-member-type').addClass('bpmtp-admin-hidden');
        } else {
            jQuery('#bpmtp-selected-member-type').removeClass('bpmtp-admin-hidden');
        }
    });


    // for multi member types
    if ( jQuery('#fieldtype').val() == 'membertypes' ) {
        jQuery('#membertypes').show();
    }
    //for new field
    jQuery(document).on('change', '#fieldtype', function() {
        var $this = jQuery(this);
        if ( $this.val() == 'membertypes' ) {
            //display tos field
            jQuery('#membertypes').show();
        } else {
            jQuery('#membertypes').hide();
        }
    });

    jQuery(document).on('change', '.bpmtp-multi-member-type-list-restriction', function() {
        var $this = jQuery(this);
        if ( $this.val() == 'all' ) {
            jQuery('#bpmtp-multi-selected-member-type').addClass('bpmtp-admin-hidden');
        } else {
            jQuery('#bpmtp-multi-selected-member-type').removeClass('bpmtp-admin-hidden');
        }
    });
});
