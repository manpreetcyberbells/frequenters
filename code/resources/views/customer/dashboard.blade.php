@extends('layouts.app')

@section('title', 'Customer Dashboard')

@section('content')

<body>
    <div class="container">
        <div class="profile-info">
            <h2>Welcome, {{ $customer->name }}</h2>
            <p><strong>Email:</strong> {{ $customer->email }}</p>
            <p><strong>Role:</strong> {{ Auth::user()->role }}</p>
        </div>
        <div class="actions">
            <a href="#" 
               class="btn btn-primary"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
               Logout
            </a>

            <a href="#" class="btn btn-secondary">View Profile</a>
        </div>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
        <a href="./qr-code">View QR Code</a>
    </div>
</body>
</html>
@endsection