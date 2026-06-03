@extends('layouts.app')

@section('title', 'Manage Check-In Points')

@section('content')
<div class="container">
    <h1>Manage Check-In Points</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('business.updateCheckinPoints') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="points_per_checkin">Points Per Check-In:</label>
            <input type="number" name="points_per_checkin" id="points_per_checkin" class="form-control" value="{{ $business->points_per_checkin }}" required>
            <small class="form-text text-muted">Minimum: 10 points</small>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
