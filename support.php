<?php
require_once 'includes/header.php';
?>

<style>
/* Custom Support Page Styles */
.support-hero {
    background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    color: white;
    padding: 80px 0;
    margin-bottom: 50px;
    position: relative;
    overflow: hidden;
}

.support-hero::after {
    content: '';
    position: absolute;
    bottom: -50px;
    left: 0;
    right: 0;
    height: 100px;
    background: white;
    transform: skewY(-3deg);
}

.support-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 12px;
    background: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.support-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.contact-icon-box {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
}

.accordion-button:not(.collapsed) {
    background-color: rgba(52, 152, 219, 0.1);
    color: #2c3e50;
    font-weight: 600;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(52, 152, 219, 0.5);
}
</style>

<!-- Support Hero -->
<div class="support-hero text-center">
    <div class="container position-relative z-1">
        <h1 class="display-4 fw-bold mb-3 animate__animated animate__fadeInDown">How can we help you?</h1>
        <p class="lead mb-4 animate__animated animate__fadeInUp animate__delay-1s opacity-75">
            Search our knowledge base or reach out to our dedicated support team.
        </p>
        <div class="row justify-content-center animate__animated animate__fadeInUp animate__delay-1s">
            <div class="col-md-6">
                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden">
                    <span class="input-group-text bg-white border-0 ps-4"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-0" placeholder="Search for answers...">
                    <button class="btn btn-primary px-4">Search</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    
    <!-- Quick Contact Cards -->
    <div class="row g-4 mb-5" style="margin-top: -80px; position: relative; z-index: 10;">
        <div class="col-md-4">
            <div class="support-card h-100 p-4 text-center">
                <div class="contact-icon-box bg-primary bg-opacity-10 text-primary mx-auto">
                    <i class="fas fa-book"></i>
                </div>
                <h5 class="fw-bold mb-2">Documentation</h5>
                <p class="text-muted small mb-3">Browse our detailed guides and step-by-step tutorials.</p>
                <a href="#" class="btn btn-outline-primary btn-sm rounded-pill">View Guides</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="support-card h-100 p-4 text-center">
                <div class="contact-icon-box bg-success bg-opacity-10 text-success mx-auto">
                    <i class="fas fa-envelope"></i>
                </div>
                <h5 class="fw-bold mb-2">Email Support</h5>
                <p class="text-muted small mb-3">Get a response within 24 hours from our team.</p>
                <a href="mailto:support@inventoryms.com" class="btn btn-outline-success btn-sm rounded-pill">Email Us</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="support-card h-100 p-4 text-center">
                <div class="contact-icon-box bg-warning bg-opacity-10 text-warning mx-auto">
                    <i class="fas fa-comments"></i>
                </div>
                <h5 class="fw-bold mb-2">Live Chat</h5>
                <p class="text-muted small mb-3">Chat with us directly between 9AM - 5PM EST.</p>
                <button class="btn btn-outline-warning btn-sm rounded-pill">Start Chat</button>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- FAQ Section -->
        <div class="col-lg-7 mb-5">
            <h3 class="fw-bold mb-4 text-gray-800"><i class="fas fa-question-circle text-primary me-2"></i>Frequently Asked Questions</h3>
            
            <div class="accordion shadow-sm rounded-3 overflow-hidden" id="faqAccordion">
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How do I reset my password?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            You can reset your password by going to the login page and clicking "Forgot Password". 
                            Alternatively, an administrator can reset it for you via the User Management panel.
                        </div>
                    </div>
                </div>
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Can I export my inventory data?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            Yes! You can export product lists, sales reports, and stock valuation reports to CSV format 
                            directly from their respective pages. Look for the "Export" button in the top toolbar.
                        </div>
                    </div>
                </div>
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            How do I add multiple users?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            Administrators can add unlimited users via the "Users" menu. You can assign different roles 
                            (Admin, Manager, Staff) to control what features they can access.
                        </div>
                    </div>
                </div>
                <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            Is the barcode scanner compatible with my phone?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            Our barcode scanner works with any device that has a camera and a modern web browser. 
                            It supports common formats like UPC, EAN, and QR codes.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="col-lg-5">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-4 bg-white rounded-4">
                    <h4 class="fw-bold mb-4">Send us a message</h4>
                    <form>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Your Name</label>
                            <input type="text" class="form-control bg-light border-0 py-3" placeholder="John Doe">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Email Address</label>
                            <input type="email" class="form-control bg-light border-0 py-3" placeholder="name@example.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Subject</label>
                            <select class="form-select bg-light border-0 py-3">
                                <option>Technical Issue</option>
                                <option>Feature Request</option>
                                <option>Billing Question</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold text-uppercase">Message</label>
                            <textarea class="form-control bg-light border-0 py-3" rows="4" placeholder="How can we help?"></textarea>
                        </div>
                        <button type="button" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-sm">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
