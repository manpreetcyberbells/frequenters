@extends('admin.layouts.master')
@section('title', 'Manage Business Points')
@section('content')

<header class="header">
    <nav class="headerNav">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a> | <a href="manual-checkin">Manual Check-In</a> | <a href="businesspoints">Business Points</a>
        <form action="{{ route('admin.logout') }}" method="POST" style="display: inline;">
               @csrf
               <button type="submit">Logout</button>
        </form>
    </nav>
</header>
<div class="container">
    @if (session('success'))
    <div style="color: green; margin-bottom: 20px; font-weight: bold;">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div style="color: red; margin-bottom: 20px; font-weight: bold;">
        {{ session('error') }}
    </div>
@endif
<div class="container">
    <div class="profile-info">
        <h2>Add Reward Points to a Single Business</h2>
        <form id="grant-reward-form" method="POST">
            @csrf
            <label for="business_id">Select Business:</label>
            <select name="business_id" id="business_id" required onchange="updateFormAction(this)">
                <option value="">-- Select Business --</option>
                @foreach ($businessList as $business)
                    <option value="{{ $business->business_id }}">{{ $business->business_name }}</option>
                @endforeach
            </select>

            <label for="points">Reward Points:</label>
            <input type="number" name="points" id="points" placeholder="Enter reward points" required>

            <button type="submit" style="margin-top: 10px;">Grant Points</button>
        </form><br><br>
        <h2>Add Reward Points to a All Businesses</h2>
        <form action="{{ route('admin.addPointsToAll') }}" method="POST">
            @csrf
            <label for="points">Reward Points for All Businesses:</label>
            <input type="number" name="points" id="points" placeholder="Enter reward points" required>
            <button type="submit" style="margin-top: 10px;">Add Points to All</button>
        </form>
    </div>
</div>

<script>
    function updateFormAction(selectElement) {
        const form = document.getElementById('grant-reward-form');
        const selectedBusinessId = selectElement.value;

        if (selectedBusinessId) {
            // Update form action dynamically
            form.setAttribute('action', `/admin/business/${selectedBusinessId}/grant-checkin-points`);
        } else {
            // Remove action if no business is selected
            form.removeAttribute('action');
        }
    }
</script>

@endsection