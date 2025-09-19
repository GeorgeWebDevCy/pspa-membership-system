jQuery(function($){
    if (typeof pspaMsDir === 'undefined') {
        return;
    }

    var $filters = $('#pspa-graduate-filters');
    var $results = $('#pspa-graduate-results');
    var $loader = $results.find('.pspa-ajax-loader');
    var $items = $results.find('.pspa-ajax-results__items');
    var currentPage = 1;
    var request = null;
    var requestToken = 0;

    function setBusy(isBusy){
        if (isBusy) {
            $results.attr('aria-busy', 'true');
            $loader.removeAttr('hidden');
        } else {
            $results.removeAttr('aria-busy');
            $loader.attr('hidden', 'hidden');
        }
    }

    function showError(){
        if (pspaMsDir.errorMessage) {
            $items.html('<p>' + pspaMsDir.errorMessage + '</p>');
        }
    }

    function fetchGraduates(){
        if (request && request.readyState !== 4) {
            request.abort();
        }

        var data = {
            action: 'pspa_ms_filter_graduates',
            nonce: pspaMsDir.nonce,
            profession: $filters.find('[name="profession"]').val(),
            job_title: $filters.find('[name="job_title"]').val(),
            city: $filters.find('[name="city"]').val(),
            country: $filters.find('[name="country"]').val(),
            graduation_year: $filters.find('[name="graduation_year"]').val(),
            full_name: $filters.find('[name="full_name"]').val(),
            page: currentPage
        };

        requestToken += 1;
        var activeToken = requestToken;

        setBusy(true);

        request = $.post(pspaMsDir.ajaxUrl, data)
            .done(function(response){
                if (response && response.success && response.data && typeof response.data.html !== 'undefined') {
                    $items.html(response.data.html);
                } else {
                    showError();
                }
            })
            .fail(function(jqXHR, textStatus){
                if (textStatus === 'abort') {
                    return;
                }
                showError();
            })
            .always(function(){
                if (activeToken === requestToken) {
                    setBusy(false);
                    request = null;
                }
            });
    }

    $filters.on('change', 'select', function(){
        currentPage = 1;
        fetchGraduates();
    });

    $filters.find('[name="full_name"], [name="graduation_year"]').on('input', function(){
        currentPage = 1;
        fetchGraduates();
    });

    $results.on('click', '.pspa-dir-pagination a', function(event){
        event.preventDefault();
        var page = $(this).data('page');
        if (page) {
            currentPage = page;
            fetchGraduates();
        }
    });

    fetchGraduates();
});
