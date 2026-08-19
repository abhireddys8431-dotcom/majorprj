<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../shared/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Smart Library</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/student.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="../../index.php">📚 Smart Library</a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-light me-2">Welcome, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong> (Student)</span>
                <a href="../../backend/api.php?action=logout" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4 px-4">
        <div class="row g-4">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2">
                <div class="sidebar">
                    <h6 class="text-uppercase text-muted fw-bold mb-3 small">Navigation</h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" id="search-tab">🔍 Search Books</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="map-tab">🗺️ 2D Floor Map</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="recommendations-tab">⭐ Recommendations</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="issued-tab">📖 My Books</a>
                        </li>
                        <li class="nav-item mt-3 pt-3 border-top">
                            <a class="nav-link text-secondary" href="../../index.php">🏠 Main Landing Page</a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="col-md-9 col-lg-10">
                <!-- 1. Search Books Tab -->
                <div id="search-content" class="tab-content">
                    <div class="card p-4 border-0 shadow-sm mb-4">
                        <h3 class="fw-bold mb-3">🔍 Search Book Catalog</h3>
                        <div class="row g-2">
                            <div class="col-md-7">
                                <input type="text" class="form-control form-control-lg" id="search-input" placeholder="Search by title, author, category, or ISBN...">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select form-select-lg" id="search-filter">
                                    <option value="title">Search by Title</option>
                                    <option value="author">Search by Author</option>
                                    <option value="isbn">Search by ISBN</option>
                                    <option value="category">Search by Category</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary btn-lg w-100" onclick="searchBooks()">Search</button>
                            </div>
                        </div>
                    </div>
                    <div id="search-results"></div>
                </div>

                <!-- 2. 2D Visual Floor Map Tab -->
                <div id="map-content" class="tab-content" style="display:none;">
                    <div class="card p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h3 class="fw-bold mb-1">🗺️ 2D Visual Library Shelf Map</h3>
                                <p class="text-muted small mb-0">Interactive architectural blueprint showing precise book shelf locations & aisle coordinates.</p>
                            </div>
                            <button class="btn btn-outline-danger btn-sm" onclick="drawFloorMap()">🔄 Refresh Map Pins</button>
                        </div>
                        <div class="canvas-container text-center">
                            <canvas id="floorMap" width="800" height="500" class="w-100 rounded shadow-sm" style="max-width: 800px; cursor: crosshair;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- 3. Recommendations Tab -->
                <div id="recommendations-content" class="tab-content" style="display:none;">
                    <div class="card p-4 border-0 shadow-sm mb-4">
                        <h3 class="fw-bold mb-1">⭐ AI-Powered Book Recommendations</h3>
                        <p class="text-muted small">Algorithmic suggestions combining content category preferences & peer reader history.</p>
                    </div>
                    <div id="recommendations" class="row g-4"></div>
                </div>

                <!-- 4. My Issued Books Tab -->
                <div id="issued-content" class="tab-content" style="display:none;">
                    <div class="card p-4 border-0 shadow-sm mb-4">
                        <h3 class="fw-bold mb-1">📖 My Issued Books & Active Loans</h3>
                        <p class="text-muted small mb-0">Track due dates, active checkouts, and return books to the library system.</p>
                    </div>
                    <div id="issued-list"></div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/student.js"></script>
</body>
</html>
