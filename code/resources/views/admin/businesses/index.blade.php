@extends('admin.layouts.master')
@section('title', 'Business Listing')
@section('content')
<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Business Listing</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
              <li class="breadcrumb-item"><a href="{{ route('business.index') }}">Business</a></li>
              <li class="breadcrumb-item active">Listing</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
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
                <!-- <h3 class="card-title">Business Listing</h3> -->
                <div class="card-tools">
                  <a href="{{ route('business.create') }}" ><i class="fa fa-plus-square" aria-hidden="true"></i>Create Business</a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th style="width: 10px">#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Last Login</th>
                    <th>Points Per Checkin</th>
                    <th class="text-center" style="width: 40px">Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    @foreach ($businesses as $index => $business)
                      <tr class="align-middle">
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $business->name }}</td>
                        <td>{{ $business->email }}</td>
                        <td>
                          @if ($business->last_login_at)
                              {{ \Carbon\Carbon::parse($business->last_login_at)->format('d M Y, h:i A') }}
                          @else
                              <span class="text-muted">Never Logged In</span>
                          @endif
                        </td>
                        <td>{{ $business->businessProfile->points_per_checkin }}</td>
                        <td class="text-center">
                        @switch($business->status)
                            @case('active')
                                <span class="badge bg-success">Active</span>
                                @break
                            @case('pending')
                                <span class="badge bg-warning">Pending</span>
                                @break
                            @case('suspended')
                                <span class="badge bg-danger">Suspended</span>
                                @break
                            @default
                                <span class="badge bg-secondary">Unknown</span>
                        @endswitch
                        </td>
                        <td>
                          <!-- View Business -->
                          <a href="{{ route('business.show', $business->id) }}" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Edit">
                            <i class="nav-icon fas fa-eye"></i>
                          </a>

                          <!-- Edit Button -->
                          <a href="{{ route('business.edit', $business->id) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                            <i class="nav-icon fas fa-edit"></i>
                          </a>

                          <!-- Delete Button (with confirmation) -->
                          <form action="{{ route('business.destroy', $business->id) }}" method="POST" class="d-inline">
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
                    <th>Name</th>
                    <th>Email</th>
                    <th>Last Login</th>
                    <th>Points Per Checkin</th>
                    <th  class="text-center" style="width: 40px">Status</th>
                    <th>Action</th>
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