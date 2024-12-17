jQuery(function ($) {
  // reload page with preset slug param
  $(".wcpt-presets__item").on("click", function () {
    var $this = $(this),
      slug = $this.attr("data-wcpt-preset-slug"),
      currentUrl = window.location.href;

    // Check if the URL has a fragment (#)
    var urlParts = currentUrl.split("#");
    var baseUrl = urlParts[0]; // URL without the fragment
    var fragment = urlParts[1] ? "#" + urlParts[1] : ""; // Retain the fragment if it exists

    // Update the URL
    var newUrl = baseUrl + "&wcpt_preset=" + slug + fragment;
    window.location.href = newUrl;
  }),
    // dismiss preset applied message
    $(".wcpt-preset-applied-message__dismiss").on("click", function () {
      var $this = $(this);
      $this.closest(".wcpt-preset-applied-message").slideUp();
    });

  // copy shortcode
  $(".wcpt-preset-applied-message__shortcode-copy-button").on(
    "click",
    function () {
      var $this = $(this);
      $input = $this.siblings("input");
      $input.select();
      document.execCommand("copy");
    }
  );
});
