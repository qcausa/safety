jQuery(function ($) {
  var $shippingFields = $(
    "#shipping_address_1, #shipping_address_2, #shipping_city, #shipping_state, #shipping_postcode, #shipping_country"
  );
  var $groupSelect = $("#buddy_group");
  var $customShipToDifferentAddress = $("#custom-ship-to-different-address");
  var $shippingSection = $(".shipping_address");
  var addressCache = {}; // Cache for address data

  // Ensure shipping section is visible
  $shippingSection.show();

  // Set initial state and prefetch addresses
  initializeCheckoutState();
  setTimeout(prefetchAddresses, 1000);

  function prefetchAddresses() {
    var groupIds = [];
    $groupSelect.find("option").each(function () {
      var value = $(this).val();
      if (value) groupIds.push(value);
    });

    if (!groupIds.length) return;

    $.ajax({
      url: wcCheckoutCustom.ajaxUrl,
      type: "POST",
      data: {
        action: "get_all_group_addresses",
        group_ids: groupIds,
        nonce: wcCheckoutCustom.nonce,
      },
      success: function (response) {
        if (response.success) {
          addressCache = response.data;
          // If a group is already selected, update its address
          var selectedGroupId = $groupSelect.val();
          if (selectedGroupId && addressCache[selectedGroupId]) {
            loadGroupAddress(selectedGroupId);
          }
        }
      },
    });
  }

  function initializeCheckoutState() {
    $shippingSection.show();
    disableShippingFields();
    copyBillingNameToShipping();
  }

  function copyBillingNameToShipping() {
    // Copy first name if billing field exists and has value
    var billingFirstName = $("#billing_first_name").val();
    if (billingFirstName) {
      $("#shipping_first_name").val(billingFirstName);
    }

    // Copy last name if billing field exists and has value
    var billingLastName = $("#billing_last_name").val();
    if (billingLastName) {
      $("#shipping_last_name").val(billingLastName);
    }
  }

  function loadGroupAddress(groupId) {
    if (!addressCache[groupId]) return;

    $shippingFields.prop("disabled", true);
    populateFields(addressCache[groupId]);

    if (!$customShipToDifferentAddress.is(":checked")) {
      disableShippingFields();
    }
  }

  // Handle group selection
  $groupSelect.on("change", function () {
    var groupId = $(this).val();

    if (!groupId) {
      if ($customShipToDifferentAddress.is(":checked")) {
        enableShippingFields();
      } else {
        disableShippingFields();
        clearShippingFields();
        copyBillingNameToShipping(); // Restore names after clearing
      }
      return;
    }

    loadGroupAddress(groupId);
  });

  // Handle shipping to different address
  $customShipToDifferentAddress.on("change", function () {
    if ($(this).is(":checked")) {
      enableShippingFields();
      clearShippingFields();
      copyBillingNameToShipping(); // Restore names after clearing
    } else {
      var groupId = $groupSelect.val();
      if (groupId) {
        loadGroupAddress(groupId);
      } else {
        disableShippingFields();
        clearShippingFields();
        copyBillingNameToShipping(); // Restore names after clearing
      }
    }
  });

  function populateFields(address) {
    $("#shipping_address_1").val(address.address_1);
    $("#shipping_address_2").val(address.address_2);
    $("#shipping_city").val(address.city);
    $("#shipping_state").val(address.state);
    $("#shipping_postcode").val(address.postcode);
    $("#shipping_country").val(address.country);
  }

  function enableShippingFields() {
    $shippingFields.prop("disabled", false);
  }

  function disableShippingFields() {
    $shippingFields.prop("disabled", true);
  }

  function clearShippingFields() {
    $shippingFields.val("");
  }

  // Also copy billing name to shipping when billing name changes
  $("#billing_first_name, #billing_last_name").on("change", function () {
    if (!$customShipToDifferentAddress.is(":checked") && !$groupSelect.val()) {
      copyBillingNameToShipping();
    }
  });
});
