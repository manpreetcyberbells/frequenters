@extends('admin.layouts.master')
@section('title', 'Business Menu')
@section('content')

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Create Menu</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('menus.index') }}">Menu Listing</a></li>
                        <li class="breadcrumb-item active">Create Menu</li>
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
                <div class="container-fluid">
                    <div class="card card-info card-outline mb-4">
                    <!-- <div class="card-header"><div class="card-title">Create Menu</div></div> -->
                    <form id="menuForm" method="POST" action="{{ route('menus.store') }}" enctype="multipart/form-data" class="needs-validation">
                    @csrf
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="form-group col-md-6">
                                    <label for="status" class="form-label">Business</label>
                                    <select id="business_id" name="business_id" class="form-control" required>
                                        @foreach(\App\Models\User::where('role', 'business')->get() as $business)
                                            <option value="{{ $business->id }}">{{ $business->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="name">Menu Name</label>
                                    <input type="text" name="name" class="form-control" id="name" placeholder="Enter Name">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="form-group col-md-6">
                                    <label for="availability_status" class="form-label">Availability Status</label>
                                    <select id="availability_status" name="availability_status" class="form-control" required>
                                        <option value="">Select Status</option>
                                        <option value="0">N/A</option>
                                        <option value="1">Available</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select id="category_id" name="category_id" class="form-control" required>
                                        @foreach($categories as $index => $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="form-group col-md-6">
                                    <label for="photo" class="form-label">Upload Images</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="menuPhoto" name="photo" accept="image/*">
                                        <label class="custom-file-label" for="customFile">Upload Images</label>
                                    </div>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="price">Price</label>
                                    <input type="number" name="price" class="form-control" id="price">
                                </div>

                                <!-- images upload -->
                                <div class="col-md-12 text-center">
                                    <div id="imagePreview" class="mt-3 d-flex flex-wrap justify-content-center" style="float: left;"></div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-12 form-group">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter menu description..."></textarea>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Create Menu</button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection