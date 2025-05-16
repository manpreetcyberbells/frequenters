@extends('admin.layouts.master')
@section('title', 'Create Staff Member')
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
                        <li class="breadcrumb-item"><a href="{{ route('rewards.index') }}">Rewards</a></li>
                        <li class="breadcrumb-item active">Create Reward</li>
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
                    <div class="card-header"><div class="card-title">Create Reward</div></div>
                    <form id="rewardForm" method="POST" action="{{ route('rewards.store') }}" enctype="multipart/form-data" class="needs-validation">
                    @csrf
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="form-group col-md-4">
                                    <label for="business" class="form-label">Business</label>
                                    <select id="business_id" name="business_id" class="form-control" required>
                                        @foreach(\App\Models\User::where('role', 'business')->get() as $business)
                                            <option value="{{ $business->id }}">{{ $business->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="reward_name">Name</label>
                                    <input type="text" name="reward_name" class="form-control" id="reward_name" placeholder="Free Coffee">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="points_required">Points Required</label>
                                    <input type="number" name="points_required" class="form-control" id="points_required" placeholder="50" min="1" max="999">
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4 form-group">
                                    <label for="valid_from" class="form-label">Valid From</label>
                                    <input type="date" class="form-control" id="valid_from" name="valid_from" required placeholder="2025-04-24" min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"/>
                                </div>

                                <div class="col-md-4 form-group">
                                    <label for="valid_until" class="form-label">Valid Until</label>
                                    <input type="date" class="form-control" id="valid_until" name="valid_until" required placeholder="2025-04-26" min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"/>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-info">Create Reward</button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection