<footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="text-white mb-3">
                        <i class="fas fa-blog me-2"></i>BlogPlatform
                    </h5>
                    <p class="text-light">
                        A modern blog platform for sharing ideas, connecting with readers, and building communities through content.
                    </p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <h6 class="text-white mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="../public/index.php" class="text-light text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="../public/about.php" class="text-light text-decoration-none">About</a></li>
                        <li class="mb-2"><a href="../public/register.php" class="text-light text-decoration-none">Join Us</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <h6 class="text-white mb-3">Categories</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Technology</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Design</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Business</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none">Lifestyle</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-4 mb-4">
                    <h6 class="text-white mb-3">Subscribe to Newsletter</h6>
                    <p class="text-light mb-3">Get the latest posts delivered right to your inbox.</p>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Enter your email">
                        <button class="btn btn-primary" type="button">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-light">
                        &copy; 2025 BlogPlatform. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-light">
                        Built with <i class="fas fa-heart text-danger"></i> using PHP & Bootstrap
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Add smooth scrolling to anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add fade-in animation to cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.card').forEach(card => {
            observer.observe(card);
        });
    </script>
</body>
</html>
