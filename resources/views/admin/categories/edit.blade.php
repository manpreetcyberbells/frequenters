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
                      <div class="col-md-3 form-group">
                        <label for="validationActive" class="form-label">Active Icon</label>
                        <div class="input-group has-validation">
                          <img id="active_icon_preview" src="{{ isset($category) && $category->active_icon ? asset('storage/' . $category->active_icon) : '/media/dummy.png' }}" width="35">
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
                          <img id="inactive_icon_preview" src="{{ isset($category) && $category->inactive_icon ? asset('storage/' . $category->inactive_icon) : '/media/dummy.png' }}" width="35">                          
                          <input type="file" name="inactive_icon" class="form-control" id="inactive_icon">
                        </div>
                      </div>

                      <div class="col-md-3 form-group">
                        <label for="validationdark" class="form-label">Dark Mode Icon</label>
                          <div class="input-group has-validation">
                            <img id="darkmode_active_preview"  src="{{ isset($category) && $category->darkmode_active ? asset('storage/' . $category->darkmode_active) : '/media/dummy.png' }}" width="35">
                            <input type="file" name="darkmode_active" class="form-control" id="validationdark">
                          </div>
                      </div>

                      <div class="col-md-3 form-group">
                        <label for="validationdarkin" class="form-label">Dark Mode Inactive</label>
                          <div class="input-group has-validation">
                            <img id="darkmode_inactive_preview" src="{{ isset($category) && $category->darkmode_inactive ? asset('storage/' . $category->darkmode_inactive) : '/media/dummy.png' }}" width="35">
                            <input type="file" name="darkmode_inactive" class="form-control" id="validationdarkin">
                          </div>
                      </div>
                    </div>
                    
                    <div class="row g-3">
                      <div class="col-md-6 form-group">
                        <label class="form-label">Parent Category</label>
                        <select name="parent_id" id="parentCategory" class="form-control">
                          <option value="">Please select</option>
                          @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $cat->id == $category->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                          @endforeach
                          <option value="other" {{ old('parent_id') ? 'selected' : '' }}>Other</option>
                        </select>
                      </div>

                      <div class="col-md-6 form-group" id="customParentCategory" style="display:none;">
                        <label class="form-label">Parent Category Name</label>
                        <input type="text" name="new_parent_name" class="form-control" placeholder="Enter New Parent Category">
                      </div>

                      <div class="col-md-6 form-group" id="subcategoryGroup">
                        <label class="form-label">Sub Category</label>
                          <select name="child_id" id="childCategory"class="select2" multiple="multiple" data-placeholder="Select a State" style="width: 100%;">
                            <option value="">Select Sub Category</option>
                            @foreach ($sub_categories as $child)
                              <option value="{{ $child->id }}" {{ $child->id == $category->parent_id ? 'selected' : '' }} selected>{{ $child->name }}</option>
                            @endforeach
                            <option value="other" {{ old('parent_id') ? 'selected' : '' }}>Other</option>
                          </select>
                      </div>

                      <div class="col-md-6 form-group" id="customSubCategory" style="display:none;">
                        <label class="form-label">Sub Category Name</label>
                        <input type="text" name="new_sub_name" class="form-control" placeholder="Enter Sub Category">
                      </div>

                      <div class="col-md-6 form-group" id="grandchildCategory" style="display:none;">
                        <label class="form-label">Sub-Grand Category Name</label>
                        <input type="text" name="subGrand" class="form-control" placeholder="Enter Sub-Grand Category Name">
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