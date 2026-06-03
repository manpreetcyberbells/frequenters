<div class="container">
    <h1>Search Businesses</h1>
    <form action="{{ route('customer.search') }}" method="GET">
        <input type="text" name="query" placeholder="Search businesses..." value="{{ $query }}">
        <button type="submit">Search</button>
    </form>

    <ul>
        @foreach ($businesses as $business)
            <li>
                <a href="{{ route('customer.business.profile', ['business_id' => $business->business_id]) }}">
                    {{ $business->business_name }} ({{ $business->industry_type }})
                </a>
            </li>
        @endforeach
    </ul>

    {{ $businesses->links() }}
</div>