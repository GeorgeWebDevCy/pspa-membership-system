jQuery(function($){
    if (typeof pspaMsFinder === 'undefined') {
        return;
    }

    $('.pspa-graduate-finder').each(function(){
        var $container = $(this);
        var $form = $container.find('.pspa-graduate-finder__filters');
        var $results = $container.find('.pspa-graduate-finder__results');
        var currentPage = 1;
        var request = null;

        function fetchResults(){
            if (request && request.readyState !== 4) {
                request.abort();
            }

            var data = {
                action: 'pspa_ms_filter_graduate_finder',
                nonce: pspaMsFinder.nonce,
                page: currentPage,
                first_name: $form.find('[name="first_name"]').val(),
                last_name: $form.find('[name="last_name"]').val(),
                graduation_year: $form.find('[name="graduation_year"]').val()
            };

            $results.addClass('is-loading');

            request = $.post(pspaMsFinder.ajaxUrl, data)
                .done(function(response){
                    if (response && response.success && response.data && typeof response.data.html !== 'undefined') {
                        $results.html(response.data.html);
                    } else if (pspaMsFinder.errorMessage) {
                        $results.html('<p>' + pspaMsFinder.errorMessage + '</p>');
                    }
                })
                .fail(function(){
                    if (pspaMsFinder.errorMessage) {
                        $results.html('<p>' + pspaMsFinder.errorMessage + '</p>');
                    }
                })
                .always(function(){
                    $results.removeClass('is-loading');
                    request = null;
                });
        }

        $form.on('submit', function(event){
            event.preventDefault();
        });

        $form.on('input', 'input', function(){
            currentPage = 1;
            fetchResults();
        });

        $results.on('click', '.pspa-finder-pagination a', function(event){
            event.preventDefault();
            var $link = $(this);
            var page = parseInt($link.data('page'), 10);
            if (!isNaN(page)) {
                currentPage = page;
                fetchResults();
            }
        });

        fetchResults();
    });
});
