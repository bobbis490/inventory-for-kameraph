/**
 * KameraPH Interactive Animation Enhancements
 * Staggered reveals, image cross-fades, and button ripple effects
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // 1. Catalog Grid Staggered Entrance
    const catalogCards = document.querySelectorAll('.kp-card');
    if (catalogCards.length > 0) {
        catalogCards.forEach((card, index) => {
            const col = card.closest('.col');
            if (col) {
                col.classList.add('kp-catalog-item');
                col.style.setProperty('--item-index', Math.min(index, 24));
            }
        });
    }

    // 2. Listing Table Rows Staggered Reveal
    const tableRows = document.querySelectorAll('.table-kamera tbody tr');
    if (tableRows.length > 0 && tableRows.length <= 40) {
        tableRows.forEach((row, index) => {
            row.style.animation = 'kphFadeInUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) both';
            row.style.animationDelay = `${Math.min(index * 0.025, 0.6)}s`;
        });
    }

    // 3. Review Queue Cards Stagger
    const reviewCards = document.querySelectorAll('.review-queue-card');
    if (reviewCards.length > 0) {
        reviewCards.forEach((card, index) => {
            card.style.setProperty('--item-index', index);
        });
    }

    // 4. Product Detail Image Gallery Smooth Switch (product_listing/view.php)
    const mainImg = document.getElementById('mainDisplayImage');
    const thumbContainer = document.querySelector('.card-body .d-flex.flex-wrap.gap-2');
    if (mainImg && thumbContainer) {
        thumbContainer.classList.add('kph-thumb-strip');
        const thumbs = thumbContainer.querySelectorAll('img');
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', function() {
                if (mainImg.src === this.src) return;
                mainImg.style.opacity = '0.3';
                mainImg.style.transform = 'scale(0.97)';
                setTimeout(() => {
                    mainImg.src = this.src;
                    mainImg.style.opacity = '1';
                    mainImg.style.transform = 'scale(1)';
                }, 140);

                // Update active border
                thumbs.forEach(t => t.style.setProperty('border', '1px solid #cbd5e1', 'important'));
                this.style.setProperty('border', '2px solid #d84315', 'important');
            });
        });
    }

    // 5. Button Ripple / Press Effect
    const actionButtons = document.querySelectorAll('.btn-primary, .btn-hold-action, .btn-nav-new-listing, .btn-warning');
    actionButtons.forEach(btn => {
        btn.addEventListener('mousedown', function(e) {
            const rect = btn.getBoundingClientRect();
            const ripple = document.createElement('span');
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = `${size}px`;
            ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
            ripple.style.top = `${e.clientY - rect.top - size / 2}px`;
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(255, 255, 255, 0.35)';
            ripple.style.pointerEvents = 'none';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'kphRipple 0.5s ease-out';
            
            // Ensure button has relative positioning
            if (getComputedStyle(btn).position === 'static') {
                btn.style.position = 'relative';
            }
            btn.style.overflow = 'hidden';
            btn.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 550);
        });
    });

    // Inject ripple keyframes if not already present
    if (!document.getElementById('kph-ripple-style')) {
        const style = document.createElement('style');
        style.id = 'kph-ripple-style';
        style.textContent = `
            @keyframes kphRipple {
                to {
                    transform: scale(2.4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
});
