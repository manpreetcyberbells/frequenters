@extends('layouts.general')
@section('title', 'Frequenters - Coming Soon')
@section('content')
    <div class="content">
        <h1>Frequenters</h1>
        <p>Coming Soon! Revolutionizing the way businesses connect with customers.</p>
        <form action="signup_handler.php" method="POST">
            <input type="text" name="contact_fname" placeholder="Your First Name" required><input type="text" name="contact_lname" placeholder="Your Last Name" required>
            <input type="text" name="business_name" placeholder="Business Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="text" name="hidden_field" style="display:none;">
            <button type="submit">Join the Waitlist</button>
        </form>
        <?php 
        if(isset($_GET['m'])){
        	echo "<br>Thank you for signing up! We’ll keep you updated on our launch.";
        }
        ?>
    </div>
    <div class="loginLink">
        <p>Already a member? <a href="./login">Sign In</a></p>
    </div>

     <!-- Sticky Footer -->
     <footer class="footer">
        <p>&copy; {{ date('Y') }} Frequenters. All rights reserved.</p>
    </footer>
</body>
</html>
@endsection