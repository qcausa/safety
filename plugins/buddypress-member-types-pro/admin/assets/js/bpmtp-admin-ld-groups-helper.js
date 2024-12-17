jQuery( function ( $ ) {
// learndash group helper.
    var $groups_list = $('#bpmtp-selected-ld-groups-list');
    var $group_selector_field =  $("#bpmtp-ld-group-selector");

    $group_selector_field.autocomplete({
        // define callback to format results, fetch data
        source: function(req, add){
            var ids= get_included_group_ids();
                ids = ids.join(',');
            // pass request to server
            $.post( ajaxurl,
                {
                    action: 'bpmtp_get_ld_groups_list',
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
                "<input type='hidden' value='" + ui.item.id + "' name='_bp_member_type_ld_groups[]'/>" +
                "<a class='bpmtp-remove-ld-group' href='#'>X</a>" +
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
    $groups_list.on( 'click', '.bpmtp-remove-ld-group', function() {
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
});