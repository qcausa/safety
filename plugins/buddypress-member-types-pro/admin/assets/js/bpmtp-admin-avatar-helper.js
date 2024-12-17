/**
 * Credit
 * Adapted from Matt's code
 * @link  http://www.webmaster-source.com/2013/02/06/using-the-wordpress-3-5-media-uploader-in-your-plugin-or-theme/
 * Modified to be used with PT Settings
 */
jQuery(document).ready(function ($) {
     // avatar thumbnail
    //on remove
    $('#bpmtp-associated-avatar-thumb-delete-btn').click(function () {
        $('#bpmtp-associated-avatar-thumb-url').val('');
        $('#bpmtp-associated-avatar-thumb-image').hide().attr('src', '');
        $(this).hide();
        return false;
    });

    var thumbnail_uploader;
    $('.bpmtp-associated-avatar-thumb-upload-button').click(function (e) {

        e.preventDefault();
        var $this = $(this);
        //If the uploader object has already been created, reopen the dialog
        if (thumbnail_uploader) {
            thumbnail_uploader.open();
            return;
        }

        // Extend the wp.media object.
        thumbnail_uploader = wp.media.frames.file_frame = wp.media({
            title: $this.data('uploader-title'),
            button: {
                text: $this.data('btn-title')
            },
            allowedTypes: ['image'],
            multiple: false
        });

        //When a file is selected, grab the URL and set it as the text field's value
        thumbnail_uploader.on('select', function () {
            var attachment = thumbnail_uploader.state().get('selection').first().toJSON();
            //console.log(attachment.sizes);

            $('#bpmtp-associated-avatar-thumb-url').val(attachment.url);
            $('#bpmtp-associated-avatar-thumb-image').show().attr('src', attachment.url);
            $('#bpmtp-associated-avatar-thumb-delete-btn').show();
        });

        //Open the uploader dialog
        thumbnail_uploader.open();
    });

    // Avatar full image handler

    $('#bpmtp-associated-avatar-full-delete-btn').click(function () {
        $('#bpmtp-associated-avatar-full-url').val('');
        $('#bpmtp-associated-avatar-full-image').hide().attr('src', '');
        $(this).hide();
        return false;
    });

    var full_uploader;
    $('.bpmtp-associated-avatar-full-upload-button').click(function (e) {

        e.preventDefault();
        var $this = $(this);
        //If the uploader object has already been created, reopen the dialog
        if (full_uploader) {
            full_uploader.open();
            return;
        }

        // Extend the wp.media object.
        full_uploader = wp.media.frames.file_frame = wp.media({
            title: $this.data('uploader-title'),
            button: {
                text: $this.data('btn-title')
            },
            allowedTypes: ['image'],
            multiple: false
        });

        //When a file is selected, grab the URL and set it as the text field's value
        full_uploader.on('select', function () {
            var attachment = full_uploader.state().get('selection').first().toJSON();
            //console.log(attachment.sizes);

            $('#bpmtp-associated-avatar-full-url').val(attachment.url);
            $('#bpmtp-associated-avatar-full-image').show().attr('src', attachment.url);
            $('#bpmtp-associated-avatar-full-delete-btn').show();
        });

        //Open the uploader dialog
        full_uploader.open();
    });
});