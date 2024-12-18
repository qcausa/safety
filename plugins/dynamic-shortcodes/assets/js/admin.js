"use strict";
(function ($) {
	const initPower = () => {
		function countIndentationLevelChange(str) {
			let smatches = str.match(/{[A-Za-z\-][A-Za-z0-9\-_]*:/g);
			let ematches = str.match(/}/g);
			let sn = smatches ? smatches.length : 0;
			let en = ematches ? ematches.length : 0;
			return sn - en;
		}

		function formatJsString(input) {
			let lines = input.split("\n");
			let currentIndent = 0;
			const indentString = "    "; // 4 spaces for indentation

			lines = lines.map((l) => {
				l = l.trim();
				if (l === "}") {
					currentIndent = Math.max(currentIndent - 1, 0);
					return indentString.repeat(currentIndent) + l;
				}
				l = indentString.repeat(currentIndent) + l;
				let diff = countIndentationLevelChange(l);
				currentIndent = currentIndent + diff;
				currentIndent = Math.max(currentIndent, 0);
				return l;
			});
			return lines.join("\n");
		}
		const form = document.getElementById("power-shortcodes-form");
		if (!form) {
			return;
		}
		let licenseActive = form.dataset.licenseActive === "yes";
		const fieldWrapper = document.getElementById(
			"power-shortcodes-wrapper"
		);
		const saveButtons = document.getElementsByClassName(
			"power-shortcodes-save"
		);

		const formMessage = document.getElementById(
			"power-shortcodes-form-message"
		);
		const oldJson = form.elements.power_shortcodes.value;
		const itemTemplate = document.getElementById(
			"power-shortcodes-template"
		);
		const repeaterWrapper = document.getElementById(
			"power-shortcodes-repeater"
		);
		const addButton = document.getElementById("power-shortcodes-add");

		let oldValue = [];
		if (typeof oldJson === "string" && oldJson !== "") {
			try {
				oldValue = JSON.parse(oldJson);
			} catch (e) {
				console.error("json parse error");
			}
		}

		const showFormError = (message) => {
			formMessage.textContent = message;
			formMessage.classList.remove("hidden");
			formMessage.classList.remove("notice-success");
			formMessage.classList.add("notice-error");
		};

		const showFormSuccess = (message) => {
			formMessage.textContent = message;
			formMessage.classList.add("notice-success");
			formMessage.classList.remove("notice-error");
			formMessage.classList.remove("hidden");
		};

		const showItemsErrors = (errors) => {
			const errorLists = repeaterWrapper.getElementsByClassName(
				"power-shortcodes-item-error"
			);
			for (let i = 0; i < errorLists.length; i++) {
				errorLists[i].innerHTML = "";
			}
			for (let e of errors) {
				const wrapper = repeaterWrapper.childNodes[e.index];
				const ul = wrapper.getElementsByClassName(
					"power-shortcodes-item-error"
				)[0];
				const li = document.createElement("li");
				li.textContent = e.message;
				ul.appendChild(li);
				ul.classList.remove("hidden");
			}
		};

		const saveCallback = (e) => {
			const httpRequest = e.target;
			if (httpRequest.readyState === XMLHttpRequest.DONE) {
				window.scrollTo({
					top: 0,
					left: 0,
					behavior: "smooth",
				});
				if (httpRequest.status === 200) {
					var response;
					try {
						response = JSON.parse(httpRequest.responseText);
					} catch (e) {
						showFormError("Response Error");
						return;
					}
					if (!response?.success) {
						showFormError(response?.data.message);
						showItemsErrors(response?.data.items_errors);
					} else {
						showFormSuccess(response?.data.message);
					}
				} else {
					showFormError(
						"There was a problem with the admin ajax request."
					);
				}
			}
		};

		for (let saveButton of saveButtons) {
			saveButton.addEventListener("click", (event) => {
				// Hide all previous message:
				formMessage.classList.add("hidden");
				const divs = document.getElementsByClassName(
					"power-shortcodes-item-error"
				);
				for (let div of divs) {
					div.classList.add("hidden");
				}
				const items = document.getElementsByClassName(
					"power-shortcodes-repeater-item"
				);
				let res = [];
				for (const item of items) {
					const name = item.getElementsByClassName(
						"power-shortcodes-name"
					)[0].value;
					const code = item.getElementsByClassName(
						"power-shortcodes-code"
					)[0].value;
					res.push({ name: name, code: code });
				}
				const value = JSON.stringify(res);
				let data = new FormData(form);
				data.set("power_shortcodes", value);
				const httpRequest = new XMLHttpRequest();
				httpRequest.onreadystatechange = saveCallback;
				const url = form.getAttribute("action");
				httpRequest.open("POST", url, true);
				httpRequest.send(data);
			});
		}

		const addRepeaterItem = (values) => {
			let isNew = false;
			if (typeof values === "undefined") {
				isNew = true;
				values = { name: "", code: "" };
			}
			const repeaterItem =
				itemTemplate.content.firstElementChild.cloneNode(true);
			if (isNew) {
				repeaterItem.dataset.testid = "new-power";
			}
			const usageInfo = repeaterItem.getElementsByClassName(
				"power-shortcodes-usage"
			)[0];
			const nameInput = repeaterItem.getElementsByClassName(
				"power-shortcodes-name"
			)[0];
			nameInput.value = values.name;
			let codeArea = repeaterItem.getElementsByClassName(
				"power-shortcodes-code"
			)[0];
			codeArea.value = values.code;
			const delButton = repeaterItem.getElementsByClassName(
				"power-shortcodes-del"
			)[0];
			const formatButton = repeaterItem.getElementsByClassName(
				"power-shortcodes-format"
			)[0];
			if (!licenseActive) {
				nameInput.disabled = true;
				codeArea.disabled = true;
				delButton.disabled = true;
				formatButton.disabled = true;
			}
			const updateUsageInfo = (name) => {
				name = name.trim();
				let msg = "";
				if (/^[a-zA-Z_$][a-zA-Z0-9_\-$]*$/.test(name)) {
					msg = `You can use it with <code>{power:${name}}</code>`;
				}
				usageInfo.innerHTML = msg;
			};
			updateUsageInfo(values.name);
			formatButton.addEventListener("click", () => {
				codeArea.value = formatJsString(codeArea.value);
			});
			nameInput.addEventListener("input", (e) => {
				const name = e.target.value;
				updateUsageInfo(name);
			});
			delButton.addEventListener("click", () => {
				const confirmDelete = confirm(
					"Are you sure you want to delete this Power Shortcode? This action is irreversible!"
				);
				if (confirmDelete) {
					delButton.parentElement.remove();
				}
			});
			repeaterWrapper.appendChild(repeaterItem);
		};

		if (oldValue.length) {
			for (const item of oldValue) {
				addRepeaterItem(item);
			}
		}

		addButton.addEventListener("click", () => {
			addRepeaterItem();
		});
	};

	// Code from https://rudrastyh.com/wordpress/select2-for-metaboxes-with-ajax.html
	// initialize Select2 with ajax for searching posts.
	const getSelect2Options = (postType) => {
		return {
			ajax: {
				url: ajaxurl,
				dataType: "json",
				delay: 250,
				data: function (params) {
					let args = {
						q: params.term,
						action: "dsh_get_posts",
					};
					if (postType) {
						args.dsh_post_type = postType;
					}
					return args;
				},
				processResults: function (data) {
					var options = [];
					if (data) {
						jQuery.each(data, function (index, text) {
							options.push({ id: text[0], text: text[1] });
						});
					}
					return {
						results: options,
					};
				},
				cache: true,
			},
			minimumInputLength: 3, // the minimum of symbols to input before perform a search
		};
	};

	const initDemosPage = () => {
		let $demosPage = $(".wrap.dynamic-shortcodes-demo");
		if (!$demosPage.length) {
			return;
		}
		let $postSelectorWrapper = $(".demos-preview-select-wrapper");
		let $postSelector = $("#dsh-demos-post-selector");
		$postSelector.select2(getSelect2Options(false));
		$postSelector.on("change", () => {
			let val = $postSelector.val();
			const url = new URL(window.location);
			url.searchParams.set("demos_post_id", val);
			window.location.href = url.toString();
		});

		let $barButtons = $demosPage.find("ul#demo-tabs-bar li a");
		// restore tab if present in url fragment:
		let fragment = window.location.hash;
		let selectedTab = $barButtons.first().attr("href");
		if (fragment) {
			selectedTab = fragment;
		}
		const showTab = (tab) => {
			$barButtons.removeClass("active");
			let $a = $barButtons.filter(`[href="${tab}"]`);
			if (
				$a.data("showPostSelector") === "yes" &&
				$a.parent().hasClass("enabled")
			) {
				$postSelectorWrapper.show();
			} else {
				$postSelectorWrapper.hide();
			}
			$a.addClass("active");
			$demosPage.find(".dsh-tab").hide();
			$(tab).fadeIn();
		};
		showTab(selectedTab);
		$barButtons.click(function (e) {
			e.preventDefault();
			let selectTab = $(this).attr("href");
			// setting the url fragment causes scrolling, the following is to
			// avoid it:
			let scrollPosition = $(window).scrollTop();
			window.location.hash = selectTab;
			$(window).scrollTop(scrollPosition);
			showTab(selectTab);
		});
	};
	initPower();
	initDemosPage();

	// Dismissable Admin Notices (from https://www.alexgeorgiou.gr/persistently-dismissible-notices-wordpress/):
	jQuery(".dynamic-shortcodes-dismissible-notice").on(
		"click",
		".notice-dismiss",
		function (event, el) {
			var $notice = jQuery(this).parent(".notice.is-dismissible");
			var dismiss_url = $notice.attr("data-dismiss-url");
			if (dismiss_url) {
				jQuery.get(dismiss_url);
			}
		}
	);
})(jQuery);
