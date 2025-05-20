@extends('admin.layouts.master')
@section('title', 'Business Service')
@section('content')

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
   <!-- Content Header (Page header) -->
   <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                  <div class="col-sm-6">
                    <h1>Service Listing</h1>
                  </div>
                  <div class="col-sm-6">
                     <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active"><a href="#">Service</a></li>   
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
                     <!-- <h3 class="card-title">Services</h3> -->
                     <div class="card-tools">
                        <a href="{{ route('services.create') }}" ><i class="fa fa-plus-square" aria-hidden="true"></i>Create Service</a>
                     </div>
                  </div>
                  <!-- /.card-header -->
                  <div class="card-body">
                     <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                              <th style="width: 10px">#</th>
                              <th>Photo</th>
                              <th>Business</th>
                              <th>Name</th>
                              <th>Category</th>
                              <th>Price</th>
                              <th>Duration</th>
                              <th>Description</th>
                              <th></th>
                           </tr>
                        </thead>
                        <tbody>
                           @foreach($services as $index => $service)
                              <tr class="align-middle">
                                 <td>{{ $index + 1 }}</td>
                                 <td><img src="{{ $service->photo }}" alt="" style="width:80px; height:80px;"></td>
                                 <td> {{ $service->businessInfo->name }} </td>
                                 <td> {{ $service->name }} </td>
                                 <td> {{ $service->name }} </td>
                                 <td> {{ $service->price }} </td>
                                 <td> {{ $service->duration }} </td>
                                 <td> {{ $service->description }} </td>
                                 <td>
                                    <!-- Edit Button -->
                                    <a href="{{ route('services.edit', $service->id) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                       <i class="nav-icon fas fa-edit"></i>
                                    </a>
                                 </td>
                              </tr>
                           @endforeach
                        </tbody>
                        <tfoot>
                           <tr>
                              <th style="width: 10px">#</th>
                              <th>Photo</th>
                              <th>Business</th>
                              <th>Name</th>
                              <th>Category</th>
                              <th>Price</th>
                              <th>Duration</th>
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