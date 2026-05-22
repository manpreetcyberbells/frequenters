@extends('admin.layouts.master')
@section('title', 'Business Detail')
@section('content')

<?php
    // echo"<pre>"; print_r($business);  die;
?>


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
                        <li class="breadcrumb-item"><a href="{{ route('business.index') }}">Business</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('business.index') }}">Listing</a></li>
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
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fa fa-info-circle"></i>
                                Business Detail
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-5 col-sm-1">
                                    <div class="nav flex-column nav-tabs h-100" id="vert-tabs-tab" role="tablist" aria-orientation="vertical">
                                        <a class="nav-link active" id="vert-tabs-home-tab" data-toggle="pill" href="#vert-tabs-home" role="tab" aria-controls="vert-tabs-home" aria-selected="true">Detail</a>
                                        <a class="nav-link" id="vert-tabs-profile-tab" data-toggle="pill" href="#vert-tabs-profile" role="tab" aria-controls="vert-tabs-profile" aria-selected="false">Contest</a>
                                        <a class="nav-link" id="vert-tabs-messages-tab" data-toggle="pill" href="#vert-tabs-messages" role="tab" aria-controls="vert-tabs-messages" aria-selected="false">Event</a>
                                        <a class="nav-link" id="vert-tabs-settings-tab" data-toggle="pill" href="#vert-tabs-settings" role="tab" aria-controls="vert-tabs-settings" aria-selected="false">Offer</a>
                                        <a class="nav-link" id="vert-tabs-reward-tab" data-toggle="pill" href="#vert-tabs-reward" role="tab" aria-controls="vert-tabs-reward" aria-selected="false">Reward</a>
                                        <a class="nav-link" id="vert-tabs-staff-tab" data-toggle="pill" href="#vert-tabs-staff" role="tab" aria-controls="vert-tabs-staff" aria-selected="false">Staff</a>
                                        <a class="nav-link" id="vert-tabs-menu-tab" data-toggle="pill" href="#vert-tabs-menu" role="tab" aria-controls="vert-tabs-menu" aria-selected="false">Menu</a>
                                        <a class="nav-link" id="vert-tabs-services-tab" data-toggle="pill" href="#vert-tabs-services" role="tab" aria-controls="vert-tabs-services" aria-selected="false">Service</a>
                                    </div>
                                </div>
                                <div class="col-7 col-sm-11">
                                    <div class="tab-content" id="vert-tabs-tabContent">
                                        <div class="tab-pane text-left fade show active" id="vert-tabs-home" role="tabpanel" aria-labelledby="vert-tabs-home-tab">
                                            @include('admin.businesses.tabs.detail')
                                        </div>
                                        <div class="tab-pane fade" id="vert-tabs-profile" role="tabpanel" aria-labelledby="vert-tabs-profile-tab">
                                            @include('admin.businesses.tabs.contest')
                                        </div>
                                        <div class="tab-pane fade" id="vert-tabs-messages" role="tabpanel" aria-labelledby="vert-tabs-messages-tab">
                                            @include('admin.businesses.tabs.event')
                                        </div>
                                        <div class="tab-pane fade" id="vert-tabs-settings" role="tabpanel" aria-labelledby="vert-tabs-settings-tab">
                                            @include('admin.businesses.tabs.offer')
                                        </div>
                                        <div class="tab-pane fade" id="vert-tabs-reward" role="tabpanel" aria-labelledby="vert-tabs-reward-tab">
                                            @include('admin.businesses.tabs.reward')
                                        </div>
                                        <div class="tab-pane fade" id="vert-tabs-staff" role="tabpanel" aria-labelledby="vert-tabs-staff-tab">
                                            @include('admin.businesses.tabs.staff')
                                        </div>
                                        <div class="tab-pane fade" id="vert-tabs-menu" role="tabpanel" aria-labelledby="vert-tabs-menu-tab">
                                            @include('admin.businesses.tabs.menu')
                                        </div>
                                        <div class="tab-pane fade" id="vert-tabs-services" role="tabpanel" aria-labelledby="vert-tabs-services-tab">
                                            @include('admin.businesses.tabs.service')
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!-- /.card -->
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