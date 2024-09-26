<?php
/*
Plugin Name: Post View Counter and Top Viewed Posts
Description: A plugin to track post views and display the top-viewed posts via a shortcode.
Version: 1.0
Author: Shahin
*/

// Track post views when a post is visited
function pvc_track_post_views($post_id) {
	if (!is_single() || !is_user_logged_in() || empty($post_id)) {
		return;
	}

	$count_key = 'pvc_post_views_count';
	$views = get_post_meta($post_id, $count_key, true);

	if ($views == '') {
		$views = 0;
		delete_post_meta($post_id, $count_key);
		add_post_meta($post_id, $count_key, '1');
	} else {
		$views++;
		update_post_meta($post_id, $count_key, $views);
	}
}
add_action('wp_head', 'pvc_track_post_views');

// Get the post view count for a specific post
function pvc_get_post_views($post_id) {
	$count_key = 'pvc_post_views_count';
	$views = get_post_meta($post_id, $count_key, true);
	if ($views == '') {
		return '0';
	}
	return $views;
}

// Shortcode to display top viewed posts
function pvc_top_viewed_posts($atts) {
	// Define shortcode attributes
	$atts = shortcode_atts(array(
		'posts_per_page' => 5 // Number of posts to display
	), $atts, 'top_viewed_posts');

	// Get top viewed posts based on post meta
	$args = array(
		'post_type'      => 'post',
		'posts_per_page' => $atts['posts_per_page'],
		'meta_key'       => 'pvc_post_views_count',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
	);

	$posts_query = new WP_Query($args);

	if ($posts_query->have_posts()) {
		$output = '<ul class="top-viewed-posts">';
		while ($posts_query->have_posts()) {
			$posts_query->the_post();

			// Create output for each post
			$output .= '<li>';
			$output .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
			$output .= ' - ' . pvc_get_post_views(get_the_ID()) . ' Views';
			$output .= '</li>';
		}
		$output .= '</ul>';
	} else {
		$output = '<p>No top viewed posts found.</p>';
	}

	wp_reset_postdata();
	return $output;
}
add_shortcode('top_viewed_posts', 'pvc_top_viewed_posts');

// Function to reset the view count for a post (optional)
function pvc_reset_post_views($post_id) {
	$count_key = 'pvc_post_views_count';
	delete_post_meta($post_id, $count_key);
}

// Admin column to display post view count in the posts list
function pvc_add_views_column($columns) {
	$columns['post_views'] = 'Views';
	return $columns;
}
add_filter('manage_posts_columns', 'pvc_add_views_column');

// Populate the view count in the custom column
function pvc_display_views_column($column_name, $post_id) {
	if ($column_name === 'post_views') {
		echo pvc_get_post_views($post_id);
	}
}
add_action('manage_posts_custom_column', 'pvc_display_views_column', 10, 2);
