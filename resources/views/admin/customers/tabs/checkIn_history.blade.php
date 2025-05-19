<!-- /.card-header -->
<div class="card-body">
    <table id="example2" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Check-In Time</th>
            <th>Customer Name</th>
            <th>Business Name</th>
        </tr>
    </thead>
    <tbody>
        @foreach($customer->customerCheckIn as $index => $checkIn)
            <tr class="align-middle">
                <td>{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($checkIn->check_in_time)->format('d M Y h:i A') }}</td>
                <td>{{ $checkIn->customer->name ?? 'N/A' }}</td>
                <td>{{ $checkIn->business->name ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
        <th style="width: 10px">#</th>
        <th>Check-In Time</th>
        <th>Customer Name</th>
        <th>Business Name</th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->
