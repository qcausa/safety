jQuery(function ($) {
    /**
     * Handle rename action.
     */
    $(document).on('click', '.bpmtp-tools-form-table .bpmtp-rename-action', function () {

        var $tr = $(this).parents('tr');
        var value = $tr.find('.bpmtp-new-name').val();
        // Make sure the new value is given.
        if (value.length < 1) {
            showNotice(BPMTPTools.emptyTypeNameNotice);
            return false;
        }

        // Do the action...
        showLoader($tr);
        $.post( BPMTPTools.ajaxURL, {
            action: 'bpmtp_rename_member',
            new_type: value,
            current_type: $tr.find('.bpmtp-current-type').val(),
            _wpnonce: BPMTPTools.wpnonce
        }, function ( response ) {

            if( ! response.success ) {
                hideLoader( $tr);
                showFeedback( response.data.message, 'error' );
                return false;
            }

            // if we are here, the operation succeeded.
            // Show notice.
            $tr.replaceWith(response.data.html);
            showNotice(response.data.message);
        });

        return false;
    });

    /**
     * On clicking dismiss of notice, hide the notice.
     */
    $(document).on('click', '.bpmtp-tools-notice .notice-dismiss', function () {
        $(this).parents('.bpmtp-tools-notice').remove();
    });

    /**
     * Show notice.
     *
     * @param message
     */
    function showNotice(message) {
        showFeedback(message, 'notice');
    }

    /**
     * Show feedback message.
     *
     * @param {string} message message to be shown.
     * @param {string} className css class name.
     */
    function showFeedback(message, className) {
        var html = '<div  class="updated is-dismissible ' + className + ' bpmtp-tools-notice ">' +
            '<p><strong>' + message + '</strong></p>' +
            '<button type="button" class="notice-dismiss"></button>' +
            '</div>';

        $('#bpmtp-rename-section').before(html);
    }

    /**
     * Show loader icon.
     *
     * @param $tr
     */
    function showLoader( $tr ) {
        $tr.find('.bpmtp-loader').show();
    }

    /**
     * Hide loader icon.
     *
     * @param $tr
     */
    function hideLoader( $tr ) {
        $tr.find('.bpmtp-loader').hide();
    }
});