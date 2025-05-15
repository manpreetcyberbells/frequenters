@extends('admin.layouts.master')
@section('title', 'Business Offers')
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
                        <li class="breadcrumb-item"><a href="{{ route('admin.business.offer.create') }}">Create Offer</a></li>
                        <li class="breadcrumb-item active">Offers</li>
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
                     <h3 class="card-title">Business Offers</h3>
                     <div class="card-tools">
                        <a href="{{ route('admin.business.offer.create') }}" ><i class="fa fa-plus-square" aria-hidden="true"></i>Create Offer</a>
                     </div>
                  </div>
                  <!-- /.card-header -->
                  <div class="card-body">
                     <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                              <th style="width: 10px">#</th>
                              <th>Banner</th>
                              <th>Business</th>
                              <th>Title</th>
                              <th>Discount Percentage</th>
                              <th>Discount Cap</th>
                              <th>Applicable Users</th>
                              <th>Offer Time</th>
                              <th>Start Date</th>
                              <th>Start Time</th>
                              <th>End Date</th>
                              <th>End Time</th>
                              <th>Applicable Days</th>
                              <th>Min Order Value</th>
                              <th>Tagline</th>
                              <th>Description</th>
                           </tr>
                        </thead>
                        <tbody>
                            @foreach ($offers as $index => $offer)
                            <tr class="align-middle">
                                <td>{{ $index + 1 }}</td>
                                <td><img src="{{ $offer->banner_image }}" style="height: 50px; width: 50px;"/></td>
                                <td>{{ $offer->business->name }}</td>
                                <td>{{ $offer->title }}</td>
                                <td>{{ $offer->discount_percentage }}</td>
                                <td>{{ $offer->discount_cap }}</td>
                                <td> <span class="badge @if($offer->applicable_users == 'regular') badge-success @elseif($offer->applicable_users == 'new') badge-primary @else badge-dark @endif">{{ $offer->applicable_users }}</span></td>
                                <td>{{ $offer->offer_time }}</td>
                                <td>{{ $offer->campaign_start_date }}</td>
                                <td>{{ $offer->campaign_start_time }}</td>
                                <td>{{ $offer->campaign_end_date }}</td>
                                <td>{{ $offer->campaign_end_time }}</td>
                                <td>{{ $offer->applicable_days }}</td>
                                <td>{{ $offer->min_order_value }}</td>
                                <td>{{ $offer->terms_and_conditions }}</td>
                                <td>{{ $offer->description }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                           <tr>
                           <th style="width: 10px">#</th>
                              <th>Banner</th>
                              <th>Business</th>
                              <th>Title</th>
                              <th>Discount Percentage</th>
                              <th>Discount Cap</th>
                              <th>Applicable Users</th>
                              <th>Offer Time</th>
                              <th>Start Date</th>
                              <th>Start Time</th>
                              <th>End Date</th>
                              <th>End Time</th>
                              <th>Applicable Days</th>
                              <th>Min Order Value</th>
                              <th>Tagline</th>
                              <th>Description</th>
                           </tr>
                        </tfoot>
                     </table>
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