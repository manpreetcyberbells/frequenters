@extends('admin.layouts.master')
@section('title', 'Edit Staff Member')
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
                        <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff Member</a></li>
                        <li class="breadcrumb-item active">Edit Staff Members</li>
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
                    <div class="card-header"><div class="card-title">Edit Staff member</div></div>
                    <form id="staffForm" method="POST" action="{{ route('staff.update', $staff->id) }}" enctype="multipart/form-data" class="needs-validation">
                    @csrf
                    @method('PUT')
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="form-group col-md-6">
                                    <label for="role" class="form-label">Role</label>
                                    <select id="role" name="role" class="form-control" required>
                                        <option value="">Select Role</option>
                                        <option value="employee"  {{ $staff->role == 'employee' ? 'selected' : '' }}>Employee</option>
                                        <option value="manager"  {{ $staff->role == 'manager' ? 'selected' : '' }}>Manager</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="business" class="form-label">Business</label>
                                    <select id="business_id" name="business_id" class="form-control select2" required>
                                        @foreach(\App\Models\User::where('role', 'business')->get() as $business)
                                            <option value="{{ $business->id }}" {{ $staff->business_id == $business->business_id ? 'selected' : '' }}>{{ $business->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>
                            <div class="row g-3">
                                <div class="form-group col-md-6">
                                    <label for="name">Name</label>
                                    <input type="text" name="name" class="form-control" id="name" placeholder="Enter Name" value="{{ $staff->name }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="email">Email address</label>
                                    <input type="email" name="email" class="form-control" id="email" placeholder="Enter email" value="{{ $staff->email }}">
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
                                        <option value="active" {{ $staff->status == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="pending" {{ $staff->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="suspended" {{ $staff->status == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    </select>
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