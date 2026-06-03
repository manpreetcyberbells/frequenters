<!-- /.card-header -->
<div class="card-body">
    <table class="table table-bordered table-striped datatableExample">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Customer</th>
            <th>Complaint to Business</th>
            <th>Description</th>
            <th>Status</th>
            <th>Resolved At</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        @foreach($complaints as $index => $complaint)
            <tr class="align-middle">
                <td>{{ $index + 1 }}</td>
                <td>{{ $complaint->customer->name ?? 'N/A' }}</td>
                <td>{{ $complaint->business->name ?? 'N/A' }}</td>
                <td>{{ $complaint->description }}</td>
                <td class="textCapital">{{ $complaint->status }}</td>
                <td>{{ $complaint->resolved_at ? \Carbon\Carbon::parse($complaint->resolved_at)->format('d M Y h:i A') : '-' }}</td>
                <td>{{ \Carbon\Carbon::parse($complaint->created_at)->format('d M Y h:i A') }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th style="width: 10px">#</th>
            <th>Customer</th>
            <th>Complaint to Business</th>
            <th>Description</th>
            <th>Status</th>
            <th>ResolvedAt</th>
            <th>CreatedAt</th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->
