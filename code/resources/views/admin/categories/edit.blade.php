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

                <div class="card-body">
<div class="card mt-4 shadow-sm border-0">
                      <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i>Category Hierarchy</h5>
                      </div>
                      <div class="card-body">

                        <div class="mb-4">
                          <strong class="text-muted">Parent Category:</strong>
                          <span class="badge bg-primary fs-6 ms-2">{{ $category->name }}</span>
                        </div>

                        @forelse($structure as $block)
                          <div class="mb-4 p-3 bg-light border rounded-3 shadow-sm">

                            <div class="d-flex align-items-center mb-2">
                              <i class="fas fa-folder-open text-secondary me-2"></i>
                              <strong class="me-1">Sub Category:</strong>
                              <span class="badge bg-secondary">{{ $block['sub']->name }}</span>
                            </div>

                            @if(count($block['grand']))
                              <div class="ps-4">
                                <div class="mb-1 text-muted fw-bold"><i class="fas fa-sitemap me-1"></i>Sub-Grand Categories:</div>
                                <ul class="list-unstyled mb-0">
                                  @foreach($block['grand'] as $grand)
                                    <li class="mb-1 d-flex justify-content-between align-items-center">
                                      <span>
                                        <i class="fas fa-angle-right text-muted me-1"></i> {{ $grand->name }}
                                      </span>
                                      <form action="{{ route('categories.subgrand.destroy', $grand->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this Sub-Grand Category?')" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Delete">
                                          <i class="fas fa-trash-alt"></i>
                                        </button>
                                      </form>
                                    </li>
                                  @endforeach
                                </ul>
                              </div>
                            @else
                              <div class="ps-4 text-muted fst-italic">
                                <i class="fas fa-circle-info me-1"></i> No Sub-Grand Categories
                              </div>
                            @endif

                          </div>
                        @empty
                          <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle me-1"></i> No subcategories found for this category.
                          </div>
                        @endforelse
                      </div>
                    </div>
                </div>
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
                        @php
                          $isMainCategory = is_null($category->parent_id);
                        @endphp 
                        <select name="parent_id" id="parentCategory" class="form-control" {{ $isMainCategory ? 'disabled' : '' }}>
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
                          <select name="child_id" id="childCategory" form-control select2 data-placeholder="Select a State" style="width: 100%;">
                            <option value="">Select Sub Category</option>
                            @foreach ($sub_categories as $child)
                              <option value="{{ $child->id }}">{{ $child->name }}</option>
                            @endforeach
                            <option value="other" {{ old('parent_id') ? 'selected' : '' }}>Other</option>
                          </select>
                      </div>

                      <div class="col-md-6 form-group" id="customSubCategory" style="display:none;">
                        <label class="form-label">Sub Category Name</label>
                        <input type="text" name="new_sub_name" class="form-control" placeholder="Enter Sub Category">
                      </div>

                      <div class="col-md-6 form-group" id="grandchildCategory">
                        <label class="form-label">Sub-Grand Category Name</label>
                          <select name="subGrand" id="subGrand" class="form-control" data-placeholder="Select a State" style="width: 100%;">
                            <option value="">Select Sub-Grand Category</option>
                            @foreach ($children as $child)
                              <option value="{{ $child->id }}">{{ $child->name }}</option>
                            @endforeach
                          </select>
                      </div>

                      <div class="col-md-6 form-group" id="customGrandCategory" style="display:none;">
                        <label class="form-label">Sub-Grand Category Name</label>
                        <input type="text" name="new_grand_name" class="form-control" placeholder="Enter New Grand Category">
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