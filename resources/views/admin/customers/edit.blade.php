@extends('admin.layouts.master')
@section('title', 'Customer Create')
@section('content')
<style>
  .profileHgt{
    height: 100px;
  }
  </style>

 <!-- Content Wrapper. Contains page content -->
 <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Edit Customer</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
              <li class="breadcrumb-item"><a href="{{ route('customer.index') }}">Customer</a></li>
              <li class="breadcrumb-item"><a href="{{ route('customer.index') }}">Listing</a></li>
              <li class="breadcrumb-item active">Edit</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">


        <!--begin::App Content-->
        <div class="app-content">
            @if ($errors->any())
              <div class="alert alert-danger">
                  <ul>
                      @foreach ($errors->all() as $error)
                          <li>{{ $error }}</li>
                      @endforeach
                  </ul>
              </div>
            @endif
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Form Validation-->
                <div class="card card-info card-outline mb-4">
                  <!--begin::Header-->
                  <!-- <div class="card-header"><div class="card-title">Customer Edit</div></div> -->
                  <!--end::Header-->
                  <!--begin::Form-->
                  <form action="{{route('customers.update', $customer->id)}}" method="post" class="needs-validation" novalidate enctype="multipart/form-data">
                  @csrf
                  @method('PUT')
                    <!--begin::Body-->
                    <div class="card-body">
                      <!--begin::Row-->
                      <div class="row g-3">  <!-- profile image -->
                        <div class="col-md-12 form-group">
                          <label for="pimage" class="form-label">Profile Image</label>
                          <div class="">
                            <img src="{{ isset($customer->profile_image) && $customer->profile_image ? $customer->profile_image : '/media/dummy.png' }}" alt="Business Image" width="100" height="100" class="preview-image contestImage border border-dark img-fluid" data-src="{{ $customer->profile_image }}" style="cursor: pointer;">
                          </div>
                        </div>
                        <!-- profile image --> 
                        </div>
                        <div class="row g-3">
                          <div id="image-preview" class="mt-3"></div>
                        </div>
                      <div class="row g-3">    
                        <div class="col-md-4 form-group ml-2">
                         
                            <input type="file" class="custom-file-input form-control" id="profile_image" name="profile_image" multiple="" accept="image/*" required="">
                            <label class="custom-file-label" for="customFile">Upload Profile Image</label>
                          </div>
                      </div>
                      <div class="row g-3">
                       
                        <!--begin::Col-->
                        <div class="col-md-4 form-group">
                          <label for="name" class="form-label">Name</label>
                          <input
                            type="text"
                            class="form-control"
                            id="name"
                            name="name"
                            value="{{ $customer->name }}"
                            required
                            placeholder="Enter customer name"
                          />
                        </div>
                        <!--end::Col-->
                        <!--begin::Col-->
                        <div class="col-md-4 form-group">
                            <label for="validationCustomUsername" class="form-label">Email Address</label>
                          <div class="input-group has-validation">
                            <span class="input-group-text" id="email">@</span>
                            <input
                              type="email"
                              name="email"
                              class="form-control"
                              id="validationCustomUsername"
                              aria-describedby="email"
                              value="{{ $customer->email }}"
                              required
                              placeholder="Enter valid email address"
                            />
                            @error('email')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback">Email should be unique.</div>
                          </div>
                        </div>

                        <!--begin::Col-->
                        <div class="col-md-4 form-group">
                            <!-- <label for="password" class="form-label">Password</label>
                          <div class="input-group has-validation">
                            <input
                              type="password"
                              name="password"
                              class="form-control"
                              id="password"
                              required
                            />
                            <div class="invalid-feedback">Create valid password</div>
                          </div> -->
                        </div>

                        <!--begin::Col-->
                        <div class="col-md-4 form-group">
                            <label for="mobileNumber" class="form-label">Mobile</label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                id="mobileNumber" 
                                name="phone_number" 
                                pattern="^\d{10,15}$" 
                                placeholder="Enter your mobile number"
                                value="{{ $customer->phone }}"
                                required 
                                placeholder="Enter valid mobile number"
                            />
                            <div class="invalid-feedback">Please enter a valid mobile number (10-15 digits).</div>
                        </div>

                        <div class="col-md-4 form-group">
                            <label for="birthday" class="form-label">Birthday</label>
                            <input 
                                type="date" 
                                class="form-control" 
                                id="birthday" 
                                name="birthday"
                                value="{{ isset($customer) && optional($customer->customerProfile)->birthday ? \Carbon\Carbon::parse($customer->customerProfile->birthday)->format('Y-m-d') : '' }}"
                                max="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                            />
                        </div>

                        <div class="col-md-4 form-group">
                            <label for="anniversary" class="form-label">Anniversary</label>
                            <input 
                                type="date" 
                                class="form-control" 
                                id="anniversary" 
                                name="anniversary"
                                value="{{ isset($customer) && optional($customer->customerProfile)->anniversary ? \Carbon\Carbon::parse($customer->customerProfile->anniversary)->format('Y-m-d') : '' }}"
                                max="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                            />
                        </div>

                    

                        <div class="col-md-4 form-group">
                            <label for="gender" class="form-label">Gender</label>
                            <select id="gender" name="gender" class="form-control">
                              <option value="">Select Gender</option>
                              <option value="male" {{ $customer->gender == 'male' ? 'selected' : '' }}>Male</option>
                              <option value="female" {{ $customer->gender == 'female' ? 'selected' : '' }}>Female</option>
                              <option value="other" {{ $customer->gender == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div class="col-md-4 form-group">
                          <label for="status" class="form-label">Status</label>
                          <select id="status" name="status" class="form-control">
                              <option value="">Select State</option>
                              <option value="active" {{ $customer->status == 'active' ? 'selected' : '' }}>Active</option>
                              <option value="pending" {{ $customer->status == 'pending' ? 'selected' : '' }}>Pending</option>
                              <option value="suspended" {{ $customer->status == 'suspended' ? 'selected' : '' }}>Suspended</option>
                          </select>
                        </div>
                        
                        <div class="col-12 form-group">
                            <label for="inputAddress" class="form-label">Address</label>
                            <input type="text" name="address1" class="form-control" id="inputAddress" placeholder="1234 Main St" value="{{ $customer->customerAddress->address_line1 }}" required>
                        </div>

                        <div class="col-12 form-group">
                            <label for="inputAddress2" class="form-label">Address 2</label>
                            <input type="text" name="address2" class="form-control" id="inputAddress2" placeholder="Apartment, studio, or floor" value="{{ $customer->customerAddress->address_line2 }}">
                        </div>

                        <div class="col-md-3 form-group">
                          <label for="zip-code" class="form-label">Zip</label>
                          <input type="text" id="zip-code" name="zip_code" class="form-control" placeholder="Enter Zip Code" value="{{ $customer->customerAddress->zip_code }}" required>
                        </div>

                        <div class="col-md-3 form-group">
                          <label for="inputCity" class="form-label">City</label>
                          <select id="city" name="city" class="form-control" required>
                            <option value="{{ $customer->customerAddress->city }}">{{ $cityName ?? 'Select City' }}</option>
                          </select>
                        </div>

                        <div class="col-md-3 form-group">
                          <label for="state" class="form-label">State</label>
                          <input type="text" id="state" name="state" class="form-control" placeholder="State" readonly value="{{ $customer->customerAddress->state }}" required>
                        </div>

                        <div class="col-md-3 form-group">
                          <label for="country" class="form-label">Country</label>
                          <input type="text" id="country" name="country" class="form-control" placeholder="Country" readonly value="{{ $customer->customerAddress->country }}" required>
                        </div>

                        <div class="col-md-3 form-group">
                          <label for="latitude" class="form-label">Latitude</label>
                          <input type="text" id="latitude" name="latitude" class="form-control" placeholder="Latitude" readonly value="{{ $customer->customerAddress->latitude }}" required>
                        </div>

                        <div class="col-md-3 form-group">
                          <label for="longitude" class="form-label">Longitude</label>
                          <input type="text" id="longitude" name="longitude" class="form-control" placeholder="Longitude" readonly value="{{ $customer->customerAddress->longitude }}" required>
                        </div>

                        <!--end::Col-->
                      </div>
                      <!--end::Row-->
                    </div>
                    <!--end::Body-->
                    <!--begin::Footer-->
                    <div class="card-footer">
                      <button class="btn btn-info" type="submit">Update Customer</button>
                    </div>
                    <!--end::Footer-->
                  </form>
                  <!--end::Form-->
                  <!--begin::JavaScript-->
                  <script>
                    // Example starter JavaScript for disabling form submissions if there are invalid fields
                    (() => {
                      'use strict';

                      // Fetch all the forms we want to apply custom Bootstrap validation styles to
                      const forms = document.querySelectorAll('.needs-validation');

                      // Loop over them and prevent submission
                      Array.from(forms).forEach((form) => {
                        form.addEventListener(
                          'submit',
                          (event) => {
                            if (!form.checkValidity()) {
                              event.preventDefault();
                              event.stopPropagation();
                            }

                            form.classList.add('was-validated');
                          },
                          false,
                        );
                      });
                    })();
                  </script>
                  <!--end::JavaScript-->
                </div>
                <!--end::Form Validation-->

            </div>
    <!--end::Row-->
    </div>
<!--end::Container-->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
    <script>
      var selectedState = @json($customer->customerAddress->state);
      var selectedCity = @json($customer->customerAddress->city);
      var selectedCountry = @json($customer->customerAddress->country);
    </script>
@endsection