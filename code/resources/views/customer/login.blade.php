@extends('layouts.general')
@section('title', 'Login - Frequenters')
@section('content')
<head>   
    <!-- Include Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<header class="header"><h1>Frequenters</h1></header>
<div class="content">
        <div class="login-container">
            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="form-group mb-3">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                <div class="form-group mb-3">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            @if (session('error'))
                <div class="alert alert-danger mt-3">
                    {{ session('error') }}
                </div>
            @endif
            <div class="footer mt-3">
                <p>Don't have an account? <a href="#">Sign Up</a></p>
            </div>
        </div>
    </div>
</body>
</html>
@endsection