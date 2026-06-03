@extends('admin.layouts.master')
@section('title', 'Manual CheckIn')
@section('content')


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Check-In</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Check-In</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
       <!-- Success/Error Messages -->
        @include('admin.layouts.notifications')
        <div class="row">
          <!-- left column -->
          <div class="col-md-12">
            <!-- jquery validation -->
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Manual <small>Check-In</small></h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form id="checkInForm" action="{{ route('admin.manual-checkin.process') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="status" class="form-label">Business</label>
                        <select id="businessIdManual" name="business_id" class="form-control" required >
                            @foreach(\App\Models\User::where('role', 'business')->get() as $business)
                                <option value="{{ $business->id }}">{{ $business->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="text_code" class="form-label">Enter Text Code:</label>
                        <textarea class="form-control" id="text_code" name="text_code" rows="3" required placeholder="Enter text code here"></textarea>
                    </div>
                </div>
                <!-- /.card-body -->
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Submit</button>
                </div>
              </form>
            </div>
            <!-- /.card -->
            </div>
          <!--/.col (left) -->
          <!-- right column -->
          <div class="col-md-6">

          </div>
          <!--/.col (right) -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

 
@endsection