@extends('admin.layouts.master')
@section('title', 'Business Contests')
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
                        <li class="breadcrumb-item"><a href="{{ route('contests.create') }}">Create Contest</a></li>
                        <li class="breadcrumb-item active">Contests</li>
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
                     <h3 class="card-title">Business Contests</h3>
                     <div class="card-tools">
                        <a href="{{ route('contests.create') }}" ><i class="fa fa-plus-square" aria-hidden="true"></i>Create Contest</a>
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
                              <th>Contest</th>
                              <th>Status</th>
                              <th>Capacity</th>
                              <th>Start Date</th>
                              <th>Start Time</th>
                              <th>End Date</th>
                              <th>End Time</th>
                              <th>Description</th>
                              <th></th>
                           </tr>
                        </thead>
                        <tbody>
                            @foreach($contents as $index => $content)
                              <tr class="align-middle">
                                <td>{{ $index + 1 }}</td>
                                <td><img src="{{ $content->banner_image }}" style="height: 50px; width: 50px;"/></td>
                                <td>{{ $content->businessDetail->name }}</td>
                                <td>{{ $content->name }}</td>
                                <td><span class="badge @if($content->status == 'open') badge-primary @elseif($content->status == 'close') badge-danger @else badge-success @endif">{{ $content->status }}</span></td>
                                <td>{{ $content->max_participants }}</td>
                                <td>{{ $content->start_date }}</td>
                                <td>{{ $content->start_time }}</td>
                                <td>{{ $content->end_date }}</td>
                                <td>{{ $content->end_time }}</td>
                                <td>{{ $content->description }}</td>
                                <td>
                                 <div class="input-group-prepend">
                                       <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown">
                                          Action
                                       </button>
                                       <ul class="dropdown-menu">
                                          <li class="dropdown-item"><a href="{{ route('contests.show', $content->id) }}">View</a></li>
                                          <li class="dropdown-item"><a href="{{ route('contests.edit', $content->id) }}">Edit</a></li>
                                          <li class="dropdown-item">
                                             <form action="{{ route('contests.destroy', $content->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this contest?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link p-0 m-0" style="color: red;">Delete</button>
                                             </form>
                                          </li>
                                       </ul>
                                    </div>  
                                 </td>
                              </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                           <tr>
                           <th style="width: 10px">#</th>
                              <th>Banner</th>
                              <th>Business</th>
                              <th>Contest</th>
                              <th>Status</th>
                              <th>Capacity</th>
                              <th>Start Date</th>
                              <th>Start Time</th>
                              <th>End Date</th>
                              <th>End Time</th>
                              <th>Description</th>
                              <th></th>
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