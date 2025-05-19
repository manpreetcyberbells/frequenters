<?php
    $contents = $business['businessContest'];
?>

<!-- /.card-header -->
<div class="card-body">
    <table id="example2" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Banner</th>
            <th>Business</th>
            <th>Contest</th>
            <th>Status</th>
            <th>Capacity</th>
            <th>Start Date</th>
            <th>Start Time</th>
            <th>End Date</th>
            <th>End Time</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($contents as $index => $content)
            <tr class="align-middle">
            <td>{{ $index + 1 }}</td>
            <td><img src="{{ $content->banner_image }}" style="height: 50px; width: 50px;"/></td>
            <td>{{ @$content->businessDetail->name }}</td>
            <td>{{ $content->name }}</td>
            <td><span class="badge @if($content->status == 'open') badge-primary @elseif($content->status == 'close') badge-danger @else badge-success @endif">{{ $content->status }}</span></td>
            <td>{{ $content->max_participants }}</td>
            <td>{{ $content->start_date }}</td>
            <td>{{ $content->start_time }}</td>
            <td>{{ $content->end_date }}</td>
            <td>{{ $content->end_time }}</td>
            <td>
                <!-- View Business -->
                <a href="{{ route('contests.show', $content->id) }}" target="_blank" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Edit">
                    <i class="nav-icon fas fa-eye"></i>
                </a>
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
        <th style="width: 10px">#</th>
            <th>Banner</th>
            <th>Business</th>
            <th>Contest</th>
            <th>Status</th>
            <th>Capacity</th>
            <th>Start Date</th>
            <th>Start Time</th>
            <th>End Date</th>
            <th>End Time</th>
            <th></th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->