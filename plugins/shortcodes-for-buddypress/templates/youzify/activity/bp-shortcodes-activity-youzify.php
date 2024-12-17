<?php

/**
 * BP Youzify - Activity Shortcode 
 * 
 * Template to be used while listing activity shortcode when Youzify is active with BuddyPress.
 * Reference  from youzify activity loop template. (includes/public/templates/activity/activity-loop.php)
 * 
 * @since 2.7.2
 * 
 */
global $activity_atts;

/**
 * Fires before the activity directory listing.
 *
 * @since 2.7.2
 */

do_action( 'bp_before_directory_activity' );

if ( ( !empty( $activity_atts ) ) && ( $activity_atts['use_compat'] ) ) {

}
if( empty( $atts ) ){
	$atts = array();
}

if ( ( !empty( $activity_atts ) ) && ( $activity_atts['allow_posting'] == 'true' ) &&  ( is_user_logged_in() ) ) :
	bp_get_template_part( 'activity/post-form' );
endif;

do_action( 'before_activity_listing_output', $atts, $activity_atts ); ?>

<?php 
if ( !empty( $activity_atts['bpsh_query'] ) ) {
    $bp_activities  = $activity_atts['bpsh_query'];
} else {
    $bp_activities  = bp_ajax_querystring( 'activity' );
}

if ( bp_has_activities( $bp_activities ) ) : ?>

