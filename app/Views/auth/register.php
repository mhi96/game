<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guess The Code - Register</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background: #f0f2f5;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Arial', sans-serif;
        }

        .register-card {
            background: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        .register-card h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #343a40;
            font-weight: bold;
        }

        .register-card input {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ced4da;
        }

        .register-card button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            border: none;
            border-radius: 6px;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
        }

        .register-card button:hover {
            background-color: #218838;
        }

        .register-card a {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
        }

        .register-card a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="register-card">
    <h2>Register</h2>

    <form action="<?= site_url("register") ?>" method="post">
        <input name="username" placeholder="Username" type="text" required>
        <input name="email" placeholder="Email" type="text" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>

    <a href="<?= site_url("login") ?>">Login</a>
</div>

</body>
</html>
