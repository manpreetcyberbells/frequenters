<!-- /.card-header -->
<div class="card-body">
    <table class="table table-bordered table-striped datatableExample">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Business</th>
            <th>Event Name</th>
            <th>venue_name</th>
            <th>venue_location</th>
            <th>status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($events as $index => $event)
            <tr class="align-middle">
                <td>{{ $index + 1 }}</td>
                <td>{{ $event['event']->business->business_name ?? 'N/A' }}</td>
                <td>{{ $event['event']->event_name ?? 'N/A' }}</td>
                <td class="textCapital">{{ $event['event']->venue_name }}</td>
                <td class="textCapital">{{ $event['event']->venue_location }}</td>
                <td class="textCapital">{{ $event->status }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th style="width: 10px">#</th>
            <th>Business</th>
            <th>Event Name</th>
            <th>venue_name</th>
            <th>venue_location</th>
            <th>status</th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->
