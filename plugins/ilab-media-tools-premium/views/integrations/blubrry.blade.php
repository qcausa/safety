<script>
    jQuery(document).ready(function () {
        var button = jQuery("<div class='pp-blue-button' style='margin-left: 5px; margin-right: 10px'>Media Library</div>");
        button.on('click', function(e) {
           e.preventDefault();

            var selector = wp.media({
                title: 'Select an audio file to use',
                button: {
                    text: 'Use this audio file',
                },
                library: { type: "audio" },
                multiple: false	// Set to true to allow multiple files to be selected
            });

            selector.on('select', function() {
                console.log(selector.state().get('selection').first());

                jQuery('#powerpress_url_podcast').val(selector.state().get('selection').first().get('url'));
            });

            selector.open();

           return false;
        });

        jQuery('#pp-url-input-label-container-podcast').css({width: 'calc(100% - 135px)'});
        jQuery('#powerpress_url_podcast').css({width: 'calc(100% - 180px)'});

        button.insertAfter(jQuery('#powerpress_url_podcast'));
    });
</script>