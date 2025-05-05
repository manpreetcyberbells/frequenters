@extends('admin.layouts.master')
@section('title', 'Business Event')
@section('content')
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
   <!-- Content Header (Page header) -->
   <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                  <div class="col-sm-6">
                    <h1>Business</h1>
                  </div>
                  <div class="col-sm-6">
                     <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('events.create') }}">Create Event</a></li>
                        <li class="breadcrumb-item active">Events</li>
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
                     <h3 class="card-title">Business Event</h3>
                     <div class="card-tools">
                        <a href="{{ route('events.create') }}" ><i class="fa fa-plus-square" aria-hidden="true"></i>Create Event</a>
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
                              <th>Venue Name</th>
                              <th>Venue Location</th>
                              <th>Capacity</th>
                              <th>Start Date</th>
                              <th>Start Time</th>
                              <th>End Date</th>
                              <th>End Time</th>
                              <th>Target Tier</th>
                              <th>Status</th>
                              <!-- <th>CreatedAt</th> -->
                              <th>Description</th>
                           </tr>
                        </thead>
                        <tbody>
                            @foreach ($events as $index => $event)
                            <tr class="align-middle">
                                <td>{{ $index + 1 }}</td>
                                <td><img src="{{ $event->event_banner }}" style="height: 50px; width: 50px;"/></td>
                                <td>{{ $event->businessDetail->name }}</td>
                                <td>{{ $event->event_name }}</td>
                                <td>{{ $event->venue_name }}</td>
                                <td>{{ $event->venue_location }}</td>
                                <td>{{ $event->capacity }}</td>
                                <td>{{ $event->start_date }}</td>
                                <td>{{ $event->start_time }}</td>
                                <td>{{ $event->end_date }}</td>
                                <td>{{ $event->end_time }}</td>
                                <td>{{ $event->target_tier }}</td>
                                <td>{{ $event->status }}</td>
                                <!-- <td>{{ $event->created_at }}</td> -->
                                <td>{{ $event->event_description }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                           <tr>
                              <th style="width: 10px">#</th>
                              <th>Banner</th>
                              <th>Business</th>
                              <th>Title</th>
                              <th>Venue Name</th>
                              <th>Venue Location</th>
                              <th>Capacity</th>
                              <th>Start Date</th>
                              <th>Start Time</th>
                              <th>End Date</th>
                              <th>End Time</th>
                              <th>Target Tier</th>
                              <th>Status</th>
                              <!-- <th>CreatedAt</th> -->
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