<?php
    $rewards = $business['businessReward'];
?>

<!-- /.card-header -->
<div class="card-body">
    <table class="table table-bordered table-striped datatableExample">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Business</th>
            <th>Reward Name</th>
            <th>Points Required</th>
            <th>Effective From</th>
            <th>Effective To</th>
            <th></th>
        </tr>
    </thead>
    <tbody>  
    @foreach($rewards as $index => $reward)
            <tr class="align-middle">
                <td>{{ $index + 1 }}</td>
                <td>{{$reward->business->name}}</td>
                <td>{{$reward->reward_name}}</td>
                <td>{{$reward->points_required}}</td>
                <td>{{$reward->valid_from}}</td>
                <td>{{$reward->valid_until}}</td>
                <td>
                <!-- View Business -->
                <a href="{{ route('reward.reedemption.history', $reward->id) }}" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Edit">
                    <i class="fa fa-history"></i>
                </a>
                </td>
            </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th style="width: 10px">#</th>
            <th>Business</th>
            <th>Reward Name</th>
            <th>Points</th>
            <th>Effective From</th>
            <th>Effective To</th>
            <th></th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->