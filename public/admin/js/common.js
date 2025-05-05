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
    
});
