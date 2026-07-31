@extends('admin.layouts.master')
@section('title', 'Business Create Offer')
@section('content')


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Create Offer</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.business.offer.index') }}">Offer</a></li>
                        <li class="breadcrumb-item active">Create Offer</li>
                    </ol>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
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
                    <!-- <div class="card-header"><div class="card-title">Create Offer</div></div> -->
                    <!--end::Header-->
                    <!--begin::Form-->
                    <form id="offerForm" method="POST" action="{{ route('admin.business.offer.store') }}" enctype="multipart/form-data" class="needs-validation" novalidate>
                    @csrf    
                    <!--begin::Body-->
                        <div class="card-body">
                        <div class="row g-3">
                             <div class="col-md-4 mb-3">
                                <div class="col-md-4 p-0">
                                    <div id="image-preview" class="mt-3"></div>
                                </div>
                                <label for="banner" class="form-label">Upload Banner</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input"  id="banner" name="banner" accept="image/*">
                                    <label class="custom-file-label" for="customFile">Upload Banner</label>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">

                            <div class="col-md-4 form-group">
                                <label for="status" class="form-label">Business</label>
                                <select id="business_id" name="business_id" class="form-control select2bs4" required>
                                    <option value="">Select Business</option>
                                    @foreach(\App\Models\User::where('role', 'business')->get() as $business)
                                        <option value="{{ $business->id }}"
                                            {{ old('business_id') == $business->id ? 'selected' : '' }}>
                                            {{ $business->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 form-group">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" required />
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 form-group">
                                <label for="campaign_start_date" class="form-label">Campaign Start Date</label>
                                <input type="date" class="form-control" id="campaign_start_date" name="campaign_start_date" value="{{ old('campaign_start_date') }}" required placeholder="2025-04-24" min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="campaign_start_time" class="form-label">Campaign Start Time</label>
                                <input type="time" class="form-control" id="campaign_start_time" name="campaign_start_time" value="{{ old('campaign_start_time') }}" required placeholder="10:00:00"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="campaign_end_date" class="form-label">Campaign End Date</label>
                                <input type="date" class="form-control" id="campaign_end_date" name="campaign_end_date" value="{{ old('campaign_end_date') }}" required placeholder="2025-04-26" min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="campaign_end_time" class="form-label">Campaign End Time</label>
                                <input type="time" class="form-control" id="campaign_end_time" name="campaign_end_time" value="{{ old('campaign_end_time') }}" required placeholder="20:00:00"/>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-2 form-group">
                                <label for="min_order_value" class="form-label">Min Order Value</label>
                                <input type="number" class="form-control" id="min_order_value" name="min_order_value" value="{{ old('min_order_value') }}" required min="1" max="99999" />
                            </div>
                        
                            <div class="col-md-2 form-group">
                                <label for="discount_percentage" class="form-label">Discount Percentage</label>
                                <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" value="{{ old('discount_percentage') }}" required min="1" max="100" />
                            </div>

                            <div class="col-md-2 form-group">
                                <label for="discount_cap" class="form-label">Discount Cap</label>
                                <input type="number" class="form-control" id="discount_cap" name="discount_cap" value="{{ old('discount_cap') }}" required min="1" max="99999" />
                            </div>

                            <div class="col-md-2 form-group">
                                <label for="applicable_users" class="form-label">Discount Applicable</label>
                                <select id="applicable_users" name="applicable_users" class="form-control" required>
                                    <option value="">Select group</option>
                                        <option value="new" {{ old('applicable_users') == 'new' ? 'selected' : '' }}>New</option>
                                        <option value="regular" {{ old('applicable_users') == 'regular' ? 'selected' : '' }}>Regular</option>
                                        <option value="daily" {{ old('applicable_users') == 'daily' ? 'selected' : '' }}>Frequent</option>
                                </select>
                            </div>

                            <div class="col-md-2 form-group">
                                <label for="offer_time" class="form-label">Select Time</label>
                                <select id="offer_time" name="offer_time" class="form-control" required>
                                <option value="">Select Time</option>
                                    <option value="all_day" {{ old('offer_time') == 'all_day' ? 'selected' : '' }}>All Day</option>
                                    <option value="morning_only" {{ old('offer_time') == 'morning_only' ? 'selected' : '' }}>Morning Only</option>
                                    <option value="evening_only" {{ old('offer_time') == 'evening_only' ? 'selected' : '' }}>Evening Only</option>
                                </select>
                            </div>

                            <div class="col-md-2 form-group">
                                <label for="applicable_days" class="form-label">Applicable Days</label>
                                <select id="applicable_days" name="applicable_days" class="form-control" required>
                                <option value="">Select Offer</option>
                                <option value="Entire Week" {{ old('applicable_days') == 'Entire Week' ? 'selected' : '' }}>Entire Week</option>
                                <option value="Mon-Thu" {{ old('applicable_days') == 'Mon-Thu' ? 'selected' : '' }}>Mon-Thu</option>
                                <option value="Fri-Sun" {{ old('applicable_days') == 'Fri-Sun' ? 'selected' : '' }}>Fri-Sun</option>
                                </select>
                            </div>                           
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12 form-group">
                                <label for="terms_and_conditions" class="form-label">Terms and Conditions</label>
                                <input type="text" class="form-control" id="terms_and_conditions" name="terms_and_conditions" value="{{ old('terms_and_conditions') }}" required />
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12 form-group">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter offer description...">{{ old('description') }}</textarea>
                            </div>
                        </div>


                        <!--end::Row-->
                        </div>
                        <!--end::Body-->
                        <!--begin::Footer-->
                        <div class="card-footer">
                        <button class="btn btn-info" type="submit">Create Offer</button>
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
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
 <script>
    document.querySelectorAll('#banner').forEach(function(element) {
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
</script>
@endsection