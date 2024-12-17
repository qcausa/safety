jQuery('html, body').on('click', 'a', function (e) {

	const url = jQuery(this).attr('href');
	// Let's see if we need to do XHR download on this link.
	let noXHR = false;
	if (jQuery(this).hasClass('dlm-no-xhr-download')) {
		noXHR = true;
	}
	// Non XHR global links.
	if ('undefined' !== typeof dlmNonXHRGlobalLinks && dlmNonXHRGlobalLinks.length > 0) {
		if ('undefined' != typeof url) {
			dlmNonXHRGlobalLinks.forEach((element) => {
				if (url.indexOf(element) >= 0) {
					noXHR = true;
				}
			});
		}
	}
	// If no XHR, return.
	if (noXHR) { // No XHR so return;
		jQuery('#dlm-no-access-modal').remove(); // Close the modal also in case we opened it before.
		return;
	}

	if ('undefined' !== typeof url && url.indexOf(dlmPAlinks) >= 0) {
		dlmXHRinstance.handleDownloadClick(this, e);
	}
});