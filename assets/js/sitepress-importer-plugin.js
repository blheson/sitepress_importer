jQuery(document).ready(function ($) {
    function sitepress_trigger_poll() {
        setInterval(() => {
            get_progress_report()
        }, 1000)
    }
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
                    $('#response-message').html('<div class="updated"><p> processing...</p></div>');
                    sitepress_trigger_poll();
                } else {
                    if (response?.data?.message) {
                        $('#response-message').html('<div class="error"><p>' + response.data.message + '</p></div>');
                    } else {
                        $('#response-message').html('<div class="error"><p>Error uploading template</p></div>');
                    }
                }
            },
            error: function () {
                $('#response-message').html('<div class="error"><p>An error occurred while uploading the CSV.</p></div>');
            }
        });
    });
    $('#siteimporter-reset').on('click', function (e) {

        e.preventDefault(); // Prevent the default form submission

        // Add confirmation popup
        if (!confirm('Are you sure you want to reset the importer? All imported templates and pages will be deleted.')) {
            return; // Exit if the user cancels
        }
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
                 
                } else {
                    $('#response-message').html('<div class="error"><p>' + response.data + '</p></div>');
                }
            },
            error: function () {
                $('#response-message').html('<div class="error"><p>An error occurred while uploading the CSV.</p></div>');
            }
        });
    });
    function get_progress_report() {
        //Get progress report
        $.ajax({
            url: sitepress_importer_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sitepress_importer_load_import_process'
            },
            success: function (response) {
                if (response.success) {

                    const messages = JSON.parse(response.data);
                    if (Array.isArray(messages)) {
                        $('#progress-message').html("");
                        $('#response-message').html("");
                        
                        messages.forEach((message) => {

                            const indMessages = message.split('--n--')
                            if (indMessages.lenght === 0) {
                                // @todo -- handle
                                return;
                            }
                            indMessages.forEach((unitMessage) => {
                                const p = document.createElement('p');
                                p.innerText = unitMessage;
                                $('#progress-message').append(p);
                            });

                        })
                    }
                    // @todo: handle import properly
                    // $('#progress-message').html(response.data);
                } else {
                    $('.form-section').css('display', 'inherit');
                    // $('#progress-message').html('No progress data available.');

                }
            }
        });
    }
    get_progress_report()

});