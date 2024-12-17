<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

$table_data = wcpt_get_table_data();
$html_class = trim(apply_filters('wcpt_container_html_class', 'wcpt wcpt-' . $table_data['id'] . ' ' . trim($this->attributes['class']) . ' ' . trim($this->attributes['html_class'])));

$attributes = sprintf(
	'data-wcpt-table-id="%1$s"
	data-wcpt-query-string="%2$s" 
	data-wcpt-sc-attrs="%3$s"
	data-wcpt-encrypted-query-vars="%4$s"
	data-wcpt-encrypted-user-filters="%5$s"',
	$table_data['id'],
	esc_attr(wcpt_get_table_query_string()),
	esc_attr(json_encode($table_data['query']['sc_attrs'])),
	wcpt_encrypt(json_encode(wcpt_cull_query_vars($GLOBALS['wcpt_products']->query_vars))),
	wcpt_encrypt(json_encode($GLOBALS['wcpt_user_filters']))
);

$attributes = apply_filters('wcpt_container_html_attributes', $attributes);
?>
<div id="wcpt-<?php echo $table_data['id']; ?>" class="<?php echo $html_class; ?>" <?php echo $attributes; ?>>