<!-- /.card-header -->
<div class="card-body">
    <table id="example5" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Customer</th>
            <th>Wishlist Business</th>
            <th>Date Time</th>
        </tr>
    </thead>
    <tbody>
        @foreach($wishlists as $index => $list)
            <tr class="align-middle">
                <td>{{ $index + 1 }}</td>
                <td>{{ $list->customer->name ?? 'N/A' }}</td>
                <td>{{ $list->business->name ?? 'N/A' }}</td>
                <td>{{ \Carbon\Carbon::parse($list->created_at)->format('d M Y h:i A') }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
        <th style="width: 10px">#</th>
        <th>Customer</th>
        <th>Wishlist Business</th>
        <th>Date Time</th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->