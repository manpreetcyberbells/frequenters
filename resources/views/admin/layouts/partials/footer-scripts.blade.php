<!-- jQuery -->
<script src="{{ asset('admin_v1/plugins/jquery/jquery.min.js') }}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset('admin_v1/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="{{ asset('admin_v1/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- Select2 -->
<script src="{{ asset('admin_v1/plugins/select2/js/select2.full.min.js') }}"></script>
<!-- ChartJS -->
<script src="{{ asset('admin_v1/plugins/chart.js/Chart.min.js') }}"></script>
<!-- Sparkline -->
<script src="{{ asset('admin_v1/plugins/sparklines/sparkline.js') }}"></script>
<!-- JQVMap -->
<script src="{{ asset('admin_v1/plugins/jqvmap/jquery.vmap.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/jqvmap/maps/jquery.vmap.usa.js') }}"></script>
<!-- jQuery Knob Chart -->
<script src="{{ asset('admin_v1/plugins/jquery-knob/jquery.knob.min.js') }}"></script>
<!-- daterangepicker -->
<script src="{{ asset('admin_v1/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/daterangepicker/daterangepicker.js') }}"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="{{ asset('admin_v1/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
<!-- Summernote -->
<script src="{{ asset('admin_v1/plugins/summernote/summernote-bs4.min.js') }}"></script>
<!-- overlayScrollbars -->
<script src="{{ asset('admin_v1/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('admin_v1/dist/js/adminlte.js') }}"></script>
<!-- AdminLTE for demo purposes -->
<!-- <script src="{{ asset('admin_v1/dist/js/demo.js') }}"></script> -->
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="{{ asset('admin_v1/dist/js/pages/dashboard.js') }}"></script>

<!-- DataTables  & Plugins -->
<script src="{{ asset('admin_v1/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/pdfmake/vfs_fonts.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
<!-- jquery-validation -->
<script src="{{ asset('admin_v1/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
<script src="{{ asset('admin_v1/plugins/jquery-validation/additional-methods.min.js') }}"></script>
<!-- AdminLTE for demo purposes -->
<!-- <script src="{{ asset('admin_v1/dist/js/demo.js') }}"></script> -->
<script src="{{ asset('admin/js/business/create.js') }}"></script>
<script src="{{ asset('admin/js/business/edit.js') }}"></script>
<!-- Ekko Lightbox -->
<script src="{{ asset('admin_v1/plugins/ekko-lightbox/ekko-lightbox.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('admin_v1/dist/js/adminlte.min.js') }}"></script>
<!-- Filterizr-->
<script src="{{ asset('admin_v1/plugins/filterizr/jquery.filterizr.min.js') }}"></script>
<!-- Common jQuery -->
<script src="{{ asset('admin/js/common.js') }}"></script>
<script src="{{ asset('admin/js/formvalidation.js') }}"></script>
<!-- Page specific script -->
<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
    
    $("#example1").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    $('.datatableExample').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
  });

  $(function () {
    $(document).on('click', '[data-toggle="lightbox"]', function(event) {
      event.preventDefault();
      $(this).ekkoLightbox({
        alwaysShowClose: true
      });
    });

    $('.filter-container').filterizr({gutterPixels: 3});
    $('.btn[data-filter]').on('click', function() {
      $('.btn[data-filter]').removeClass('active');
      $(this).addClass('active');
    });
  })


  // Category Page
  $(document).ready(function () {
    const parentSelect = document.getElementById("parentCategory");
    const subCatGroup = document.getElementById("subcategoryGroup");
    const subCatSelect = document.getElementById("childCategory");
    const customParentInput = document.getElementById("customParentCategory");
    const customSubInput = document.getElementById("customSubCategory");
    const grandchildInput = document.getElementById("grandchildCategory");

    parentSelect.addEventListener("change", function () {
        const selectedValue = this.value;

        if (selectedValue === "other") {
            subCatGroup.style.display = "none";
            subCatSelect.innerHTML = `<option value="">Select Sub Category</option><option value="other">Other</option>`;
            customParentInput.style.display = "block";
            customSubInput.style.display = "block";
            grandchildInput.style.display = "block";
        } else if (selectedValue) {
            fetch(`/admin/categories/${selectedValue}/children`)
                .then(response => response.json())
                .then(data => {
                    subCatSelect.innerHTML = `<option value="">Select Sub Category</option>`;

                    if (data.length > 0) {
                        data.forEach(cat => {
                            subCatSelect.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                        });

                        // ✅ Always append "Other" option at end
                        subCatSelect.innerHTML += `<option value="other">Other</option>`;
                        subCatGroup.style.display = "block";
                    } else {
                        // Still show dropdown with just "Other"
                        subCatSelect.innerHTML = `<option value="">Select Sub Category</option><option value="other">Other</option>`;
                        subCatGroup.style.display = "block";
                    }

                    customParentInput.style.display = "none";
                    customSubInput.style.display = "none";
                    grandchildInput.style.display = "none";
                });
        } else {
            subCatGroup.style.display = "none";
            customParentInput.style.display = "none";
            customSubInput.style.display = "none";
            grandchildInput.style.display = "none";
        }
    });

    subCatSelect.addEventListener("change", function () {
        const selectedValue = this.value;

        if (selectedValue === "other") {
            customSubInput.style.display = "block";
            grandchildInput.style.display = "block";
        } else if (selectedValue) {
            customSubInput.style.display = "none";
            grandchildInput.style.display = "block";
        } else {
            customSubInput.style.display = "none";
            grandchildInput.style.display = "none";
        }
    });
  });

</script>