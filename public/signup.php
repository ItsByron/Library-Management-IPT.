<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AlgoReadthm — Sign Up</title>


  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Crimson+Pro:ital,wght@0,300;0,400;0,600;1,300&display=swap" rel="stylesheet"/>


  <link rel="stylesheet" href="assets/LoginStyle.css"/>
    <link rel="stylesheet" href="assets/toast.css"/>
</head>

<body>

<div class="login-card">
  <div class="logo">
    <h1>AlgoReadthm</h1>
    <p>Create an Account</p>
  </div>

  <div class="error-msg" id="errorMsg">
    ⚠️ Please fill all fields.
  </div>

  <div class="form-group">
    <label>Username</label>
    <input type="text" id="username" placeholder="Enter username"/>
  </div>

  <div class="form-group">
    <label>Password</label>
    <input type="password" id="password" placeholder="Enter password"/>
  </div>

  <div class="form-group">
    <label>Confirm Password</label>
    <input type="password" id="confirmPassword" placeholder="Confirm password"/>
  </div>

  <button class="btn-login" onclick="signup()">Sign Up</button>

  <button class="btn-signup" onclick="goToLogin()">Back to Login</button>

</div>
<script src="assets/toast.js"></script>
<script src="assets/login.js"></script>
<!-- <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div> -->
<!-- <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999"></div> -->
 <div id="toastContainer"></div>
</body>
</html>