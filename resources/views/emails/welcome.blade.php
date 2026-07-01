<!DOCTYPE html>
<html lang="{{$user->lang}}">
<head>
    <title>Welcome Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            color: #333;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-container img {
            max-width: 150px;
            height: auto;
        }

        .content {
            text-align: center;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            text-align: center;
            color: #aaa;
        }
    </style>
</head>
<body>
<div class="email-container">
    <!-- Logo Section -->
    <div class="logo-container">
        <img src="{{ asset('images/logo-gold.png') }}" alt="Company Logo">
    </div>

    <!-- Content Section -->
    <div class="content">
        <h1>Welcome, {{ $user->name }}!</h1>
        <p>Thank you for registering. We're excited to have you join us!</p>
        <p>You can now upload your previous work, receive job offers, or explore the available work for
            submission. Take advantage of this opportunity to showcase your skills and connect with potential
            clients!</p>
    </div>

    <!-- Footer Section -->
    <div class="footer">
        © {{ date('Y') }} Fannan App. All rights reserved.
    </div>
</div>
</body>
</html>
