<!-- /.card-header -->
<div class="card-body">
    <table class="table table-bordered table-striped datatableExample">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Customer</th>
            <th>Business</th>
            <th>Rating</th>
            <th>Review</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reviews as $index => $review)
            <tr class="align-middle">
                <td>{{ $index + 1 }}</td>
                <td>{{ $review->customer->name ?? 'N/A' }}</td>
                <td>{{ $review->business->name ?? 'N/A' }}</td>
                <td >{{ $review->rating }}</td>
                <td >{{ $review->review }}</td>
                <td>{{ \Carbon\Carbon::parse($review->created_at)->format('d M Y h:i A') }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th style="width: 10px">#</th>
            <th>Customer</th>
            <th>Business</th>
            <th>Rating</th>
            <th>Review</th>
            <th>Created At</th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->
