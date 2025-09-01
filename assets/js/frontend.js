jQuery(document).ready(function($) {
    // Only initialize if notification bar exists
    if ($('.glint-scrolling-notification').length) {
        // Add class to body for initial padding
        $('body').addClass('gsn-notification-visible');
        
        // Initialize Swiper with dragging disabled
        if (typeof Swiper !== 'undefined') {
            var notificationSwiper = new Swiper('.glint-swiper-notifications', {
                direction: 'vertical',
                loop: true,
                autoplay: {
                    delay: glint_sn_vars.autoplay_delay || 3000,
                    disableOnInteraction: false,
                },
                speed: glint_sn_vars.animation_speed || 500,
                effect: 'slide',
                slidesPerView: 1,
                spaceBetween: 0,
                allowTouchMove: false, // Disable touch movement
                simulateTouch: false,  // Disable simulated touch events
                noSwiping: true,       // Completely disable swiping
                noSwipingClass: 'swiper-slide' // Apply to all slides
            });
        }
        
        // Handle scroll behavior
        let lastScrollTop = 0;
        const notificationBar = $('.glint-scrolling-notification');
        const scrollThreshold = 100; // Pixels to scroll before hiding
        
        $(window).scroll(function() {
            const scrollTop = $(this).scrollTop();
            
            if (scrollTop > lastScrollTop && scrollTop > scrollThreshold) {
                // Scrolling down - hide notification and remove space
                notificationBar.addClass('gsn-hidden');
                $('body')
                    .removeClass('gsn-notification-visible')
                    .addClass('gsn-notification-hidden');
            } else if (scrollTop < lastScrollTop || scrollTop <= scrollThreshold) {
                // Scrolling up or at top - show notification and add space
                notificationBar.removeClass('gsn-hidden');
                $('body')
                    .addClass('gsn-notification-visible')
                    .removeClass('gsn-notification-hidden');
            }
            
            lastScrollTop = scrollTop;
        });
        
        // Handle resize events to ensure proper positioning
        $(window).resize(function() {
            if (!$('.glint-scrolling-notification').hasClass('gsn-hidden')) {
                $('body').addClass('gsn-notification-visible');
            }
        });
    }
});