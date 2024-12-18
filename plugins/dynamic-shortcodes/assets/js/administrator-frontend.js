"use strict";
// This register the action on the bar button that clears the current page cache
// of the Cache Shortcode.
document.addEventListener("DOMContentLoaded", function () {
	if (window.location.search.indexOf("dsh-action=clear-cache") > -1) {
		var url = new URL(window.location.href);
		url.searchParams.delete("dsh-action");
		history.replaceState({}, "", url.toString());
	}
	let cacheButton = document.getElementById(
		"wp-admin-bar-dsh-clear-cache-bar-button"
	);
	if (!cacheButton) {
		return;
	}
	cacheButton.addEventListener("click", () => {
		var url = new URL(window.location.href);
		url.searchParams.set("dsh-action", "clear-cache");
		window.location.href = url.toString();
	});
});
