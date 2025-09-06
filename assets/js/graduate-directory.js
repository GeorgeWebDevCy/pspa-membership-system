jQuery(function($){
    function fetchGraduates(){
        var data = {
            action: 'pspa_ms_filter_graduates',
            nonce: pspaMsDir.nonce,
            profession: $('#pspa-graduate-filters [name="profession"]').val(),
            job_title: $('#pspa-graduate-filters [name="job_title"]').val(),
            city: $('#pspa-graduate-filters [name="city"]').val(),
            country: $('#pspa-graduate-filters [name="country"]').val()
        };
        $.post(pspaMsDir.ajaxUrl, data, function(response){
            if(response.success){
                $('#pspa-graduate-results').html(response.data.html);
            }
        });
    }

    $('#pspa-graduate-filters').on('change', 'select', fetchGraduates);
    fetchGraduates();
});
