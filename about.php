<?php
require_once 'includes/header.php';
?>

<style>
/* Custom About Page Styles */
.hero-section {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    padding: 80px 0;
    border-radius: 0 0 50% 50% / 4%;
    margin-bottom: 50px;
    position: relative;
    overflow: hidden;
}

.hero-bg-icon {
    position: absolute;
    font-size: 20rem;
    color: rgba(255, 255, 255, 0.05);
    top: -50px;
    right: -50px;
    transform: rotate(15deg);
}

.feature-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
}

.feature-icon-wrapper {
    height: 80px;
    width: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin: 0 auto 20px;
    font-size: 2rem;
    background: rgba(78, 115, 223, 0.1);
    color: #4e73df;
    transition: all 0.3s ease;
}

.feature-card:hover .feature-icon-wrapper {
    background: #4e73df;
    color: white;
    transform: scale(1.1);
}

.stats-section {
    background-color: #f8f9fc;
    padding: 60px 0;
    margin-top: 50px;
}

.stat-item h2 {
    font-weight: 800;
    font-size: 3rem;
    background: -webkit-linear-gradient(#4e73df, #224abe);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 10px;
}

.team-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 5px solid white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: -60px;
    position: relative;
    z-index: 10;
    object-fit: cover;
}
</style>

<!-- Hero Section -->
<div class="hero-section text-center">
    <div class="container position-relative">
        <i class="fas fa-boxes hero-bg-icon"></i>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="display-3 fw-bold mb-4 animate__animated animate__fadeInDown">Inventory Excellence</h1>
                <p class="lead mb-5 opacity-75 animate__animated animate__fadeInUp animate__delay-1s">
                    Empowering your business with precision, speed, and intelligent insights. 
                    Manage your stock effortlessly and focus on what truly matters—growth.
                </p>
                <a href="products/view_products.php" class="btn btn-light btn-lg px-5 py-3 rounded-pill fw-bold shadow-sm animate__animated animate__fadeInUp animate__delay-2s text-primary">
                    Explore Inventory
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <!-- Our Mission -->
    <div class="row align-items-center mb-5 pb-5">
        <div class="col-lg-6 mb-4 mb-lg-0 animate__animated animate__fadeInLeft">
            <h6 class="text-uppercase text-primary fw-bold letter-spacing-2">Our Mission</h6>
            <h2 class="fw-bold mb-4 display-6 text-gray-800">Simplifying Complexity, <br>Maximizing Efficiency.</h2>
            <p class="text-muted fs-5 mb-4">
                We believe that inventory management shouldn't be a headache. Our platform is built 
                around the philosophy that powerful tools can be intuitive, beautiful, and accessible.
            </p>
            <ul class="list-unstyled space-y-3">
                <li class="d-flex align-items-center mb-3">
                    <i class="fas fa-check-circle text-success me-3 fa-lg"></i>
                    <span class="fs-5 text-gray-700">Real-time stock tracking across locations</span>
                </li>
                <li class="d-flex align-items-center mb-3">
                    <i class="fas fa-check-circle text-success me-3 fa-lg"></i>
                    <span class="fs-5 text-gray-700">Intelligent alerts for low stock & expiration</span>
                </li>
                <li class="d-flex align-items-center">
                    <i class="fas fa-check-circle text-success me-3 fa-lg"></i>
                    <span class="fs-5 text-gray-700">Data-driven insights for smarter purchasing</span>
                </li>
            </ul>
        </div>
        <div class="col-lg-6 animate__animated animate__fadeInRight">
            <div class="row g-3">
                <div class="col-6">
                    <img src="https://images.unsplash.com/photo-1553413077-190dd305871c?auto=format&fit=crop&q=80&w=600" class="img-fluid rounded-4 shadow-lg mb-3" alt="Warehouse">
                    <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&q=80&w=600" class="img-fluid rounded-4 shadow-lg" alt="Logistics">
                </div>
                <div class="col-6 mt-5">
                    <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=600" class="img-fluid rounded-4 shadow-lg" alt="Analytics">
                </div>
            </div>
        </div>
    </div>

    <!-- System Overview Section -->
    <div class="row align-items-center mb-5 pb-5 flex-row-reverse">
        <div class="col-lg-6 mb-4 mb-lg-0 animate__animated animate__fadeInRight">
            <h6 class="text-uppercase text-primary fw-bold letter-spacing-2">The Platform</h6>
            <h2 class="fw-bold mb-4 display-6 text-gray-800">Built for Modern Commerce.</h2>
            <p class="text-muted fs-5 mb-4">
                Our Stock Management System is an all-in-one solution designed to streamline your inventory operations. 
                Built on a robust PHP/MySQL architecture, we deliver enterprise-grade performance with consumer-grade usability.
            </p>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-cubes text-primary fs-3"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-1">Centralized Control</h5>
                            <p class="text-secondary small mb-0">Manage products, suppliers, and fleets from a single dashboard.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-truck-moving text-primary fs-3"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-1">Logistics Integrated</h5>
                            <p class="text-secondary small mb-0">Seamlessly connect inventory with delivery and driver management.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-lock text-primary fs-3"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-1">Role-Based Security</h5>
                            <p class="text-secondary small mb-0">Granular permissions for Admins, Managers, and Staff.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-mobile-alt text-primary fs-3"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="fw-bold mb-1">Mobile Ready</h5>
                            <p class="text-secondary small mb-0">Access your inventory from any device, anywhere, anytime.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 animate__animated animate__fadeInLeft">
            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&q=80&w=800" class="img-fluid rounded-4 shadow-lg" alt="Dashboard Interface" style="transform: perspective(1000px) rotateY(5deg);">
        </div>
    </div>

    <!-- Core Values Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card feature-card h-100 shadow-sm py-4 px-3 text-center">
                <div class="card-body">
                    <div class="feature-icon-wrapper">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="fw-bold text-gray-800 mb-3">Security First</h4>
                    <p class="text-muted">
                        Built with enterprise-grade security protocols. Role-based access ensure your data stays 
                        in the right hands, always.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card feature-card h-100 shadow-sm py-4 px-3 text-center">
                <div class="card-body">
                    <div class="feature-icon-wrapper">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h4 class="fw-bold text-gray-800 mb-3">Lightning Fast</h4>
                    <p class="text-muted">
                        Optimized for speed. From barcode scanning to report generation, receive instant feedback 
                        to keep your operations flowing.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card feature-card h-100 shadow-sm py-4 px-3 text-center">
                <div class="card-body">
                    <div class="feature-icon-wrapper">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4 class="fw-bold text-gray-800 mb-3">Actionable Analytics</h4>
                    <p class="text-muted">
                        Transform raw data into meaningful strategy. Visual dashboards help you spot trends 
                        and optimize your inventory turnover.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="stats-section mb-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 mb-4 mb-md-0">
                <div class="stat-item">
                    <h2 class="counter">99.9%</h2>
                    <p class="text-uppercase fw-bold text-secondary text-xs letter-spacing-1">Uptime Reliability</p>
                </div>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <div class="stat-item">
                    <h2 class="counter">5000+</h2>
                    <p class="text-uppercase fw-bold text-secondary text-xs letter-spacing-1">Items Tracked</p>
                </div>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <div class="stat-item">
                    <h2 class="counter">24/7</h2>
                    <p class="text-uppercase fw-bold text-secondary text-xs letter-spacing-1">Support Monitoring</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <h2 class="counter">100%</h2>
                    <p class="text-uppercase fw-bold text-secondary text-xs letter-spacing-1">User Satisfaction</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="container mb-5 pb-5">
    <div class="card bg-dark text-white shadow-lg border-0 rounded-4 overflow-hidden">
        <div class="row g-0">
            <div class="col-lg-6 p-5 d-flex flex-column justify-content-center">
                <h2 class="fw-bold mb-3">Ready to optimize your workflow?</h2>
                <p class="lead mb-4 text-white-50">Join the future of inventory management today. Secure, Scalable, Simple.</p>
                <div>
                    <a href="index.php" class="btn btn-primary btn-lg rounded-pill px-4 me-2">Go to Dashboard</a>
                    <a href="mailto:support@inventoryms.com" class="btn btn-outline-light btn-lg rounded-pill px-4">Contact Support</a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block" style="background: url('https://images.unsplash.com/photo-1542744173-8e7e53415bb0?auto=format&fit=crop&q=80&w=800') center/cover;">
                <div class="h-100 w-100" style="background: rgba(0,0,0,0.3);"></div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
