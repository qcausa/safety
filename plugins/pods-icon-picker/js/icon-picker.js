jQuery(document).ready(function ($) {
  // Initialize the Font Awesome Icon Picker
  $(".icon-picker-input").iconpicker({
    placement: "bottomLeft",
    animation: false,
  });

  // Update the preview on icon selection
  $(".icon-picker-input").on("iconpickerSelected", function (event) {
    var selectedIcon = event.iconpickerValue;
    $(this)
      .siblings(".icon-preview")
      .html('<i class="' + selectedIcon + '"></i>');
  });
});
