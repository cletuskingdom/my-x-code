<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email</title>
    </head>
    <body style="margin: 0; padding: 0; background-color: #f4f4f4;">

        <!-- Main Table -->
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4; width: 100%;">
            <tbody>
                <!-- Header -->
                <tr>
                    <td align="center" style="background-color: #fecb00;">
                    <img src="https://eathappybelly.com/banner.png" alt="Logo" style="width: 200px; height: auto; display: block;">
                    </td>
                </tr>
                    

                <!-- Body -->
                <tr>
                    <td style="padding: 20px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 5px;">
                            <tbody>
                                <tr>
                                    <td style="padding: 30px;">
                                        <p>Hey {{ $details['name'] }},</p>

                                        <p>
                                            Thanks for signing up for {{ $details['app_name'] }}!<br>
                                            We're excited to have you on board.
                                        </p>

                                        <p>
                                            To complete your registration, please enter the following One-Time Password (OTP) in the app:<br>
                                            <strong>OTP: {{ $details['otp'] }}</strong>
                                        </p>

                                        <p>This code is valid for 10 minutes. If you didn't request this code, please ignore this email.</p>

                                        <p>Cheers,<br>
                                            {{ $details['app_name'] }} Team
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td align="center" style="padding: 20px; background-color: #f4f4f4;">
                        <p style="margin: 0; color: #999999; font-size: 12px;">&copy; {{ date('Y') . ' ' . $details['app_name'] }}. All rights reserved.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>