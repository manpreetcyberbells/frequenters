@extends('admin.layouts.master')
@section('title', 'Business Staff Members')
@section('content')
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
   <!-- Content Header (Page header) -->
   <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                  <div class="col-sm-6">
                    <h1>Staff</h1>
                  </div>
                  <div class="col-sm-6">
                     <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active"><a href="#">Staff</a></li>
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
                     <!-- <h3 class="card-title">Staff</h3> -->
                     <div class="card-tools">
                        <a href="{{ route('staff.create') }}" ><i class="fa fa-plus-square" aria-hidden="true"></i>Create Employee</a>
                     </div>
                  </div>
                  <!-- /.card-header -->
                  <div class="card-body">
                     <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                              <th style="width: 10px">#</th>
                              <th>Business</th>
                              <th>Employee Name</th>
                              <th>Employee Email</th>
                              <th>Employee Phone</th>
                              <th>Employee Status</th>
                              <th>Employee Role</th>
                              <th>Providers</th>
                              <th></th>
                           </tr>
                        </thead>
                        <tbody>
                           @foreach($staffMembers as $index => $staff)
                              <tr class="align-middle">
                                 <td>{{ $index + 1 }}</td>
                                 <td>{{$staff->parent_business}}</td>
                                 <td> {{$staff->name}} </td>
                                 <td> {{$staff->email}} </td>
                                 <td> {{$staff->phone}} </td>
                                 <td class="text-center"> <span class="badge @if($staff->status == 'active') badge-success @elseif ($staff->status == 'pending') badge-success @else badge-danger @endif"> {{$staff->status}} </span></td>
                                 <td class="text-center"> <span class="badge @if($staff->role == 'manager') badge-primary @else badge-info @endif">{{$staff->role}} </span></td>
                                 <td class="text-center"> <span class="badge @if($staff->providers == 'email') bg-navy @elseif($staff->providers == 'facebook') badge-primary @elseif($staff->providers == 'google') badge-info @elseif($staff->providers == 'apple') badge-gray @else badge-lime @endif"> {{$staff->providers}} </td>
                                 <td class="text-center">
                                    <!-- Edit Button -->
                                    <a href="{{ route('staff.edit', $staff->id) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                    <i class="nav-icon fas fa-edit"></i>
                                    </a>
                                 </td>
                              </tr>
                           @endforeach
                        </tbody>
                        <tfoot>
                           <tr>
                              <th style="width: 10px">#</th>
                              <th>Business</th>
                              <th>Name</th>
                              <th>Email</th>
                              <th>Phone</th>
                              <th>Status</th>
                              <th>Role</th>
                              <th>Providers</th>
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