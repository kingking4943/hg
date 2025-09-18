/**
 * Admin JavaScript for Colitalia Real Estate Plugin
 * @package ColitaliaRealEstate
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        initPropertyGallery();
        initFormValidation();
        initConditionalFields();
        initAutoComplete();
        initBookingActions();
        
    });

    /**
     * Gestisce le azioni sulle prenotazioni (es. cancellazione, update stato, delete)
     */
    function initBookingActions() {
        var nonce = colitaliaAdmin.nonce;

        // Azione per ANNULLARE
        $('#the-list').on('click', '.cancel-booking-link', function(e) {
            e.preventDefault();
            if (!confirm('Sei sicuro di voler ANNULLARE questa prenotazione?')) return;
            
            var bookingId = $(this).data('booking-id');
            var $row = $('#booking-' + bookingId);
            $row.css('opacity', '0.5');

            $.ajax({
                url: colitaliaAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'colitalia_cancel_booking',
                    booking_id: bookingId,
                    _nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Errore: ' + response.data.message);
                        $row.css('opacity', '1');
                    }
                },
                error: function() {
                    alert('Errore di comunicazione.');
                    $row.css('opacity', '1');
                }
            });
        });

        // Azione per AGGIORNARE LO STATO
        $('#the-list').on('change', '.booking-status-select', function() {
            var $select = $(this);
            var bookingId = $select.data('booking-id');
            var newStatus = $select.val();
            
            $select.prop('disabled', true);

            $.ajax({
                url: colitaliaAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'colitalia_update_booking_status',
                    booking_id: bookingId,
                    new_status: newStatus,
                    _nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        var $notice = $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                        $('#booking-ajax-response').html($notice).hide().fadeIn();
                        setTimeout(function() { $notice.fadeOut(400, function(){ $(this).remove(); }); }, 3000);
                    } else {
                        alert('Errore: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Errore di comunicazione.');
                },
                complete: function() {
                    $select.prop('disabled', false);
                }
            });
        });

        // Azione per ELIMINARE DEFINITIVAMENTE
        $('#the-list').on('click', '.delete-booking-link', function(e) {
            e.preventDefault();
            if (!confirm('ATTENZIONE: Stai per ELIMINARE DEFINITIVAMENTE questa prenotazione. L\'azione non è reversibile. Continuare?')) return;

            var bookingId = $(this).data('booking-id');
            var $row = $('#booking-' + bookingId);
            $row.css('opacity', '0.5');

            $.ajax({
                url: colitaliaAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'colitalia_delete_booking',
                    booking_id: bookingId,
                    _nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(400, function() { $(this).remove(); });
                    } else {
                        alert('Errore: ' + response.data.message);
                        $row.css('opacity', '1');
                    }
                },
                error: function() {
                    alert('Errore di comunicazione.');
                    $row.css('opacity', '1');
                }
            });
        });
    }
    
    /**
     * Initialize property gallery functionality
     */
    function initPropertyGallery() {
        var mediaUploader;
        
        $(document).on('click', '#add-gallery-images', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: colitaliaAdmin.strings.chooseImages,
                button: { text: colitaliaAdmin.strings.addToGallery },
                multiple: true,
                library: { type: 'image' }
            });
            
            mediaUploader.on('select', function() {
                var attachments = mediaUploader.state().get('selection').toJSON();
                var currentIds = $('#property_gallery_ids').val().split(',').filter(id => id !== '');
                
                attachments.forEach(function(attachment) {
                    if (currentIds.indexOf(attachment.id.toString()) === -1) {
                        currentIds.push(attachment.id);
                        addImageToGallery(attachment);
                    }
                });
                
                $('#property_gallery_ids').val(currentIds.join(','));
                updateGalleryPreview();
            });
            
            mediaUploader.open();
        });
        
        $(document).on('click', '.remove-image', function(e) {
            e.preventDefault();
            
            var imageId = $(this).parent().data('image-id');
            var currentIds = $('#property_gallery_ids').val().split(',');
            var newIds = currentIds.filter(id => id != imageId);
            
            $('#property_gallery_ids').val(newIds.join(','));
            $(this).parent().remove();
            updateGalleryPreview();
        });
        
        if ($('#property-gallery-preview').length) {
            $('#property-gallery-preview').sortable({
                items: '.gallery-image',
                cursor: 'move',
                tolerance: 'pointer',
                update: function() {
                    var sortedIds = [];
                    $('.gallery-image').each(function() {
                        sortedIds.push($(this).data('image-id'));
                    });
                    $('#property_gallery_ids').val(sortedIds.join(','));
                }
            });
        }
    }
    
    function addImageToGallery(attachment) {
        var thumbnailUrl = attachment.sizes?.thumbnail?.url || attachment.url;
        var imageHtml = `<div class="gallery-image" data-image-id="${attachment.id}"><img src="${thumbnailUrl}" alt="${attachment.alt}" /><button type="button" class="remove-image" title="${colitaliaAdmin.strings.removeImage}">×</button></div>`;
        $('#property-gallery-preview').append(imageHtml);
    }
    
    function updateGalleryPreview() {
        var $preview = $('#property-gallery-preview');
        $preview.toggleClass('empty', $preview.find('.gallery-image').length === 0);
    }
    
    function initFormValidation() {
        $('#post').on('submit', function(e) {
            var hasErrors = false;
            var errorMessages = [];
            
            $('.required-numeric').each(function() {
                var $field = $(this);
                if ($field.val() !== '' && (isNaN(parseFloat($field.val())) || parseFloat($field.val()) < 0)) {
                    hasErrors = true; $field.addClass('error'); errorMessages.push(colitaliaAdmin.strings.invalidNumber.replace('%s', $field.attr('name')));
                } else { $field.removeClass('error'); }
            });
            
            if (hasErrors) {
                e.preventDefault();
                showErrorMessages(errorMessages);
                $('html, body').animate({ scrollTop: $('.error').first().offset().top - 100 }, 500);
            }
        });
    }
    
    function initConditionalFields() {
        $('#property_is_multiproperty').on('change', function() {
            $('.multiproperty-field').toggle($(this).is(':checked'));
        }).trigger('change');
        
        $('input[name="tax_input[tipo_proprieta][]"]').on('change', updatePricingFieldsVisibility);
        updatePricingFieldsVisibility();
    }
    
    function updatePricingFieldsVisibility() {
        var selectedTypes = [];
        $('input[name="tax_input[tipo_proprieta][]"]:checked').each(function() { selectedTypes.push($(this).val()); });
        $('.sale-price-field').toggle(selectedTypes.includes('vendita'));
        $('.rental-price-field').toggle(selectedTypes.includes('casa-vacanze') || selectedTypes.includes('multiproprieta'));
    }
    
    function initAutoComplete() {
        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            var locationField = document.getElementById('property_location');
            if (locationField) {
                var autocomplete = new google.maps.places.Autocomplete(locationField);
                autocomplete.addListener('place_changed', function() {
                    var place = autocomplete.getPlace();
                    if (place.geometry) {
                        $('#property_latitude').val(place.geometry.location.lat());
                        $('#property_longitude').val(place.geometry.location.lng());
                    }
                });
            }
        }
    }
    
})(jQuery);