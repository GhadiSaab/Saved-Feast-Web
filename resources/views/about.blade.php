@extends('layouts.app')

@section('title', 'About Us | SavedFeast')

@section('content')
<!-- Hero Section -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-4">About SavedFeast</h1>
                <p class="lead mb-0">Our mission is to reduce food waste while helping businesses and consumers save money.</p>
            </div>
        </div>
    </div>
</section>

<!-- Our Story Section -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center mb-5">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="{{ asset('images/about-story.jpg') }}" alt="Our Story" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6">
                <h2 class="mb-4">Our Story</h2>
                <p>SavedFeast began with a simple observation: every day, restaurants, cafes, and bakeries throw away perfectly good food because they couldn't sell it before closing time.</p>
                <p>Founded in 2022 by a team of food enthusiasts and environmental advocates, our platform bridges the gap between food providers with surplus items and consumers looking for quality meals at reduced prices.</p>
                <p>What started as a small initiative in one city has grown into a nationwide movement with hundreds of partner businesses and thousands of users joining our fight against food waste.</p>
            </div>
        </div>
        
        <div class="row align-items-center flex-lg-row-reverse">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="{{ asset('images/about-mission.jpg') }}" alt="Our Mission" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6">
                <h2 class="mb-4">Our Mission</h2>
                <p>At SavedFeast, we're on a mission to revolutionize how we think about surplus food. We believe that good food should be eaten, not wasted.</p>
                <p>Through our platform, we aim to:</p>
                <ul>
                    <li>Reduce food waste and its environmental impact</li>
                    <li>Help food businesses recover costs on surplus items</li>
                    <li>Provide consumers with access to quality food at reduced prices</li>
                    <li>Raise awareness about sustainable consumption</li>
                    <li>Build a community of environmentally conscious eaters and providers</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Meet Our Team</h2>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="{{ asset('images/team-1.jpg') }}" class="card-img-top" alt="Team Member">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Daniel Wilson</h5>
                        <p class="text-muted mb-3">Co-Founder & CEO</p>
                        <p class="card-text">Former restaurant manager with a passion for sustainable food systems.</p>
                        <div class="d-flex justify-content-center">
                            <a href="#" class="text-primary mx-2"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-primary mx-2"><i class="bi bi-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="{{ asset('images/team-2.jpg') }}" class="card-img-top" alt="Team Member">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Sophia Chen</h5>
                        <p class="text-muted mb-3">Co-Founder & CTO</p>
                        <p class="card-text">Tech innovator with experience in developing sustainability solutions.</p>
                        <div class="d-flex justify-content-center">
                            <a href="#" class="text-primary mx-2"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-primary mx-2"><i class="bi bi-github"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="{{ asset('images/team-3.jpg') }}" class="card-img-top" alt="Team Member">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Marcus Johnson</h5>
                        <p class="text-muted mb-3">Head of Operations</p>
                        <p class="card-text">Operations expert with a background in logistics and supply chain management.</p>
                        <div class="d-flex justify-content-center">
                            <a href="#" class="text-primary mx-2"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-primary mx-2"><i class="bi bi-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="{{ asset('images/team-4.jpg') }}" class="card-img-top" alt="Team Member">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Laura Martinez</h5>
                        <p class="text-muted mb-3">Marketing Director</p>
                        <p class="card-text">Creative marketer with expertise in promoting sustainable businesses.</p>
                        <div class="d-flex justify-content-center">
                            <a href="#" class="text-primary mx-2"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-primary mx-2"><i class="bi bi-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Impact Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Our Impact</h2>
        
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="display-4 fw-bold text-primary mb-3">5,000+</div>
                        <h5 class="mb-3">Meals Saved Every Month</h5>
                        <p class="text-muted">That's over 60,000 meals saved from landfills each year.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="display-4 fw-bold text-primary mb-3">300+</div>
                        <h5 class="mb-3">Partner Restaurants</h5>
                        <p class="text-muted">Local businesses benefiting from reduced waste and increased revenue.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="display-4 fw-bold text-primary mb-3">30,000kg</div>
                        <h5 class="mb-3">CO2 Emissions Reduced</h5>
                        <p class="text-muted">Equivalent to taking 150 cars off the road for a month.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Join Us Section -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container">
        <h2 class="mb-4">Join Us in the Fight Against Food Waste</h2>
        <p class="lead mb-4">Whether you're a food provider or a conscious consumer, we invite you to be part of the solution.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('register') }}" class="btn btn-light btn-lg">Sign Up Now</a>
            <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg">Contact Us</a>
        </div>
    </div>
</section>
@endsection
