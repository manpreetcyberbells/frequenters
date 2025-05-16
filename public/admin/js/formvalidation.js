$(function () {
    $.validator.setDefaults({
      submitHandler: function () {
        // alert( "Form successful submitted!" );
        return true;
      }
    });
    $('#staffForm').validate({
      rules: {
        name: {
          required: true,
        },
        email: {
            required: true,
            email: true,
          },
        password: {
            required: true,
            minlength: 5
        },
        password_confirmation: {
            required: true,
            equalTo: "#password"
        },
      },
      messages: {
        name: {
            required: "Please enter a valid name",
        },
        email: {
            required: "Please enter a email address",
            email: "Please enter a valid email address"
        },
        password: {
          required: "Please provide a password",
          minlength: "Your password must be at least 5 characters long"
        },
        password_confirmation: {
            required: "Please confirm your password",
            equalTo: "Passwords do not match"
        }
        // terms: "Please accept our terms"
      },
      errorElement: 'span',
      errorPlacement: function (error, element) {
        error.addClass('invalid-feedback');
        element.closest('.form-group').append(error);
      },
      highlight: function (element, errorClass, validClass) {
        $(element).addClass('is-invalid');
      },
      unhighlight: function (element, errorClass, validClass) {
        $(element).removeClass('is-invalid');
      }
    });
});



$(function () {
    $.validator.setDefaults({
      submitHandler: function () {
        // alert( "Form successful submitted!" );
        return true;
      }
    });
    $('#menuForm').validate({
      rules: {
        name: {
          required: true,
        },
        price: {
            required: true,
            number: true,
            min: 1
        },
      },
      messages: {
        name: {
            required: "Please enter a valid name",
        },
        price: {
            required: "Please enter the price",
            number: "Please enter a valid number",
            min: "Price must be greater than 0"
        }
        // terms: "Please accept our terms"
      },
      errorElement: 'span',
      errorPlacement: function (error, element) {
        error.addClass('invalid-feedback');
        element.closest('.form-group').append(error);
      },
      highlight: function (element, errorClass, validClass) {
        $(element).addClass('is-invalid');
      },
      unhighlight: function (element, errorClass, validClass) {
        $(element).removeClass('is-invalid');
      }
    });
});



$(function () {
  // Custom validator: reward name should only contain letters and spaces
  $.validator.addMethod("rewardNameValid", function (value, element) {
    return this.optional(element) || /^[a-zA-Z\s]+$/.test(value);
  }, "Please enter a valid name (letters and spaces only)");

  // Custom validator: valid_until must be >= valid_from
  $.validator.addMethod("dateAfterOrEqual", function (value, element, params) {
    var fromDate = $(params).val();
    return !fromDate || new Date(value) >= new Date(fromDate);
  }, "Valid Until must be after or equal to Valid From");

  $.validator.setDefaults({
    submitHandler: function () {
      // alert("Form successfully submitted!");
      return true;
    }
  });

  $('#rewardForm').validate({
    rules: {
      reward_name: {
        required: true,
        rewardNameValid: true
      },
      points_required: {
        required: true,
        number: true,
        min: 1
      },
      valid_from: {
        required: true,
        date: true
      },
      valid_until: {
        required: true,
        date: true,
        dateAfterOrEqual: "#valid_from"
      }
    },
    messages: {
      reward_name: {
        required: "Please enter a reward name"
      },
      points_required: {
        required: "Please enter points required",
        number: "Please enter a valid number",
        min: "Points must be greater than 0"
      },
      valid_from: {
        required: "Please enter the start date",
        date: "Please enter a valid date"
      },
      valid_until: {
        required: "Please enter the end date",
        date: "Please enter a valid date",
        dateAfterOrEqual: "Valid Until must be after or equal to Valid From"
      }
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group').append(error);
    },
    highlight: function (element) {
      $(element).addClass('is-invalid');
    },
    unhighlight: function (element) {
      $(element).removeClass('is-invalid');
    }
  });
});

$(function () {
  // Custom method to validate end time is after start time (optional)
  $.validator.addMethod("timeAfter", function (value, element, params) {
    const startTime = $(params).val();
    return startTime && value > startTime;
  }, "End Time must be after Start Time");

  $.validator.setDefaults({
    submitHandler: function () {
      // alert("Form successfully submitted!");
      return true;
    }
  });
  
  $('#serviceForm').validate({
    rules: {
      business_id: {
        required: true
      },
      name: {
        required: true,
      },
      category_id: {
        required: true
      },
      duration: {
        required: true
      },
      price: {
        required: true,
        number: true,
        min: 1
      }
    },
    messages: {
      business_id: {
        required: "Please select a business"
      },
      name: {
        required: "Please enter a reward name"
      },
      category_id: {
        required: "Please select a category"
      },
      duration: {
        required: "Please enter the duration"
      },
      price: {
        required: "Please enter the price",
        number: "Price must be a valid number",
        min: "Price must be greater than 0"
      }
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group').append(error);
    },
    highlight: function (element) {
      $(element).addClass('is-invalid');
    },
    unhighlight: function (element) {
      $(element).removeClass('is-invalid');
    }
  });

  const days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
  days.forEach((day, index) => {
    $(`input[name="availability[${index}][start_time]"]`).rules('add', {
      required: true,
      messages: {
        required: `${day} start time is required`
      }
    });

    $(`input[name="availability[${index}][end_time]"]`).rules('add', {
      required: true,
      timeAfter: `input[name="availability[${index}][start_time]"]`,
      messages: {
        required: `${day} end time is required`,
        timeAfter: `${day} end time must be after start time`
      }
    });
  });
});