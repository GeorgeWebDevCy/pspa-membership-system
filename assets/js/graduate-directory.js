jQuery(function($){
    let currentPage = 1;

    function fetchGraduates(){
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
            if(response.success){
                $('#pspa-graduate-results').html(response.data.html);
            }
        });
    }

    $('#pspa-graduate-filters').on('change', 'select', function(){
        currentPage = 1;
        fetchGraduates();
    });

    $('#pspa-graduate-filters [name="full_name"]').on('input', function(){
        currentPage = 1;
        fetchGraduates();
    });

    $('#pspa-graduate-results').on('click', '.pspa-dir-pagination a', function(e){
        e.preventDefault();
        currentPage = $(this).data('page');
        fetchGraduates();
    });

    fetchGraduates();
});
