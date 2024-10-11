<?php
/**
 * Template Name: Custom No-Style Template
 */
get_header(); ?>

<style>
    /* Remove all default theme styles from the body and all nested elements, including those with !important */
    body.page-template-custom-template .site-content,
    body.page-template-custom-template .site-content * {
        all: unset !important;
    }
</style>

<div class="custom-page-body">
    <!-- Your content goes here -->
    <?php while ( have_posts() ) : the_post(); ?>
        <h1><?php the_title(); ?></h1>
        <div class="content">
            <?php the_content(); ?>
        </div>
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
