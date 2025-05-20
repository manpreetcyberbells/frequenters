<?php
    // echo"<pre>"; print_r($staffMembers);  die;
?>

<!-- /.card-header -->
<div class="card-body">
    <table class="table table-bordered table-striped datatableExample">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Business</th>
            <th>Employee Name</th>
            <th>Employee Email</th>
            <th>Employee Phone</th>
            <th>Employee Status</th>
            <th>Employee Role</th>
            <th>Providers</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($staffMembers as $index => $staff)
            <tr class="align-middle">
                <td> {{ $index + 1 }}</td>
                <td> {{$staff->parent_business}}</td>
                <td> {{$staff->name}} </td>
                <td> {{$staff->email}} </td>
                <td> {{$staff->phone}} </td>
                <td> <span class="badge @if($staff->status == 'active') badge-success @elseif ($staff->status == 'pending') badge-success @else badge-danger @endif"> {{$staff->status}} </span></td>
                <td> <span class="badge @if($staff->role == 'manager') badge-primary @else badge-info @endif">{{$staff->role}} </span></td>
                <td> <span class="badge @if($staff->providers == 'email') bg-navy @elseif($staff->providers == 'facebook') badge-primary @elseif($staff->providers == 'google') badge-info @elseif($staff->providers == 'apple') badge-gray @else badge-lime @endif"> {{$staff->providers}} </td>
                <td>
                <!-- Edit Button -->
                <a href="{{ route('customers.show', $staff->id) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                    <i class="nav-icon fas fa-eye"></i>
                </a>
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th style="width: 10px">#</th>
            <th>Business</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Role</th>
            <th>Providers</th>
            <th></th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->