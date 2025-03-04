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
});
