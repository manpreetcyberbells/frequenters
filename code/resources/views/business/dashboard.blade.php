<?php
use Illuminate\Support\Facades\Auth;
?>
@extends('layouts.app')

@section('title', 'Customer Dashboard')

@section('content')

<body>
    <div class="container mt-5">
        <h2>Welcome to Your Business Dashboard, {{ $user->name }}</h2>
        <p>Email: {{ $user->email }}</p>
        <div class="profile-info">
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Role:</strong> {{ Auth::user()->role }}</p>
        </div>
        
        <div class="business-points" style="margin-top: 20px;">
            <h3>Your Points Summary</h3>
            <p><strong>Available Points:</strong> {{ $business->monthly_points_available }}</p>
            <p><strong>Total Points Given:</strong> {{ $business->points_given }}</p>
            <p><strong>Total Points Redeemed:</strong> {{ $business->points_redeemed }}</p>
            <p><strong>Points Per Check-In:</strong> {{ $business->points_per_checkin }}</p>

        </div>

        <h3>Your Rewards</h3>
        <table>
            <thead>
                <tr>
                    <th>Reward Name</th>
                    <th>Points Required</th>
                    <th>Validity</th>
                    <th>Usage Limit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rewards as $reward)
                <tr>
                    <td>{{ $reward->reward_name }}</td>
                    <td>{{ $reward->points_required }}</td>
                    <td>{{ $reward->valid_from }} to {{ $reward->valid_until }}</td>
                    <td>{{ $reward->usage_limit }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="create-reward" style="margin-top: 40px;">
            <a href="{{ route('business.createReward') }}" class="btn btn-primary">Create a Reward</a>
        </div>
        
        <div class="actions">
            <a href="#" 
               class="btn btn-danger"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
               Logout
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
        <a href="./qr-code">View QR Code</a>
    </div>
</body>
</html>
@endsection