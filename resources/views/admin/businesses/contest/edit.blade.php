@extends('admin.layouts.master')
@section('title', 'Business Edit Content')
@section('content')


@php 
    $question = @$content['questions'][0]['question'];
    $questionType = @$content['questions'][0]['question_type'];
    $submittedAnswers = @$content['questions'][0]['answers'];
@endphp
                        
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <!-- <h1>Business</h1> -->
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('contests.edit', $content->id) }}">Edit Contest</a></li>
                        <li class="breadcrumb-item active">Contests</li>
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
                    <div class="card-header"><div class="card-title">Edit Content</div></div>
                    <!--end::Header-->
                    <!--begin::Form-->
                    <form id="contestEditForm" method="post" action="{{ route('contests.update', $content->id) }}" enctype="multipart/form-data" class="needs-validation">
                    @csrf
                    @method('PUT')    
                    <input type="hidden" name="question_type" value="{{ $questionType }}">
                    <input type="hidden" name="reward_type" value="{{ $content->reward_type }}">
                    <!--begin::Body-->
                        <div class="card-body">

                        <div class="row g-3">
                            <div class="col-md-12">
                                <img src="{{ $content->banner_image }}" alt="">
                                <!-- <label for="banner_image" class="form-label">Banner Image</label> -->
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="banner_image" class="form-label">Banner Image</label>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 mb-3">
                                <label for="banner_image" class="form-label">Upload Banner</label>
                                <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/*">
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="status" class="form-label">Business</label>
                                <select id="business_id" name="business_id" class="form-control select2" required>
                                    @foreach(\App\Models\User::where('role', 'business')->get() as $business)
                                        <option value="{{ $business->id }}" {{ $business->id == $content->business_id ? 'selected' : '' }}>{{ $business->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="name" class="form-label">Contest Name</label>
                                <input type="text" class="form-control" id="name" name="name" required value="{{ $content->name }}"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-control" required>
                                <option value="open" {{ $content->status == "open" ? 'selected' : '' }} >Open</option>
                                <option value="close" {{ $content->status == "close" ? 'selected' : '' }} >Close</option>
                                <option value="completed" {{ $content->status == "completed" ? 'selected' : '' }} >Completed</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 form-group">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required placeholder="2025-04-24" value="{{ $content->start_date }}"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required placeholder="10:00:00" value="{{ $content->start_time }}"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required placeholder="2025-04-26" value="{{ $content->end_date }}"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required placeholder="20:00:00" value="{{ $content->end_time }}"/>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 form-group">
                                <label for="max_participants" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="max_participants" name="max_participants" required min="1" max="99999" value="{{ $content->max_participants }}"/>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12 form-group">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter Contest description...">{{ $content->description }}</textarea>
                            </div>
                        </div>



                        <div class="row g-3">
                            <div class="col-md-3 form-group">
                                <label for="close_start_date" class="form-label">Close Contest Start Date</label>
                                <input type="date" class="form-control" id="close_start_date" name="close_start_date" required placeholder="2025-04-24" value="{{ $content->close_start_date }}"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="close_start_time" class="form-label">Close Contest Start Time</label>
                                <input type="time" class="form-control" id="close_start_time" name="close_start_time" required placeholder="10:00:00" value="{{ $content->close_start_time }}"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="close_end_date" class="form-label">Close Contest End Date</label>
                                <input type="date" class="form-control" id="close_end_date" name="close_end_date" required placeholder="2025-04-26" value="{{ $content->close_end_date }}"/>
                            </div>

                            <div class="col-md-3 form-group">
                                <label for="close_end_time" class="form-label">End Contest Time</label>
                                <input type="time" class="form-control" id="close_end_time" name="close_end_time" required placeholder="20:00:00" value="{{ $content->close_end_time }}"/>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12 form-group">
                                <label for="close_description" class="form-label">Close Description</label>
                                <textarea class="form-control" id="close_description" name="close_description" rows="4" placeholder="Enter Close Contest description...">{{ $content->close_description }}</textarea>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 form-group">
                                <label for="question_type" class="form-label">Question Type</label>
                                <select id="question_type" name="question_type" class="form-control" disabled>
                                    <option value="">Please select question type</option>
                                    <option value="text_answer" {{ $questionType == 'text_answer' ? 'selected' : '' }} >Customer can write their own answers</option>
                                    <option value="multiple_choice" {{ $questionType == 'multiple_choice' ? 'selected' : '' }}>Multiple Choice Questions</option>
                                </select>
                            </div>
                        </div>
<?php
    // foreach ($submittedAnswers as $key => $answer) {
    //     echo"<pre>"; print_r($answer['participant']['user']['name']); 
    //     echo"<pre>"; print_r($answer->created_at->diffForHumans());
    // }
    // die;
    // echo"<pre>"; print_r($content['questions'][0]['question']);  die;
?>
                        <!-- Timelime example  -->
                        <div class="row">
                            <div class="col-md-12">

                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-text-width"></i>
                                            Question : {{ $question }}
                                        </h3>
                                    </div>
                                </div>

                                <!-- The time line -->
                                <div class="timeline">
                                    <!-- timeline time label -->
                                    <div class="time-label">
                                        <span class="bg-primary">Submitted Answers</span>
                                    </div>
                                <!-- /.timeline-label -->
                                
                                <!-- timeline item -->
                                @foreach ($submittedAnswers as $key => $answer)
                                <div>
                                    <i class="fas fa-comments bg-yellow"></i>
                                    <div class="timeline-item">
                                        <span class="time"><i class="fas fa-clock"></i> {{ @$answer->created_at->diffForHumans() }}</span>
                                        <h3 class="timeline-header"><a href="#">{{ @$answer['participant']['user']['name'] }}</a> commented on your post</h3>
                                        <div class="timeline-body">
                                            {{ @$answer->answer }}
                                        </div>
                                        <div class="timeline-footer">
                                            <button class="btn btn-danger btn-sm delete-answer" data-id="{{ $answer->id }}" data-url="{{ route('contest.answers.destroy', $answer->id) }}">Delete</button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                <!-- END timeline item -->
                                </div>
                            </div>
                            <!-- /.col -->
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 form-group">
                                <label for="reward_type" class="form-label">Reward Type</label>
                                <select id="reward_type" class="form-control" disabled>
                                    <option value="">Please select Reward Type</option>
                                    <option value="points" {{ $content->reward_type == 'points' ? 'selected' : '' }}>Point</option>
                                    <option value="discount" {{ $content->reward_type == 'discount' ? 'selected' : '' }}>Discount</option>
                                    <option value="free_item" {{ $content->reward_type == 'free_item' ? 'selected' : '' }}>Free Item</option>
                                </select>
                            <!-- </div>

                            <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
                                     -->
                                    <br> @if($content->reward_type == "points")
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="position-relative p-3 bg-gray" style="height: 180px">
                                                <div class="ribbon-wrapper">
                                                    <div class="ribbon bg-primary">Points</div>
                                                </div>
                                                Points
                                                <br>
                                                <ul>
                                                    <li> Winner Points : {{ $content->winner_points}} </li>
                                                    <li> Participant Points : {{ $content->participant_points }} </li>
                                                    <li> Voter Points : {{ $content->voter_points}} </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @elseif ($content->reward_type == "discount")
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="position-relative p-3 bg-gray" style="height: 180px">
                                                <div class="ribbon-wrapper">
                                                    <div class="ribbon bg-primary">Discount</div>
                                                </div>
                                                Discount
                                                <br>
                                                <ul>
                                                    <li> Winner Discount : {{ $content->winner_discount}} </li>
                                                    <li> Participant Discount : {{ $content->participant_discount}} </li>
                                                    <li> Voter Discount : {{ $content->voter_discount}} </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @elseif ($content->reward_type == "free_item")
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="position-relative p-3 bg-gray" style="height: 180px">
                                                <div class="ribbon-wrapper">
                                                    <div class="ribbon bg-primary">Free Item</div>
                                                </div>
                                                Free Item
                                                <br>
                                                <ul>
                                                    <li> Winner Free Item : {{ $content->winner_free_item}} </li>
                                                    <li> Participant Free Item : {{ $content->participant_free_item}} </li>
                                                    <li> Voter Free Item : {{ $content->voter_free_item}} </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                        </div>
                        <!--end::Row-->
                        </div>
                        <!--end::Body-->
                        <!--begin::Footer-->
                        <div class="card-footer">
                            <button class="btn btn-info" type="submit">Update Contest</button>
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