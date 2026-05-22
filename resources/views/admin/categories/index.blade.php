@extends('admin.layouts.master')
@section('title', 'Category Listing')
@section('content')

<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Category</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
              <li class="breadcrumb-item active">Category</li>
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
                <h3 class="card-title">Category Listing</h3>
                <div class="card-tools">
                  <a href="{{ route('categories.create') }}" ><i class="fa fa-plus-square" aria-hidden="true"></i>Create Category</a>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th style="width: 10px">#</th>
                    <th>Name</th>
                    <th>Sub Categories</th>
                    <th>SubGrand Categories</th>
                    <th>Active</th>
                    <th>Inactive</th>
                    <th>Darkmode Active</th>
                    <th>Darkmode Inactive</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    @foreach ($categories as $index => $category)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $category->name }}</strong></td> <!-- Parent Category -->
                            <td>
                                @if ($category->children->count())
                                    <ul class="mb-0 pl-3">
                                        @foreach ($category->children as $subcategory)
                                            <li>{{ $subcategory->name }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <em>No Subcategories</em>
                                @endif
                            </td>
                            <td>
                              @if ($category->children->count())
                                  <ul class="mb-0 pl-3">
                                      @foreach ($category->children as $child)
                                          @if ($child->children->count())
                                              <strong>{{ $child->name }}
                                              @foreach ($child->children as $grandcategory)
                                                </strong> <li>{{ $grandcategory->name }}</li>
                                              @endforeach
                                          @endif
                                      @endforeach
                                  </ul>
                              @else
                                  <em>No Subcategories</em>
                              @endif
                          </td>

                            <td>
                                @if ($category->active_icon)
                                    <img src="{{ asset('storage/' . $category->active_icon) }}" width="50">
                                @endif
                            </td>
                            <td>
                                @if ($category->inactive_icon)
                                    <img src="{{ asset('storage/' . $category->inactive_icon) }}" width="50">
                                @endif
                            </td>
                            <td>
                                @if ($category->darkmode_active)
                                    <img src="{{ asset('storage/' . $category->darkmode_active) }}" width="50">
                                @endif
                            </td>
                            <td>
                                @if ($category->darkmode_inactive)
                                    <img src="{{ asset('storage/' . $category->darkmode_inactive) }}" width="50">
                                @endif
                            </td>
                            <td>
                              <!-- Edit Button -->
                              <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                              <i class="nav-icon fas fa-edit"></i>
                              </a>

                              <!-- Delete Button (with confirmation) -->
                              <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="d-inline">
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
                    <th>Sub Categories</th>
                    <th>SubGrand Categories</th>
                    <th>Active</th>
                    <th>Inactive</th>
                    <th>Darkmode Active</th>
                    <th>Darkmode Inactive</th>
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