  document.querySelectorAll('#images, #profile_image').forEach(function(element) {
    element.addEventListener('change', function(event) {
        console.log(event.target.id + ' changed!');
        let previewContainer = document.getElementById('image-preview');
        previewContainer.innerHTML = ""; // Clear previous images
        Array.from(event.target.files).forEach(file => {
            let reader = new FileReader();
            reader.onload = function(e) {
                let imgElement = document.createElement("img");
                imgElement.className = 'preview-image border border-dark rounded';
                imgElement.src = e.target.result;
                imgElement.style.width = "100px";
                imgElement.style.height = "100px";
                imgElement.style.margin = "5px";
                previewContainer.appendChild(imgElement);
            };
            reader.readAsDataURL(file);
        });
    });
  });

  // Preview Quick View Images
  $(document).ready(function () {
    $(".preview-image").click(function () {
      var src = $(this).attr("data-src");
      $("#previewImage").attr("src", src);
      $("#imagePreviewModal").fadeIn();
    });

    $("#imagePreviewModal").click(function () {
      $(this).fadeOut();
    });

    // Copy Monday's hours to all days
    $("#copy-monday").click(function () {
        let mondayOpen = $("input[name='business_hours[0][open_time]']").val();
        let mondayClose = $("input[name='business_hours[0][close_time]']").val();
        
        $(".open-time").each(function (index) {
            if (index !== 0) { // Skip Monday itself
                $(this).val(mondayOpen);
            }
        });

        $(".close-time").each(function (index) {
            if (index !== 0) { // Skip Monday itself
                $(this).val(mondayClose);
            }
        });
    });

    // Disable time inputs if closed checkbox is checked
    $(".closed-checkbox").change(function () {
        let row = $(this).closest("tr");
        if ($(this).is(":checked")) {
            row.find(".open-time, .close-time").prop("disabled", true).val('');
        } else {
            row.find(".open-time, .close-time").prop("disabled", false);
        }
    });

    // Get Parent Categories // Create Business
    $.get('/api/categories/parents', function (data) {
      $('#parent-category').append(data.map(category => 
          `<option value="${category.id}">${category.name}</option>`
      ));
    });

    // Load Child Categories on Parent Selection // Create Business
    $('#parent-category').on('change', function () {
        var parentId = $(this).val();
        $('#child-category').empty().append('<option value="">Select Child Category</option>');
        
        if (parentId) {
            $.get(`/api/categories/${parentId}/children`, function (data) {
                $('#child-category').append(data.map(category => 
                    `<option value="${category.id}">${category.name}</option>`
                ));
            });
        }
    });

    // Fetch Cities when Zip Code is Entered
    $('#zip-code').on('blur', function () {
      var zipCode = $(this).val();
      $('#city').empty().append('<option value="">Select City</option>');

        if (zipCode) {
            $.get(`/api/zip/${zipCode}/cities`, function (data) {
                $('#city').append(data.map(city =>
                    `<option value="${city.id}">${city.name}</option>`
                ));
            }).fail(function () {
                //alert('No cities found for this zip code.');
                $('#zip-code').addClass('is-invalid');
                $('#zip-code').after('<div class="invalid-feedback">Invalid Zip-code, Failed to fetch cities. Please try again.</div>');
            });
        }
    });

    // Fetch State, Latitude, and Longitude when City is Selected
    $('#city').on('change', function () {
        var cityId = $(this).val();
        if (cityId) {
            $.get(`/api/city/${cityId}/details`, function (data) {
                $('#state').val(data.state);
                $('#latitude').val(data.latitude);
                $('#longitude').val(data.longitude);
                $('#country').val(data.country);
            }).fail(function () {
                alert('City details not found.');
            });
        }
    });

  });
