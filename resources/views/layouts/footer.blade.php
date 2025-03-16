<footer class="footer mt-5 py-4 bg-dark text-white">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5>SavedFeast</h5>
                <p class="text-muted">Fighting food waste in Lebanon, one meal at a time.</p>
                <p class="text-muted">&copy; {{ date('Y') }} SavedFeast. All rights reserved.</p>
            </div>
            <div class="col-md-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="{{ route('home') }}" class="text-decoration-none text-light">Home</a></li>
                    <li><a href="{{ route('search') }}" class="text-decoration-none text-light">Find Meals</a></li>
                    <li><a href="#" class="text-decoration-none text-light">How It Works</a></li>
                    <li><a href="#" class="text-decoration-none text-light">About Us</a></li>
                    <li><a href="#" class="text-decoration-none text-light">Contact</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Connect With Us</h5>
                <div class="d-flex gap-3 fs-4">
                    <a href="#" class="text-light"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-light"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-light"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="text-light"><i class="bi bi-linkedin"></i></a>
                </div>
                <div class="mt-3">
                    <p class="mb-1"><i class="bi bi-envelope me-2"></i> support@savedfeast.com</p>
                    <p><i class="bi bi-telephone me-2"></i> +961 1 234 5678</p>
                </div>
            </div>
        </div>
    </div>
</footer>
