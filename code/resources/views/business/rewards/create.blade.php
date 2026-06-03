<?php
use Illuminate\Support\Facades\Auth;
?>
@extends('layouts.app')

@section('title', 'Customer Dashboard')

@section('content')

<body>
    <div class="container">
        <h1>Create Reward</h1>
        <form action="{{ route('business.storeReward') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="reward_name">Reward Name:</label>
                <input type="text" name="reward_name" id="reward_name" placeholder="Enter reward name" required>
            </div>

            <div class="form-group">
                <label for="points_required">Points Required:</label>
                <input type="number" name="points_required" id="points_required" placeholder="Enter points required" required>
            </div>

            <div class="form-group">
                <label for="valid_from">Valid From:</label>
                <input type="date" name="valid_from" id="valid_from">
            </div>

            <div class="form-group">
                <label for="valid_until">Valid Until:</label>
                <input type="date" name="valid_until" id="valid_until">
            </div>

            <div class="form-group">
                <label for="usage_limit">Usage Limit:</label>
                <input type="number" name="usage_limit" id="usage_limit" placeholder="Enter usage limit">
            </div>

            <button type="submit" class="search-button">Create Reward</button>
        </form>
    </div>

</body>
</html>
@endsection            