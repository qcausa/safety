<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Collection;

class Manager {
	public function __construct() {
	}

	/**
	 * @return array<string,string>
	 */
	public function get_search_tags() {
		return [
			'Post' => 'medium',
			'Taxonomy' => 'large',
			'Query' => 'small',
			'User' => 'medium',
			'Loop' => 'large',
			'Custom Field' => 'small',
			'Featured Image' => 'large',
		];
	}

	protected function get_placeholder_description() {
		return [
			'field-name' => esc_html__( 'Field Name', 'dynamic-shortcodes' ),
			'category-slug' => esc_html__( 'Slug of the category', 'dynamic-shortcodes' ),
			'query-users' => esc_html__( 'Any query on users', 'dynamic-shortcodes' ),
			'user-field' => esc_html__( 'User field', 'dynamic-shortcodes' ),
			'separator' => esc_html__( 'Any separator', 'dynamic-shortcodes' ),
			'custom-message' => esc_html__( 'Any custom message or a Dynamic Shortcode', 'dynamic-shortcodes' ),
			'user-id' => esc_html__( 'User ID', 'dynamic-shortcodes' ),
			'author-id' => esc_html__( 'Author ID', 'dynamic-shortcodes' ),
			'post-id' => esc_html__( 'Post ID', 'dynamic-shortcodes' ),
			'term-id' => esc_html__( 'Term ID', 'dynamic-shortcodes' ),
			'media-id' => esc_html__( 'Media ID', 'dynamic-shortcodes' ),
			'date' => esc_html__( 'Any date', 'dynamic-shortcodes' ),
			'date-format' => esc_html__( 'Any date format', 'dynamic-shortcodes' ),
			'acf-sub-field' => esc_html__( 'Any ACF sub field', 'dynamic-shortcodes' ),
			'acf-repeater-field' => esc_html__( 'Any ACF repeater field', 'dynamic-shortcodes' ),
			'cache-key' => esc_html__( 'Key used to retrieve the cached value', 'dynamic-shortcodes' ),
			'shortcode' => esc_html__( 'Any shortcode', 'dynamic-shortcodes' ),
			'cache-expiration' => esc_html__( 'Expiration time of the cached value', 'dynamic-shortcodes' ),
			'taxonomy' => esc_html__( 'Taxonomy Slug', 'dynamic-shortcodes' ),
			'post-status' => esc_html__( 'Post Status', 'dynamic-shortcodes' ),
		];
	}

