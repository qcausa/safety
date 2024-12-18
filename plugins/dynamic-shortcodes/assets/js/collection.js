jQuery(function () {
	jQuery(document).ready(function ($) {
		function filterExamples(query) {
			$(".dynamic-shortcodes-demo tr").each(function () {
				var tags = $(this).data("tags");
				if (tags) {
					tags = tags.toLowerCase();
					if (tags.includes(query.toLowerCase())) {
						$(this).show();
					} else {
						$(this).hide();
					}
				}
			});

			updateRowColors();
		}

		function updateRowColors() {
			jQuery(".dynamic-shortcodes-demo tr").removeClass(
				"odd-row even-row"
			);

			let i = 1;
			jQuery(".dynamic-shortcodes-demo > table > tbody > tr")
				.filter(":visible")
				.each(function (index) {
					console.log(i);
					jQuery(this).addClass(i % 2 === 0 ? "odd-row" : "even-row");
					i++;
				});
		}

		$("#tagSearch").on("keyup", function () {
			filterExamples($(this).val());
		});

		$(".quick-search-tag").on("click", function () {
			var tag = $(this).data("tag");
			$("#tagSearch").val(tag);
			filterExamples(tag);
		});
	});
});
