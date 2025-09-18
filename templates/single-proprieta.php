<?php
/**
 * Single Property Template - Stile Elementor (Versione Finale Definitiva)
 * * Questo template gestisce la visualizzazione di una singola proprietà.
 * Include la logica per mostrare la sezione di azione corretta (Investimento, 
 * Prenotazione o Contatto per la Vendita) in base al tipo di proprietà.
 * * @package ColitaliaRealEstate
 * @subpackage Templates
 */

// Impedisce l'accesso diretto al file
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="colitalia-single-property-container">
    <?php while (have_posts()) : the_post(); 
    
        // 1. RECUPERO DI TUTTI I DATI NECESSARI
        // ===================================================================
        $property_id = get_the_ID();
        
        // Utilizza le funzioni helper del plugin per ottenere i dati
        $property_info = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_info($property_id);
        $pricing_info = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_pricing($property_id);
        $gallery_images = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_gallery($property_id, 'large');
        
        // Determina a quali categorie appartiene la proprietà usando la funzione WordPress corretta
        $is_for_sale = has_term('vendita', 'tipo_proprieta', $property_id);
        $is_timeshare = has_term('multiproprieta', 'tipo_proprieta', $property_id);
        $is_vacation_rental = has_term('casa-vacanze', 'tipo_proprieta', $property_id);
    ?>
        
        <div class="property-main-layout">

            <div class="property-gallery-column">
                <?php if (!empty($gallery_images)): ?>
                    <div class="gallery-main-image">
                        <img src="<?php echo esc_url($gallery_images[0]['url']); ?>" alt="<?php echo esc_attr($gallery_images[0]['alt']); ?>">
                    </div>
                    <?php if (count($gallery_images) > 1): ?>
                        <div class="gallery-thumbnails">
                            <?php foreach ($gallery_images as $image): ?>
                                <img class="gallery-thumbnail" src="<?php echo esc_url(wp_get_attachment_image_url($image['id'], 'thumbnail')); ?>" data-full-image="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php elseif (has_post_thumbnail()): ?>
                    <div class="gallery-main-image">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php else: ?>
                     <div class="gallery-main-image">
                        <div style="height:400px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#999; border-radius:8px;">Nessuna immagine</div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="property-details-column">
                <h1 class="property-title"><?php the_title(); ?></h1>
                
                <?php if (!empty($property_info['location'])): ?>
                    <div class="property-location-meta">
                        <i class="fas fa-map-marker-alt"></i> <?php echo esc_html($property_info['location']); ?>
                    </div>
                <?php endif; ?>

                <div class="property-price-box">
                    <?php if ($is_for_sale && !empty($pricing_info['sale_price'])): ?>
                        <span class="price-label">Prezzo di Vendita</span>
                        <span class="price-amount">€<?php echo number_format_i18n($pricing_info['sale_price'], 0); ?></span>
                    <?php elseif ($is_timeshare && !empty($pricing_info['sale_price'])): ?>
                        <span class="price-label">Investimento a partire da</span>
                        <span class="price-amount">€<?php echo number_format_i18n($pricing_info['sale_price'], 0); ?></span>
                    <?php elseif (($is_vacation_rental || $is_timeshare) && !empty($pricing_info['weekly_price'])): ?>
                         <span class="price-label">A partire da</span>
                        <span class="price-amount">€<?php echo number_format_i18n($pricing_info['weekly_price'], 0); ?></span>
                        <span class="price-period">/ settimana</span>
                    <?php endif; ?>
                </div>

                <div class="property-quick-info">
                    <?php if (!empty($property_info['max_guests'])): ?><span><i class="fas fa-users"></i> <?php echo esc_html($property_info['max_guests']); ?> Ospiti</span><?php endif; ?>
                    <?php if (!empty($property_info['bedrooms'])): ?><span><i class="fas fa-bed"></i> <?php echo esc_html($property_info['bedrooms']); ?> Camere</span><?php endif; ?>
                    <?php if (!empty($property_info['bathrooms'])): ?><span><i class="fas fa-bath"></i> <?php echo esc_html($property_info['bathrooms']); ?> Bagni</span><?php endif; ?>
                    <?php if (!empty($property_info['size_sqm'])): ?><span><i class="fas fa-ruler-combined"></i> <?php echo esc_html($property_info['size_sqm']); ?> m²</span><?php endif; ?>
                </div>

                <div class="property-excerpt">
                    <?php the_excerpt(); ?>
                </div>

                <?php 
                // 2. LOGICA CON PRIORITÀ PER MOSTRARE UNA SOLA SEZIONE DI AZIONE
                // ===================================================================
                // Questa struttura if/elseif/else assicura che solo un blocco venga mostrato,
                // seguendo la logica richiesta dal cliente.
                
                if ($is_for_sale) {

                    echo '<h3>Contattaci per questa proprietà</h3>';
                    echo '<p>Per maggiori informazioni e per organizzare una visita, contattaci direttamente.</p>';
                    // Qui puoi aggiungere un pulsante di contatto o un form dedicato alla vendita.

                } elseif ($is_timeshare) {

                    echo '<h3>Investimento in Multiproprietà</h3>';
                    echo '<p>Scopri i vantaggi di possedere una quota di questa proprietà.</p>';
                    // Usa lo shortcode del calcolatore di investimento.
                    echo do_shortcode('[colitalia_investment_calculator property_id="' . $property_id . '"]');

                } elseif ($is_vacation_rental) {

                    echo '<h3>Prenota la tua Vacanza</h3>';
                    echo '<p>Verifica la disponibilità e prenota il tuo soggiorno.</p>';
                    // Usa lo shortcode del form di prenotazione.
                    echo do_shortcode('[colitalia_booking_form property_id="' . $property_id . '"]');

                }
                ?>
            </div>
        </div>

        <div class="property-full-details">
            <div class="tabs">
                <button class="tab-link active" onclick="openTab(event, 'description')">Descrizione</button>
                <button class="tab-link" onclick="openTab(event, 'features')">Caratteristiche</button>
                <button class="tab-link" onclick="openTab(event, 'map')">Mappa</button>
            </div>

            <div id="description" class="tab-content active">
                <h3>Descrizione Completa</h3>
                <?php the_content(); ?>
            </div>

            <div id="features" class="tab-content">
                <h3>Fatti e Caratteristiche</h3>
                <?php
                if (!empty($property_info['features'])): 
                    $features_array = explode("\n", $property_info['features']);
                    $features_array = array_filter(array_map('trim', $features_array));
                    if (!empty($features_array)): ?>
                        <ul class="features-list">
                            <?php foreach ($features_array as $feature): ?>
                                <li><i class="fas fa-check-circle"></i> <?php echo esc_html($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                         <p>Non ci sono caratteristiche aggiuntive specificate per questa proprietà.</p>
                    <?php endif;
                else: ?>
                    <p>Non ci sono caratteristiche aggiuntive specificate per questa proprietà.</p>
                <?php endif; ?>
            </div>

            <div id="map" class="tab-content">
                <h3>Posizione</h3>
                <?php
                $latitude = $property_info['latitude'];
                $longitude = $property_info['longitude'];
                if ($latitude && $longitude): ?>
                    <div class="property-map-container">
                        <iframe
                            width="100%"
                            height="450"
                            style="border:0"
                            loading="lazy"
                            allowfullscreen
                            src="https://maps.google.com/maps?q=<?php echo esc_attr($latitude); ?>,<?php echo esc_attr($longitude); ?>&hl=it&z=14&output=embed">
                        </iframe>
                    </div>
                <?php else: ?>
                    <p>Mappa non disponibile per questa proprietà.</p>
                <?php endif; ?>
            </div>
        </div>

    <?php endwhile; ?>
</div>

<script>
// 3. JAVASCRIPT PER LA GALLERIA E I TAB
// ===================================================================
// Questo script è essenziale per l'interattività della pagina.

document.addEventListener('DOMContentLoaded', function() {
    // Script per la galleria di immagini
    const mainImageEl = document.querySelector('.gallery-main-image img');
    const thumbnails = document.querySelectorAll('.gallery-thumbnail');
    if(mainImageEl && thumbnails.length > 0) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                mainImageEl.src = this.dataset.fullImage;
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
        thumbnails[0].classList.add('active');
    }
});

// Script per i Tab
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        tabcontent[i].classList.remove("active");
    }
    tablinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.className += " active";
}

// Mostra il primo tab di default
document.addEventListener('DOMContentLoaded', function() {
    if(document.querySelector('.tab-link.active')) {
       document.querySelector('.tab-link.active').click();
    }
});
</script>

<?php get_footer(); ?>