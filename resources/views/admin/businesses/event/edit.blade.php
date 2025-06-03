@extends('admin.layouts.master')
@section('title', 'Business Edit Event')
@section('content')


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Event</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('events.index') }}">Event</a></li>
                        <li class="breadcrumb-item active">Edit</li>
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
                    <!-- <div class="card-header"><div class="card-title">Create Event</div></div> -->
                    <!--end::Header-->
                    <!--begin::Form-->
                    <form id="eventForm" method="POST" action="{{ route('events.store') }}" enctype="multipart/form-data" class="needs-validation" novalidate>
                    @csrf    
                    <!--begin::Body-->
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <img src="{{ $event->event_banner }}" alt="" class="mt-3 img-fluid contestImage border border-dark rounded">
                                </div>
                            </div>
                            <div class="row g-3">
                                 <div class="col-md-3 mb-3">
                                <label for="event_banner" class="form-label">Upload Banner</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="event_banner" name="event_banner" accept="image/*">
                                    <label class="custom-file-label" for="customFile">Upload Banner</label>
                                </div>
                            </div>
                            </div>

                        <div class="row g-3">
                           

                            <div class="col-md-3 form-group">
                                <label for="status" class="form-label">Business</label>
                                <select id="business_id" name="business_id" class="form-control select2bs4" required>
                                    @foreach(\App\Models\User::where('role', 'business')->get() as $business)
                                        <option value="{{ $business->id }}">{{ $business->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="event_name" class="form-label">Event Name</label>
                                <input type="text" class="form-control" id="event_name" name="event_name" required />
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-control" required>
                                <option value="">Select Status</option>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 form-group">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required placeholder="2025-04-24" min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" />
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required placeholder="10:00:00"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required placeholder="2025-04-26" min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" />
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required placeholder="20:00:00"/>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-2 form-group">
                                <label for="capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" required min="1" max="99999" />
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="venue_name" class="form-label">Venue Name</label>
                                <input type="text" class="form-control" id="venue_name" name="venue_name" required />
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="venue_location" class="form-label">Venue Location</label>
                                <input type="text" class="form-control" id="venue_location" name="venue_location" required />
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-12 form-group">
                                <label for="event_description" class="form-label">Description</label>
                                <textarea class="form-control" id="event_description" name="event_description" rows="4" placeholder="Enter Event description..."></textarea>
                            </div>
                        </div>


                        <!--end::Row-->
                        </div>
                        <!--end::Body-->
                        <!--begin::Footer-->
                        <div class="card-footer">
                            <button class="btn btn-info" type="submit">Create Event</button>
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
@endsection