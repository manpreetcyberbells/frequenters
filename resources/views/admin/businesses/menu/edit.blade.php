@extends('admin.layouts.master')
@section('title', 'Business Menu Edit')
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
                        <li class="breadcrumb-item"><a href="{{ route('menus.index') }}">Menus</a></li>
                        <li class="breadcrumb-item active">Edit Menu</li>
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
                    <div class="card-header"><div class="card-title">Edit Menu</div></div>
                    <form id="menuForm" method="post" action="{{ route('menus.update', $menu->id) }}" enctype="multipart/form-data" class="needs-validation">
                    @csrf
                    @method('PUT')
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="form-group col-md-6">
                                    <label for="status" class="form-label">Business</label>
                                    <select id="business_id" name="business_id" class="form-control select2" required>
                                        @foreach(\App\Models\User::where('role', 'business')->get() as $business)
                                            <option value="{{ $business->id }}" {{ $menu->business_id == $business->business_id ? 'selected' : '' }}>{{ $business->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="name">Menu Name</label>
                                    <input type="text" name="name" class="form-control" id="name" value="{{$menu->name}}" placeholder="Enter Name">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="form-group col-md-6">
                                    <label for="availability_status" class="form-label">Availability Status</label>
                                    <select id="availability_status" name="availability_status" class="form-control" required>
                                        <option value="">Select Status</option>
                                        <option value="0" {{ $menu->availability_status == '0' ? 'selected' : '' }}>N/A</option>
                                        <option value="1" {{ $menu->availability_status == '1' ? 'selected' : '' }}>Available</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select id="category_id" name="category_id" class="form-control select2" required>
                                        @foreach($categories as $index => $category)
                                            <option value="{{ $category->id }}" {{ $menu->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="form-group col-md-6">
                                    <label for="photo" class="form-label">Upload Images</label>
                                    <input type="file" class="form-control" id="menuPhoto" name="photo" accept="image/*" required>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="price">Price</label>
                                    <input type="number" name="price" class="form-control" id="price" value="{{$menu->price}}">
                                </div>

                                <!-- images upload -->
                                <div class="col-md-12 text-center">
                                    <img src="{{$menu->photo}}" alt="" style="width:100px; height:100px;">
                                    <div id="imagePreview" class="mt-3 d-flex flex-wrap justify-content-center" style="float: left;"></div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-12 form-group">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter menu description...">{{ $menu->description }}</textarea>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection