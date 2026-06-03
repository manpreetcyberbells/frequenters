<!-- /.card-header -->
<div class="card-body">
    <table class="table table-bordered table-striped datatableExample">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Search_term</th>
            <th>Customer Name</th>
            <th>Date Time</th>
        </tr>
    </thead>
    <tbody>
        @foreach($history as $index => $h)
            <tr class="align-middle">
                <td>{{ $index + 1 }}</td>
                <td>{{ $h->search_term ?? 'N/A' }}</td>
                <td>{{ $h->customer->name ?? 'N/A' }}</td>
                <td>{{ \Carbon\Carbon::parse($h->created_at)->format('d M Y h:i A') }}</td>
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