<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Library Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">📚 Smart Library</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="pages/<?php echo $_SESSION['role']; ?>/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-warning" href="backend/api.php?action=logout">Logout (<?php echo htmlspecialchars($_SESSION['name']); ?>)</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="pages/shared/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white ms-2" href="pages/shared/register.php">Register Account</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="card p-4 shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                        <p class="text-muted mb-0">Role: <span class="badge bg-danger"><?php echo ucfirst($_SESSION['role']); ?></span></p>
                    </div>
                    <a href="pages/<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-primary btn-lg">Open Portal Dashboard &rarr;</a>
                </div>
            </div>
        <?php else: ?>
            <div class="hero-card mb-5">
                <h1 class="hero-title">Smart Library Management System</h1>
                <p class="hero-subtitle">Next-generation academic portal featuring 2D visual shelf map navigation, QuaggaJS automated barcode scanner, SQL recommendation engine, and live RSS updates.</p>
                <div class="d-flex gap-3">
                    <a class="btn btn-primary btn-lg px-4" href="pages/shared/login.php">Student & Admin Login</a>
                    <a class="btn btn-outline-light btn-lg px-4" href="pages/shared/register.php">Register Account</a>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 p-4">
                        <div class="fs-1 text-danger mb-3">📱</div>
                        <h4 class="card-title">Barcode Scanning</h4>
                        <p class="text-muted">Instant camera barcode scanning powered by QuaggaJS and Open Library REST API for rapid cataloging.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-4">
                        <div class="fs-1 text-warning mb-3">🗺️</div>
                        <h4 class="card-title">2D Floor Shelf Mapping</h4>
                        <p class="text-muted">Interactive visual canvas floor map pinpoints exact shelf location and aisle coordinates for every book.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-4">
                        <div class="fs-1 text-success mb-3">⭐</div>
                        <h4 class="card-title">AI Recommendations</h4>
                        <p class="text-muted">Content-based and peer-collaborative SQL filtering engine delivering customized book suggestions.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
