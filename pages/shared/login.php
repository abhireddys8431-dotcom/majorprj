<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Library</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="bg-light d-flex flex-column justify-content-center align-items-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card p-4 shadow-lg border-0">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-danger">📚 Smart Library Login</h2>
                            <p class="text-muted small">Enter your portal credentials to access your dashboard</p>
                        </div>
                        
                        <div id="alert-box"></div>

                        <form id="loginForm">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Email Address</label>
                                <input type="email" class="form-control form-control-lg" id="email" placeholder="admin@library.com or student@library.com" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Password</label>
                                <input type="password" class="form-control form-control-lg" id="password" placeholder="••••••••" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">Sign In &rarr;</button>
                        </form>
                        
                        <hr>
                        <div class="text-center">
                            <p class="mb-0 text-muted">Don't have an account? <a href="register.php" class="text-danger font-weight-bold">Register here</a></p>
                            <a href="../../index.php" class="text-secondary small mt-2 d-inline-block">&larr; Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const alertBox = document.getElementById('alert-box');
            alertBox.innerHTML = '<div class="alert alert-info py-2">Authenticating...</div>';

            fetch('../../backend/api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=login&email=' + encodeURIComponent(document.getElementById('email').value) + '&password=' + encodeURIComponent(document.getElementById('password').value)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.user) {
                    alertBox.innerHTML = '<div class="alert alert-success py-2">Login successful! Redirecting...</div>';
                    setTimeout(() => {
                        if (data.user.role === 'admin') {
                            window.location.href = '../admin/dashboard.php';
                        } else {
                            window.location.href = '../student/dashboard.php';
                        }
                    }, 500);
                } else {
                    alertBox.innerHTML = `<div class="alert alert-danger py-2">${data.message || 'Invalid credentials'}</div>`;
                }
            })
            .catch(err => {
                alertBox.innerHTML = '<div class="alert alert-danger py-2">Connection error. Please try again.</div>';
            });
        });
    </script>
</body>
</html>
