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
                    <h1>Contest Listing</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('contests.index') }}">Contest</a></li>
                        <li class="breadcrumb-item active">Listing</li>
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
                     <!-- <h3 class="card-title">Contest Listing</h3> -->
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
                              <th class="text-center">Status</th>
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
                                <td class="text-center"><span class="badge @if($content->status == 'open') badge-primary @elseif($content->status == 'close') badge-danger @else badge-success @endif">{{ $content->status }}</span></td>
                                <td>{{ $content->max_participants }}</td>
                                <td>{{ $content->start_date }}</td>
                                <td>{{ $content->start_time }}</td>
                                <td>{{ $content->end_date }}</td>
                                <td>{{ $content->end_time }}</td>
                                <td>{{ $content->description }}</td>
                                <td style="width: 110px;">
                                    <!-- View Business -->
                                    <a href="{{ route('contests.show', $content->id) }}" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Edit">
                                       <i class="nav-icon fas fa-eye"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="{{ route('contests.edit', $content->id) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                    <i class="nav-icon fas fa-edit"></i>
                                    </a>

                                    <!-- Delete Button (with confirmation) -->
                                    <form action="{{ route('contests.destroy', $content->id) }}" method="POST" class="d-inline">
                                       @csrf
                                       @method('DELETE')
                                       <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this?');" data-bs-toggle="tooltip" title="Delete">
                                       <i class="nav-icon fas fa-trash"></i>
                                       </button>
                                    </form>

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
                              <th class="text-center">Status</th>
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