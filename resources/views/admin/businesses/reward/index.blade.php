@extends('admin.layouts.master')
@section('title', 'Business Rewards')
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
                        <li class="breadcrumb-item active">Rewards</li>
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
                     <h3 class="card-title">Rewards</h3>
                     <div class="card-tools">
                        <a href="{{ route('rewards.create') }}" ><i class="fa fa-plus-square" aria-hidden="true"></i>Create Reward</a>
                     </div>
                  </div>
                  <!-- /.card-header -->
                  <div class="card-body">
                     <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                              <th style="width: 10px">#</th>
                              <th>Business</th>
                              <th>Reward Name</th>
                              <th>Points Required</th>
                              <th>Effective From</th>
                              <th>Effective To</th>
                              <th></th>
                           </tr>
                        </thead>
                        <tbody>  
                        @foreach($rewards as $index => $reward)
                              <tr class="align-middle">
                                 <td>{{ $index + 1 }}</td>
                                 <td>{{$reward->business->name}}</td>
                                 <td>{{$reward->reward_name}}</td>
                                 <td>{{$reward->points_required}}</td>
                                 <td>{{$reward->valid_from}}</td>
                                 <td>{{$reward->valid_until}}</td>
                                 <td>
                                    <!-- View Business -->
                                    <a href="{{ route('reward.reedemption.history', $reward->id) }}" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Edit">
                                       <i class="fa fa-history"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="{{ route('rewards.edit', $reward->id) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
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
                              <th>Reward Name</th>
                              <th>Points</th>
                              <th>Effective From</th>
                              <th>Effective To</th>
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