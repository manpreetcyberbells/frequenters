<!-- /.card-header -->
<div class="card-body">
    <table class="table table-bordered table-striped datatableExample">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Business</th>
            <th>Contest Title</th>
            <th>Status</th>
            <th>Reward Type</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($contests as $index => $contest)
            <tr class="align-middle">
                <td>{{ $index + 1 }}</td>
                <td>{{ $contest['contest']->businessDetail->name ?? 'N/A' }}</td>
                <td>{{ $contest['contest']->name ?? 'N/A' }}</td>
                <td class="textCapital">{{ $contest['contest']->status }}</td>
                <td class="textCapital">{{ $contest['contest']->reward_type }}</td>
                <td>
                    <a href="{{ route('contests.show', $contest['contest']->id) }}" target="_blank" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Edit">
                        <i class="nav-icon fas fa-eye"></i>
                    </a>
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th style="width: 10px">#</th>
            <th>Business</th>
            <th>Contest Title</th>
            <th>Status</th>
            <th>Reward Type</th>
            <th></th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->
