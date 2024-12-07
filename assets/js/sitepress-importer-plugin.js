jQuery(document).ready(function ($) {
    $('#csv-upload-form').on('submit', function (e) {
        e.preventDefault(); // Prevent the default form submission

        var formData = new FormData();
        var fileInput = $('input[name=template_csv_file]')[0];
        var pageFileInput = $('input[name=page_csv_file]')[0];

        formData.append('template_csv_file', fileInput.files[0]);
        formData.append('page_csv_file', pageFileInput.files[0]);

        formData.append('action', 'process_csv'); // Action for the AJAX request

        $.ajax({
            url: sitepress_importer_ajax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.success) {
                    $('#response-message').html('<div class="updated"><p>' + response.data + '</p></div>');
                } else {
                    $('#response-message').html('<div class="error"><p>' + response.data + '</p></div>');
                }
            },
            error: function () {
                $('#response-message').html('<div class="error"><p>An error occurred while uploading the CSV.</p></div>');
            }
        });
    });
    $('#siteimporter-reset').on('click', function (e) {
 
        e.preventDefault(); // Prevent the default form submission

        var formData = new FormData();

        formData.append('action', 'sitepress_importer_reset'); // Action for the AJAX request

        $.ajax({
            url: sitepress_importer_ajax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {

                if (response.success) {
                    window.location = location.href;
                    // $('#response-message').html('<div class="updated"><p>' + response.data + '</p></div>');
                } else {
                    $('#response-message').html('<div class="error"><p>' + response.data + '</p></div>');
                }
            },
            error: function () {
                $('#response-message').html('<div class="error"><p>An error occurred while uploading the CSV.</p></div>');
            }
        });
    });

    $.ajax({
        url: sitepress_importer_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'sitepress_importer_load_import_process'
        },
        success: function (response) {
            if (response.success) {
                console.log({ response })
                const messages = JSON.parse(response.data)
                // @todo: handle import properly
                $('#progress-message').html(response.data);
            } else {
                $('.form-section').css('display', 'inherit');
                // $('#progress-message').html('No progress data available.');

            }
        }
    });

});