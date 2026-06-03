<div class="container">
    <h1>{{ $business->business_name }}</h1>
    <p>{{ $business->industry_type }}</p>
    <p>{{ $business->description }}</p>

    <h2>Rewards</h2>
    <ul>
        @foreach ($rewards as $reward)
            <li>
                <a href="{{ route('customer.rewards.qr', ['reward_id' => $reward->reward_id]) }}">
                    {{ $reward->reward_name }} ({{ $reward->points_required }} points)
                </a>
            </li>
        @endforeach
    </ul>

    <h2>Contests</h2>
    <ul>
        @foreach ($contests as $contest)
            <li>{{ $contest->question }} - Ends {{ $contest->end_time }}</li>
        @endforeach
    </ul>
</div>