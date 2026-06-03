@extends('admin.layouts.master') 
@section('title', 'Business Contest Details') 
@section('content')

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
                        <li class="breadcrumb-item"><a href="{{ route('contests.index') }}">Contests</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('contests.index') }}">Listing</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- Main content -->
    <section class="content">
        @include('admin.layouts.notifications')
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- /.card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ $content->name }} <span class="badge @if($content->status == 'open') badge-primary @elseif($content->status == 'close') badge-danger @else badge-success @endif">{{ $content->status }}</span> </h3>
                            <div class="card-tools">
                                <a href="{{ route('contests.create') }}"><i class="fa fa-plus-square" aria-hidden="true"></i>Create Contest</a>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 col-md-12 col-lg-8 order-2 order-md-1">
                                    <h4>Open Contest</h4>
                                    <div class="row">
                                        <div class="col-12 col-sm-3">
                                            <div class="info-box bg-light">
                                                <div class="info-box-content">
                                                    <span class="info-box-text text-center text-muted">Start Date</span>
                                                    <span class="info-box-number text-center text-muted mb-0">{{ $content->start_date }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-3">
                                            <div class="info-box bg-light">
                                                <div class="info-box-content">
                                                    <span class="info-box-text text-center text-muted">Start Time</span>
                                                    <span class="info-box-number text-center text-muted mb-0">{{ $content->start_time}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-3">
                                            <div class="info-box bg-light">
                                                <div class="info-box-content">
                                                    <span class="info-box-text text-center text-muted">End Date</span>
                                                    <span class="info-box-number text-center text-muted mb-0">{{ $content->end_date}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-3">
                                            <div class="info-box bg-light">
                                                <div class="info-box-content">
                                                    <span class="info-box-text text-center text-muted">End Time</span>
                                                    <span class="info-box-number text-center text-muted mb-0">{{ $content->end_time}}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h4>Close Contest</h4>
                                    <div class="row">
                                        <div class="col-12 col-sm-3">
                                            <div class="info-box bg-light">
                                                <div class="info-box-content">
                                                    <span class="info-box-text text-center text-muted">Start Date</span>
                                                    <span class="info-box-number text-center text-muted mb-0">{{ $content->close_start_date }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-3">
                                            <div class="info-box bg-light">
                                                <div class="info-box-content">
                                                    <span class="info-box-text text-center text-muted">Start Time</span>
                                                    <span class="info-box-number text-center text-muted mb-0">{{ $content->close_start_time}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-3">
                                            <div class="info-box bg-light">
                                                <div class="info-box-content">
                                                    <span class="info-box-text text-center text-muted">End Date</span>
                                                    <span class="info-box-number text-center text-muted mb-0">{{ $content->close_end_date}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-3">
                                            <div class="info-box bg-light">
                                                <div class="info-box-content">
                                                    <span class="info-box-text text-center text-muted">End Time</span>
                                                    <span class="info-box-number text-center text-muted mb-0">{{ $content->close_end_time}}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="col-12">
                                            <h4>Close Contest Description</h4>
                                            <div class="post">
                                                <p> {{ $content->close_description}} </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
                                    <h3 class="text-primary">Open Contest Description</h3>
                                    <p class="text-muted">{{ $content->description}}</p>
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
                                                    <li> Participant Points : {{ $content->winner_points}} </li>
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

                            <!-- @php $question = $content->questions[0] ?? null; @endphp 
                            @if($question)
                            <div class="timeline-item">

                                <h3 class="timeline-header">
                                    Type of Question: {{ $question['question_type'] === 'text_answer' ? 'Custom' : 'MCQ' }}
                                </h3>

                                <h3 class="timeline-header">
                                    Question: {{ $question['question'] ?? 'N/A' }}
                                </h3>

                                <h3 class="timeline-header">
                                    Options for Answers
                                </h3>
                                <ul>
                                    @php $options = $content->questions[0]['options'] ?? null; @endphp 
                                    @foreach($options as $option)
                                        <li>{{ $option->option_text }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif -->

                            
                            @php 
                                $question = @$content['questions'][0]['question'];
                                $questionType = @$content['questions'][0]['question_type'];
                                $submittedAnswers = @$content['questions'][0]['answers'];
                            @endphp
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
                                                <h5>Question</h5>   : {{ $question }}
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
                                            <!-- <div class="timeline-footer">
                                                <a class="btn btn-warning btn-sm">Comment</a>
                                            </div> -->
                                        </div>
                                    </div>
                                    @endforeach
                                    <!-- END timeline item -->
                                    </div>
                                </div>
                                <!-- /.col -->
                            </div>
                        
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
@endsection