<?php
    $menus = $business['businessMenu'];
    // echo"<pre>"; print_r($menus);  die;
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
            <th>Availability Status</th>
            <th>Description</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($menus as $index => $menu)
            <tr class="align-middle">
                <td>{{ $index + 1 }}</td>
                <td><img src="{{$menu->photo}}" alt="" style="width:100px; height:100px;"></td>
                <td> {{@$menu->business->business_name}} </td>
                <td> {{$menu->name}} </td>
                <td> {{$menu->category_id}} </td>
                <td> {{$menu->price}} </td>
                <td> <span class="badge @if($menu->availability_status == 1) badge-primary @else badge-danger @endif"> 
                @if($menu->availability_status == 1)
                    Available
                @else
                    N/A
                @endif
                </td>
                <td> {{$menu->description}} </td>
                <td>
                <!-- Edit Button -->
                <a href="{{ route('menus.edit', $menu->id) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
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
            <th>Availability Status</th>
            <th>Description</th>
            <th></th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->
