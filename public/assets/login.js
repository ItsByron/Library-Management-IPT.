function goToSignup() {
  window.location.href = 'signup.php';
}
function goToLogin() {
  window.location.href = 'login.php';
}
async function signup() {
  const username = document.getElementById('username')?.value.trim();
  const password = document.getElementById('password')?.value.trim();
  const confirmPassword = document.getElementById('confirmPassword')?.value.trim();

  // Check empty fields
  if (!username || !password || !confirmPassword) {
    showToast("Please fill all fields", "warning");
    return;
  }


  if (password !== confirmPassword) {
    showToast("Passwords do not match", "error");
    return;
  }

  // Optional: minimum password length
  if (password.length < 6) {
    showToast("Password must be at least 6 characters", "warning");
    return;
  }

  try {
    const res = await fetch('../routes/loginRoute.php?action=signup', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });

    const result = await res.json();

    if (result.status === 'success') {
      showToast("Account created successfully!", "success");

      // Redirect after short delay
      setTimeout(() => {
        window.location.href = 'login.php';
      }, 1500);

    } else {
      showToast(result.message || "Signup failed", "error");
    }

  } catch (err) {
    console.error(err);
    showToast("Server error. Try again.", "error");
  }
}
async function login() {
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();

  if (!username || !password) {
    showError();
    return;
  }

  try {
    const res = await fetch('../routes/loginRoute.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });

    const result = await res.json();

    if (result.status === 'success') {
    //   localStorage.setItem('loggedIn', 'true');
    // showToast("Login Successful","success");
    localStorage.setItem('toastMessage', 'Login Successful');
    localStorage.setItem('toastType', 'success');
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