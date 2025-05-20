@extends('admin.layouts.master')
@section('title', 'Business Create Content')
@section('content')


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Create Content</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('contests.index') }}">Contest</a></li>
                        <li class="breadcrumb-item active">Create Contest</li>
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
                    <!-- <div class="card-header"><div class="card-title">Create Content</div></div> -->
                    <!--end::Header-->
                    <!--begin::Form-->
                    <form id="contestForm" method="POST" action="{{ route('contests.store') }}" enctype="multipart/form-data" class="needs-validation" novalidate>
                    @csrf    
                    <!--begin::Body-->
                        <div class="card-body">

                        <div class="row g-3">
                            <div class="col-md-3 mb-3">
                                <label for="banner_image" class="form-label">Upload Banner</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input"  id="banner_image" name="banner_image" accept="image/*">
                                        <label class="custom-file-label" for="customFile">Upload Banner</label>
                                    </div>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="status" class="form-label">Business</label>
                                <select id="business_id" name="business_id" class="form-control" required >
                                    @foreach(\App\Models\User::where('role', 'business')->get() as $business)
                                        <option value="{{ $business->id }}">{{ $business->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="name" class="form-label">Contest Name</label>
                                <input type="text" class="form-control" id="name" name="name" required />
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-control" required>
                                <option value="open">Open</option>
                                <option value="close">Close</option>
                                <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 form-group">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input 
                                    type="date" 
                                    class="form-control" 
                                    id="start_date" 
                                    name="start_date" 
                                    required 
                                    placeholder="2025-04-24"
                                    min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                                />
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required placeholder="10:00:00"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required placeholder="2025-04-26" min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required placeholder="20:00:00"/>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 form-group">
                                <label for="max_participants" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="max_participants" name="max_participants" required min="1" max="99999" />
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12 form-group">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter Contest description..."></textarea>
                            </div>
                        </div>



                        <div class="row g-3">
                            <div class="col-md-3 form-group">
                                <label for="close_start_date" class="form-label">Close Contest Start Date</label>
                                <input type="date" class="form-control" id="close_start_date" name="close_start_date" required placeholder="2025-04-24" min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="close_start_time" class="form-label">Close Contest Start Time</label>
                                <input type="time" class="form-control" id="close_start_time" name="close_start_time" required placeholder="10:00:00"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="close_end_date" class="form-label">Close Contest End Date</label>
                                <input type="date" class="form-control" id="close_end_date" name="close_end_date" required placeholder="2025-04-26" min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="close_end_time" class="form-label">End Contest Time</label>
                                <input type="time" class="form-control" id="close_end_time" name="close_end_time" required placeholder="20:00:00"/>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12 form-group">
                                <label for="close_description" class="form-label">Close Description</label>
                                <textarea class="form-control" id="close_description" name="close_description" rows="4" placeholder="Enter Close Contest description..."></textarea>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 form-group">
                                <label for="question_type" class="form-label">Question Type</label>
                                <select id="question_type" name="question_type" class="form-control" required>
                                    <option value="">Please select question type</option>
                                    <option value="text_answer">Customer can write their own answers</option>
                                    <option value="multiple_choice">Multiple Choice Questions</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Question Field -->
                            <div class="col-md-12 form-group question-field-wrapper" style="display: none;">
                                <label for="question_field" class="form-label">Question Field</label>
                                <input type="text" class="form-control" id="question_field" name="question_field" placeholder="Example: “What’s our best-selling coffee?”" />
                            </div>

                            <!-- MCQ Options -->
                            <div class="col-md-12 form-group option-group-wrapper" style="display: none;">
                                <div id="option_container">
                                    <div class="col-md-12 form-group option-item">
                                        <label class="form-label">Option</label>
                                        <input type="text" class="form-control" name="options[]" placeholder="Enter an option" />
                                    </div>
                                </div>
                                <div class="col-md-12 form-group">
                                    <button type="button" id="add_option" class="btn btn-sm btn-primary float-end">+ Add Option</button>
                                </div>
                            </div>
                        </div>


                        <div class="row g-3">
                            <div class="col-md-3 form-group">
                                <label for="reward_type" class="form-label">Reward Type</label>
                                <select id="reward_type" name="reward_type" class="form-control" required>
                                    <option value="">Please select Reward Type</option>
                                    <option value="points">Point</option>
                                    <option value="discount">Discount</option>
                                    <option value="free_item">Free Item</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 points_div" style="display: none;">
                            <div class="col-md-4 form-group">
                                <label for="winner_points" class="form-label">Winner Points</label>
                                <input type="number" class="form-control" id="winner_points" name="winner_points" placeholder="50" />
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="participant_points" class="form-label">Participant Points</label>
                                <input type="number" class="form-control" id="participant_points" name="participant_points" placeholder="50" />
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="voter_points" class="form-label">Voter Points</label>
                                <input type="number" class="form-control" id="voter_points" name="voter_points" placeholder="50" />
                            </div>
                        </div>

                        <div class="row g-3 discount_div" style="display: none;">
                            <div class="col-md-4 form-group">
                                <label for="winner_discount" class="form-label">Winner Discount</label>
                                <input type="text" class="form-control" id="winner_discount" name="winner_discount" placeholder="20%" />
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="participant_discount" class="form-label">Participant Discount</label>
                                <input type="text" class="form-control" id="participant_discount" name="participant_discount" placeholder="20%" />
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="voter_discount" class="form-label">Voter Discount</label>
                                <input type="text" class="form-control" id="voter_discount" name="voter_discount" placeholder="20%" />
                            </div>
                        </div>

                        <div class="row g-3 freeItem_div" style="display: none;">
                            <div class="col-md-4 form-group">
                                <label for="winner_free_item" class="form-label">Winner Free Item</label>
                                <input type="text" class="form-control" id="winner_free_item" name="winner_free_item" placeholder="Free Coffee" />
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="participant_free_item" class="form-label">Participant Free Item</label>
                                <input type="text" class="form-control" id="participant_free_item" name="participant_free_item" placeholder="Free Coffee" />
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="voter_free_item" class="form-label">Voter Free Item</label>
                                <input type="text" class="form-control" id="voter_free_item" name="voter_free_item" placeholder="Free Coffee" />
                            </div>
                        </div>
                        


                        <!--end::Row-->
                        </div>
                        <!--end::Body-->
                        <!--begin::Footer-->
                        <div class="card-footer">
                            <button class="btn btn-info" type="submit">Create Contest</button>
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