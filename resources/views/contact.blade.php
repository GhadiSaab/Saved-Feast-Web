@extends('layouts.app')

@section('title', 'Contact Us | SavedFeast')

@section('content')
<!-- Hero Section -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-4">Contact Us</h1>
                <p class="lead mb-0">Have questions or suggestions? We'd love to hear from you.</p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Form Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card border-0 shadow">
                    <div class="card-body p-4 p-md-5">
                        <div class="alert alert-success d-none" id="contact-success">
                            Thank you for your message! We'll get back to you as soon as possible.
                        </div>
                        
                        <div class="alert alert-danger d-none" id="contact-error">
                            Sorry, there was a problem sending your message. Please try again later.
                        </div>
                        
                        <form id="contact-form">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="form-label">Your Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                        <div class="invalid-feedback" id="name-error"></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                        <div class="invalid-feedback" id="email-error"></div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" name="subject" required>
                                        <div class="invalid-feedback" id="subject-error"></div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                        <div class="invalid-feedback" id="message-error"></div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary" id="submit-button">
                                        <i class="bi bi-send me-2"></i>Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Info Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 60px; height: 60px;">
                            <i class="bi bi-geo-alt fs-4"></i>
                        </div>
                        <h5 class="mb-3">Office Location</h5>
                        <p class="text-muted mb-0">123 Green Street<br>San Francisco, CA 94110<br>United States</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 60px; height: 60px;">
                            <i class="bi bi-envelope fs-4"></i>
                        </div>
                        <h5 class="mb-3">Email Us</h5>
                        <p class="mb-1"><strong>General Inquiries:</strong></p>
                        <p class="text-muted mb-2">info@savedfeast.com</p>
                        <p class="mb-1"><strong>Support:</strong></p>
                        <p class="text-muted mb-0">support@savedfeast.com</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-4" style="width: 60px; height: 60px;">
                            <i class="bi bi-telephone fs-4"></i>
                        </div>
                        <h5 class="mb-3">Call Us</h5>
                        <p class="mb-1"><strong>Customer Support:</strong></p>
                        <p class="text-muted mb-2">(555) 123-4567</p>
                        <p class="mb-1"><strong>Business Inquiries:</strong></p>
                        <p class="text-muted mb-0">(555) 987-6543</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-body p-0">
                        <div class="ratio ratio-21x9">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3153.7462631226584!2d-122.42676548371042!3d37.77103732075744!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x808580991ef4d8c1%3A0xa0a451489cd55de5!2sMission%20District%2C%20San%20Francisco%2C%20CA!5e0!3m2!1sen!2sus!4v1628186221799!5m2!1sen!2sus" allowfullscreen="" loading="lazy"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Teaser -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="mb-4">Frequently Asked Questions</h2>
                <p class="mb-4">Can't find the answer you're looking for? Check out our comprehensive FAQ section or contact us directly.</p>
                <a href="{{ route('faq') }}" class="btn btn-primary">View All FAQs</a>
            </div>
            <div class="col-lg-6">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                How does SavedFeast work?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                SavedFeast connects you with local restaurants and cafes offering surplus food at discounted prices. Browse available meals, place your order, and pick up your food during the specified time window.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                How do I become a food provider?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Register as a provider on our platform, complete your business profile, and start listing your surplus meals. Our team will help you get set up and optimize your listings.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    const submitButton = document.getElementById('submit-button');
    const successAlert = document.getElementById('contact-success');
    const errorAlert = document.getElementById('contact-error');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Reset form state
        successAlert.classList.add('d-none');
        errorAlert.classList.add('d-none');
        const fields = ['name', 'email', 'subject', 'message'];
        fields.forEach(field => {
            document.getElementById(field).classList.remove('is-invalid');
        });
        
        // Disable button and show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
        
        // Collect form data
        const formData = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            subject: document.getElementById('subject').value,
            message: document.getElementById('message').value
        };
        
        // Send to backend
        axios.post('/api/contact', formData)
            .then(response => {
                // Show success message
                form.reset();
                successAlert.classList.remove('d-none');
                
                // Reset button
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bi bi-send me-2"></i>Send Message';
                
                // Scroll to top of form
                form.scrollIntoView({behavior: 'smooth'});
            })
            .catch(error => {
                // Reset button
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bi bi-send me-2"></i>Send Message';
                
                if (error.response) {
                    // Validation errors
                    if (error.response.status === 422 && error.response.data.errors) {
                        const errors = error.response.data.errors;
                        
                        fields.forEach(field => {
                            if (errors[field]) {
                                document.getElementById(field).classList.add('is-invalid');
                                document.getElementById(`${field}-error`).textContent = errors[field][0];
                            }
                        });
                    } else {
                        // General error
                        errorAlert.classList.remove('d-none');
                        errorAlert.textContent = error.response.data.message || 'An error occurred. Please try again.';
                    }
                } else {
                    // Network error
                    errorAlert.classList.remove('d-none');
                    errorAlert.textContent = 'Network error. Please try again later.';
                }
                
                // Scroll to top of form
                form.scrollIntoView({behavior: 'smooth'});
            });
    });
});
</script>
@endpush
