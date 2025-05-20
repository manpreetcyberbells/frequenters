@extends('admin.layouts.master')
@section('title', 'Customer Detail')
@section('content')

<div class="content-wrapper">
   <section class="content-header">
      <div class="container-fluid">
         <div class="row mb-2">
            <div class="col-sm-6">
            </div>
            <div class="col-sm-6">
               <ol class="breadcrumb float-sm-right">
                  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                  <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customer</a></li>
                  <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Listing</a></li>
                  <li class="breadcrumb-item active">Detail</li>
               </ol>
            </div>
         </div>
      </div>
   </section>

   <section class="content">
      @include('admin.layouts.notifications')
      <div class="container-fluid">
         <div class="row">
            <div class="col-12">
               <div class="card card-primary card-outline">
                  <div class="card-header">
                     <h3 class="card-title">
                        <i class="fa fa-info-circle"></i>
                        Customer Detail
                     </h3>
                  </div>

                  <div class="card-body">
                     <div class="row">
                        <div class="col-5 col-sm-2">
                           <div class="nav flex-column nav-tabs h-100" id="vert-tabs-tab" role="tablist" aria-orientation="vertical">
                                 <a class="nav-link active" id="vert-tabs-home-tab" data-toggle="pill" href="#vert-tabs-home" role="tab" aria-controls="vert-tabs-home" aria-selected="true">Detail</a>
                                 <a class="nav-link" id="vert-tabs-checkin-tab" data-toggle="pill" href="#vert-tabs-checkin" role="tab" aria-controls="vert-tabs-checkin" aria-selected="false">Check-In History</a>
                                 <a class="nav-link" id="vert-tabs-redeem-tab" data-toggle="pill" href="#vert-tabs-redeem" role="tab" aria-controls="vert-tabs-redeem" aria-selected="false">Redeem</a>
                                 <a class="nav-link" id="vert-tabs-search-tab" data-toggle="pill" href="#vert-tabs-search" role="tab" aria-controls="vert-tabs-search" aria-selected="false">Search</a>
                                 <a class="nav-link" id="vert-tabs-wishlist-tab" data-toggle="pill" href="#vert-tabs-wishlist" role="tab" aria-controls="vert-tabs-wishlist" aria-selected="false">Wishlist</a>
                                 <a class="nav-link" id="vert-tabs-complaint-tab" data-toggle="pill" href="#vert-tabs-complaint" role="tab" aria-controls="vert-tabs-complaint" aria-selected="false">Complaint</a>
                                 <a class="nav-link" id="vert-tabs-invited-tab" data-toggle="pill" href="#vert-tabs-invited" role="tab" aria-controls="vert-tabs-invited" aria-selected="false">Friends Invited</a>
                                 <a class="nav-link" id="vert-tabs-review-tab" data-toggle="pill" href="#vert-tabs-review" role="tab" aria-controls="vert-tabs-review" aria-selected="false">Reviews</a>
                                 <a class="nav-link" id="vert-tabs-contests-tab" data-toggle="pill" href="#vert-tabs-contests" role="tab" aria-controls="vert-tabs-contests" aria-selected="false">Contests</a>
                                 <a class="nav-link" id="vert-tabs-events-tab" data-toggle="pill" href="#vert-tabs-events" role="tab" aria-controls="vert-tabs-events" aria-selected="false">Events</a>
                                 <a class="nav-link" id="vert-tabs-interaction-tab" data-toggle="pill" href="#vert-tabs-interaction" role="tab" aria-controls="vert-tabs-interaction" aria-selected="false">Reward Earned</a>
                           </div>
                        </div>
                        <div class="col-7 col-sm-10">
                           <div class="tab-content" id="vert-tabs-tabContent">
                                 <div class="tab-pane text-left fade show active" id="vert-tabs-home" role="tabpanel" aria-labelledby="vert-tabs-home-tab">
                                    @include('admin.customers.tabs.detail')
                                 </div>
                                 <div class="tab-pane fade" id="vert-tabs-checkin" role="tabpanel" aria-labelledby="vert-tabs-checkin-tab">
                                    @include('admin.customers.tabs.checkIn_history')
                                 </div>
                                 <div class="tab-pane fade" id="vert-tabs-redeem" role="tabpanel" aria-labelledby="vert-tabs-redeem-tab">
                                    @include('admin.customers.tabs.redeem_history')
                                 </div>
                                 <div class="tab-pane fade" id="vert-tabs-search" role="tabpanel" aria-labelledby="vert-tabs-search-tab">
                                    @include('admin.customers.tabs.search_history')
                                 </div>
                                 <div class="tab-pane fade" id="vert-tabs-wishlist" role="tabpanel" aria-labelledby="vert-tabs-wishlist-tab">
                                    @include('admin.customers.tabs.wishlist')
                                 </div>
                                 <div class="tab-pane fade" id="vert-tabs-complaint" role="tabpanel" aria-labelledby="vert-tabs-complaint-tab">
                                    @include('admin.customers.tabs.complaint')
                                 </div>
                                 <div class="tab-pane fade" id="vert-tabs-invited" role="tabpanel" aria-labelledby="vert-tabs-invited-tab">
                                    @include('admin.customers.tabs.invite')
                                 </div>
                                 <div class="tab-pane fade" id="vert-tabs-review" role="tabpanel" aria-labelledby="vert-tabs-review-tab">
                                    @include('admin.customers.tabs.review')
                                 </div>
                                 <div class="tab-pane fade" id="vert-tabs-contests" role="tabpanel" aria-labelledby="vert-tabs-contests-tab">
                                    @include('admin.customers.tabs.contests')
                                 </div>
                                 <div class="tab-pane fade" id="vert-tabs-events" role="tabpanel" aria-labelledby="vert-tabs-events-tab">
                                    @include('admin.customers.tabs.events')
                                 </div>
                                 <div class="tab-pane fade" id="vert-tabs-interaction" role="tabpanel" aria-labelledby="vert-tabs-interaction-tab">
                                    @include('admin.customers.tabs.interaction')
                                 </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </section>
</div>
@endsection