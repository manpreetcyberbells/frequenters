@extends('admin.layouts.master')
@section('title', 'Business Edit')
@section('content')

 <!-- Content Wrapper. Contains page content -->
 <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Business</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
              <li class="breadcrumb-item active">Business Edit</li>
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
                  <div class="card-header"><div class="card-title">Business Edit</div></div>
                  <!--end::Header-->
                  <!--begin::Form-->
                  <form action="{{route('businesses.update', $business->id)}}" method="post" class="needs-validation" novalidate enctype="multipart/form-data">
                  @csrf
                  @method('PUT')
                    <!--begin::Body-->
                    <div class="card-body">
                      <!--begin::Row-->
                      <div class="row g-3">
                        <!--begin::Col-->
                        <div class="col-md-4 form-group">
                          <label for="name" class="form-label">Name</label>
                          <input
                            type="text"
                            class="form-control"
                            id="name"
                            name="name"
                            value="{{ $business->name }}"
                            required
                            placeholder="Enter Business name"
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
                              value="{{ $business->email }}"
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
                            <label for="mobileNumber" class="form-label">Mobile</label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                id="mobileNumber" 
                                name="mobile" 
                                pattern="^\d{10,15}$" 
                                placeholder="Enter your mobile number"
                                value="{{ $business->phone }}"
                                required 
                                placeholder="Enter valid mobile number"
                            />
                            <div class="invalid-feedback">Please enter a valid mobile number (10-15 digits).</div>
                        </div>

                        <div class="col-md-4 form-group">
                          <label for="website" class="form-label">Website <small class="text-muted">(e.g., https://www.example.com)</small></label>
                          <input
                            type="text"
                            class="form-control"
                            id="website"
                            name="website"
                            value="{{ $business->businessProfile->website }}"
                            placeholder="https://www.example.com/"
                          />
                          @error('website')
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>


                        <div class="col-md-4 form-group">
                          <label for="role" class="form-label">Role</label>
                          <input
                            type="text"
                            class="form-control"
                            id="role"
                            name="role"
                            value="{{ $business->role}}"
                            readonly
                          />
                        </div>

                        <div class="col-md-4 form-group">
                          <label for="status" class="form-label">Change Status</label>
                            <select id="status" name="status" class="form-control" required>
                              <option value="">Select status</option>
                              <option value="active" {{ $business->status == 'active' ? 'selected' : '' }}>Active</option>
                              <option value="pending" {{ $business->status == 'pending' ? 'selected' : '' }}>Pending</option>
                              <option value="suspended" {{ $business->status == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                        </div>

                        <div class="col-md-4 form-group">
                          <label for="category" class="form-label">Category</label>
                          <select id="parent-category" name="category" class="form-control" required>
                            <option value="">Select Parent Category</option>
                          </select>
                        </div>

                        <div class="col-md-4 form-group">
                          <label for="category" class="form-label">Child Category</label>
                          <select id="child-category" name="child_category" class="form-control" required>
                            <option value="">Select Child Category</option>
                          </select>
                        </div>

                        <div class="col-md-4 form-group">
                          <label for="website" class="form-label">Points Per Checkin</label>
                          <input
                            type="number"
                            class="form-control"
                            id="points_per_checkin"
                            name="points_per_checkin"
                            required
                            min="1"
                            max="50"
                            value="{{ $business->businessProfile->points_per_checkin }}"
                            placeholder="points Per Checkin"
                          />
                        </div>

                        <div class="col-md-4 form-group">
                          <label for="owner_name" class="form-label">Owner Name</label>
                          <input type="text" class="form-control" id="owner_name" name="owner_name" required placeholder="Enter Owner Name" value="{{ $business->businessProfile->owner_name }}" />
                        </div>
                        <div class="col-md-4 form-group">
                          <label for="owner_email" class="form-label">Owner Email</label>
                          <input type="email" class="form-control" id="owner_email" name="owner_email" required placeholder="Enter Owner Email address" value="{{ $business->businessProfile->owner_email }}" />
                        </div>
                        <div class="col-md-4 form-group">
                          <label for="owner_phone" class="form-label">Owner Phone</label>
                          <input type="tel" class="form-control" id="owner_phone" name="owner_phone" pattern="^\d{10,15}$"  required placeholder="Enter valid Owner Phone number" value="{{ $business->businessProfile->owner_phone }}" />
                        </div>

                        <!--end::Col-->

                        <div class="col-md-6 mb-3 form-group">
                            <label for="images" class="form-label">Upload Images</label>
                            <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                            <div id="image-preview" class="mt-3"></div>
                        </div>

                             <!-- uploaded images -->
                             <div class="col-12 form-group text-center">
                                  @if(!empty($business['businessImage']))
                                  @foreach($business['businessImage'] as $image)
                                   
                                      <img src="{{ $image->photo }}" 
                                          alt="Business Image" 
                                          width="100" 
                                          height="100"
                                          class="preview-image border border-dark rounded" 
                                          data-src="{{ $image->photo }}"
                                          style="cursor: pointer;">
                               
                                  @endforeach
                                @else
                                  <p>No images available</p>
                                @endif

                             
                               <div id="imagePreviewModal" style="display: none; position: fixed; top: 4rem; bottom: 4rem; width: 75%; height: 100%; background: #383838cc; align-items: center; justify-content: center; z-index: 1;">
                                    <div class="close text-white p-2 cursor-pointer"><i class="fas fa-window-close cursor-pointer"></i></div>
                                    <img id="previewImage" src="" style="text-align: center;">
                                </div>
                             </div>
                              <!-- test -->
                             <!-- col-12 -->
                              <!-- description -->
                              <div class="col-md-12 mb-3 form-group">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3">{{ $business->businessProfile->description }}</textarea>
                              </div>
                               <!-- description -->
                        <!--end::Col-->
                        
                        <div class="col-12 form-group">
                            <label for="inputAddress" class="form-label">Address</label>
                            <input type="text" name="address1" class="form-control" id="inputAddress" placeholder="1234 Main St" value="{{ $business->businessAddress->address_line1 }}" required>
                        </div>
                        <div class="col-12 form-group">
                            <label for="inputAddress2" class="form-label">Address 2</label>
                            <input type="text" name="address2" class="form-control" id="inputAddress2" placeholder="Apartment, studio, or floor" value="{{ $business->businessAddress->address_line2 }}">
                        </div>

                        <div class="col-md-3 form-group">
                          <label for="zip-code" class="form-label">Zip</label>
                          <input type="text" id="zip-code" name="zip_code" class="form-control" placeholder="Enter Zip Code" value="{{ $business->businessAddress->zip_code }}" required>
                        </div>

                        <div class="col-md-3 form-group">
                          <label for="inputCity" class="form-label">City</label>
                          <select id="city" name="city" class="form-control" required>
                            <option value="{{ $business->businessAddress->city }}">{{ $cityName ?? 'Select City' }}</option>
                          </select>
                        </div>

                        <div class="col-md-3 form-group">
                          <label for="state" class="form-label">State</label>
                          <input type="text" id="state" name="state" class="form-control" placeholder="State" readonly value="{{ $business->businessAddress->state }}" required>
                        </div>

                        <div class="col-md-3 form-group">
                          <label for="country" class="form-label">Country</label>
                          <input type="text" id="country" name="country" class="form-control" placeholder="Country" readonly value="{{ $business->businessAddress->country }}" required>
                        </div>

                        <div class="col-md-3 form-group">
                          <label for="latitude" class="form-label">Latitude</label>
                          <input type="text" id="latitude" name="latitude" class="form-control" placeholder="Latitude" readonly value="{{ $business->businessAddress->latitude }}" required>
                        </div>

                        <div class="col-md-3 form-group">
                          <label for="longitude" class="form-label">Longitude</label>
                          <input type="text" id="longitude" name="longitude" class="form-control" placeholder="Longitude" readonly value="{{ $business->businessAddress->longitude }}" required>
                        </div>

                        <!--end::Col-->
                      </div>
                      <!--end::Row-->
                      </br>
                      <!-- Business Hours Table -->
                      <div id="business-hours-section">
                        <h4>Business Hours</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th class="text-center">Open Time</th>
                                    <th class="text-center">Close Time</th>
                                    <th class="text-center">Closed?</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                @endphp
                                @foreach($days as $key => $day)
                                @php
                                    // Find existing business hour entry for this day
                                    $existingHour = $business->businessHours->where('day_of_week', strtolower($day))->first();
                                    $isClosed = $existingHour ? $existingHour->is_closed : 0;
                                @endphp
                                <tr>
                                    <td>{{ $day }}</td>
                                    <td>
                                        <input type="time" name="business_hours[{{ $key }}][open_time]" class="form-control open-time"
                                            value="{{ $isClosed ? '' : ($existingHour ? $existingHour->open_time : '') }}"
                                            {{ $isClosed ? 'disabled' : '' }}>
                                    </td>
                                    <td>
                                        <input type="time" name="business_hours[{{ $key }}][close_time]" class="form-control close-time"
                                            value="{{ $isClosed ? '' : ($existingHour ? $existingHour->close_time : '') }}"
                                            {{ $isClosed ? 'disabled' : '' }}>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="business_hours[{{ $key }}][is_closed]" class="closed-checkbox"
                                            value="1" {{ $isClosed ? 'checked' : '' }}>
                                    </td>
                                    <input type="hidden" name="business_hours[{{ $key }}][day_of_week]" value="{{ strtolower($day) }}">
                                </tr>
                                @endforeach
                            </tbody>
                          </table>
                        <button type="button" id="copy-monday" class="btn btn-success">Copy Monday’s Hours to All</button>
                      </div>
                    <!-- End Business Hours Table -->


                    </div>
                    <!--end::Body-->
                    <!--begin::Footer-->
                    <div class="card-footer">
                      <button class="btn btn-info" ype="submit">Update Business</button>
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
                     t         event.preventDefault();
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
      var selectedState = @json($business->businessAddress->state);
      var selectedCity = @json($business->businessAddress->city);
      var selectedCountry = @json($business->businessAddress->country);

      var selectedParent = "{{ $business->businessProfile->category_id }}";
      var selectedChild = "{{ $business->businessProfile->child_category_id }}";

    </script>
@endsection