<?php
    $events = $business['businessEvent'];
?>

<!-- /.card-header -->
<div class="card-body">
    <table id="example2" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Banner</th>
            <th>Business</th>
            <th>Title</th>
            <th>Venue Name</th>
            <th>Venue Location</th>
            <th>Capacity</th>
            <th>Start Date</th>
            <th>Start Time</th>
            <th>End Date</th>
            <th>End Time</th>
            <th>Target Tier</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($events as $index => $event)
        <tr class="align-middle">
            <td>{{ $index + 1 }}</td>
            <td><img src="{{ $event->event_banner }}" style="height: 50px; width: 50px;"/></td>
            <td>{{ $event->businessDetail->name }}</td>
            <td>{{ $event->event_name }}</td>
            <td>{{ $event->venue_name }}</td>
            <td>{{ $event->venue_location }}</td>
            <td>{{ $event->capacity }}</td>
            <td>{{ $event->start_date }}</td>
            <td>{{ $event->start_time }}</td>
            <td>{{ $event->end_date }}</td>
            <td>{{ $event->end_time }}</td>
            <td>{{ $event->target_tier }}</td>
            <td> <span class="badge @if($event->status == 'published') badge-success @else badge-secondary @endif">{{ $event->status }}</span></td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th style="width: 10px">#</th>
            <th>Banner</th>
            <th>Business</th>
            <th>Title</th>
            <th>Venue Name</th>
            <th>Venue Location</th>
            <th>Capacity</th>
            <th>Start Date</th>
            <th>Start Time</th>
            <th>End Date</th>
            <th>End Time</th>
            <th>Target Tier</th>
            <th>Status</th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->