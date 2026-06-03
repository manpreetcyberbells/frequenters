<!-- /.card-header -->
<div class="card-body">
    <table class="table table-bordered table-striped datatableExample">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Redeem Time</th>
            <th>Customer Name</th>
            <th>Business Name</th>
            <th>Points Redeemed</th>
            <th>Status</th>
            <th>Redemption Code</th>
            <th>Redemption Method</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rewardRedemptions as $index => $redem)
            <tr class="align-middle">
                <td>{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($redem->redeemed_at)->format('d M Y h:i A') }}</td>
                <td>{{ $redem->customer->name ?? 'N/A' }}</td>
                <td>{{ $redem->business->name ?? 'N/A' }}</td>
                <td>{{ $redem->points_redeemed ?? '0' }}</td>
                <td>{{ $redem->status ?? 'N/A' }}</td>
                <td>{{ $redem->redemption_code ?? 'N/A' }}</td>
                <td>{{ $redem->redemption_method ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
        <th style="width: 10px">#</th>
        <th>Redeem Time</th>
        <th>Customer Name</th>
        <th>Business Name</th>
        <th>Points Redeemed</th>
        <th>Status</th>
        <th>Redemption Code</th>
        <th>Redemption Method</th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->
