jQuery( function ( $ ) {

    var $groups_list = $('#bpgtc-selected-groups-list');
    var $group_selector_field =  $("#bpgtc-group-selector");

    $group_selector_field.autocomplete({
        // define callback to format results, fetch data
        source: function(req, add){
            var ids= get_included_group_ids();
                ids = ids.join(',');
            // pass request to server
            $.post( ajaxurl,
                {
                    action: 'bpgtc_get_groups_list',
                    'q': req.term,
                    'included': ids,
                    cookie:encodeURIComponent(document.cookie)
                } , function(data) {

                add(data);
            },'json');
        },
        //define select handler
        select: function(e, ui) {

            var $li = "<li>" +
                "<input type='hidden' value='" + ui.item.id + "' name='_bpgtc_tab_groups[]'/>" +
                "<a class='bpgtc-remove-group' href='#'>X</a>" +
                "<a href='"+ui.item.url + "'>" + ui.item.label + "</a>" +
                "</li>";
            $groups_list.append($li );

            this.value="";
            return false;// do not update input box
        },
        // when a new menu is shown
        open: function(e, ui) {

        },
        // define select handler
        change: function(e, ui) {
        }
    });// end of autosuggest.


    //remove
    $groups_list.on( 'click', '.bpgtc-remove-group', function() {
        $(this).parent().remove();
        return false;
    });

    function get_included_group_ids() {
        var ids = [];

        $groups_list.find('li input').each( function (index, element ) {
           ids.push( $(element).val());
        });

        return ids;
    }

    var $excluded_groups_list = $('#bpgtc-selected-excluded-groups-list');
    var $excluded_groups_field =  $("#bpgtc-excluded-group-selector");

    $excluded_groups_field.autocomplete({
        // define callback to format results, fetch data
        source: function(req, add){
            var ids= get_excluded_group_ids();
            ids = ids.join(',');
            // pass request to server
            $.post( ajaxurl,
                {
                    action: 'bpgtc_get_groups_list',
                    'q': req.term,
                    'included': ids,
                    cookie:encodeURIComponent(document.cookie)
                } , function(data) {

                    add(data);
                },'json');
        },
        //define select handler
        select: function(e, ui) {

            var $li = "<li>" +
                "<input type='hidden' value='" + ui.item.id + "' name='_bpgtc_tab_excluded_groups[]'/>" +
                "<a class='bpgtc-remove-excluded-group' href='#'>X</a>" +
                "<a href='"+ui.item.url + "'>" + ui.item.label + "</a>" +
                "</li>";
            $excluded_groups_list.append($li );

            this.value="";
            return false;// do not update input box
        },
        // when a new menu is shown
        open: function(e, ui) {

        },
        // define select handler
        change: function(e, ui) {
        }
    });// end of autosuggest.

    //remove
    $excluded_groups_list.on( 'click', '.bpgtc-remove-excluded-group', function() {
        $(this).parent().remove();
        return false;
    });

    function get_excluded_group_ids() {
        var ids = [];

        $excluded_groups_list.find('li input').each( function (index, element ) {
           ids.push( $(element).val());
        });

        return ids;
    }
});