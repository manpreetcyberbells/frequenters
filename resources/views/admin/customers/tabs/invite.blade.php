<!-- /.card-header -->
<div class="card-body">
    <table class="table table-bordered table-striped datatableExample">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Customer</th>
            <th>Friend Email</th>
            <th>Status</th>
            <!-- <th>Role</th> -->
            <th>Invitation Sent On</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invitedFriends as $index => $invite)
            <tr class="align-middle">
                <td>{{ $index + 1 }}</td>
                <td>{{ $invite->customer->name ?? 'N/A' }}</td>
                <td>{{ $invite->email ?? 'N/A' }}</td>
                <td class="textCapital">{{ $invite->status }}</td>
                <!-- <td class="textCapital">{{ $invite->role }}</td> -->
                <td>{{ \Carbon\Carbon::parse($invite->created_at)->format('d M Y h:i A') }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th style="width: 10px">#</th>
            <th>Customer</th>
            <th>Friend Email</th>
            <th>Status</th>
            <!-- <th>Role</th> -->
            <th>Invitation Sent On</th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->
