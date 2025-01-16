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
<<<<<<< HEAD
                        <img src="https://eat .com/banner.png" alt="Logo" style="width: 200px; height: auto; display: block;">
=======
                        <img src="{{ asset(env('APP_PUBLIC') . 'logo.png') }}" alt="Logo" style="width: 100px; height: auto; display: block;">
>>>>>>> c079073a57487d2c0bd6af1374e9bf2521d79d0b
                    </td>
                </tr>
                    

                <!-- Body -->
                <tr>
                    <td style="padding: 20px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 5px;">
                            <tbody>
                                <tr>
                                    <td style="padding: 30px;">
                                        <p>
                                            {{ $details['message'] }}
                                        </p>

                                        <p>Cheers,<br>
                                            {{ env('APP_NAME') }} Team
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
                        <p style="margin: 0; color: #999999; font-size: 12px;">&copy; {{ date('Y') . ' ' . env('APP_NAME') }}. All rights reserved.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>