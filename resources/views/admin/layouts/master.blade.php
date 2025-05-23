<!DOCTYPE html>
<html lang="en">
    @include('admin.layouts.partials.head')
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="" src="{{ asset('admin_v1/dist/img/frequenter.png') }}" alt="Frequenter" height="60" width="60">
  </div>
    @include('admin.layouts.partials.nav')
    @include('admin.layouts.partials.sidebar')
      <!-- Dashbord -->
    @yield('content')
    @include('admin.layouts.partials.footer')
    
  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->
    @include('admin.layouts.partials.footer-scripts')
</body>
</html>
