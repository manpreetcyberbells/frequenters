@extends('admin.layouts.master')
@section('title', 'Create Employee')
@section('content')

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Create Employee</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active"><a href="#">Staff</a></li>
                        <li class="breadcrumb-item active"><a href="#">Listing</a></li>
                        <li class="breadcrumb-item active">Create Employee</li>
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
                    <!-- <div class="card-header"><div class="card-title">Create Staff member</div></div> -->
                    <form id="staffForm" method="POST" action="{{ route('staff.store') }}" enctype="multipart/form-data" class="needs-validation" novalidate>
                    @csrf
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="form-group col-md-6">
                                    <label for="role" class="form-label">Role</label>
                                    <select id="role" name="role" class="form-control" required>
                                        <option value="">Select Role</option>
                                        <option value="employee" {{ old('role') == 'employee' ? 'selected' : '' }}>Employee</option>
                                        <option value="manager"  {{ old('role') == 'manager'  ? 'selected' : '' }}>Manager</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="business" class="form-label">Business</label>
                                    <select id="business_id" name="business_id" class="form-control select2bs4" required>
                                        <option value="">Select Business</option>
                                        @foreach(\App\Models\User::where('role', 'business')->get() as $business)
                                            <option value="{{ $business->id }}" {{ old('business_id') == $business->id ? 'selected' : '' }}>
                                                {{ $business->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>
                            <div class="row g-3">
                                <div class="form-group col-md-6">
                                    <label for="name">Name</label>
                                    <input type="text" name="name" class="form-control" id="name" value="{{ old('name') }}" placeholder="Enter Name">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="email">Email address</label>
                                    <input type="email" name="email" class="form-control" id="email" value="{{ old('email') }}" placeholder="Enter email">
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="form-group col-md-6">
                                    <label for="password">Password</label>
                                    <input type="password" name="password" class="form-control" id="password" placeholder="Password">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="password_confirmation">Confirm Password</label>
                                    <input type="password" name="password_confirmation" class="form-control" id="password_confirmation" placeholder="Password">
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="form-group col-md-6">
                                    <label for="status" class="form-label">Change Status</label>
                                    <select id="status" name="status" class="form-control" required>
                                        <option value="">Select status</option>
                                        <option value="active" {{ old('status') == 'active'    ? 'selected' : '' }}>Active</option>
                                        <option value="pending" {{ old('status') == 'pending'   ? 'selected' : '' }}>Pending</option>
                                        <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection