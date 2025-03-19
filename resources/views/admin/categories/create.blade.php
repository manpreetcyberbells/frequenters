@extends('admin.layouts.master')
@section('title', 'Category Create')
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
              <li class="breadcrumb-item active">Category Create</li>
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
                <div class="card-header"><div class="card-title">Category Form</div></div>
                <form action="{{ route('categories.store') }}" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                  @csrf
                  <div class="card-body">
                    <!--begin::Row-->
                    <div class="row g-3">
                      <div class="col-md-6 form-group">
                        <label for="validationCategoryUsername" class="form-label">Category Name</label>
                        <div class="input-group has-validation">
                          <input
                            type="text"
                            name="name"
                            class="form-control"
                            id="validationCategoryUsername"
                            required
                            placeholder="Enter Category name"
                          />
                        </div>
                      </div>

                      <div class="col-md-6 form-group">
                          <label class="form-label">Parent Category (Optional for main category):</label>
                          <select name="parent_id" class="form-control">
                            <option value="">None (Main Category)</option>
                            @foreach ($categories as $category)
                              <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                          </select>
                      </div>

                      <div class="col-md-3 form-group">
                        <label for="validationActive" class="form-label">Active Icon</label>
                        <div class="input-group has-validation">
                            <img id="active_icon_preview" src="/media/dummy.png" width="35">
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
                          <img id="inactive_icon_preview" src="/media/dummy.png" width="35">
                          <input type="file" name="inactive_icon" class="form-control" id="inactive_icon">
                        </div>
                      </div>

                      <div class="col-md-3 form-group">
                          <label for="validationdark" class="form-label">Dark Mode Icon</label>
                          <div class="input-group has-validation">
                            <img id="darkmode_active_preview" src="/media/dummy.png" width="35">
                            <input type="file" name="darkmode_active" class="form-control" id="validationdark">
                          </div>
                      </div>

                      <div class="col-md-3 form-group">
                          <label for="validationdarkin" class="form-label">Dark Mode Icon</label>
                          <div class="input-group has-validation">
                            <img id="darkmode_inactive_preview" src="/media/dummy.png" width="35">
                            <input type="file" name="darkmode_inactive" class="form-control" id="validationdarkin">
                          </div>
                      </div>

                    </div>
                  </div>

                  <div class="card-footer">
                    <button type="submit" class="btn btn-info">Save Category</button>
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