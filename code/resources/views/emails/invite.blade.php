<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Join Frequenters Invitation</title>
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
            <td align="left" style="padding: 20px;">
              <img src="{{ asset('storage/email/images/logo.png') }}" alt="Frequenters Logo" width="150" style="display:block; margin-bottom: 10px;" />
            </td>
          </tr>
		  
          <tr>
            <td style="padding: 0 30px 20px 30px; color: #444;">
              <p style="margin: 0 0 15px 0;">Hey <strong>{{ $receiverName }}</strong>,</p>
              <p style="margin: 0 0 15px 0;">
                Your friend <strong>{{ $senderName }}</strong> just invited you to join Frequenters – the app that rewards you for visiting local cafes, shops, and hangout spots. 🛍️☕
              </p>
              <p style="margin: 0 0 15px 0;">
                With Frequenters, every check-in earns you points, perks, and cool rewards.
              </p>
              <p style="margin: 0 0 25px 0;">
                Why just visit places when you can get rewarded for it?
              </p>
              <div style="text-align:left;">
                <a href="{{ $url }}" style="background-color:#00A6A6; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:6px; font-weight:bold; display:inline-block;">Join Frequenters</a>
              </div>
                <div style="text-align:center;">
                    <p>If the button doesn’t work, copy and paste this URL into your browser:</p>
                    <p>{{ $url }}</p>
                </div>
            </td>
          </tr>
          <tr>
            <td align="left" style="padding: 30px;">
              <h3 style="margin: 0; color: #000; font-weight: 500;">Let your visits count. Be a Frequenter.</h3>
              <p style="margin: 5px 0 20px;">Cheers,<br/>Team Frequenters</p>

              <!-- Social Icons -->
              <div style="display: flex; justify-content: flex-start; gap: 15px;">
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