	/**
	 * @return array
	 */
	public function get_data() {
		$placeholders = $this->get_placeholder_description();

		return [
			[
				'description' => esc_html__( 'Retrieve an ACF Field', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:field-name}',
				'placeholders' => [
					'field-name' => $placeholders['field-name'],
				],
				'tags' => [ 'ACF', 'Custom Field' ],
			],
			[
				'description' => esc_html__( 'Display the current post title', 'dynamic-shortcodes' ),
				'shortcode' => '{post:title}',
				'tags' => [ 'Post', 'Title' ],
			],
			[
				'description' => esc_html__( 'List terms of a taxonomy for the post', 'dynamic-shortcodes' ),
				'shortcode' => '{post:terms@taxonomy=category}',
				'placeholders' => [
					'category' => $placeholders['taxonomy'],
				],
				'tags' => [ 'Taxonomy', 'Terms' ],
			],
			[
				'description' => esc_html__( 'Retrieve Posts with a Specific Custom Field Value', 'dynamic-shortcodes' ),
				'shortcode' => '{query:posts@meta_key=event_date meta_value="2023-12-01"}',
				'placeholders' => [
					'event_date' => $placeholders['field-name'],
					'2023-12-01' => $placeholders['date'],
				],
				'tags' => [ 'Query', 'Post', 'Custom Field' ],
			],
			[
				'description' => esc_html__( 'Loop Users', 'dynamic-shortcodes' ),
				'shortcode' => "{for:user-id {query:users} [{user:nickname @ID={get:user-id}] @ sep=', '}",
				'placeholders' => [
					'user-id' => $placeholders['user-id'],
					'{query:users}' => $placeholders['query-users'],
					'nickname' => $placeholders['user-field'],
					', ' => $placeholders['separator'],
				],
				'tags' => [ 'Users', 'Loop', 'Query' ],
			],
			[
				'description' => esc_html__( 'Display tomorrow\'s date', 'dynamic-shortcodes' ),
				'shortcode' => '{date:"+1 day"}',
				'tags' => [ 'Date', 'Future' ],
			],
			[
				'description' => esc_html__( 'Display yesterday\'s date', 'dynamic-shortcodes' ),
				'shortcode' => '{date:"-1 day"}',
				'tags' => [ 'Date', 'Past' ],
			],
			[
				'description' => esc_html__( 'Show the date one week from now', 'dynamic-shortcodes' ),
				'shortcode' => '{date:"+1 week"}',
				'tags' => [ 'Date', 'Future' ],
			],
			[
				'description' => esc_html__( 'Display the date in one month', 'dynamic-shortcodes' ),
				'shortcode' => '{date:"+1 month"}',
				'tags' => [ 'Date', 'Future' ],
			],
			[
				'description' => esc_html__( 'Show the current time', 'dynamic-shortcodes' ),
				'shortcode' => '{date:now@format="H:i:s"}',
				'placeholders' => [
					'H:i:s' => $placeholders['date-format'],
				],
				'tags' => [ 'Date', 'Time' ],
			],
			[
				'description' => esc_html__( 'Display the first day of the current year', 'dynamic-shortcodes' ),
				'shortcode' => '{date:"first day of January this year"}',
				'tags' => [ 'Date', 'Year' ],
			],
			[
				'description' => esc_html__( 'Show the last day of the current year', 'dynamic-shortcodes' ),
				'shortcode' => '{date:"last day of December this year"}',
				'tags' => [ 'Date', 'Year' ],
			],
			[
				'description' => esc_html__( 'Display the current month in full name format', 'dynamic-shortcodes' ),
				'shortcode' => '{date:now@format="F"}',
				'tags' => [ 'Date', 'Month' ],
			],
			[
				'description' => esc_html__( 'Show current year', 'dynamic-shortcodes' ),
				'shortcode' => '{date:now@format="Y"}',
				'tags' => [ 'Date', 'Year' ],
			],
			[
				'description' => esc_html__( 'Display the date and time for the next meeting on the first Monday of next month', 'dynamic-shortcodes' ),
				'shortcode' => '{date:"first Monday of next month 14:00"}',
				'tags' => [ 'Date', 'Meeting' ],
			],
			[
				'description' => esc_html__( 'Show the date 3 months ago', 'dynamic-shortcodes' ),
				'shortcode' => '{date:"-3 months"}',
				'tags' => [ 'Date', 'Past' ],
			],
			[
				'description' => esc_html__( 'Display the date 5 years into the future', 'dynamic-shortcodes' ),
				'shortcode' => '{date:"+5 years"}',
				'tags' => [ 'Date', 'Future' ],
			],
			[
				'description' => esc_html__( 'Display the current week number', 'dynamic-shortcodes' ),
				'shortcode' => '{date:now@format="W"}',
				'tags' => [ 'Date', 'Week' ],
			],
			[
				'description' => esc_html__( 'Show the date in a custom format: Day of the week, Day Month Year', 'dynamic-shortcodes' ),
				'shortcode' => '{date:now@format="l, d F Y"}',
				'tags' => [ 'Date', 'Custom Format' ],
			],
			[
				'description' => esc_html__( 'Display the date 30 minutes into the future', 'dynamic-shortcodes' ),
				'shortcode' => '{date:"+30 minutes"}',
				'tags' => [ 'Date', 'Future' ],
			],
			[
				'description' => esc_html__( 'Show the current date with the time set to midnight', 'dynamic-shortcodes' ),
				'shortcode' => '{date:"00:00"}',
				'tags' => [ 'Date', 'Time' ],
			],
			[
				'description' => esc_html__( 'Display the date exactly one year from now, at the current time', 'dynamic-shortcodes' ),
				'shortcode' => '{date:"+1 year"}',
				'tags' => [ 'Date', 'Future' ],
			],
			[
				'description' => esc_html__( 'Subtract one day and two hours from the start date of an event', 'dynamic-shortcodes' ),
				'shortcode' => '{date:{acf:start_date} @sub={timedelta:@days=1 hours=2}}',
				'placeholders' => [
					'{acf:start_date}' => $placeholders['date'],
				],
				'tags' => [ 'Date', 'Subtraction', 'Event' ],
			],
			[
				'description' => esc_html__( 'Add two weeks and three days to a project start date', 'dynamic-shortcodes' ),
				'shortcode' => '{date:{acf:project_start_date} @add={timedelta:@weeks=2 days=3}}',
				'placeholders' => [
					'{acf:project_start_date}' => $placeholders['date'],
				],
				'tags' => [ 'Date', 'Addition', 'Project' ],
			],
			[
				'description' => esc_html__( 'Add forty-five seconds to the timestamp of a post publication', 'dynamic-shortcodes' ),
				'shortcode' => '{date:{post:date} @add={timedelta:@seconds=45}}',
				'placeholders' => [
					'{post:date}' => $placeholders['date'],
				],
				'tags' => [ 'Date', 'Addition', 'Post' ],
			],
			[
				'description' => esc_html__( 'Add six months to the end date of a course', 'dynamic-shortcodes' ),
				'shortcode' => '{date:{acf:end_date} @add={timedelta:@months=6}}',
				'placeholders' => [
					'{acf:end_date}' => $placeholders['date'],
				],
				'tags' => [ 'Date', 'Addition', 'Course' ],
			],
			[
				'description' => esc_html__( 'Show a message if the current date is after a specific project deadline', 'dynamic-shortcodes' ),
				'shortcode' => '{if: {gt: {date:now@fmt=U} {date:{acf:project_deadline}@fmt=U}} [Project deadline has passed.]}',
				'placeholders' => [
					'{acf:project_deadline}' => $placeholders['date'],
					'Project deadline has passed.' => $placeholders['custom-message'],
				],
				'tags' => [ 'Date', 'Project', 'Deadline' ],
			],
			[
				'description' => esc_html__( 'Display ACF Field for a specific post', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:field-name@id=42}',
				'placeholders' => [
					'field-name' => $placeholders['field-name'],
				],
				'tags' => [ 'ACF', 'Post', 'Custom Field' ],
			],
			[
				'description' => esc_html__( 'Retrieve ACF Field for the current logged-in user', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:field-name@user}',
				'placeholders' => [
					'field-name' => $placeholders['field-name'],
				],
				'tags' => [ 'ACF', 'User', 'Custom Field' ],
			],
			[
				'description' => esc_html__( 'Get ACF Options Field Value', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:field-name@option}',
				'placeholders' => [
					'field-name' => $placeholders['field-name'],
				],
				'tags' => [ 'ACF', 'Options', 'Custom Field' ],
			],
			[
				'description' => esc_html__( 'Access ACF Field Data from a specific taxonomy term', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:field-name@term id=23}',
				'placeholders' => [
					'field-name' => $placeholders['field-name'],
					'23' => $placeholders['term-id'],
				],
				'tags' => [ 'ACF', 'Term', 'Custom Field' ],
			],
			[
				'description' => esc_html__( 'Display ACF Field with formatting', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:field-name@format}',
				'placeholders' => [
					'field-name' => $placeholders['field-name'],
				],
				'tags' => [ 'ACF', 'Format', 'Custom Field' ],
			],
			[
				'description' => esc_html__( 'Show the default setting of an ACF Field', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:field-name@setting=default_value}',
				'placeholders' => [
					'field-name' => $placeholders['field-name'],
				],
				'tags' => [ 'ACF', 'Setting', 'Default Value' ],
			],
			[
				'description' => esc_html__( 'List all items of a repeater field in ACF', 'dynamic-shortcodes' ),
				'shortcode' => '{acf-loop:field-name [<li>{acf:sub-field-name} qty: {acf:sub-field-qty}] }',
				'placeholders' => [
					'field-name' => $placeholders['field-name'],
					'sub-field-name' => $placeholders['acf-sub-field'],
					'sub-field-qty' => $placeholders['acf-sub-field'],
				],
				'tags' => [ 'ACF', 'Loop', 'Repeater' ],
			],
			[
				'description' => esc_html__( 'Display an ACF Field for a specific user', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:field-name@user id=2}',
				'placeholders' => [
					'field-name' => $placeholders['field-name'],
				],
				'tags' => [ 'ACF', 'User', 'Custom Field' ],
			],
			[
				'description' => esc_html__( 'Retrieve a global ACF Options Field for site-wide usage', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:field-name@option}',
				'placeholders' => [
					'field-name' => $placeholders['field-name'],
				],
				'tags' => [ 'ACF', 'Options', 'Global' ],
			],
			[
				'description' => esc_html__( 'Display a formatted ACF Field for a specific post', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:field-name@id=33 format}',
				'placeholders' => [
					'field-name' => $placeholders['field-name'],
					'33' => $placeholders['post-id'],
				],
				'tags' => [ 'ACF', 'Post', 'Formatted' ],
			],
			[
				'description' => esc_html__( 'Retrieve the ID of the current post', 'dynamic-shortcodes' ),
				'shortcode' => '{post:ID}',
				'tags' => [ 'Post', 'ID' ],
			],
			[
				'description' => esc_html__( 'Get the ID of the featured image of the current post', 'dynamic-shortcodes' ),
				'shortcode' => '{post:featured-image-id}',
				'tags' => [ 'Post', 'Featured Image' ],
			],
			[
				'description' => esc_html__( 'Get the URL of the featured image of the current post', 'dynamic-shortcodes' ),
				'shortcode' => '{media:url @ID={post:featured-image-id}}',
				'tags' => [ 'Post', 'Featured Image', 'URL' ],
			],
			[
				'description' => esc_html__( 'Get the HTML of the featured image of the current post', 'dynamic-shortcodes' ),
				'shortcode' => '{media:image @ID={post:featured-image-id}}',
				'tags' => [ 'Post', 'Featured Image', 'Image', 'HTML' ],
			],
			[
				'description' => esc_html__( 'Show the publication date and time of the current post', 'dynamic-shortcodes' ),
				'shortcode' => '{post:date}',
				'tags' => [ 'Post', 'Date' ],
			],
			[
				'description' => esc_html__( 'Retrieve the author ID of the current post', 'dynamic-shortcodes' ),
				'shortcode' => '{post:author}',
				'tags' => [ 'Post', 'Author' ],
			],
			[
				'description' => esc_html__( 'Get the permalink of the current post', 'dynamic-shortcodes' ),
				'shortcode' => '{post:permalink}',
				'tags' => [ 'Post', 'Permalink' ],
			],
			[
				'description' => esc_html__( 'Retrieve the type of the current post', 'dynamic-shortcodes' ),
				'shortcode' => '{post:type}',
				'tags' => [ 'Post', 'Type' ],
			],
			[
				'description' => esc_html__( 'Get the parent ID of the current post', 'dynamic-shortcodes' ),
				'shortcode' => '{post:parent-id}',
				'tags' => [ 'Post', 'Parent ID' ],
			],
			[
				'description' => esc_html__( 'Retrieve the user ID', 'dynamic-shortcodes' ),
				'shortcode' => '{user:ID}',
				'tags' => [ 'User', 'ID' ],
			],
			[
				'description' => esc_html__( 'Get the login name of the user', 'dynamic-shortcodes' ),
				'shortcode' => '{user:login}',
				'tags' => [ 'User', 'Login' ],
			],
			[
				'description' => esc_html__( 'Retrieve the user\'s email address', 'dynamic-shortcodes' ),
				'shortcode' => '{user:email}',
				'tags' => [ 'User', 'Email' ],
			],
			[
				'description' => esc_html__( 'List all roles of the user in a formatted list', 'dynamic-shortcodes' ),
				'shortcode' => '{for:value {user:roles} [<li>{get:value}]}',
				'tags' => [ 'User', 'Roles', 'List' ],
			],
			[
				'description' => esc_html__( 'Display the user\'s display name', 'dynamic-shortcodes' ),
				'shortcode' => '{user:display_name}',
				'tags' => [ 'User', 'Display Name' ],
			],
			[
				'description' => esc_html__( 'Retrieve the user\'s nickname', 'dynamic-shortcodes' ),
				'shortcode' => '{user:nickname}',
				'tags' => [ 'User', 'Nickname' ],
			],
			[
				'description' => esc_html__( 'Get the user\'s first name', 'dynamic-shortcodes' ),
				'shortcode' => '{user:first_name}',
				'tags' => [ 'User', 'First Name' ],
			],
			[
				'description' => esc_html__( 'Retrieve the user\'s last name', 'dynamic-shortcodes' ),
				'shortcode' => '{user:last_name}',
				'tags' => [ 'User', 'Last Name' ],
			],
			[
				'description' => esc_html__( 'Get the last active date of the user in WooCommerce', 'dynamic-shortcodes' ),
				'shortcode' => '{user:wc_last_active}',
				'tags' => [ 'WooCommerce', 'User', 'Last Active' ],
			],
			[
				'description' => esc_html__( 'Retrieve the WooCommerce billing details of the user', 'dynamic-shortcodes' ),
				'shortcode' => '{user:billing_first_name} {user:billing_last_name} {user:billing_company} {user:billing_address_1} {user:billing_address_2} {user:billing_city} {user:billing_postcode} {user:billing_country} {user:billing_state} {user:billing_phone} {user:billing_email}',
				'tags' => [ 'WooCommerce', 'User', 'Billing Details' ],
			],
			[
				'description' => esc_html__( 'Get the WooCommerce shipping details of the user', 'dynamic-shortcodes' ),
				'shortcode' => '{user:shipping_first_name} {user:shipping_last_name} {user:shipping_company} {user:shipping_address_1} {user:shipping_address_2} {user:shipping_city} {user:shipping_postcode} {user:shipping_country} {user:shipping_state} {user:shipping_phone}',
				'tags' => [ 'WooCommerce', 'User', 'Shipping Details' ],
			],
			[
				'description' => esc_html__( 'Retrieve the registration date of the current user formatted as day-month-year hour:minute', 'dynamic-shortcodes' ),
				'shortcode' => '{date:{user:registered} @format="d-m-Y H:i"}',
				'placeholders' => [
					'd-m-Y H:i' => $placeholders['date-format'],
				],
				'tags' => [ 'User', 'Registration Date', 'Format' ],
			],
			[
				'description' => esc_html__( 'Fetch the login name of the user with a specified ID', 'dynamic-shortcodes' ),
				'shortcode' => '{user:login@id=10}',
				'placeholders' => [
					'10' => $placeholders['user-id'],
				],
				'tags' => [ 'User', 'Login', 'Specific ID' ],
			],
			[
				'description' => esc_html__( 'Display the date one day before the start date of an event', 'dynamic-shortcodes' ),
				'shortcode' => '{date:{acf:start_date} "-1 day"}',
				'placeholders' => [
					'{acf:start_date}' => $placeholders['date'],
				],
				'tags' => [ 'Date', 'Event', 'Manipulation' ],
			],
			[
				'description' => esc_html__( 'Subtract one day and one hour from a specified start date', 'dynamic-shortcodes' ),
				'shortcode' => '{date:{acf:start_date} "-1 day, -1 hour"}',
				'placeholders' => [
					'{acf:start_date}' => $placeholders['date'],
				],
				'tags' => [ 'Date', 'Subtraction', 'Complex' ],
			],
			[
				'description' => esc_html__( 'Subtract one day and two hours from the start date using precise interval specification', 'dynamic-shortcodes' ),
				'shortcode' => '{date:{acf:start_date} @sub={timedelta:@days=1 hours=2}}',
				'placeholders' => [
					'{acf:start_date}' => $placeholders['date'],
				],
				'tags' => [ 'Date', 'ACF', 'Timedelta', 'Precision' ],
			],
			[
				'description' => esc_html__( 'Compare two dates and display a message if the start date is earlier than the current date', 'dynamic-shortcodes' ),
				'shortcode' => '{if: {gt: {date:{acf:start_date}@fmt=U} {date:now@fmt=U}} [Registration opens on {acf:start_date}]}',
				'placeholders' => [
					'{acf:start_date}' => $placeholders['date'],
				],
				'tags' => [ 'Date', 'Comparison', 'Conditional' ],
			],
			[
				'description' => esc_html__( 'Fetch the name of the author with a specific ID', 'dynamic-shortcodes' ),
				'shortcode' => '{author:name@id=5}',
				'placeholders' => [
					'5' => $placeholders['author-id'],
				],
				'tags' => [ 'Author', 'Name', 'Specific ID' ],
			],
			[
				'description' => esc_html__( 'Retrieve a specific custom field value for the author with ID 3', 'dynamic-shortcodes' ),
				'shortcode' => '{author:custom_field@id=37}',
				'placeholders' => [
					'custom_field' => $placeholders['field-name'],
					'37' => $placeholders['author-id'],
				],
				'tags' => [ 'Author', 'Custom Field', 'Specific ID' ],
			],
			[
				'description' => esc_html__( 'Retrieve the URL of a media item by its ID', 'dynamic-shortcodes' ),
				'shortcode' => '{media:url@id=123}',
				'placeholders' => [
					'123' => $placeholders['media-id'],
				],
				'tags' => [ 'Media', 'URL', 'Specific ID' ],
			],
			[
				'description' => esc_html__( 'Get the title of a media item with a specific ID', 'dynamic-shortcodes' ),
				'shortcode' => '{media:title@id=456}',
				'placeholders' => [
					'456' => $placeholders['media-id'],
				],
				'tags' => [ 'Media', 'Title', 'Specific ID' ],
			],
			[
				'description' => esc_html__( 'Print the HTML for the featured image of the current post', 'dynamic-shortcodes' ),
				'shortcode' => '{if:{post:featured-image-id} {media:html@id={post:featured-image-id}}}',
				'tags' => [ 'Media', 'HTML', 'Featured Image' ],
			],
			[
				'description' => esc_html__( 'Fetch the URL of a media item in a specific size', 'dynamic-shortcodes' ),
				'shortcode' => '{media:url@id=789 size=thumbnail}',
				'placeholders' => [
					'789' => $placeholders['media-id'],
				],
				'tags' => [ 'Media', 'URL', 'Thumbnail' ],
			],
			[
				'description' => esc_html__( 'Display the caption of a media item with a specific ID', 'dynamic-shortcodes' ),
				'shortcode' => '{media:caption@id=321}',
				'placeholders' => [
					'321' => $placeholders['media-id'],
				],
				'tags' => [ 'Media', 'Caption', 'Specific ID' ],
			],
			[
				'description' => esc_html__( 'Access the description of a media item with a specific ID', 'dynamic-shortcodes' ),
				'shortcode' => '{media:description@id=654}',
				'placeholders' => [
					'654' => $placeholders['media-id'],
				],
				'tags' => [ 'Media', 'Description', 'Specific ID' ],
			],
			[
				'description' => esc_html__( 'Fetch a specific price field from a post using its ID', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:price@id=15}',
				'placeholders' => [
					'15' => $placeholders['post-id'],
				],
				'tags' => [ 'ACF', 'Post', 'Price' ],
			],
			[
				'description' => esc_html__( 'Retrieve the bio field for the current logged-in user', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:bio@user}',
				'tags' => [ 'ACF', 'User', 'Bio' ],
			],
			[
				'description' => esc_html__( 'Get the value of the color options field', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:color@option}',
				'tags' => [ 'ACF', 'Options', 'Color' ],
			],
			[
				'description' => esc_html__( 'Fetch ingredients field from a specific taxonomy term', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:ingredients@term id=5}',
				'placeholders' => [
					'5' => $placeholders['term-id'],
				],
				'tags' => [ 'ACF', 'Term', 'Ingredients' ],
			],
			[
				'description' => esc_html__( 'Retrieve the gallery field with ACF formatting applied', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:gallery@format}',
				'tags' => [ 'ACF', 'Gallery', 'Format' ],
			],
			[
				'description' => esc_html__( 'Display the default value set for the "name" field in ACF settings', 'dynamic-shortcodes' ),
				'shortcode' => '{acf:name@setting=default_value}',
				'tags' => [ 'ACF', 'Settings', 'Default Value' ],
			],
			[
				'description' => esc_html__( 'List all toppings in a pizza with their names and quantities using an ACF loop', 'dynamic-shortcodes' ),
				'shortcode' => '{acf-loop:pizza-toppings [<li>name: {acf:name} qty: {acf:quantity}]}',
				'placeholders' => [
					'name' => $placeholders['field-name'],
					'quantity' => $placeholders['field-name'],
					'pizza-toppings' => $placeholders['acf-repeater-field'],
				],
				'tags' => [ 'ACF', 'Loop', 'Pizza Toppings' ],
			],
			[
				'description' => esc_html__( 'Retrieve the value of a custom field from the current post', 'dynamic-shortcodes' ),
				'shortcode' => '{metabox:custom_field}',
				'placeholders' => [
					'custom_field' => $placeholders['field-name'],
				],
				'tags' => [ 'Meta Box', 'Custom Field', 'Current Post' ],
			],
			[
				'description' => esc_html__( 'Fetch a specific custom field value from a post with a given ID', 'dynamic-shortcodes' ),
				'shortcode' => '{metabox:another_field@id=123}',
				'placeholders' => [
					'another_field' => $placeholders['field-name'],
					'123' => $placeholders['post-id'],
				],
				'tags' => [ 'Meta Box', 'Custom Field', 'Specific Post' ],
			],
			[
				'description' => esc_html__( 'Fetch the value of a custom field from the current post or page using JetEngine', 'dynamic-shortcodes' ),
				'shortcode' => '{jet:custom_field_name}',
				'placeholders' => [
					'custom_field_name' => $placeholders['field-name'],
				],
				'tags' => [ 'JetEngine', 'Custom Field', 'Current Post' ],
			],
			[
				'description' => esc_html__( 'Retrieve a specific custom field value from a post with a given ID using JetEngine', 'dynamic-shortcodes' ),
				'shortcode' => '{jet:custom_field_name@id=10}',
				'placeholders' => [
					'custom_field_name' => $placeholders['field-name'],
					'10' => esc_html__( 'Post ID', 'dynamic-shortcodes' ),
				],
				'tags' => [ 'JetEngine', 'Custom Field', 'Specific Post' ],
			],
			[
				'description' => esc_html__( 'Fetch the value of a custom field from the current post using Pods', 'dynamic-shortcodes' ),
				'shortcode' => '{pods:custom_field_name}',
				'placeholders' => [
					'custom_field_name' => $placeholders['field-name'],
				],
				'tags' => [ 'Pods', 'Custom Field', 'Current Post' ],
			],
			[
				'description' => esc_html__( 'Retrieve a specific custom field value from a post with a given ID using Pods', 'dynamic-shortcodes' ),
				'shortcode' => '{pods:custom_field_name@id=10}',
				'placeholders' => [
					'custom_field_name' => $placeholders['field-name'],
					'10' => esc_html__( 'Post ID', 'dynamic-shortcodes' ),
				],
				'tags' => [ 'Pods', 'Custom Field', 'Specific Post' ],
			],
			[
				'description' => esc_html__( 'Retrieve the value of a custom field from the current post using Toolset', 'dynamic-shortcodes' ),
				'shortcode' => '{toolset:field_name}',
				'placeholders' => [
					'field_name' => $placeholders['field-name'],
				],
				'tags' => [ 'Toolset', 'Custom Field', 'Current Post' ],
			],
			[
				'description' => esc_html__( 'Retrieve a specific custom field from a post with a given ID using Toolset', 'dynamic-shortcodes' ),
				'shortcode' => '{toolset:field_name@id=456}',
				'placeholders' => [
					'field_name' => $placeholders['field-name'],
					'456' => esc_html__( 'Post ID', 'dynamic-shortcodes' ),
				],
				'tags' => [ 'Toolset', 'Custom Field', 'Specific Post' ],
			],
			[
				'description' => esc_html__( 'Retrieve and format a custom field as HTML from the current post using Toolset', 'dynamic-shortcodes' ),
				'shortcode' => '{toolset:field_name@format=html}',
				'placeholders' => [
					'field_name' => $placeholders['field-name'],
				],
				'tags' => [ 'Toolset', 'Custom Field', 'HTML Format' ],
			],
			[
				'description' => esc_html__( 'Cache the results of a complex query to improve performance', 'dynamic-shortcodes' ),
				'shortcode' => '{cache:latest_articles {query:posts@posts_per_page=50} @expiration="1 hour"}',
				'placeholders' => [
					'latest_articles' => $placeholders['cache-key'],
					'{query:posts@posts_per_page=50}' => $placeholders['shortcode'],
					'1 hour' => $placeholders['cache-expiration'],
				],
				'tags' => [ 'Cache', 'Query', 'Performance' ],
			],
			[
				'description' => esc_html__( 'Retrieve cached data for displaying latest articles, reducing database load', 'dynamic-shortcodes' ),
				'shortcode' => '{loop:{get-cache:latest_articles} {post:title} @separator=", "}',
				'placeholders' => [
					'latest_articles' => $placeholders['cache-key'],
					', ' => $placeholders['separator'],
				],
				'tags' => [ 'Cache', 'Retrieve', 'Display' ],
			],
			[
				'description' => esc_html__( 'Delete cached data for latest articles to ensure content freshness after significant updates', 'dynamic-shortcodes' ),
				'shortcode' => '{delete-cache:latest_articles}',
				'placeholders' => [
					'latest_articles' => $placeholders['cache-key'],
				],
				'tags' => [ 'Cache', 'Delete', 'Refresh' ],
			],
			[
				'description' => esc_html__( 'Retrieve the site URL from WordPress settings', 'dynamic-shortcodes' ),
				'shortcode' => '{option:siteurl}',
				'tags' => [ 'Option', 'Site URL' ],
			],
			[
				'description' => esc_html__( 'Fetch a non-existent option with a fallback URL', 'dynamic-shortcodes' ),
				'shortcode' => '{option:nonexistent_option?https://fallbacksite.com}',
				'placeholders' => [
					'https://fallbacksite.com' => $placeholders['custom-message'],
				],
				'tags' => [ 'Option', 'Fallback' ],
			],
			[
				'description' => esc_html__( 'Display the site name (blogname) from WordPress settings', 'dynamic-shortcodes' ),
				'shortcode' => '{option:blogname}',
				'tags' => [ 'Option', 'Site Name' ],
			],
			[
				'description' => esc_html__( 'Retrieve the start of the week setting from WordPress options', 'dynamic-shortcodes' ),
				'shortcode' => '{option:start_of_week}',
				'tags' => [ 'Option', 'Start of Week' ],
			],
			[
				'description' => esc_html__( 'Get the default date format set in WordPress settings', 'dynamic-shortcodes' ),
				'shortcode' => '{option:date_format}',
				'tags' => [ 'Option', 'Date Format' ],
			],
			[
				'description' => esc_html__( 'Display the time format from WordPress settings', 'dynamic-shortcodes' ),
				'shortcode' => '{option:time_format}',
				'tags' => [ 'Option', 'Time Format' ],
			],
			[
				'description' => esc_html__( 'Retrieve the timezone set in WordPress', 'dynamic-shortcodes' ),
				'shortcode' => '{option:timezone_string}',
				'tags' => [ 'Option', 'Timezone' ],
			],
			[
				'description' => esc_html__( 'Access the thumbnail width setting from WordPress options', 'dynamic-shortcodes' ),
				'shortcode' => '{option:thumbnail_size_w}',
				'tags' => [ 'Option', 'Thumbnail Width' ],
			],
			[
				'description' => esc_html__( 'Retrieve the latest 10 posts from the "technology" category.', 'dynamic-shortcodes' ),
				'shortcode' => '{query:posts@category_name=technology posts_per_page=10}',
				'placeholders' => [
					'technology' => $placeholders['category-slug'],
				],
				'tags' => [ 'Query', 'Posts', 'Technology' ],
			],
			[
				'description' => esc_html__( 'Fetch posts that are tagged with "health" or "fitness".', 'dynamic-shortcodes' ),
				'shortcode' => '{query:posts@tag="health,fitness"}',
				'tags' => [ 'Query', 'Posts', 'Tags' ],
			],
			[
				'description' => esc_html__( 'Retrieve all users with the "editor" role.', 'dynamic-shortcodes' ),
				'shortcode' => '{query:users@role=editor}',
				'tags' => [ 'Query', 'Users', 'Role' ],
			],
			[
				'description' => esc_html__( 'Fetch users sorted by display name in ascending order.', 'dynamic-shortcodes' ),
				'shortcode' => '{query:users@orderby=display_name order=ASC}',
				'tags' => [ 'Query', 'Users', 'Orderby' ],
			],
			[
				'description' => esc_html__( 'Retrieve all terms in the "category" taxonomy.', 'dynamic-shortcodes' ),
				'shortcode' => '{query:terms@taxonomy=category}',
				'placeholders' => [
					'category' => $placeholders['taxonomy'],
				],
				'tags' => [ 'Query', 'Terms', 'Taxonomy' ],
			],
			[
				'description' => esc_html__( 'Fetch terms from the "product_cat" taxonomy in WooCommerce.', 'dynamic-shortcodes' ),
				'shortcode' => '{query:terms@taxonomy=product_cat}',
				'placeholders' => [
					'product_cat' => $placeholders['taxonomy'],
				],
				'tags' => [ 'Query', 'Terms', 'WooCommerce' ],
			],
			[
				'description' => esc_html__( 'Retrieve the last 5 completed WooCommerce orders.', 'dynamic-shortcodes' ),
				'shortcode' => '{query:woo-orders@status=completed limit=5}',
				'placeholders' => [
					'status' => $placeholders['post-status'],
				],
				'tags' => [ 'Query', 'WooCommerce', 'Orders' ],
			],
			[
				'description' => esc_html__( 'Fetch recent WooCommerce orders that are still processing.', 'dynamic-shortcodes' ),
				'shortcode' => '{query:woo-orders@status=processing limit=5}',
				'placeholders' => [
					'status' => $placeholders['post-status'],
				],
				'tags' => [ 'Query', 'WooCommerce', 'Recent Orders' ],
			],
			[
				'description' => esc_html__( 'Retrieve the name of the current term.', 'dynamic-shortcodes' ),
				'shortcode' => '{term:name}',
				'tags' => [ 'Term', 'Name', 'Current' ],
			],
			[
				'description' => esc_html__( 'Fetch the description of the term with ID 45.', 'dynamic-shortcodes' ),
				'shortcode' => '{term:description@id=45}',
				'placeholders' => [
					'45' => $placeholders['term-id'],
				],
				'tags' => [ 'Term', 'Description', 'Specific ID' ],
			],
			[
				'description' => esc_html__( 'Get the permalink of the term with ID 12.', 'dynamic-shortcodes' ),
				'shortcode' => '{term:permalink@id=12}',
				'placeholders' => [
					'12' => $placeholders['term-id'],
				],
				'tags' => [ 'Term', 'Permalink', 'Specific ID' ],
			],
			[
				'description' => esc_html__( 'Show the count of posts associated with the term ID 8.', 'dynamic-shortcodes' ),
				'shortcode' => '{term:count@id=8}',
				'placeholders' => [
					'8' => $placeholders['term-id'],
				],
				'tags' => [ 'Term', 'Count', 'Specific ID' ],
			],
			[
				'description' => esc_html__( 'Sum multiple numbers to get a total.', 'dynamic-shortcodes' ),
				'shortcode' => '{+:10 15 20}',
				'tags' => [ 'Arithmetic', 'Addition', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Subtract one number from another.', 'dynamic-shortcodes' ),
				'shortcode' => '{-:50 20}',
				'tags' => [ 'Arithmetic', 'Subtraction', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Multiply two numbers.', 'dynamic-shortcodes' ),
				'shortcode' => '{*:7 8}',
				'tags' => [ 'Arithmetic', 'Multiplication', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Divide one number by another.', 'dynamic-shortcodes' ),
				'shortcode' => '{/:36 6}',
				'tags' => [ 'Arithmetic', 'Division', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Calculate the remainder of the division of two numbers.', 'dynamic-shortcodes' ),
				'shortcode' => '{modulo:29 5}',
				'tags' => [ 'Arithmetic', 'Modulo', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Calculate the total cost by adding the base price to the product of price and sales tax retrieved via ACF.', 'dynamic-shortcodes' ),
				'shortcode' => '{+:{acf:price} {*:{acf:price} {acf:sales_tax}}}',
				'placeholders' => [
					'price' => $placeholders['field-name'],
					'sales_tax' => $placeholders['field-name'],
				],
				'tags' => [ 'Arithmetic', 'Nested', 'ACF', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Calculate the total price by adding the base price to the calculated tax, both retrieved from ACF fields.', 'dynamic-shortcodes' ),
				'shortcode' => '{+: {acf:base_price} {*: {acf:base_price} {acf:tax_rate}/100}}',
				'placeholders' => [
					'base_price' => $placeholders['field-name'],
					'tax_rate' => $placeholders['field-name'],
				],
				'tags' => [ 'Arithmetic', 'Addition', 'ACF', 'Dynamic', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Calculate the final price after a discount, where the original price and discount rate are retrieved via ACF fields.', 'dynamic-shortcodes' ),
				'shortcode' => '{-: {acf:original_price} {*: {acf:original_price} {acf:discount_rate}/100}}',
				'placeholders' => [
					'original_price' => $placeholders['field-name'],
					'discount_rate' => $placeholders['field-name'],
				],
				'tags' => [ 'Arithmetic', 'Subtraction', 'Discount', 'ACF', 'Dynamic', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Calculate the total cost by multiplying the price per item by quantity, both provided via custom fields.', 'dynamic-shortcodes' ),
				'shortcode' => '{*: {acf:price_per_item} {acf:quantity}}',
				'placeholders' => [
					'price_per_item' => $placeholders['field-name'],
					'quantity' => $placeholders['field-name'],
				],
				'tags' => [ 'Arithmetic', 'Multiplication', 'ACF', 'Dynamic', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Calculate the average cost per item by dividing the total cost by the number of items, values fetched via custom fields.', 'dynamic-shortcodes' ),
				'shortcode' => '{/: {acf:total_cost} {acf:number_of_items}}',
				'placeholders' => [
					'total_cost' => $placeholders['field-name'],
					'number_of-items' => $placeholders['field-name'],
				],
				'tags' => [ 'Arithmetic', 'Division', 'ACF', 'Dynamic', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Determine how many sets of an item can be purchased within a budget by using the modulo operation.', 'dynamic-shortcodes' ),
				'shortcode' => '{modulo: {acf:budget} {acf:price_per_set}}',
				'placeholders' => [
					'budget' => $placeholders['field-name'],
					'price_per_set' => $placeholders['field-name'],
				],
				'tags' => [ 'Arithmetic', 'Modulo', 'Budgeting', 'ACF', 'Dynamic', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Calculate the total project cost by adding the labor cost and material cost retrieved from MetaBox fields.', 'dynamic-shortcodes' ),
				'shortcode' => '{+: {metabox:labor_cost} {metabox:material_cost}}',
				'placeholders' => [
					'labor_cost' => $placeholders['field-name'],
					'material_cost' => $placeholders['field-name'],
				],
				'tags' => [ 'Arithmetic', 'Addition', 'MetaBox', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Calculate total investment returns by multiplying the investment amount by the return rate (percentage) retrieved from JetEngine fields.', 'dynamic-shortcodes' ),
				'shortcode' => '{*: {jet:investment_amount} {/: {jet:return_rate} 100}}',
				'placeholders' => [
					'investment_amount' => $placeholders['field-name'],
					'return_rate' => $placeholders['field-name'],
				],
				'tags' => [ 'Arithmetic', 'Multiplication', 'JetEngine', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Calculate net income by subtracting taxes from gross income, both values retrieved from Toolset fields.', 'dynamic-shortcodes' ),
				'shortcode' => '{-: {toolset:gross_income} {toolset:taxes}}',
				'placeholders' => [
					'gross_income' => $placeholders['field-name'],
					'taxes' => $placeholders['field-name'],
				],
				'tags' => [ 'Arithmetic', 'Subtraction', 'Toolset', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Calculate the remaining balance after a purchase by subtracting the purchase amount from the user\'s total balance, values retrieved from user fields.', 'dynamic-shortcodes' ),
				'shortcode' => '{-: {user:total_balance} {user:purchase_amount}}',
				'placeholders' => [
					'total_balance' => $placeholders['field-name'],
					'purchase_amount' => $placeholders['field-name'],
				],
				'tags' => [ 'Arithmetic', 'Subtraction', 'User Fields', 'Calculation' ],
			],
			[
				'description' => esc_html__( 'Retrieve the value of the "user_session" cookie to use in session-specific user data.', 'dynamic-shortcodes' ),
				'shortcode' => '{cookie:user_session}',
				'tags' => [ 'Cookie', 'Session', 'Retrieval' ],
			],
			[
				'description' => esc_html__( 'Attempt to retrieve the "preferences" cookie and default to "Default settings" if not found.', 'dynamic-shortcodes' ),
				'shortcode' => '{cookie:preferences?"Default settings"}',
				'placeholders' => [
					'Default settings' => $placeholders['custom-message'],
				],
				'tags' => [ 'Cookie', 'Preferences', 'Fallback' ],
			],
			[
				'description' => esc_html__( 'Check if the "name" cookie exists and display a message accordingly.', 'dynamic-shortcodes' ),
				'shortcode' => '{if:{cookie:name} "The Cookie exists" "The Cookie doesn\'t exist"}',
				'placeholders' => [
					'The Cookie exists' => $placeholders['custom-message'],
					"The Cookie doesn\'t exist" => $placeholders['custom-message'],
				],
				'tags' => [ 'Cookie', 'Conditional', 'Check' ],
			],
			[
				'description' => esc_html__( 'Retrieve the value of a GET parameter named "myparam" from the URL.', 'dynamic-shortcodes' ),
				'shortcode' => '{param-get:myparam}',
				'tags' => [ 'Parameter', 'GET', 'Retrieval' ],
			],
			[
				'description' => esc_html__( 'Output the value of a POST parameter named "myparam" after a form submission.', 'dynamic-shortcodes' ),
				'shortcode' => '{param-post:myparam}',
				'tags' => [ 'Parameter', 'POST', 'Retrieval' ],
			],
			[
				'description' => esc_html__( 'Grabs the current search term, useful for custom search results pages to display or utilize the search term entered by the user.', 'dynamic-shortcodes' ),
				'shortcode' => '{query_var:s}',
				'tags' => [ 'Query Var', 'Search Term', 'Custom Search' ],
			],
			[
				'description' => esc_html__( 'Retrieve the server\'s domain name.', 'dynamic-shortcodes' ),
				'shortcode' => '{server:SERVER_NAME}',
				'tags' => [ 'Server', 'Domain Name', 'Retrieval' ],
			],
			[
				'description' => esc_html__( 'Display the server software being used (e.g., Apache, Nginx).', 'dynamic-shortcodes' ),
				'shortcode' => '{server:SERVER_SOFTWARE}',
				'tags' => [ 'Server', 'Software', 'Display' ],
			],
			[
				'description' => esc_html__( 'Returns the document root directory under which the current script is executing.', 'dynamic-shortcodes' ),
				'shortcode' => '{server:DOCUMENT_ROOT}',
				'tags' => [ 'Server', 'Document Root', 'Retrieval' ],
			],
			[
				'description' => esc_html__( 'Shows the name and revision of the information protocol (e.g., HTTP/1.1).', 'dynamic-shortcodes' ),
				'shortcode' => '{server:SERVER_PROTOCOL}',
				'tags' => [ 'Server', 'Protocol', 'Display' ],
			],
			[
				'description' => esc_html__( 'Retrieves the IP address from which the user is viewing the current page.', 'dynamic-shortcodes' ),
				'shortcode' => '{server:REMOTE_ADDR}',
				'tags' => [ 'Server', 'IP Address', 'Retrieval' ],
			],
			[
				'description' => esc_html__( 'Retrieve posts based on a custom field that can be true or false, showing only those marked as "important".', 'dynamic-shortcodes' ),
				'shortcode' => '{query:posts@meta_key=important meta_value={lit:true}}',
				'tags' => [ 'Query', 'Posts', 'Meta Key', 'Meta Value', 'Literal' ],
			],
			[
				'description' => esc_html__( 'Exclude posts that are less important by using a false value.', 'dynamic-shortcodes' ),
				'shortcode' => '{query:posts@meta_key=important meta_value={lit:false}}',
				'tags' => [ 'Query', 'Posts', 'Exclude', 'Literal' ],
			],
			[
				'description' => esc_html__( 'Include posts where the \'important\' field has not been set by using a null value.', 'dynamic-shortcodes' ),
				'shortcode' => '{query:posts@meta_key=important meta_value={lit:null}}',
				'tags' => [ 'Query', 'Posts', 'Include', 'Literal' ],
			],
			[
				'description' => esc_html__( 'Use the literal true value in conditions or queries where a boolean true is required.', 'dynamic-shortcodes' ),
				'shortcode' => '{lit:true}',
				'tags' => [ 'Literal', 'True', 'Boolean' ],
			],
			[
				'description' => esc_html__( 'Use the literal false value in conditions or queries where a boolean false is required.', 'dynamic-shortcodes' ),
				'shortcode' => '{lit:false}',
				'tags' => [ 'Literal', 'False', 'Boolean' ],
			],
			[
				'description' => esc_html__( 'Use the literal null value in conditions or queries where a null value is required, particularly useful when needing to represent the absence of a value.', 'dynamic-shortcodes' ),
				'shortcode' => '{lit:null}',
				'tags' => [ 'Literal', 'Null', 'Value' ],
			],
		];
	}
}
