<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Library</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="bg-light d-flex flex-column justify-content-center align-items-center min-vh-100 py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4 shadow-lg border-0">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-danger">📝 Register Account</h2>
                            <p class="text-muted small">Create your student or librarian portal profile</p>
                        </div>
                        
                        <div id="alert-box"></div>

                        <form id="registerForm">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Full Name</label>
                                <input type="text" class="form-control" id="name" placeholder="John Doe" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Email Address</label>
                                <input type="email" class="form-control" id="email" placeholder="john@college.edu" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-bold">Password</label>
                                    <input type="password" class="form-control" id="password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-bold">Roll / ID Number</label>
                                    <input type="text" class="form-control" id="roll_number" placeholder="STU2026099">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Role</label>
                                <select class="form-select" id="role">
                                    <option value="student" selected>Student</option>
                                    <option value="admin">Administrator / Librarian</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100 mb-3">Create Account &rarr;</button>
                        </form>
                        
                        <hr>
                        <div class="text-center">
                            <p class="mb-0 text-muted">Already have an account? <a href="login.php" class="text-danger font-weight-bold">Login here</a></p>
                            <a href="../../index.php" class="text-secondary small mt-2 d-inline-block">&larr; Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const alertBox = document.getElementById('alert-box');
            alertBox.innerHTML = '<div class="alert alert-info py-2">Creating account...</div>';

            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const roll_number = document.getElementById('roll_number').value;
            const role = document.getElementById('role').value;

            fetch('../../backend/api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=register&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&roll_number=${encodeURIComponent(roll_number)}&role=${role}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alertBox.innerHTML = '<div class="alert alert-success py-2">Registration successful! Redirecting to login...</div>';
                    setTimeout(() => window.location.href = 'login.php', 1000);
                } else {
                    alertBox.innerHTML = `<div class="alert alert-danger py-2">${data.message || 'Registration failed'}</div>`;
                }
            });
        });
    </script>
</body>
</html>
