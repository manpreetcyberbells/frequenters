@extends('admin.layouts.master')
@section('title', 'Business Menu')
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
                        <li class="breadcrumb-item active">Menus</li>
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
                     <h3 class="card-title">Business Menus</h3>
                     <div class="card-tools">
                        <a href="{{ route('menus.create') }}" ><i class="fa fa-plus-square" aria-hidden="true"></i>Create menu</a>
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
                              <th>Availability Status</th>
                              <th>Description</th>
                              <th></th>
                           </tr>
                        </thead>
                        <tbody>
                           @foreach($menus as $index => $menu)
                              <tr class="align-middle">
                                 <td>{{ $index + 1 }}</td>
                                 <td><img src="{{$menu->photo}}" alt="" style="width:100px; height:100px;"></td>
                                 <td> {{@$menu->business->business_name}} </td>
                                 <td> {{$menu->name}} </td>
                                 <td> {{$menu->category_id}} </td>
                                 <td> {{$menu->price}} </td>
                                 <td> <span class="badge @if($menu->availability_status == 1) badge-primary @else badge-danger @endif"> 
                                    @if($menu->availability_status == 1)
                                       Available
                                    @else
                                       N/A
                                    @endif
                                 </td>
                                 <td> {{$menu->description}} </td>
                                 <td>
                                    <div class="input-group-prepend">
                                       <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown">
                                          Action
                                       </button>
                                       <ul class="dropdown-menu">
                                          <li class="dropdown-item"><a href="{{ route('menus.edit', $menu->id) }}">Edit</a></li>
                                       </ul>
                                    </div>
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
                              <th>Availability Status</th>
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