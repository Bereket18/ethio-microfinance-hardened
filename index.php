<?php
// index.php - Modern Landing Page
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ethio Microfinance - Modern Banking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-bank2"></i> EthioMF
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-house"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="modules/users/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="modules/users/register.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="modules/loans/apply.php">
                            <i class="bi bi-file-earmark-plus"></i> Apply Loan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/dashboard.php">
                            <i class="bi bi-shield-lock"></i> Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" style="min-height: 100vh; display: flex; align-items: center; background: var(--gradient-dark); position: relative; overflow: hidden; padding-top: 80px;">
        <div class="container position-relative z-index-1">
            <div class="row align-items-center">
                <div class="col-lg-6 fade-in-up">
                    <div class="badge-modern primary mb-4 d-inline-block">
                        <i class="bi bi-shield-check"></i> Trusted Since 2024
                    </div>
                    <h1 class="text-white display-1 fw-800 mb-4" style="font-size: 4rem; letter-spacing: -2px;">
                        Empowering<br>
                        <span class="text-gradient">Financial Freedom</span>
                    </h1>
                    <p class="text-white-50 fs-5 mb-4" style="max-width: 500px;">
                        Access microfinance services designed for Ethiopian communities. 
                        Secure, fast, and reliable banking at your fingertips.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="modules/users/register.php" class="btn btn-gradient btn-lg">
                            <i class="bi bi-person-plus"></i> Get Started
                        </a>
                        <a href="modules/users/login.php" class="btn btn-glass btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </div>
                    <div class="mt-5 d-flex gap-4">
                        <div>
                            <div class="text-white display-6 fw-800">5,000+</div>
                            <div class="text-white-50">Active Users</div>
                        </div>
                        <div>
                            <div class="text-white display-6 fw-800">$2.5M</div>
                            <div class="text-white-50">Loans Disbursed</div>
                        </div>
                        <div>
                            <div class="text-white display-6 fw-800">98%</div>
                            <div class="text-white-50">Repayment Rate</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 fade-in-scale text-center" style="animation-delay: 0.3s;">
                    <div class="glass p-4 rounded-4" style="max-width: 400px; margin: 0 auto;">
                        <div class="bg-gradient-primary rounded-3 p-4 text-white mb-3">
                            <i class="bi bi-credit-card display-1"></i>
                            <h4 class="mt-2">Apply in Minutes</h4>
                            <p class="mb-0 opacity-75">Get approved within 24 hours</p>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="glass p-3 rounded-3 text-center">
                                    <i class="bi bi-check-circle text-success display-6"></i>
                                    <small class="d-block text-white-50">No Collateral</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="glass p-3 rounded-3 text-center">
                                    <i class="bi bi-graph-up text-success display-6"></i>
                                    <small class="d-block text-white-50">Low Interest</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" style="background: #fff;">
        <div class="container">
            <div class="text-center mb-5 fade-in-up">
                <h2 class="display-4 fw-800">Why Choose <span class="text-gradient">EthioMF</span></h2>
                <p class="text-muted">Modern microfinance solutions for Ethiopian communities</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4 fade-in-up" style="animation-delay: 0.1s;">
                    <div class="card-modern p-4 text-center h-100">
                        <div class="stat-icon primary mx-auto mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-shield-check fs-1"></i>
                        </div>
                        <h5 class="fw-700">Secure Banking</h5>
                        <p class="text-muted">Enterprise-grade security protecting your financial data</p>
                    </div>
                </div>
                <div class="col-md-4 fade-in-up" style="animation-delay: 0.2s;">
                    <div class="card-modern p-4 text-center h-100">
                        <div class="stat-icon success mx-auto mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-clock-history fs-1"></i>
                        </div>
                        <h5 class="fw-700">Fast Approval</h5>
                        <p class="text-muted">Get loan decisions within 24 hours of application</p>
                    </div>
                </div>
                <div class="col-md-4 fade-in-up" style="animation-delay: 0.3s;">
                    <div class="card-modern p-4 text-center h-100">
                        <div class="stat-icon info mx-auto mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                        <h5 class="fw-700">Community Focus</h5>
                        <p class="text-muted">Empowering Ethiopian communities with accessible finance</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4" style="background: var(--dark); border-top: 1px solid rgba(255,255,255,0.05);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-white-50 mb-0">© 2026 Ethio Microfinance System</p>
                </div>
                <div class="col-md-6 text-md-end">
                    
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>