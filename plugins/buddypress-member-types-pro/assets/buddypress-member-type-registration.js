(function ($) {

    if (typeof BPMTPFieldsMap == "undefined") {
        return;
    }
    $(document).ready(function () {
        var selected;
        // for single member type.
        if ($('.field_type_membertype select').length) {
            selected = $('.field_type_membertype select').val();
            selected = (!selected || selected.length === 0) ? 'null' : selected;
            showFieldsWithMemberType(selected);
        } else if ($('.field_type_membertype .input-options').length) {
            selected = $('.field_type_membertype .input-options input:checked').val();
            selected = (!selected || selected.length === 0) ? 'null' : selected;
            showFieldsWithMemberType(selected);
        } else if ($('.field_type_membertypes select').length) {
            selected = $('.field_type_membertypes select').val();
            selected = (!selected || selected.length === 0) ? ['null'] : selected;
            showFieldsWithMemberTypes(selected);
        } else if ($('.field_type_membertypes .checkbox').length) {
            var $this = $('.field_type_membertypes .checkbox');
            selected = [];

            $this.find("input[type='checkbox']").filter(':checked').each(function () {
                selected.push($(this).val());
            });
            selected = (!selected || selected.length === 0) ? ['null'] : selected;
            showFieldsWithMemberTypes(selected);
        }

    });


    // 1. Single member type, select box view.
    $(document).on('change', '.field_type_membertype select', function () {
        var $this = $(this);
        var selected = $this.val();
        if (selected == "") {
            // all fields with 'null' member type.
            showFieldsWithMemberType("null");
        } else {
            showFieldsWithMemberType(selected);
        }
    });

    // 2. Single member type, radio.
    $(document).on('click', '.field_type_membertype .input-options input', function () {
        var $this = $(this);
        var selected = $this.val();
        if (selected == "") {
            // all fields with 'null' member type.
            showFieldsWithMemberType("null");
        } else {
            showFieldsWithMemberType(selected);
        }
    });

    // 3. Multi member type, checkbox view.
    $(document).on('click', '.field_type_membertypes select', function () {
        var $this = $(this);
        var selected = $this.val();

        if (!selected || selected.length === 0) {
            // all fields with 'null' member type.
            showFieldsWithMemberTypes(["null"]);
        } else {
            showFieldsWithMemberTypes(selected);
        }
    });

    // 4. Multi member type, checkbox view.
    $(document).on('click', '.field_type_membertypes .checkbox input[type="checkbox"]', function () {
        var $this = $(this).parents('.checkbox');
        var selected = [];

        $this.find("input[type='checkbox']").filter(':checked').each(function () {
            selected.push($(this).val());
        });

        if (!selected || selected.length === 0) {
            // all fields with 'null' member type.
            showFieldsWithMemberTypes(["null"]);
        } else {
            showFieldsWithMemberTypes(selected);
        }
    });

    /**
     * Show/Hide field based on member type.
     *
     * @param memberType
     */
    function showFieldsWithMemberType(memberType) {
        _.each(BPMTPFieldsMap, function (memberTypes, fieldID) {
            if (-1 !== $.inArray(memberType, memberTypes)) {
                showField(fieldID);
            } else {
                hideField(fieldID);
            }
        });
    }

    /**
     * Show hide fields based on selected member types.
     *
     * @param selectedMemberTypes
     */
    function showFieldsWithMemberTypes(selectedMemberTypes) {
        _.each(BPMTPFieldsMap, function (memberTypes, fieldID) {
            var visible = false;
            for (var i = 0; i < selectedMemberTypes.length; i++) {
                var memberType = selectedMemberTypes[i] == "" ? "null" : selectedMemberTypes[i];
                if (-1 !== $.inArray(memberType, memberTypes)) {
                    visible = true;
                    showField(fieldID);
                    break;
                }
            }

            if (!visible) {
                hideField(fieldID);
            }
        });
    }

    /**
     * Show a field div.
     *
     * @param fieldID
     */
    function showField(fieldID) {
        $('.editfield.' + fieldID).show();
    }

    /**
     * Hide a field div.
     *
     * @param fieldID
     */
    function hideField(fieldID) {
        $('.editfield.' + fieldID).hide();
    }
})(jQuery);
