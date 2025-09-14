jQuery(function($){
    console.log('graduate-directory ready', arguments);
    let currentPage = 1;

    function fetchGraduates(){
        console.log('fetchGraduates', arguments);
        var data = {
            action: 'pspa_ms_filter_graduates',
            nonce: pspaMsDir.nonce,
            profession: $('#pspa-graduate-filters [name="profession"]').val(),
            job_title: $('#pspa-graduate-filters [name="job_title"]').val(),
            city: $('#pspa-graduate-filters [name="city"]').val(),
            country: $('#pspa-graduate-filters [name="country"]').val(),
            graduation_year: $('#pspa-graduate-filters [name="graduation_year"]').val(),
            full_name: $('#pspa-graduate-filters [name="full_name"]').val(),
            page: currentPage
        };
        $.post(pspaMsDir.ajaxUrl, data, function(response){
            console.log('fetchGraduates response', response);
            if(response.success){
                $('#pspa-graduate-results').html(response.data.html);
            }
        });
    }

    $('#pspa-graduate-filters').on('change', 'select', function(){
        console.log('filter change', this, arguments);
        currentPage = 1;
        fetchGraduates();
    });

    $('#pspa-graduate-filters [name="full_name"], #pspa-graduate-filters [name="graduation_year"]').on('input', function(){
        console.log('filter input', this, arguments);
        currentPage = 1;
        fetchGraduates();
    });

    $('#pspa-graduate-results').on('click', '.pspa-dir-pagination a', function(e){
        console.log('pagination click', this, arguments);
        e.preventDefault();
        currentPage = $(this).data('page');
        fetchGraduates();
    });

    console.log('initial fetch');
    fetchGraduates();
});
