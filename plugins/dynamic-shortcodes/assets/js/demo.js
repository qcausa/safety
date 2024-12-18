jQuery(function () {
	document.querySelectorAll(".expand-examples").forEach(function (button) {
		button.addEventListener("click", function () {
			const isExpanded = button.getAttribute("data-expanded") === "true";

			if (isExpanded) {
				button.textContent = "Expand examples";
				button.setAttribute("data-expanded", "false");
				hideExamples(button);
			} else {
				// Expand the examples
				button.textContent = "Collapse examples";
				button.setAttribute("data-expanded", "true");
				showExamples(button);
			}
		});
	});

	var copyButtons = document.querySelectorAll(".copy-button");

	copyButtons.forEach(addListenerToCopyButton);

	function addListenerToCopyButton(button) {
		button.addEventListener("click", function () {
			var textToCopy = jQuery(this).siblings("code").text();

			navigator.clipboard.writeText(textToCopy).then(function () {
				const originalText = button.textContent;
				button.textContent = "Copied!";

				setTimeout(function () {
					button.textContent = originalText;
				}, 3000);
			});
		});
	}

	function showExamples(target) {
		const currentRow = target.closest("tr");
		const table = currentRow.closest("tbody");
		const newRow = document.createElement("tr");
		newRow.classList.add("highlighted-row");
		jQuery(newRow).hide();

		const newCell = document.createElement("td");
		newCell.class = "examples";
		newCell.setAttribute("colspan", "3");
		newRow.appendChild(newCell);

		table.insertBefore(newRow, currentRow.nextSibling);

		addSpinner(newCell);

		jQuery(newRow).fadeIn(1000);

		id = target.dataset.id_examples;
		const result_type = target.dataset.default_type;
		const original = JSON.parse(target.dataset.original);
		const available_types = JSON.parse(target.dataset.available_types);

		wp.ajax
			.post("dsh_expand_list_ajax", {
				post_id: id,
				result_type: result_type,
				original: original,
			})
			.done(function (examples) {
				const select = document.createElement("select");
				Object.keys(available_types).forEach(function (key) {
					const option = document.createElement("option");
					option.value = key;
					option.text = available_types[key];
					if (key === result_type) {
						option.selected = true;
					}
					select.appendChild(option);
				});

				select.addEventListener("change", (event) => {
					addSpinner(newCell);
					jQuery(newCell).find("table").remove();

					const selectedType = event.target.value;

					const data = {
						post_id: id,
						result_type: selectedType,
						original: original,
					};

					wp.ajax
						.post("dsh_expand_list_ajax", data)
						.done((response) => {
							updateCellContent(
								newCell,
								response,
								selectedType,
								select
							);
						})
						.fail((error) => {});
				});

				removeSpinner(newCell);
				updateCellContent(newCell, examples, result_type, select);
			})
			.fail(function (errorThrown) {
				removeSpinner(newCell);
				console.log(errorThrown);
			});
	}

	function removeSpinner(target) {
		jQuery(target).find(".spinner").remove();
	}

	function hideExamples(button) {
		const currentRow = button.closest("tr");
		const highlightedRow = currentRow.nextSibling;

		if (
			highlightedRow &&
			highlightedRow.classList.contains("highlighted-row")
		) {
			jQuery(highlightedRow).fadeOut(500, function () {
				highlightedRow.remove();
			});
		}
	}

	function addSpinner(newCell) {
		const spinner = document.createElement("div");
		spinner.classList.add("spinner");
		spinner.innerHTML =
			"Creating examples of how to manipulate the result...";
		newCell.appendChild(spinner);

		return spinner;
	}

	function updateCellContent(newCell, examples, result_type, select) {
		const description = document.createElement("div");
		description.innerHTML = `
		<h3>Examples</h3>
		<span class="inline-element">Manipulate the result as</span>
		<br />It is up to you to recognise what kind of result you wanted to achieve, and the manipulation examples serve to give you a better understanding of the syntax.<br />
		`;

		description.insertBefore(select, description.childNodes[4]);

		const table = document.createElement("table");
		table.classList.add("examples");

		const headerRow = document.createElement("tr");
		const headerCode = document.createElement("th");
		const headerResult = document.createElement("th");
		const headerDescription = document.createElement("th");
		headerDescription.textContent = "Description";
		headerCode.textContent = "Dynamic Shortcode";
		headerResult.textContent = "Result";
		headerRow.appendChild(headerDescription);
		headerRow.appendChild(headerCode);
		headerRow.appendChild(headerResult);
		table.appendChild(headerRow);

		examples.forEach((example) => {
			const tableRow = document.createElement("tr");
			const cellDescription = document.createElement("td");
			const cellCode = document.createElement("td");
			const cellResult = document.createElement("td");

			const codeElement = document.createElement("code");
			const noticeElement = document.createElement("small");
			codeElement.textContent = example.code;
			noticeElement.textContent = example.notice;
			const copyButton = document.createElement("button");
			copyButton.classList.add("copy-button");
			copyButton.textContent = "Copy";
			addListenerToCopyButton(copyButton);

			cellCode.classList.add("code");
			cellCode.appendChild(codeElement);
			cellCode.appendChild(noticeElement);
			cellCode.appendChild(copyButton);

			cellResult.classList.add("result");
			cellResult.innerHTML = example.result;

			cellDescription.classList.add("what");
			cellDescription.innerHTML = example.description;

			tableRow.appendChild(cellDescription);
			tableRow.appendChild(cellCode);
			tableRow.appendChild(cellResult);

			table.appendChild(tableRow);
		});

		newCell.innerHTML = "";
		newCell.appendChild(description);
		newCell.appendChild(table);
	}
});
