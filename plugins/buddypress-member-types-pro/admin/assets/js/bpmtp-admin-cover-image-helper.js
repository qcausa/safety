/**
 * Credit
 * Adapted from Matt's code
 * @link  http://www.webmaster-source.com/2013/02/06/using-the-wordpress-3-5-media-uploader-in-your-plugin-or-theme/
 * Modified to be used with PT Settings
 */
jQuery(document).ready(function ($) {
     // avatar thumbnail
    //on remove
    $('#bpmtp-associated-cover-image-delete-btn').click(function () {
        $('#bpmtp-associated-cover-image-url').val('');
        $('#bpmtp-associated-cover-image').hide().attr('src', '');
        $(this).hide();
        return false;
    });

    var coverImageUploader;
    $('.bpmtp-associated-cover-image-upload-button').click(function (e) {

        e.preventDefault();
        var $this = $(this);
        //If the uploader object has already been created, reopen the dialog
        if (coverImageUploader) {
            coverImageUploader.open();
            return;
        }

        // Extend the wp.media object.
        coverImageUploader = wp.media.frames.file_frame = wp.media({
            title: $this.data('uploader-title'),
            button: {
                text: $this.data('btn-title')
            },
            allowedTypes: ['image'],
            multiple: false
        });

        //When a file is selected, grab the URL and set it as the text field's value
        coverImageUploader.on('select', function () {
            var attachment = coverImageUploader.state().get('selection').first().toJSON();
            //console.log(attachment.sizes);

            $('#bpmtp-associated-cover-image-url').val(attachment.url);
            $('#bpmtp-associated-cover-image').show().attr('src', attachment.url);
            $('#bpmtp-associated-cover-image-delete-btn').show();
        });

        //Open the uploader dialog
        coverImageUploader.open();
    });
});