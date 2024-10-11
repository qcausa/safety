<?php

namespace WPDeveloper\BetterDocs\Shortcodes;
use WPDeveloper\BetterDocs\Core\Shortcode;

class Reactions extends Shortcode {
    public $view_wrapper = 'betterdocs-article-reactions';

    public function get_name() {
        return 'betterdocs_article_reactions';
    }

    public function get_style_depends() {
        return ['betterdocs-reactions'];
    }

    public function get_script_depends() {
        return ['betterdocs-reactions'];
    }

    /**
     * Summary of default_attributes
     * @return array
     */
    public function default_attributes() {
        return [
            'text'   => '',
            'layout' => 'layout-1'
        ];
    }

    public function generate_attributes() {
        $attributes = [
            'class' => [
                $this->attributes['layout']
            ]
        ];

        return $attributes;
    }

    /**
     * Summary of render
     *
     * @param mixed $atts
     * @param mixed $content
     * @return mixed
     */
    public function render( $atts, $content = null ) {
        if ( isset( $atts['layout'] ) && $atts['layout'] == 'layout-2' ) {
            $this->views( 'widgets/reactions-2' );
        } else if ( isset( $atts['layout'] ) && $atts['layout'] == 'layout-3' ) {
                $this->views( 'widgets/reactions-3' );
        } else {
            $this->views( 'widgets/reactions' );
        }
    }

    public function view_params() {
        $wrapper_attr = $this->generate_attributes();
        $reactions_text = ! empty( $this->attributes['text'] ) ? $this->attributes['text'] : $this->customizer->get(
            'betterdocs_post_reactions_text', __( 'What are your Feelings', 'betterdocs' )
        );

        return [
            'wrapper_attr'   => $wrapper_attr,
            'reactions_text' => $reactions_text
        ];
    }
}
