<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Pasword - Frequenters</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
</head>
<body style="margin:0; padding:0;  font-family: 'Inter', sans-serif; background-color:#f9f9f9;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td align="center" style="padding: 30px 10px;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
			
          <tr>
            <td align="center" style="padding: 20px;">
              <img src="{{ asset('storage/email/images/logo.png') }}" alt="Frequenters Logo" width="150" style="display:block; margin-bottom: 10px;" />
            </td>
          </tr>
		  
          <tr>
            <td style="padding: 0 30px 20px 30px; color: #444;">
              <p style="margin: 0 0 15px 0;">Hi <strong>{{$name}}</strong>,</p>
              <p style="margin: 0 0 15px 0;">
                Forgot your Frequenters password? Don't worry-Happens to the best of us- especially before coffee.☕
              </p>
              <p style="margin: 0 0 15px 0;">
                Click below to reset and get back to racking up those check-in points:
              </p>
             
              <div style="text-align:center;">
                <a href="{{ $resetLink }}" style="background-color:#00A6A6; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:6px; font-weight:bold; display:inline-block;">Reset Password</a>
              </div>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding: 30px;">
              <h3 style="margin: 0; color: #000; font-weight: 500;">Didn't request this? Just ignore us. We'll go sulk quietly.</h3>
              <p style="margin: 5px 0 20px;">Cheers,<br/>Team Frequenters</p>
              <p><i>Your memory may be hazy, but our loyalty isn't.</i></p>

              <!-- Social Icons -->
              <div style="display: flex; justify-content: center; gap: 15px;">
                <a href="#"><img src="{{ asset('storage/email/images/instagram.png') }}" alt="Instagram" width="20" height="20" /></a>
                <a href="#"><img src="{{ asset('storage/email/images/twitter.png') }}" alt="Twitter" width="20" height="20" /></a>
                <a href="#"><img src="{{ asset('storage/email/images/youtube.png') }}" alt="YouTube" width="20" height="20" /></a>
                <a href="#"><img src="{{ asset('storage/email/images/linkedin.png') }}" alt="LinkedIn" width="20" height="20" /></a>
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
