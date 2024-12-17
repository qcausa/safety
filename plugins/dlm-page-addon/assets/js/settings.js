jQuery(function ($) {
	
	$('#setting-dlm_wp_search_enabled').change(function () {
		if ($(this).is(":checked") === true) {
			$('#setting-dlm_pa_search_results_page').closest('tr').show();
		} else {
			$('#setting-dlm_pa_search_results_page').closest('tr').hide();
		}
	}).change();

});