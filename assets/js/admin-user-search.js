jQuery(function($){
    console.log('admin-user-search ready', arguments);
    var $input = $('#pspa_user_search');
    if ($input.length){
        console.log('autocomplete input found');
        $input.autocomplete({
            minLength: 3,
            source: function(request, response){
                console.log('autocomplete source', request);
                $.getJSON(pspaMsAdminSearch.ajaxUrl, {
                    action: 'pspa_ms_user_autocomplete',
                    nonce: pspaMsAdminSearch.nonce,
                    term: request.term
                }, response);
            }
        });
    } else {
        console.log('autocomplete input not found');
    }
});
