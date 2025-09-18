/**
 * Frontend JavaScript for Colitalia Real Estate Plugin
 * 
 * @package ColitaliaRealEstate
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize components
        initPropertyGallery();
        initBookingCalendar();
        initSearchForm();
        initBookingForm();
        
        // Global event handlers
        setupGlobalHandlers();
        
    });
    
    /**
     * Initialize property gallery
     */
    function initPropertyGallery() {
        
        // Gallery thumbnail click
        $(document).on('click', '.gallery-thumbnail', function(e) {
            e.preventDefault();
            
            var $thumbnail = $(this);
            var mainImageSrc = $thumbnail.data('full-image') || $thumbnail.attr('src');
            var altText = $thumbnail.attr('alt');
            
            // Update main image
            $('.gallery-main-image').attr('src', mainImageSrc).attr('alt', altText);
            
            // Update active state
            $('.gallery-thumbnail').removeClass('active');
            $thumbnail.addClass('active');
        });
        
        // Initialize first thumbnail as active
        $('.gallery-thumbnail:first').addClass('active');
    }
    
    /**
     * Initialize booking calendar
     */
    function initBookingCalendar() {
        var currentMonth = new Date().getMonth();
        var currentYear = new Date().getFullYear();
        var selectedDates = [];
        
        // Calendar navigation
        $(document).on('click', '.calendar-nav .prev-month', function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            loadCalendarMonth(currentMonth, currentYear);
        });
        
        $(document).on('click', '.calendar-nav .next-month', function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            loadCalendarMonth(currentMonth, currentYear);
        });
        
        // Date selection
        $(document).on('click', '.calendar-day.available', function() {
            var $day = $(this);
            var date = $day.data('date');
            
            if (selectedDates.length === 0) {
                // First date (check-in)
                selectedDates.push(date);
                $day.addClass('selected check-in');
                updateBookingForm('checkin', date);
                
            } else if (selectedDates.length === 1) {
                // Second date (check-out)
                var checkinDate = new Date(selectedDates[0]);
                var checkoutDate = new Date(date);
                
                if (checkoutDate > checkinDate) {
                    selectedDates.push(date);
                    $day.addClass('selected check-out');
                    highlightDateRange(checkinDate, checkoutDate);
                    updateBookingForm('checkout', date);
                    calculateBookingPrice();
                } else {
                    // Reset if second date is before first
                    resetCalendarSelection();
                    selectedDates = [date];
                    $day.addClass('selected check-in');
                    updateBookingForm('checkin', date);
                }
                
            } else {
                // Reset and start over
                resetCalendarSelection();
                selectedDates = [date];
                $day.addClass('selected check-in');
                updateBookingForm('checkin', date);
            }
        });
        
        // Load initial calendar
        if ($('.colitalia-booking-calendar').length) {
            loadCalendarMonth(currentMonth, currentYear);
        }
    }
    
    /**
     * Load calendar month
     */
    function loadCalendarMonth(month, year) {
        var propertyId = $('.colitalia-booking-calendar').data('property-id');
        
        if (!propertyId) return;
        
        $('.colitalia-booking-calendar').addClass('colitalia-loading');
        
        $.ajax({
            url: colitaliaFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'colitalia_load_calendar',
                property_id: propertyId,
                month: month,
                year: year,
                nonce: colitaliaFrontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.calendar-grid').html(response.data.calendar);
                    $('.calendar-title').text(response.data.month_name + ' ' + year);
                }
            },
            error: function() {
                showMessage('error', colitaliaFrontend.strings.error);
            },
            complete: function() {
                $('.colitalia-booking-calendar').removeClass('colitalia-loading');
            }
        });
    }
    
    /**
     * Initialize search form
     */
    function initSearchForm() {
        
        // Search form submission
        $(document).on('submit', '.colitalia-search-form form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = $form.serialize();
            
            $('.colitalia-properties-grid').addClass('colitalia-loading');
            
            $.ajax({
                url: colitaliaFrontend.ajaxUrl,
                type: 'POST',
                data: formData + '&action=colitalia_search_properties&nonce=' + colitaliaFrontend.nonce,
                success: function(response) {
                    if (response.success) {
                        $('.colitalia-properties-grid').html(response.data.properties);
                        updateSearchResults(response.data.total);
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    showMessage('error', colitaliaFrontend.strings.error);
                },
                complete: function() {
                    $('.colitalia-properties-grid').removeClass('colitalia-loading');
                }
            });
        });
        
        // Advanced search toggle
        $(document).on('click', '.toggle-advanced-search', function(e) {
            e.preventDefault();
            $('.advanced-search-fields').slideToggle();
        });
    }
    
    /**
     * Initialize booking form
     */
    function initBookingForm() {
        
        // Form submission
        $(document).on('submit', '.colitalia-booking-form form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = $form.serialize();
            
            if (!validateBookingForm($form)) {
                return;
            }
            
            $form.addClass('colitalia-loading');
            
            $.ajax({
                url: colitaliaFrontend.ajaxUrl,
                type: 'POST',
                data: formData + '&action=colitalia_submit_booking&nonce=' + colitaliaFrontend.nonce,
                success: function(response) {
                    if (response.success) {
                        showMessage('success', colitaliaFrontend.strings.bookingSuccess);
                        
                        if (response.data.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 2000);
                        }
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function() {
                    showMessage('error', colitaliaFrontend.strings.bookingError);
                },
                complete: function() {
                    $form.removeClass('colitalia-loading');
                }
            });
        });
        
        // Guest number change
        $(document).on('change', 'select[name="guests"]', function() {
            calculateBookingPrice();
        });
        
        // Service selection change
        $(document).on('change', '.service-checkbox', function() {
            calculateBookingPrice();
        });
    }
    
    /**
     * Setup global event handlers
     */
    function setupGlobalHandlers() {
        
        // Smooth scroll to elements
        $(document).on('click', 'a[href^="#"]', function(e) {
            var target = $($(this).attr('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 500);
            }
        });
        
        // Close messages
        $(document).on('click', '.colitalia-message .close', function() {
            $(this).closest('.colitalia-message').fadeOut();
        });
        
        // Property card hover effects
        $(document).on('mouseenter', '.colitalia-property-card', function() {
            $(this).addClass('hovered');
        }).on('mouseleave', '.colitalia-property-card', function() {
            $(this).removeClass('hovered');
        });
    }
    
    /**
     * Utility functions
     */
    
    /**
     * Reset calendar selection
     */
    function resetCalendarSelection() {
        $('.calendar-day').removeClass('selected check-in check-out in-range');
    }
    
    /**
     * Highlight date range
     */
    function highlightDateRange(startDate, endDate) {
        $('.calendar-day').each(function() {
            var $day = $(this);
            var dayDate = new Date($day.data('date'));
            
            if (dayDate > startDate && dayDate < endDate) {
                $day.addClass('in-range');
            }
        });
    }
    
    /**
     * Update booking form with selected dates
     */
    function updateBookingForm(field, date) {
        if (field === 'checkin') {
            $('input[name="checkin"]').val(date);
        } else if (field === 'checkout') {
            $('input[name="checkout"]').val(date);
        }
    }
    
    /**
     * Calculate booking price
     */
    function calculateBookingPrice() {
        var checkin = $('input[name="checkin"]').val();
        var checkout = $('input[name="checkout"]').val();
        var guests = $('select[name="guests"]').val();
        var propertyId = $('.colitalia-booking-form').data('property-id');
        
        if (!checkin || !checkout || !propertyId) {
            return;
        }
        
        var services = [];
        $('.service-checkbox:checked').each(function() {
            services.push($(this).val());
        });
        
        $('.booking-price-summary').addClass('colitalia-loading');
        
        $.ajax({
            url: colitaliaFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'colitalia_calculate_price',
                property_id: propertyId,
                checkin: checkin,
                checkout: checkout,
                guests: guests,
                services: services,
                nonce: colitaliaFrontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    updatePriceSummary(response.data);
                }
            },
            complete: function() {
                $('.booking-price-summary').removeClass('colitalia-loading');
            }
        });
    }
    
    /**
     * Update price summary
     */
    function updatePriceSummary(priceData) {
        var summaryHtml = '<div class="price-breakdown">';
        summaryHtml += '<div class="base-price">Prezzo base: €' + priceData.base_price + '</div>';
        
        if (priceData.services_price > 0) {
            summaryHtml += '<div class="services-price">Servizi: €' + priceData.services_price + '</div>';
        }
        
        summaryHtml += '<div class="total-price">Totale: €' + priceData.total_price + '</div>';
        summaryHtml += '</div>';
        
        $('.booking-price-summary').html(summaryHtml);
    }
    
    /**
     * Validate booking form
     */
    function validateBookingForm($form) {
        var isValid = true;
        
        // Clear previous errors
        $('.form-group').removeClass('error');
        $('.error-message').remove();
        
        // Required fields
        $form.find('[required]').each(function() {
            var $field = $(this);
            var $group = $field.closest('.form-group');
            
            if (!$field.val().trim()) {
                $group.addClass('error');
                $group.append('<div class="error-message">Questo campo è obbligatorio</div>');
                isValid = false;
            }
        });
        
        // Email validation
        var email = $form.find('input[type="email"]').val();
        if (email && !isValidEmail(email)) {
            var $group = $form.find('input[type="email"]').closest('.form-group');
            $group.addClass('error');
            $group.append('<div class="error-message">Inserisci un indirizzo email valido</div>');
            isValid = false;
        }
        
        // Phone validation
        var phone = $form.find('input[name="phone"]').val();
        if (phone && !isValidPhone(phone)) {
            var $group = $form.find('input[name="phone"]').closest('.form-group');
            $group.addClass('error');
            $group.append('<div class="error-message">Inserisci un numero di telefono valido</div>');
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Update search results count
     */
    function updateSearchResults(total) {
        var message = total === 0 ? 
            colitaliaFrontend.strings.noResults : 
            total + ' proprietà trovate';
        
        $('.search-results-count').text(message);
    }
    
    /**
     * Show message
     */
    function showMessage(type, message) {
        var messageHtml = '<div class="colitalia-message ' + type + '">' +
            '<p>' + message + '</p>' +
            '<button class="close">&times;</button>' +
            '</div>';
        
        $('.colitalia-messages').html(messageHtml);
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(function() {
                $('.colitalia-message').fadeOut();
            }, 5000);
        }
    }
    
    /**
     * Validation helpers
     */
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function isValidPhone(phone) {
        var re = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        return re.test(phone);
    }
    
    /**
     * Format currency
     */
    function formatCurrency(amount) {
        return '€' + parseFloat(amount).toLocaleString('it-IT', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    /**
     * Debounce function
     */
    function debounce(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            
            var later = function() {
                clearTimeout(timeout);
                func.apply(context, args);
            };
            
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
})(jQuery);

/**
 * Initialize on document ready and window load
 */
jQuery(document).ready(function($) {
    
    // Lazy loading for images
    if ('IntersectionObserver' in window) {
        var imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(function(img) {
            imageObserver.observe(img);
        });
    }
    
    // Progressive enhancement
    $('body').addClass('js-enabled');
    
    // Mobile menu toggle (if needed)
    $(document).on('click', '.mobile-menu-toggle', function() {
        $(this).toggleClass('active');
        $('.mobile-menu').slideToggle();
    });
    
});

/**
 * Window load events
 */
jQuery(window).on('load', function() {
    
    // Remove loading class from body
    $('body').removeClass('loading');
    
    // Initialize AOS (Animate On Scroll) if available
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true
        });
    }
    
});
