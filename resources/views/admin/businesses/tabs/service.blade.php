<?php
    $services = $business['businessService'];
?>

<!-- /.card-header -->
<div class="card-body">
    <table class="table table-bordered table-striped datatableExample">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Photo</th>
            <th>Business</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Duration</th>
            <th>Description</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($services as $index => $service)
            <tr class="align-middle">
                <td>{{ $index + 1 }}</td>
                <td><img src="{{ $service->photo }}" alt="" style="width:80px; height:80px;"></td>
                <td> {{ $service->businessInfo->name }} </td>
                <td> {{ $service->name }} </td>
                <td> {{ $service->name }} </td>
                <td> {{ $service->price }} </td>
                <td> {{ $service->duration }} </td>
                <td> {{ $service->description }} </td>
                <td>
                <!-- Edit Button -->
                <a href="{{ route('services.edit', $service->id) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                    <i class="nav-icon fas fa-edit"></i>
                </a>
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th style="width: 10px">#</th>
            <th>Photo</th>
            <th>Business</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Duration</th>
            <th>Description</th>
            <th></th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->