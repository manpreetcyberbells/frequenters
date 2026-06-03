@extends('admin.layouts.master')
@section('title', 'Manage Business Points')
@section('content')

<div class="content-wrapper">
    <!-- Content Header -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Business Points</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="manual-checkin">Manual Check-In</a></li>
                        <li class="breadcrumb-item active">Business Points</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            {{-- Session Alerts --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fas fa-check"></i> {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fas fa-ban"></i> {{ session('error') }}
                </div>
            @endif

            <div class="row">
                {{-- Single Business --}}
                <div class="col-md-6">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Add Reward Points to a Single Business</h3>
                        </div>
                        <form id="grant-reward-form" method="POST" action="{{ route('admin.business.grantCheckinPoints', ['businessId' => 'BUSINESS_ID']) }}">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="business_id">Select Business</label>
                                    <select name="business_id" id="business_id" class="form-control" required onchange="updateFormAction(this)">
                                        <option value="">-- Select Business --</option>
                                        @foreach ($businessList as $business)
                                            <option value="{{ $business->business_id }}">{{ $business->business_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="points">Reward Points</label>
                                    <input type="number" name="points" id="points" class="form-control"
                                           placeholder="Enter reward points" required>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Grant Points</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- All Businesses --}}
                <div class="col-md-6">
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Add Reward Points to All Businesses</h3>
                        </div>
                        <form action="{{ route('admin.addPointsToAll') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="points_all">Reward Points for All Businesses</label>
                                    <input type="number" name="points" id="points_all" class="form-control"
                                           placeholder="Enter reward points" required>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success">Add Points to All</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
@endsection