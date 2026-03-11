/**
 * Customer Menu JavaScript
 * Menu Scanner System - Customer Facing
 */

document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll for category navigation
    const categoryLinks = document.querySelectorAll('.category-nav .nav-link');
    categoryLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                const offsetTop = targetSection.offsetTop - 100;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
                
                // Update active state
                categoryLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // Update active category on scroll
    const sections = document.querySelectorAll('.category-section');
    const observerOptions = {
        root: null,
        rootMargin: '-100px 0px -60% 0px',
        threshold: 0
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.id;
                categoryLinks.forEach(link => {
                    if (link.getAttribute('href') === '#' + id) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
            }
        });
    }, observerOptions);
    
    sections.forEach(section => observer.observe(section));
    
    // Call waiter form submission
    const submitCallButton = document.getElementById('submitCallWaiter');
    if (submitCallButton) {
        submitCallButton.addEventListener('click', function() {
            const form = document.getElementById('callWaiterForm');
            const tableId = form.querySelector('[name="table_id"]').value;
            const tableUuid = form.querySelector('[name="table_uuid"]').value;
            const callType = form.querySelector('[name="call_type"]').value;
            const notes = form.querySelector('[name="notes"]').value;
            
            // Show loading state
            submitCallButton.disabled = true;
            submitCallButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
            
            // Send AJAX request
            fetch('../api/service-call.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    table_id: tableId,
                    table_uuid: tableUuid,
                    call_type: callType,
                    notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close call modal
                    const callModal = bootstrap.Modal.getInstance(document.getElementById('callWaiterModal'));
                    callModal.hide();
                    
                    // Show success modal
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                    
                    // Reset form
                    form.reset();
                } else {
                    alert(data.error || 'Failed to send request. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                submitCallButton.disabled = false;
                submitCallButton.innerHTML = '<i class="bi bi-bell"></i> Call Now';
            });
        });
    }
    
    // Variation price update
    const variationSelects = document.querySelectorAll('.variation-select');
    variationSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const priceModifier = parseFloat(selectedOption.dataset.price) || 0;
            const card = this.closest('.menu-item-card');
            const priceBadge = card.querySelector('.price-badge');
            
            if (priceBadge && priceModifier !== 0) {
                const basePrice = parseFloat(priceBadge.dataset.basePrice) || 0;
                const newPrice = basePrice + priceModifier;
                priceBadge.textContent = '$' + newPrice.toFixed(2);
            }
        });
    });
    
    // Add base price data to price badges for variation calculation
    const priceBadges = document.querySelectorAll('.price-badge');
    priceBadges.forEach(function(badge) {
        const text = badge.textContent.replace('$', '');
        badge.dataset.basePrice = parseFloat(text) || 0;
    });
    
    // Lazy loading images
    const images = document.querySelectorAll('.menu-item-image');
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.add('loaded');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(function(img) {
            imageObserver.observe(img);
        });
    }
    
    // Pull to refresh (mobile)
    let touchStartY = 0;
    let touchEndY = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartY = e.touches[0].clientY;
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        touchEndY = e.changedTouches[0].clientY;
        const pullDistance = touchEndY - touchStartY;
        
        if (pullDistance > 150 && window.scrollY === 0) {
            // Pull to refresh detected
            showRefreshNotification();
        }
    }, { passive: true });
    
    // Hide header on scroll down, show on scroll up (mobile)
    let lastScrollY = window.scrollY;
    const header = document.querySelector('.header');
    
    window.addEventListener('scroll', function() {
        if (window.innerWidth < 768) {
            if (window.scrollY > lastScrollY && window.scrollY > 100) {
                header.style.transform = 'translateY(-100%)';
                header.style.transition = 'transform 0.3s ease';
            } else {
                header.style.transform = 'translateY(0)';
            }
            lastScrollY = window.scrollY;
        }
    }, { passive: true });
});

// Show refresh notification
function showRefreshNotification() {
    const notification = document.createElement('div');
    notification.className = 'toast-notification';
    notification.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refreshing menu...';
    document.body.appendChild(notification);
    
    setTimeout(function() {
        location.reload();
    }, 1000);
}

// Show toast notification
function showToast(message, duration = 3000) {
    const toast = document.createElement('div');
    toast.className = 'toast-notification show';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(function() {
        toast.classList.remove('show');
        setTimeout(function() {
            toast.remove();
        }, 300);
    }, duration);
}

// Offline detection
window.addEventListener('online', function() {
    showToast('Back online!');
});

window.addEventListener('offline', function() {
    showToast('You\'re offline. Menu may be limited.', 5000);
});

// Handle back button for modals
window.addEventListener('popstate', function() {
    const modals = document.querySelectorAll('.modal.show');
    modals.forEach(function(modal) {
        const instance = bootstrap.Modal.getInstance(modal);
        if (instance) {
            instance.hide();
        }
    });
});
