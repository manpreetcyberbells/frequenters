<?php
    $offers = $business['businessOffer'];
?>

<!-- /.card-header -->
<div class="card-body">
    <table class="table table-bordered table-striped datatableExample">
    <thead>
        <tr>
            <th style="width: 10px">#</th>
            <th>Banner</th>
            <th>Business</th>
            <th>Title</th>
            <th>Discount Percentage</th>
            <th>Discount Cap</th>
            <th>Applicable Users</th>
            <th>Offer Time</th>
            <th>Start Date</th>
            <th>Start Time</th>
            <th>End Date</th>
            <th>End Time</th>
            <th>Applicable Days</th>
            <th>Min Order Value</th>
            <th>Tagline</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($offers as $index => $offer)
        <tr class="align-middle">
            <td>{{ $index + 1 }}</td>
            <td><img src="{{ $offer->banner_image }}" style="height: 50px; width: 50px;"/></td>
            <td>{{ $offer->business->name }}</td>
            <td>{{ $offer->title }}</td>
            <td>{{ $offer->discount_percentage }}</td>
            <td>{{ $offer->discount_cap }}</td>
            <td> <span class="badge @if($offer->applicable_users == 'regular') badge-success @elseif($offer->applicable_users == 'new') badge-primary @else badge-dark @endif">{{ $offer->applicable_users }}</span></td>
            <td>{{ $offer->offer_time }}</td>
            <td>{{ $offer->campaign_start_date }}</td>
            <td>{{ $offer->campaign_start_time }}</td>
            <td>{{ $offer->campaign_end_date }}</td>
            <td>{{ $offer->campaign_end_time }}</td>
            <td>{{ $offer->applicable_days }}</td>
            <td>{{ $offer->min_order_value }}</td>
            <td>{{ $offer->terms_and_conditions }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
        <th style="width: 10px">#</th>
            <th>Banner</th>
            <th>Business</th>
            <th>Title</th>
            <th>Discount Percentage</th>
            <th>Discount Cap</th>
            <th>Applicable Users</th>
            <th>Offer Time</th>
            <th>Start Date</th>
            <th>Start Time</th>
            <th>End Date</th>
            <th>End Time</th>
            <th>Applicable Days</th>
            <th>Min Order Value</th>
            <th>Tagline</th>
        </tr>
    </tfoot>
    </table>
</div>
<!-- /.card-body -->