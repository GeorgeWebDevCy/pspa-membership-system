jQuery(function($){
    var $input = $('#pspa_user_search');
    if ($input.length){
        $input.autocomplete({
            source: function(request, response){
                $.getJSON(pspaMsAdminSearch.ajaxUrl, {
                    action: 'pspa_ms_user_autocomplete',
                    nonce: pspaMsAdminSearch.nonce,
                    term: request.term
                }, response);
            }
        });
    }
});