<div id="youzify" class="bp-shortcode-activity youzify youzify-page noLightbox youzify-horizontal-layout youzify-page-btns-border-oval youzify-tabs-list-gradient youzify-global-wall youzify-blue-scheme">
	<div id="activity" class="activity">
		<ul id="activity-stream" class="activity-list item-list">

        <?php do_action( 'youzify_before_activity_loop_posts' ); ?>

            <?php while ( bp_activities() ) : bp_the_activity(); ?>
            <?php 
                if( is_admin() && isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode() && bp_get_activity_type() == "friendship_created" ){
                    continue;
                }
                ?>
                <?php do_action( 'bp_before_activity_entry' );  ?>

                <li class="activity activity_update activity-item youzify_effect fadeInDown full-visible" data-effect="fadeInDown" id="activity-<?php bp_activity_id(); ?>">

                    <?php do_action( 'bp_before_activity_entry_content' );  ?>

                    <div class="activity-content">

                        <div class="activity-header">

                            <?php do_action( 'bp_before_activity_entry_header' ); ?>

                            <div class="activity-avatar"><a href="<?php bp_activity_user_link(); ?>"><?php bp_activity_avatar(); ?></a></div>

                            <div class="activity-head"><?php bp_activity_action( array( 'no_timestamp' => true ) );?><div class="youzify-timestamp-area"><?php if ( function_exists( 'youzify_get_activity_time_stamp_meta' ) ) { echo wp_kses_post( youzify_get_activity_time_stamp_meta() ); } ?></div></div>

                        </div>

                        <?php bp_activity_content_body(); ?>

                        <?php

                        /**
                         * Fires after the display of an activity entry content.
                         *
                         * @since 2.7.2
                         */
                        do_action( 'bp_activity_entry_content' ); ?>
                        <div class="youzify-activity-statistics"><?php

                            do_action( 'youzify_before_activity_statistics' );

                            if ( function_exists( 'youzify_show_who_liked_activities' ) ) {
                                youzify_show_who_liked_activities();
                            }
                            if ( function_exists( 'youzify_activity_comments_count' ) ) {
                                youzify_activity_comments_count();
                            }
                            do_action( 'youzify_after_activity_statistics' );

                        ?></div>
                        <div class="activity-meta" data-activity-id="<?php echo esc_attr( bp_get_activity_id() ); ?>"><?php

                            if ( bp_get_activity_type() == 'activity_comment' && is_user_logged_in() ) : ?><a href="<?php bp_activity_thread_permalink(); ?>" class="button view bp-secondary-action"><?php echo esc_html__( 'View Conversation', 'shortcodes-for-buddypress' ); ?></a><?php

                            endif;

                            if ( is_user_logged_in() ) :

                                if ( bp_activity_can_favorite() ) {
                                    if( function_exists( 'youzify_get_post_like_button' ) ) {
                                        echo wp_kses_post( youzify_get_post_like_button() );
                                    }
                                    
                                }

                                if ( bp_activity_can_comment() ) : ?><a href="<?php bp_activity_comment_link(); ?>" class="button acomment-reply bp-primary-action" id="acomment-comment-<?php bp_activity_id(); ?>"><?php echo esc_html__( 'Comment', 'shortcodes-for-buddypress' ); ?></a><?php

                                endif;

                                do_action( 'bp_activity_after_comment_button' );
                                /**
                                 * Fires at the end of the activity entry meta data area.
                                 *
                                 * @since 2.7.2
                                 */
                                do_action( 'bp_activity_entry_meta' );

                                endif;

                                do_action( 'bp_activity_entry_meta_non_logged_in', bp_get_activity_id() );

                        ?></div>

                    </div>
                    <?php

                    /**
                     * Fires before the display of the activity entry comments.
                     *
                     * @since 2.7.2
                     */
                    do_action( 'bp_before_activity_entry_comments' ); ?>

                    <?php if ( ( bp_activity_get_comment_count() || bp_activity_can_comment() ) || bp_is_single_activity() ) : ?>

                        <div class="activity-comments">

                            <?php bp_activity_comments(); ?>

                            <?php if ( is_user_logged_in() && bp_activity_can_comment() ) : ?>
                                <?php $emoji_active = youzify_option( 'youzify_enable_comments_emoji', 'on' ) == 'on' ? true : false; ?>
                                <form action="<?php bp_activity_comment_form_action(); ?>" method="post" id="ac-form-<?php bp_activity_id(); ?>" class="ac-form"<?php bp_activity_comment_form_nojs_display(); ?>>
                                    <div class="ac-reply-content">
                                        <div class="ac-textarea<?php if ( $emoji_active ) echo ' youzify-comments-emojis';?>">
                                            <label for="ac-input-<?php bp_activity_id(); ?>" class="bp-screen-reader-text"><?php
                                                echo esc_html__( 'Comment', 'youzify' );
                                            ?></label>
                                            <textarea id="ac-input-<?php bp_activity_id(); ?>" class="ac-input bp-suggestions" name="ac_input_<?php bp_activity_id(); ?>" placeholder="<?php echo esc_attr__( 'Write a Comment ...', 'shortcodes-for-buddypress' ); ?>"></textarea>
                                            <?php if ( $emoji_active ) : ?>
                                                <div class="youzify-load-emojis"><i class="far fa-smile"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <input type="submit" name="ac_form_submit" value="<?php esc_attr_e( 'Post', 'buddypress' ); ?>" style="display: none;">
                                        <div class="youzify-wall-comments-buttons">
                                            <?php do_action( 'youzify_activity_comment_buttons' ); ?>
                                            <div class="youzify-send-comment"><i class="fas fa-paper-plane"></i></div>
                                        </div>
                                        <input type="hidden" name="comment_form_id" value="<?php bp_activity_id(); ?>">
                                    </div>

                                    <?php

                                    /**
                                     * Fires after the activity entry comment form.
                                     *
                                     * @since 2.7.2
                                     */
                                    do_action( 'bp_activity_entry_comments' ); ?>

                                    <?php wp_nonce_field( 'new_activity_comment', '_wpnonce_new_activity_comment' ); ?>

                                </form>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                    <?php

                    /**
                     * Fires after the display of the activity entry comments.
                     *
                     * @since 2.7.2
                     */
                    do_action( 'bp_after_activity_entry_comments' ); ?>

                </li>

                <?php

                /**
                 * Fires after the display of an activity entry.
                 *
                 * @since 2.7.2
                 */
                do_action( 'bp_after_activity_entry' ); ?>

            <?php endwhile; ?>
    	</ul>
    </div>
<?php else : ?>
    <div id="message" class="info">
		<p><?php echo esc_html__( 'There were no activities found.', 'shortcodes-for-buddypress' ); ?></p>
	</div>
<?php endif; ?>

<?php do_action( 'after_activity_listing_output', $atts, $activity_atts ); ?>

<?php
if ( ( !empty( $activity_atts ) ) && ( $activity_atts['use_compat'] ) ) {
	echo '</div>';
}
