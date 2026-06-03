<!DOCTYPE html>
<html>
<head>
    <title>Check-In QR Code</title>
</head>
<body>
    <h2>Your Check-In QR Code</h2>
    <div align="center">
        {!! $qrCode !!} <!-- Render QR Code -->
    
        <br>Text Code (Fallback):<br>
        <textarea rows="10" cols="40">{{ $textCode }}</textarea>
    </div>
</body>
</html>