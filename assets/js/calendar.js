/**
 * Colitalia Calendar JavaScript
 * 
 * Gestisce il calendario FullCalendar per disponibilità e prenotazioni
 * 
 * @since 1.2.0
 * @package Colitalia_Real_Estate
 */

(function($) {
    'use strict';
    
    // Oggetto principale
    window.ColitaliaCalendar = {
        
        // Istanze calendario
        calendars: {},
        
        // Configurazioni default
        defaultConfig: {
            initialView: 'dayGridMonth',
            locale: 'it',
            height: 'auto',
            firstDay: 1,
            displayEventTime: false,
            showNonCurrentDates: false,
            fixedWeekCount: false,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek,listMonth'
            },
            buttonText: {
                today: 'Oggi',
                month: 'Mese',
                week: 'Settimana',
                list: 'Lista'
            },
            dayMaxEvents: 3,
            moreLinkText: 'altri',
            noEventsText: 'Nessun evento da visualizzare'
        },
        
        /**
         * Inizializzazione singolo calendario
         */
        init: function(propertyId, config) {
            if (!propertyId) return;
            
            config = config || {};
            var $container = $(`#colitalia-calendar-${propertyId}`);
            
            if (!$container.length) {
                console.warn(`Container calendario non trovato: #colitalia-calendar-${propertyId}`);
                return;
            }
            
            // Merge configurazioni
            var calendarConfig = $.extend({}, this.defaultConfig, config, {
                events: this.loadEvents.bind(this, propertyId),
                eventDidMount: this.setupEventTooltip,
                dateClick: this.handleDateClick.bind(this, propertyId),
                eventClick: this.handleEventClick.bind(this, propertyId),
                datesSet: this.handleDatesSet.bind(this, propertyId)
            });
            
            // Inizializza FullCalendar
            var calendar = new FullCalendar.Calendar($container[0], calendarConfig);
            calendar.render();
            
            this.calendars[propertyId] = {
                instance: calendar,
                container: $container,
                config: calendarConfig
            };
            
            // Inizializza controlli
            this.initControls(propertyId);
            
            return calendar;
        },
        
        /**
         * Carica eventi dal server
         */
        loadEvents: function(propertyId, info, successCallback, failureCallback) {
            var self = this;
            
            $.ajax({
                url: ColitaliaCalendarAjax.ajax_url,
                type: 'GET',
                data: {
                    action: 'colitalia_get_calendar_data',
                    nonce: ColitaliaCalendarAjax.nonce,
                    property_id: propertyId,
                    start: info.startStr,
                    end: info.endStr
                },
                success: function(response) {
                    if (response.success) {
                        var events = self.processEvents(response.data);
                        successCallback(events);
                        self.hideLoading(propertyId);
                    } else {
                        failureCallback();
                        self.showError(propertyId, response.data || 'Errore caricamento eventi');
                    }
                },
                error: function() {
                    failureCallback();
                    self.showError(propertyId, 'Errore di connessione');
                },
                beforeSend: function() {
                    self.showLoading(propertyId);
                }
            });
        },
        
        /**
         * Processa eventi dal server
         */
        processEvents: function(data) {
            return data.map(function(day) {
                var event = {
                    id: `${day.extendedProps.date}`,
                    title: day.title || '',
                    start: day.start,
                    allDay: true,
                    classNames: day.classNames || [],
                    extendedProps: day.extendedProps || {}
                };
                
                // Colori basati su stato
                switch (day.extendedProps.status) {
                    case 'available':
                        event.backgroundColor = '#28a745';
                        event.borderColor = '#1e7e34';
                        event.textColor = '#ffffff';
                        break;
                    case 'booked':
                        event.backgroundColor = '#dc3545';
                        event.borderColor = '#bd2130';
                        event.textColor = '#ffffff';
                        break;
                    case 'blocked':
                        event.backgroundColor = '#6c757d';
                        event.borderColor = '#545b62';
                        event.textColor = '#ffffff';
                        break;
                    case 'maintenance':
                        event.backgroundColor = '#fd7e14';
                        event.borderColor = '#dc6502';
                        event.textColor = '#ffffff';
                        break;
                    default:
                        event.backgroundColor = '#e9ecef';
                        event.borderColor = '#ced4da';
                        event.textColor = '#495057';
                }
                
                // Evidenzia check-in/check-out
                if (day.extendedProps.is_checkin) {
                    event.classNames.push('checkin-day');
                    event.borderColor = '#007bff';
                    event.borderWidth = '3px';
                }
                if (day.extendedProps.is_checkout) {
                    event.classNames.push('checkout-day');
                    event.borderColor = '#17a2b8';
                    event.borderWidth = '3px';
                }
                
                return event;
            });
        },
        
        /**
         * Setup tooltip per eventi
         */
        setupEventTooltip: function(info) {
            var props = info.event.extendedProps;
            var $tooltip = $('#calendar-tooltip');
            
            if (!$tooltip.length) return;
            
            $(info.el).on('mouseenter', function(e) {
                var content = '';
                
                // Data
                content += `<div class="tooltip-date">${new Date(props.date).toLocaleDateString('it-IT', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                })}</div>`;
                
                // Stato
                var statusText = ColitaliaCalendarAjax.strings[props.status] || props.status;
                content += `<div class="tooltip-status status-${props.status}">${statusText}</div>`;
                
                // Prezzo
                if (props.price && props.status === 'available') {
                    content += `<div class="tooltip-price">${ColitaliaCalendarAjax.strings.price_from}${props.price}${ColitaliaCalendarAjax.strings.per_night}</div>`;
                }
                
                // Info prenotazione
                if (props.status === 'booked' && props.customer_name) {
                    content += `<div class="tooltip-booking">
                                    <strong>${ColitaliaCalendarAjax.strings.customer}:</strong> ${props.customer_name}<br>
                                    <strong>${ColitaliaCalendarAjax.strings.booking_code}:</strong> ${props.booking_code || 'N/A'}
                                </div>`;
                    
                    if (props.customer_phone) {
                        content += `<div class="tooltip-phone"><strong>${ColitaliaCalendarAjax.strings.phone}:</strong> ${props.customer_phone}</div>`;
                    }
                }
                
                // Note
                if (props.notes) {
                    content += `<div class="tooltip-notes"><strong>${ColitaliaCalendarAjax.strings.notes}:</strong> ${props.notes}</div>`;
                }
                
                $tooltip.find('.tooltip-content').html(content);
                
                // Posiziona tooltip
                var rect = e.target.getBoundingClientRect();
                $tooltip.css({
                    left: rect.left + (rect.width / 2) - ($tooltip.outerWidth() / 2),
                    top: rect.top - $tooltip.outerHeight() - 10
                }).show();
            });
            
            $(info.el).on('mouseleave', function() {
                $tooltip.hide();
            });
        },
        
        /**
         * Gestisce click su data
         */
        handleDateClick: function(propertyId, info) {
            // Solo per admin in modalità gestione
            if (!current_user_can('edit_posts')) return;
            
            var calendar = this.calendars[propertyId];
            if (!calendar) return;
            
            // Implementa logica click data per admin
            console.log('Date clicked:', info.dateStr, 'Property:', propertyId);
        },
        
        /**
         * Gestisce click su evento
         */
        handleEventClick: function(propertyId, info) {
            var props = info.event.extendedProps;
            
            // Se prenotazione, mostra dettagli
            if (props.status === 'booked' && props.booking_id) {
                this.showBookingDetails(props.booking_id);
            }
            
            // Se admin, permetti modifica
            if (current_user_can('edit_posts')) {
                this.showAvailabilityEditor(propertyId, props.date, props);
            }
        },
        
        /**
         * Gestisce cambio range date
         */
        handleDatesSet: function(propertyId, info) {
            // Aggiorna controlli se necessario
            this.updateControls(propertyId, info);
        },
        
        /**
         * Inizializza controlli calendario
         */
        initControls: function(propertyId) {
            var self = this;
            var $widget = $(`.colitalia-calendar-widget[data-property-id="${propertyId}"]`);
            
            // View switcher
            $widget.on('click', '.view-btn', function(e) {
                e.preventDefault();
                var view = $(this).data('view');
                
                $(this).addClass('active').siblings().removeClass('active');
                
                var calendar = self.calendars[propertyId];
                if (calendar) {
                    calendar.instance.changeView(view);
                }
            });
            
            // Sync calendar
            $widget.on('click', '#sync-calendar', function(e) {
                e.preventDefault();
                self.syncExternalCalendar(propertyId);
            });
            
            // Manage calendar
            $widget.on('click', '#manage-calendar', function(e) {
                e.preventDefault();
                self.openManagementModal(propertyId);
            });
            
            // Retry on error
            $widget.on('click', '.retry-calendar', function(e) {
                e.preventDefault();
                self.refreshCalendar(propertyId);
            });
        },
        
        /**
         * Mostra loading
         */
        showLoading: function(propertyId) {
            var $widget = $(`.colitalia-calendar-widget[data-property-id="${propertyId}"]`);
            $widget.find('.calendar-loading').show();
            $widget.find('.calendar-error').hide();
        },
        
        /**
         * Nascondi loading
         */
        hideLoading: function(propertyId) {
            var $widget = $(`.colitalia-calendar-widget[data-property-id="${propertyId}"]`);
            $widget.find('.calendar-loading').hide();
        },
        
        /**
         * Mostra errore
         */
        showError: function(propertyId, message) {
            var $widget = $(`.colitalia-calendar-widget[data-property-id="${propertyId}"]`);
            $widget.find('.calendar-loading').hide();
            $widget.find('.calendar-error .error-text').text(message);
            $widget.find('.calendar-error').show();
        },
        
        /**
         * Refresh calendario
         */
        refreshCalendar: function(propertyId) {
            var calendar = this.calendars[propertyId];
            if (calendar) {
                calendar.instance.refetchEvents();
            }
        },
        
        /**
         * Sincronizzazione calendario esterno
         */
        syncExternalCalendar: function(propertyId) {
            var self = this;
            
            $.ajax({
                url: ColitaliaCalendarAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'colitalia_sync_external_calendar',
                    nonce: ColitaliaCalendarAjax.nonce,
                    property_id: propertyId
                },
                success: function(response) {
                    if (response.success) {
                        self.refreshCalendar(propertyId);
                        alert('Calendario sincronizzato con successo!');
                    } else {
                        alert('Errore nella sincronizzazione: ' + (response.data || 'Errore sconosciuto'));
                    }
                },
                error: function() {
                    alert('Errore di connessione durante la sincronizzazione');
                }
            });
        },
        
        /**
         * Apre modal gestione calendario
         */
        openManagementModal: function(propertyId) {
            $('#calendar-management-modal').show();
            this.loadManagementData(propertyId);
        },
        
        /**
         * Carica dati per gestione
         */
        loadManagementData: function(propertyId) {
            // Carica stagioni esistenti
            this.loadSeasons(propertyId);
        },
        
        /**
         * Carica stagioni
         */
        loadSeasons: function(propertyId) {
            $.ajax({
                url: ColitaliaCalendarAjax.ajax_url,
                type: 'GET',
                data: {
                    action: 'colitalia_get_seasons',
                    nonce: ColitaliaCalendarAjax.nonce,
                    property_id: propertyId
                },
                success: function(response) {
                    if (response.success) {
                        // Popola lista stagioni
                        var html = '';
                        response.data.forEach(function(season) {
                            html += `<div class="season-item">
                                        <div class="season-name">${season.season_name}</div>
                                        <div class="season-dates">${season.date_from} - ${season.date_to}</div>
                                        <div class="season-price">€${season.base_price}/notte</div>
                                        <div class="season-actions">
                                            <button class="btn btn-sm edit-season" data-id="${season.id}">Modifica</button>
                                            <button class="btn btn-sm btn-danger delete-season" data-id="${season.id}">Elimina</button>
                                        </div>
                                     </div>`;
                        });
                        
                        $('.seasons-list').html(html || '<p>Nessuna stagione configurata</p>');
                    }
                }
            });
        },
        
        /**
         * Mostra dettagli prenotazione
         */
        showBookingDetails: function(bookingId) {
            // Implementa modal dettagli prenotazione
            window.open(`/wp-admin/admin.php?page=colitalia-bookings&booking_id=${bookingId}`, '_blank');
        },
        
        /**
         * Editor disponibilità
         */
        showAvailabilityEditor: function(propertyId, date, props) {
            // Implementa modal modifica disponibilità
            var newStatus = prompt('Nuovo stato (available/blocked/maintenance):', props.status);
            if (newStatus && newStatus !== props.status) {
                this.updateAvailability(propertyId, date, { status: newStatus });
            }
        },
        
        /**
         * Aggiorna disponibilità
         */
        updateAvailability: function(propertyId, date, data) {
            var self = this;
            
            $.ajax({
                url: ColitaliaCalendarAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'colitalia_update_availability',
                    nonce: ColitaliaCalendarAjax.nonce,
                    property_id: propertyId,
                    date: date,
                    ...data
                },
                success: function(response) {
                    if (response.success) {
                        self.refreshCalendar(propertyId);
                    } else {
                        alert('Errore aggiornamento: ' + (response.data || 'Errore sconosciuto'));
                    }
                },
                error: function() {
                    alert('Errore di connessione');
                }
            });
        },
        
        /**
         * Aggiorna controlli
         */
        updateControls: function(propertyId, info) {
            // Aggiorna range date nei controlli se necessario
        }
    };
    
    // Event handlers globali
    $(document).ready(function() {
        // Modal management
        $(document).on('click', '[data-dismiss="modal"]', function() {
            $(this).closest('.colitalia-modal').hide();
        });
        
        // Tab switching
        $(document).on('click', '.tab-btn', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            
            $(this).addClass('active').siblings().removeClass('active');
            $('.tab-content').removeClass('active');
            $(`#${tab}`).addClass('active');
        });
        
        // Bulk update form
        $(document).on('submit', '#bulk-update-form', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            var propertyId = ColitaliaCalendarAjax.property_id;
            
            $.ajax({
                url: ColitaliaCalendarAjax.ajax_url,
                type: 'POST',
                data: formData + `&action=colitalia_bulk_update_availability&nonce=${ColitaliaCalendarAjax.nonce}&property_id=${propertyId}`,
                success: function(response) {
                    if (response.success) {
                        alert(`Aggiornate ${response.data.count} date`);
                        ColitaliaCalendar.refreshCalendar(propertyId);
                        $('#calendar-management-modal').hide();
                    } else {
                        alert('Errore: ' + (response.data || 'Errore sconosciuto'));
                    }
                },
                error: function() {
                    alert('Errore di connessione');
                }
            });
        });
        
        // Season management
        $(document).on('click', '#add-season', function() {
            $('.new-season-form').show();
        });
        
        $(document).on('click', '#cancel-season', function() {
            $('.new-season-form').hide();
            $('#season-form')[0].reset();
        });
        
        // Hide tooltip quando si clicca fuori
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.fc-event, #calendar-tooltip').length) {
                $('#calendar-tooltip').hide();
            }
        });
    });
    
})(jQuery);
