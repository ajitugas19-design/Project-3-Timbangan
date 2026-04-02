<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(to right, #3a6f73, #4f8c91);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Card */
.login-box {
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    width: 320px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    text-align: center;
}

/* Logo */
.logo {
    width: 30%;
    max-width: 130px;
    height: auto;
    margin-bottom: 10px;
}

/* Title */
.login-box h2 {
    margin-bottom: 15px;
}

/* Input */
.input-group {
    text-align: left;
    margin-bottom: 15px;
}

.input-group input {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

/* Button */
button {
    width: 100%;
    padding: 10px;
    background: #2f8f9d;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

button:hover {
    background: #256e78;
}

/* Footer */
.footer {
    margin-top: 15px;
    font-size: 11px;
    color: gray;
}
</style>

</head>
<body>

<div class="login-box">

    <!-- LOGO -->
    <img src="Img/Logo.png" class="logo" alt="Logo">
    <h1>Login</h1>

    <form action="Dasboard/Navbar.php" method="post">
    <div class="input-group">
        <label>Username</label>
<input type="text" name="username" placeholder="Masukkan username">
    </div>

    <div class="input-group">
        <label>Password</label>
<input type="password" name="password" placeholder="Masukkan password">
    </div>

    <button type="submit">Masuk</button>
</form>

    <div class="footer">
        © PT. Langgeng Jaya Plastindo
    </div>

</div>

</body>
</html>