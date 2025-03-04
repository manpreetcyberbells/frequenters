@extends('admin.layouts.master')
@section('title', 'Category Edit')
@section('content')

 <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Category</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
              <li class="breadcrumb-item active">Category Edit</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="app-content">
            @if ($errors->any())
              <div class="alert alert-danger">
                <ul>
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif
            <div class="container-fluid">
              <div class="card card-info card-outline mb-4">
                <div class="card-header"><div class="card-title">Category Edit</div></div>
                <form action="{{ route('categories.update', $category->id) }}" method="POST" enctype="multipart/form-data">
                  @csrf
                  @method('PUT')
                  <div class="card-body">
                    <!--begin::Row-->
                    <div class="row g-3">
                      <div class="col-md-6 form-group">
                        <label for="validationCategoryUsername" class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                      </div>
                      
                      <div class="col-md-6 form-group">
                        <label class="form-label">Parent Category (Optional)</label>
                        <select name="parent_id" class="form-control">
                          <option value="">None (Parent Category)</option>
                          @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $cat->id == $category->parent_id ? 'selected' : '' }}>
                              {{ $cat->name }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    

                    <div class="col-md-3 form-group">
                      <label for="validationActive" class="form-label">Active Icon</label>
                        <div class="input-group has-validation">
                          @if ($category->active_icon)
                            <img id="active_icon_preview" src="{{ asset('storage/' . $category->active_icon) }}" width="50">
                          @endif
                          <input
                            type="file"
                            name="active_icon"
                            class="form-control"
                            id="active_icon"
                            required
                          />
                        </div>
                    </div>

                    <div class="col-md-3 form-group">
                        <label for="validationInactive" class="form-label">Inactive Icon</label>
                        <div class="input-group has-validation">
                          @if ($category->inactive_icon)
                            <img id="inactive_icon_preview" src="{{ asset('storage/' . $category->inactive_icon) }}" width="50">
                          @endif
                          <input type="file" name="inactive_icon" class="form-control" id="inactive_icon">
                        </div>
                    </div>

                    <div class="col-md-3 form-group">
                      <label for="validationdark" class="form-label">Dark Mode Icon</label>
                        <div class="input-group has-validation">
                          @if ($category->darkmode_active)
                            <img id="darkmode_active_preview"  src="{{ asset('storage/' . $category->darkmode_active) }}" width="50">
                          @endif
                          <input type="file" name="darkmode_active" class="form-control" id="validationdark">
                        </div>
                    </div>

                    <div class="col-md-3 form-group">
                      <label for="validationdarkin" class="form-label">Dark Mode Icon</label>
                        <div class="input-group has-validation">
                          @if ($category->darkmode_inactive)
                            <img id="darkmode_inactive_preview" src="{{ asset('storage/' . $category->darkmode_inactive) }}" width="50">
                          @endif
                          <input type="file" name="darkmode_inactive" class="form-control" id="validationdarkin">
                        </div>
                    </div>

                  </div>
                  <div class="card-footer">
                    <button type="submit" class="btn btn-info">Update</button>
                  </div>
               </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
@endsection