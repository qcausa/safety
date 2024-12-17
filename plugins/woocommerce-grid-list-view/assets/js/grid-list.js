jQuery(document).ready(function ($) {
  // Toggle Grid/List View
  $(".wc-view-toggle button").on("click", function () {
    var view = $(this).data("view");

    // Add/Remove Active Class
    $(".wc-view-toggle button").removeClass("active");
    $(this).addClass("active");

    // Add View Class to Body
    $("body")
      .removeClass("wc-view-grid wc-view-list")
      .addClass("wc-view-" + view);

    // Add Class to WooCommerce Products
    var productList = $("ul.products");
    if (view === "list") {
      productList.removeClass("wc-grid-view").addClass("wc-list-view");
    } else {
      productList.removeClass("wc-list-view").addClass("wc-grid-view");
    }
  });
});
