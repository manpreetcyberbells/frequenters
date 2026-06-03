<meta name="csrf-token" content="{{ csrf_token() }}">
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    @vite('resources/css/app.css') <!-- Optional: Include your CSS -->
    <style>
        input, select, button {
            appearance: none; /* Reset for cross-browser compatibility */
            -webkit-appearance: none;
            -moz-appearance: none;
        }
    </style>
</head>
<body>
    <form id="registrationForm" method="POST" action="/api/register">
        @csrf
        <div>
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" required>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div>
            <label for="password_confirmation">Confirm Password:</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required>
        </div>
        <div>
            <label for="zip_code">ZIP Code:</label>
            <input type="text" name="zip_code" id="zip_code" required maxlength="5">
        </div>
        <div>
            <label for="city_id">City:</label>
            <select name="city_id" id="city_id" disabled required>
                <option value="">Select your city</option>
            </select>
        </div>
        <button type="submit">Register</button>
    </form>

    <!-- Include JavaScript -->
    @vite('resources/js/register.js')
</body>
</html>
