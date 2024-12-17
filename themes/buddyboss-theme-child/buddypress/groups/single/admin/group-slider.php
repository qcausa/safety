<?php
/**
 * BuddyPress - Groups Admin: Change Group Photo
 *
 * Custom template part for the 'Change Group Photo' tab in group management.
 *
 * @package BuddyPress
 * @subpackage BP Nouveau
 */

?>

<div class="bp-custom-tab-content">
    <h2><?php _e( 'Change Group Photo', 'textdomain' ); ?></h2>
    <p><?php _e( 'Upload or change the group photo here.', 'textdomain' ); ?></p>

    <!-- Simple upload form -->
    <form method="post" enctype="multipart/form-data">
        <label for="group-photo"><?php _e( 'Upload New Group Photo:', 'textdomain' ); ?></label><br>
        <input type="file" name="group-photo" id="group-photo" />
        <button type="submit" class="button button-primary"><?php _e( 'Upload Photo', 'textdomain' ); ?></button>
    </form>
</div>
