@extends('admin.layouts.master')
@section('title', 'Business Service')
@section('content')

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Create Service</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('services.index') }}">Service</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('services.index') }}">Listing</a></li>
                        <li class="breadcrumb-item active">Create Service</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
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
                @include('admin.layouts.notifications')
                <div class="container-fluid">
                    <div class="card card-info card-outline mb-4">
                        <!-- <div class="card-header"><div class="card-title">Create Service</div></div> -->
                        <form id="serviceForm" method="POST" action="{{ route('services.store') }}" enctype="multipart/form-data" class="needs-validation">
                            @csrf
                            <div class="card-body">
                                <div class="row g-3">
                                <!-- images upload -->
                                    <div class="col-md-4">
                                        <div id="imagePreview" class="mt-3 d-flex flex-wrap justify-content-center" style="float: left;"></div>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="form-group col-md-4">
                                        <label for="photo" class="form-label">Upload Images</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="menuPhoto" name="photo" accept="image/*">
                                            <label class="custom-file-label" for="customFile">Upload Images</label>
                                        </div>
                                    </div>

                                    
                                </div>
                                <div class="row g-3">
                                    <div class="form-group col-md-4">
                                        <label for="business_id" class="form-label">Business</label>
                                        <select id="business_id" name="business_id" class="form-control select2bs4" required>
                                            @foreach(\App\Models\User::where('role', 'business')->get() as $business)
                                                <option value="{{ $business->id }}">{{ $business->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="name">Service Name</label>
                                        <input type="text" name="name" class="form-control" id="name" placeholder="Enter Name" required>
                                    </div>

                                     <div class="form-group col-md-4">
                                        <label for="price">Price</label>
                                        <input type="number" name="price" class="form-control" id="price" min="1" max="99999" required>
                                    </div>

                                </div>

                                <div class="row g-3">
                                    <div class="form-group col-md-4">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select id="category_id" name="category_id" class="form-control" required>
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="duration">Duration (HH:MM:SS)</label>
                                        <input type="time" name="duration" class="form-control" id="duration" required>
                                    </div>
                                </div>

                                

                                <div class="row g-3">
                                    <div class="col-md-12 form-group">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter menu description..."></textarea>
                                    </div>
                                </div>

                                <hr>
                                <h5 class="mt-4 mb-3">Weekly Availability</h5>

                                @php
                                    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                                @endphp

                                @foreach($days as $index => $day)
                                    <div class="row g-3 align-items-end">
                                        <div class="form-group col-md-2">
                                            <label>{{ $day }}</label>
                                            <input type="hidden" name="availability[{{ $index }}][day]" value="{{ $day }}">
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" name="availability[{{ $index }}][is_open]" value="1" checked>
                                                <label class="form-check-label">Open</label>
                                            </div>
                                        </div>

                                        <div class="form-group col-md-5">
                                            <label>Start Time</label>
                                            <input type="time" name="availability[{{ $index }}][start_time]" class="form-control" required>
                                        </div>

                                        <div class="form-group col-md-5">
                                            <label>End Time</label>
                                            <input type="time" name="availability[{{ $index }}][end_time]" class="form-control" required>
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Create Service</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>
@endsection