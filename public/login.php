<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AlgoReadthm — Admin Login</title>


  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Crimson+Pro:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet"/>


  <link rel="stylesheet" href="assets/LoginStyle.css"/>
  <link rel="stylesheet" href="assets/toast.css"/>
</head>

<body>

<div class="login-card">
  <div class="logo">
    <h1>AlgoReadthm</h1>
    <p>Library Management System</p>
  </div>

  <div class="error-msg" id="errorMsg">
    ⚠️ Incorrect username or password.
  </div>

  <div class="form-group">
    <label>Username</label>
    <input type="text" id="username" placeholder="admin" autocomplete="off"/>
  </div>

  <div class="form-group">
    <label>Password</label>
    <input type="password" id="password" placeholder="••••••••"/>
  </div>

  <button class="btn-login" onclick="login()">Sign In</button>

  <button class="btn-signup" onclick="goToSignup()">Create Account</button>

  <p class="hint">Default: admin / admin123</p>
</div>
<script src="assets/toast.js"></script>
<script src="assets/login.js"></script>
<!-- <script>

async function login() {
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();

  if (!username || !password) {
    showError();
    return;
  }

  try {
    const res = await fetch('../routes/loginRoute.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });

    const result = await res.json();

    if (result.status === 'success') {
    //   localStorage.setItem('loggedIn', 'true');
      window.location.href = 'dashboard.php';
    } else {
      showError();
    }

  } catch (err) {
    console.error(err);
    showError();
  }
}

function showError() {
  document.getElementById('errorMsg').style.display = 'block';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') login();
});
</script> -->

</body>
</html>