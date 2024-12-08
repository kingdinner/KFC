<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Created</title>
</head>
<body>
    <h1>Welcome to the System!</h1>
    <p>Hello,</p>
    <p>Your account has been created. Please use the following details to log in:</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Temporary Password:</strong> {{ $temporaryPassword }}</p>
    <p>It is recommended that you change your password after your first login.</p>
    <p>Thank you!</p>
</body>
</html>
