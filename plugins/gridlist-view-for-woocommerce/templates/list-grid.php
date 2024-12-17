<?php if ( ! empty($title) ) { 
    ?><div class="berocket_lgv_title"><?php 
    if( ! empty($args['before_title']) ) {
        echo $args['before_title'];
    }
    echo $title; 
    if( ! empty($args['after_title']) ) {
        echo $args['after_title'];
    }
    ?></div><?php
} 
$product_style = br_lgv_get_cookie ( 0 );
?>
<div class="berocket_lgv_widget"<?php echo ' style="',( empty( $position ) ? '' : 'float:'.$position.';' ), 'padding: '.( @ (int)$padding['top'] ? @ (int)$padding['top'] : '0' ).'px '.( @ (int)$padding['right'] ? @ (int)$padding['right'] : '0' ).'px '.( @ (int)$padding['bottom'] ? @ (int)$padding['bottom'] : '0' ).'px '.( @ (int)$padding['left'] ? @ (int)$padding['left'] : '0' ).'px;"'; ?>>
    <?php do_action('br_lgv_before_list_grid_buttons');
    global $berocket_hide_grid_list_buttons;
    if( !$berocket_hide_grid_list_buttons ) { ?>
    <a href="#" data-type="grid" class="berocket_lgv_set <?php 
        echo ( empty( $custom_class ) ? 'berocket_lgv_button' : $custom_class );
        if ( $product_style == 'grid' || ! $product_style ) echo ' selected' ?> berocket_lgv_button_grid"><i class="fa fa-th"></i></a>
    <a href="#" data-type="list" class="berocket_lgv_set <?php 
        echo ( empty( $custom_class ) ? 'berocket_lgv_button' : $custom_class );
        if ( $product_style == 'list' ) echo ' selected' ?> berocket_lgv_button_list"><i class="fa fa-bars"></i></a>
    <?php }
    do_action('br_lgv_after_list_grid_buttons') ?>
</div>
