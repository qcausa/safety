<?php

namespace WPDeveloper\BetterDocs\Editors\BlockEditor\Blocks;

use WPDeveloper\BetterDocs\Editors\BlockEditor\Block;

class ReadingTime extends Block {

    protected $frontend_styles = [
        'reading-time'
    ];

    protected $editor_styles = [
        'reading-time'
    ];

    public function get_name() {
        return 'reading-time';
    }

    public function get_default_attributes() {
        return [
            'blockId'                 => '',
            'readingTimeTitle'        => '',
            'readingTimeText'         => __( 'min read', 'betterdocs' ),
            'singularReadingTimeText' => __( 'min read', 'betterdocs' )
        ];
    }

    public function render( $attributes, $content ) {
        $settings = $attributes;
        echo '<div class="' . $settings['blockId'] . '">';
        echo do_shortcode( '[betterdocs_reading_time singular_reading_text="'.$settings['singularReadingTimeText'].'" reading_text="' . $settings['readingTimeText'] . '" reading_title="' . $settings['readingTimeTitle'] . '"]' );
        echo '</div>';
    }

    public function view_params() {
        return [
            'reactions_text' => $this->attributes['reaction_text']
        ];
    }
}
