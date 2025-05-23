// Preview Quick View Images
$(document).ready(function () {
    // Category 
    $("#active_icon").on("change", function () {
        previewImage(this, "#active_icon_preview");
    });

    $("#inactive_icon").on("change", function () {
        previewImage(this, "#inactive_icon_preview");
    });

    $("#validationdark").on("change", function () {
        previewImage(this, "#darkmode_active_preview");
    });

    $("#validationdarkin").on("change", function () {
        previewImage(this, "#darkmode_inactive_preview");
    });

    function previewImage(input, previewSelector) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function (e) {
                $(previewSelector).attr("src", e.target.result);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
// });

// $(document).ready(function () {
    // console.log('HERE123');
    function validateDateTimeRange(startDateId, startTimeId, endDateId, endTimeId) {
        const startDate = $(`#${startDateId}`).val();
        const startTime = $(`#${startTimeId}`).val() + ':00';
        const endDate = $(`#${endDateId}`).val();
        const endTime = $(`#${endTimeId}`).val() + ':00';
    
        const start = new Date(startDate + 'T' + startTime);
        const end = new Date(endDate + 'T' + endTime);
    
        if (start >= end) {
            alert('End date/time must be after start date/time.');
            return false;
        }
    
        // Ensure correct format is submitted
        $(`#${startTimeId}`).val(startTime);
        $(`#${endTimeId}`).val(endTime);
    
        return true;
    }    

    $('#offerForm').on('submit', function (e) {
        if (!validateDateTimeRange('campaign_start_date', 'campaign_start_time', 'campaign_end_date', 'campaign_end_time')) {
            e.preventDefault();
        }
    });
    
    $('#eventForm').on('submit', function (e) {
        if (!validateDateTimeRange('start_date', 'start_time', 'end_date', 'end_time')) {
            e.preventDefault();
        }
    });

    
    // Toggle between question types
    $('#question_type').on('change', function () {
        let selected = $(this).val();
        if (selected === 'text_answer') {
            $('.question-field-wrapper').show();
            $('.option-group-wrapper').hide();
        } else if (selected === 'multiple_choice') {
            $('.question-field-wrapper').show();
            $('.option-group-wrapper').show();
        } else {
            $('.question-field-wrapper, .option-group-wrapper').hide();
        }
    });

    // Add new option row
    $('#add_option').on('click', function () {
        $('#option_container').append(`
            <div class="col-md-12 form-group option-item d-flex align-items-center gap-2 mt-2">
                <input type="text" class="form-control" name="options[]" placeholder="Enter an option" />
                <button type="button" class="btn btn-danger btn-sm remove-option">Remove</button>
            </div>
        `);
    });

    // Remove option row
    $(document).on('click', '.remove-option', function () {
        $(this).closest('.option-item').remove();
    });

    // Toggle between Reward Type
    $('#reward_type').on('change', function () {
        let selected = $(this).val();
        if (selected === 'points') {
            $('.points_div').show();
            $('.discount_div').hide();
            $('.freeItem_div').hide();
        } else if (selected === 'discount') {
            $('.points_div').hide();
            $('.discount_div').show();
            $('.freeItem_div').hide();
        } else if (selected === 'free_item') {
            $('.points_div').hide();
            $('.discount_div').hide();
            $('.freeItem_div').show();
        } else {
            $('.points_div, .discount_div, freeItem_div').hide();
        }
    });
    
    // Menu Images
    let selectedImages = [];
    $('#menuPhoto').on('change', function () {
        selectedImages = Array.from(this.files);
        renderPreview();
    });

    // Render preview with remove buttons
    function renderPreview() {
        $('#imagePreview').html('');
        selectedImages.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const previewHtml = `
                    <div class="position-relative" style="display:inline-block;">
                        <img src="${e.target.result}" class="contestImage" style="object-fit:cover; border-radius:8px; border:1px solid #343a40;" />
                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-image" data-index="${index}" style="padding: 2px 6px; font-size: 12px;">×</button>
                    </div>
                `;
                $('#imagePreview').append(previewHtml);
            };
            reader.readAsDataURL(file);
        });
    }

    // Remove image on click
    $(document).on('click', '.remove-image', function () {
        const index = $(this).data('index');
        selectedImages.splice(index, 1);
        $('#menuPhoto').val('');
        renderPreview();
    });

    // Optional: if you want to send files manually (e.g., with FormData)
    function getSelectedFiles() {
        return selectedImages;
    }
    // Menu Images End


    // Set CSRF token in AJAX header
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Handle delete button click
    $('.delete-answer').click(function (event) {
        event.preventDefault();
        const id = $(this).data('id');
        var apiURL = $(this).data('url');
        // console.log('apiURL',apiURL)
        if (!confirm('Are you sure you want to delete this answer?')) return;

        $.ajax({
            // url: `/contest-answers-delete/${id}`,
            url : apiURL,
            type: 'POST',
            success: function (response) {
                // console.log('response',response.message)
                alert(response.message);
                setTimeout(function () {
                    location.reload();
                }, 1000); 
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                alert('Something went wrong. Could not delete.');
            }
        });
    });


    banner_image.onchange = evt => {
        const [file] = banner_image.files
        if (file) {
            contestImagePreview.src = URL.createObjectURL(file)
        }
    }


});
