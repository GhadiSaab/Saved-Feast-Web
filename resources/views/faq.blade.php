@extends('layouts.app')

@section('title', 'Frequently Asked Questions | SavedFeast')

@section('content')
<!-- Hero Section -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-4">Frequently Asked Questions</h1>
                <p class="lead mb-0">Find answers to the most common questions about SavedFeast.</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 mb-4 mb-lg-0">
                <div class="sticky-top pt-2" style="top: 80px;">
                    <h5 class="mb-3">Categories</h5>
                    <div class="list-group">
                        <a href="#general" class="list-group-item list-group-item-action">General Questions</a>
                        <a href="#customers" class="list-group-item list-group-item-action">For Customers</a>
                        <a href="#providers" class="list-group-item list-group-item-action">For Food Providers</a>
                        <a href="#payment" class="list-group-item list-group-item-action">Payment & Pricing</a>
                        <a href="#technical" class="list-group-item list-group-item-action">Technical Support</a>
                    </div>
                    
                    <div class="mt-4 p-4 bg-light rounded">
                        <h6>Still have questions?</h6>
                        <p class="small mb-3">We're here to help with any questions you might have.</p>
                        <a href="{{ route('contact') }}" class="btn btn-primary btn-sm d-block">Contact Us</a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-9">
                <!-- General Questions -->
                <div id="general" class="mb-5">
                    <h3 class="border-bottom pb-3 mb-4">General Questions</h3>
                    
                    <div class="accordion mb-4" id="generalAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="general1">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#generalCollapse1" aria-expanded="true" aria-controls="generalCollapse1">
                                    What is SavedFeast?
                                </button>
                            </h2>
                            <div id="generalCollapse1" class="accordion-collapse collapse show" aria-labelledby="general1" data-bs-parent="#generalAccordion">
                                <div class="accordion-body">
                                    SavedFeast is a platform that connects consumers with local restaurants, cafes, and food shops that have surplus food items at the end of their business day. These businesses offer their unsold food at discounted prices, which helps reduce food waste while offering consumers great deals on quality food.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="general2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#generalCollapse2" aria-expanded="false" aria-controls="generalCollapse2">
                                    How does SavedFeast help reduce food waste?
                                </button>
                            </h2>
                            <div id="generalCollapse2" class="accordion-collapse collapse" aria-labelledby="general2" data-bs-parent="#generalAccordion">
                                <div class="accordion-body">
                                    Food businesses often have perfectly good food left unsold at the end of the day that would otherwise be thrown away. By creating a marketplace for this surplus food, SavedFeast helps businesses reduce their food waste while recovering some of their costs. Every meal purchased through our platform is a meal saved from the landfill.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="general3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#generalCollapse3" aria-expanded="false" aria-controls="generalCollapse3">
                                    Is SavedFeast available in my area?
                                </button>
                            </h2>
                            <div id="generalCollapse3" class="accordion-collapse collapse" aria-labelledby="general3" data-bs-parent="#generalAccordion">
                                <div class="accordion-body">
                                    SavedFeast is currently operating in several major cities across the United States, with new locations being added regularly. The best way to check if we're in your area is to enter your location on our search page or check the coverage map in our app.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="general4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#generalCollapse4" aria-expanded="false" aria-controls="generalCollapse4">
                                    Is the food on SavedFeast safe to eat?
                                </button>
                            </h2>
                            <div id="generalCollapse4" class="accordion-collapse collapse" aria-labelledby="general4" data-bs-parent="#generalAccordion">
                                <div class="accordion-body">
                                    Absolutely! The food offered through SavedFeast is perfectly safe for consumption. It's simply surplus that would otherwise go unsold at the end of the day. All food providers on our platform comply with local health and safety regulations. The only difference is that you're getting it at a reduced price while helping prevent food waste.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- For Customers -->
                <div id="customers" class="mb-5">
                    <h3 class="border-bottom pb-3 mb-4">For Customers</h3>
                    
                    <div class="accordion mb-4" id="customersAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="customer1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#customerCollapse1" aria-expanded="false" aria-controls="customerCollapse1">
                                    How do I place an order?
                                </button>
                            </h2>
                            <div id="customerCollapse1" class="accordion-collapse collapse" aria-labelledby="customer1" data-bs-parent="#customersAccordion">
                                <div class="accordion-body">
                                    Placing an order is easy! Browse available meals in your area, select the items you want, add them to your cart, and proceed to checkout. You'll need to create an account if you don't have one already, then complete the payment. After that, you'll receive confirmation with pickup details.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="customer2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#customerCollapse2" aria-expanded="false" aria-controls="customerCollapse2">
                                    Can I choose what I get, or is it a surprise?
                                </button>
                            </h2>
                            <div id="customerCollapse2" class="accordion-collapse collapse" aria-labelledby="customer2" data-bs-parent="#customersAccordion">
                                <div class="accordion-body">
                                    It depends on the listing type. Some providers offer specific items with clear descriptions of what you'll receive. Others offer "surprise bags" or "chef's choice" options where the specific contents will vary but will be within the described category (e.g., "pastry assortment" or "lunch meal"). The listing description will make it clear what type of offer you're purchasing.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="customer3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#customerCollapse3" aria-expanded="false" aria-controls="customerCollapse3">
                                    What if I can't pick up my order?
                                </button>
                            </h2>
                            <div id="customerCollapse3" class="accordion-collapse collapse" aria-labelledby="customer3" data-bs-parent="#customersAccordion">
                                <div class="accordion-body">
                                    Since we're dealing with surplus food that would otherwise go to waste, pickups are time-sensitive. If you're unable to pick up your order during the designated time window, you should cancel your order as soon as possible (at least 1 hour before the pickup window). After this cancellation deadline, refunds are at the discretion of the food provider. If you repeatedly fail to pick up orders without canceling, your account may be restricted.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="customer4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#customerCollapse4" aria-expanded="false" aria-controls="customerCollapse4">
                                    Can I have my order delivered?
                                </button>
                            </h2>
                            <div id="customerCollapse4" class="accordion-collapse collapse" aria-labelledby="customer4" data-bs-parent="#customersAccordion">
                                <div class="accordion-body">
                                    Currently, SavedFeast operates on a pickup model. Delivery is not yet available, but we're exploring delivery options for the future. The pickup system allows us to keep prices low and ensure food is collected during optimal timeframes to maintain quality.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- For Food Providers -->
                <div id="providers" class="mb-5">
                    <h3 class="border-bottom pb-3 mb-4">For Food Providers</h3>
                    
                    <div class="accordion mb-4" id="providersAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="provider1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#providerCollapse1" aria-expanded="false" aria-controls="providerCollapse1">
                                    How do I sign up as a food provider?
                                </button>
                            </h2>
                            <div id="providerCollapse1" class="accordion-collapse collapse" aria-labelledby="provider1" data-bs-parent="#providersAccordion">
                                <div class="accordion-body">
                                    To sign up as a food provider, register on our platform and select "Food Provider" as your account type. Fill out your business details, including your location, operating hours, and business license information. Once your account is verified (typically within 24-48 hours), you can start listing your surplus food items.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="provider2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#providerCollapse2" aria-expanded="false" aria-controls="providerCollapse2">
                                    What are the fees for food providers?
                                </button>
                            </h2>
                            <div id="providerCollapse2" class="accordion-collapse collapse" aria-labelledby="provider2" data-bs-parent="#providersAccordion">
                                <div class="accordion-body">
                                    SavedFeast charges a commission of 15% on each successfully completed order. There are no upfront costs, monthly fees, or hidden charges. You only pay when you sell something, making it a risk-free way to generate additional revenue from food that might otherwise be wasted.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="provider3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#providerCollapse3" aria-expanded="false" aria-controls="providerCollapse3">
                                    How do I receive payments?
                                </button>
                            </h2>
                            <div id="providerCollapse3" class="accordion-collapse collapse" aria-labelledby="provider3" data-bs-parent="#providersAccordion">
                                <div class="accordion-body">
                                    After setting up your provider account, you'll be prompted to connect your bank account or payment method for direct deposits. Payments are processed weekly for all completed orders, with funds typically appearing in your account within 2-3 business days after the transfer is initiated.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="provider4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#providerCollapse4" aria-expanded="false" aria-controls="providerCollapse4">
                                    What if a customer doesn't pick up their order?
                                </button>
                            </h2>
                            <div id="providerCollapse4" class="accordion-collapse collapse" aria-labelledby="provider4" data-bs-parent="#providersAccordion">
                                <div class="accordion-body">
                                    If a customer doesn't pick up their order during the specified pickup window, you can mark the order as "uncollected" in your provider dashboard. You'll still receive payment for the order, and the customer will be notified. SavedFeast monitors customer pickup behavior, and customers with multiple missed pickups may have their accounts restricted.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment & Pricing -->
                <div id="payment" class="mb-5">
                    <h3 class="border-bottom pb-3 mb-4">Payment & Pricing</h3>
                    
                    <div class="accordion mb-4" id="paymentAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="payment1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paymentCollapse1" aria-expanded="false" aria-controls="paymentCollapse1">
                                    What payment methods are accepted?
                                </button>
                            </h2>
                            <div id="paymentCollapse1" class="accordion-collapse collapse" aria-labelledby="payment1" data-bs-parent="#paymentAccordion">
                                <div class="accordion-body">
                                    SavedFeast accepts all major credit and debit cards, including Visa, MasterCard, American Express, and Discover. We also support payments through Apple Pay, Google Pay, and PayPal for added convenience and security.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="payment2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paymentCollapse2" aria-expanded="false" aria-controls="paymentCollapse2">
                                    How much can I save on meals?
                                </button>
                            </h2>
                            <div id="paymentCollapse2" class="accordion-collapse collapse" aria-labelledby="payment2" data-bs-parent="#paymentAccordion">
                                <div class="accordion-body">
                                    Meals on SavedFeast are typically offered at 40-70% off their original price, providing significant savings. The exact discount varies by provider and listing. Each listing clearly displays both the original price and the discounted SavedFeast price so you can see your savings.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="payment3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paymentCollapse3" aria-expanded="false" aria-controls="paymentCollapse3">
                                    Is there a subscription fee to use SavedFeast?
                                </button>
                            </h2>
                            <div id="paymentCollapse3" class="accordion-collapse collapse" aria-labelledby="payment3" data-bs-parent="#paymentAccordion">
                                <div class="accordion-body">
                                    No, SavedFeast is completely free to use for customers. There are no subscription fees or membership costs. You only pay for the meals you purchase, with no additional fees or surcharges added at checkout.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="payment4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paymentCollapse4" aria-expanded="false" aria-controls="paymentCollapse4">
                                    What's your refund policy?
                                </button>
                            </h2>
                            <div id="paymentCollapse4" class="accordion-collapse collapse" aria-labelledby="payment4" data-bs-parent="#paymentAccordion">
                                <div class="accordion-body">
                                    You can cancel your order and receive a full refund up to 1 hour before the pickup window starts. After that, refunds are at the discretion of the food provider. If you experience any issues with the quality or contents of your order, please contact our customer support team within 24 hours of pickup, and we'll work with you to resolve the issue.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Technical Support -->
                <div id="technical" class="mb-5">
                    <h3 class="border-bottom pb-3 mb-4">Technical Support</h3>
                    
                    <div class="accordion mb-4" id="technicalAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="technical1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technicalCollapse1" aria-expanded="false" aria-controls="technicalCollapse1">
                                    How do I reset my password?
                                </button>
                            </h2>
                            <div id="technicalCollapse1" class="accordion-collapse collapse" aria-labelledby="technical1" data-bs-parent="#technicalAccordion">
                                <div class="accordion-body">
                                    To reset your password, click on "Login," then select "Forgot Password." Enter the email address associated with your account, and you'll receive a password reset link. Click the link in the email and follow the instructions to create a new password.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="technical2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technicalCollapse2" aria-expanded="false" aria-controls="technicalCollapse2">
                                    The app/website isn't working properly. What should I do?
                                </button>
                            </h2>
                            <div id="technicalCollapse2" class="accordion-collapse collapse" aria-labelledby="technical2" data-bs-parent="#technicalAccordion">
                                <div class="accordion-body">
                                    First, try refreshing the page or restarting the app. If the issue persists, check your internet connection and ensure your browser or app is updated to the latest version. If you're still experiencing problems, please contact our technical support team with specific details about the issue, including your device model, operating system, and screenshots if possible.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="technical3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technicalCollapse3" aria-expanded="false" aria-controls="technicalCollapse3">
                                    How do I update my account information?
                                </button>
                            </h2>
                            <div id="technicalCollapse3" class="accordion-collapse collapse" aria-labelledby="technical3" data-bs-parent="#technicalAccordion">
                                <div class="accordion-body">
                                    To update your account information, log in and navigate to your profile page. Click on "Edit Profile" to update your name, email, phone number, or other personal details. To update payment information, go to the "Payment Methods" section in your account settings.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="technical4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#technicalCollapse4" aria-expanded="false" aria-controls="technicalCollapse4">
                                    How can I contact customer support?
                                </button>
                            </h2>
                            <div id="technicalCollapse4" class="accordion-collapse collapse" aria-labelledby="technical4" data-bs-parent="#technicalAccordion">
                                <div class="accordion-body">
                                    Our customer support team is available via email at support@savedfeast.com or through the in-app/website chat feature during business hours (Monday-Friday, 9am-6pm EST). For urgent matters, you can call our support line at (555) 123-4567. We aim to respond to all inquiries within 24 hours.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <h2 class="mb-3">Still have questions?</h2>
                <p class="lead mb-4">Our customer support team is ready to assist you with any other questions or concerns you may have.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('contact') }}" class="btn btn-primary">Contact Us</a>
                    <a href="mailto:support@savedfeast.com" class="btn btn-outline-primary">Email Support</a>
                </div>
            </div>
            <div class="col-lg-5 text-center">
                <img src="{{ asset('images/customer-support.svg') }}" alt="Customer Support" class="img-fluid" style="max-height: 250px;">
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                const headerOffset = 100;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
                
                // Update active state in sidebar
                document.querySelectorAll('.list-group-item').forEach(item => {
                    item.classList.remove('active');
                });
                this.classList.add('active');
            }
        });
    });
    
    // Set active menu item on scroll
    window.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('div[id]');
        const scrollPosition = window.scrollY + 150;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                const id = section.getAttribute('id');
                document.querySelectorAll('.list-group-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.getAttribute('href') === `#${id}`) {
                        item.classList.add('active');
                    }
                });
            }
        });
    });
});
</script>
@endpush
