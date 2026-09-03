// Modern App Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeAppCards();
    initializeContactCards();
    initializeLoadingOverlay();
    initializeAnimations();
});

// App Cards Functionality
function initializeAppCards() {
    const appCards = document.querySelectorAll('.app-card:not(.disabled)');
    
    appCards.forEach(card => {
        const appName = card.getAttribute('data-app');
        const link = card.querySelector('.app-link');
        
        if (link) {
            // Add click tracking
            link.addEventListener('click', function(e) {
                trackAppClick(appName);
                showLoadingOverlay();
            });
            
            // Add keyboard navigation
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    link.click();
                }
            });
            
            // Make card focusable
            card.setAttribute('tabindex', '0');
        }
    });
    
    // Handle disabled cards
    const disabledCards = document.querySelectorAll('.app-card.disabled');
    disabledCards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            showComingSoonNotification();
        });
    });
}

// Contact Cards Functionality
function initializeContactCards() {
    const contactCards = document.querySelectorAll('.contact-card');
    
    contactCards.forEach(card => {
        const contactType = card.getAttribute('data-contact');
        const link = card.querySelector('.contact-link');
        
        if (link) {
            link.addEventListener('click', function(e) {
                trackContactClick(contactType);
                
                // Show confirmation for WhatsApp
                if (contactType === 'whatsapp') {
                    if (!confirm('Anda akan diarahkan ke WhatsApp. Lanjutkan?')) {
                        e.preventDefault();
                    }
                }
                
                // Show confirmation for email
                if (contactType === 'email') {
                    if (!confirm('Anda akan membuka aplikasi email default. Lanjutkan?')) {
                        e.preventDefault();
                    }
                }
            });
            
            // Add keyboard navigation
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    link.click();
                }
            });
            
            // Make card focusable
            card.setAttribute('tabindex', '0');
        }
    });
}

// Loading Overlay Functionality
function initializeLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    
    if (overlay) {
        // Hide loading overlay when page loads
        window.addEventListener('load', function() {
            hideLoadingOverlay();
        });
        
        // Hide loading overlay on back button
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                hideLoadingOverlay();
            }
        });
    }
}

// Show loading overlay
function showLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Auto-hide after 5 seconds as fallback
        setTimeout(() => {
            hideLoadingOverlay();
        }, 5000);
    }
}

// Hide loading overlay
function hideLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Initialize animations
function initializeAnimations() {
    // Fade in animation for cards
    const cards = document.querySelectorAll('.app-card, .contact-card');
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                entry.target.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                
                // Delay animation based on index
                const delay = Array.from(cards).indexOf(entry.target) * 100;
                
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, delay);
                
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    cards.forEach(card => {
        observer.observe(card);
    });
    
    // Parallax effect for background
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const parallax = document.querySelector('body');
        const speed = scrolled * 0.5;
        
        if (parallax) {
            parallax.style.backgroundPosition = `center ${speed}px`;
        }
    });
}

// Show coming soon notification
function showComingSoonNotification() {
    // Create custom notification

    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        min-width: 320px;
        animation: slideIn 0.3s ease;
    `;
    
    // Add notification styles
    const style = document.createElement('style');
    style.textContent = `
        .custom-notification {
            transform: translateX(100%);
        }
        
        @keyframes slideIn {
            to { transform: translateX(0); }
        }
        
        @keyframes slideOut {
            to { transform: translateX(100%); }
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            padding: 20px;
            gap: 15px;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .notification-text h5 {
            margin: 0 0 5px 0;
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
        }
        
        .notification-text p {
            margin: 0;
            font-size: 0.85rem;
            color: #6b7280;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 1rem;
            color: #9ca3af;
            cursor: pointer;
            padding: 5px;
        }
    `;
    document.head.appendChild(style);
    
    // Append notification to body
    document.body.appendChild(notification);
    
    // Close notification on button click
    notification.querySelector('.notification-close').addEventListener('click', function() {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    });
}

// Show the notification
showComingSoonNotification();