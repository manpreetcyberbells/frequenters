@extends('layouts.app')

@section('title', 'Customer Dashboard')

@section('content')

<body>
    <div class="container">
        <h1>Redeem Your Reward</h1>
        <p>Present this QR code to the business to redeem your reward:</p>
        {{ $qrCode }}
    </div>
</body>
</html>
@endsection