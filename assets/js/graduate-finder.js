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
        var requestToken = 0;

        function setBusy(isBusy){
            if (isBusy) {
                $results.addClass('is-loading').attr('aria-busy', 'true');
            } else {
                $results.removeClass('is-loading').removeAttr('aria-busy');
            }
        }

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

            requestToken += 1;
            var activeToken = requestToken;

            setBusy(true);

            request = $.post(pspaMsFinder.ajaxUrl, data)
                .done(function(response){
                    if (response && response.success && response.data && typeof response.data.html !== 'undefined') {
                        $results.html(response.data.html);
                    } else if (pspaMsFinder.errorMessage) {
                        $results.html('<p>' + pspaMsFinder.errorMessage + '</p>');
                    }
                })
                .fail(function(jqXHR, textStatus){
                    if (textStatus === 'abort') {
                        return;
                    }
                    if (pspaMsFinder.errorMessage) {
                        $results.html('<p>' + pspaMsFinder.errorMessage + '</p>');
                    }
                })
                .always(function(){
                    if (activeToken === requestToken) {
                        setBusy(false);
                        request = null;
                    }
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
