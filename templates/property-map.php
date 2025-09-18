<?php
/**
 * Property Map Template
 * 
 * @package ColitaliaRealEstate
 * @version 1.5.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$property_id = $args['property_id'] ?? get_the_ID();
$latitude = get_post_meta($property_id, '_colitalia_latitude', true);
$longitude = get_post_meta($property_id, '_colitalia_longitude', true);
$address = get_post_meta($property_id, '_colitalia_address', true);
$zoom = get_post_meta($property_id, '_colitalia_map_zoom', true) ?: 15;

if (!$latitude || !$longitude) {
    echo '<p class="colitalia-no-map">' . __('Mappa non disponibile per questa proprietà.', 'colitalia-real-estate') . '</p>';
    return;
}
?>

<div class="colitalia-property-map-wrapper">
    <?php if ($address): ?>
        <div class="colitalia-map-address">
            <i class="fas fa-map-marker-alt"></i>
            <span><?php echo esc_html($address); ?></span>
        </div>
    <?php endif; ?>
    
    <div id="colitalia-property-map" 
         class="colitalia-map" 
         data-lat="<?php echo esc_attr($latitude); ?>"
         data-lng="<?php echo esc_attr($longitude); ?>"
         data-zoom="<?php echo esc_attr($zoom); ?>"
         data-address="<?php echo esc_attr($address); ?>"
         data-title="<?php echo esc_attr(get_the_title($property_id)); ?>">
    </div>
    
    <div class="colitalia-map-controls">
        <button type="button" class="colitalia-map-fullscreen" title="<?php _e('Visualizza a schermo intero', 'colitalia-real-estate'); ?>">
            <i class="fas fa-expand"></i>
        </button>
        <button type="button" class="colitalia-map-directions" title="<?php _e('Ottieni indicazioni', 'colitalia-real-estate'); ?>">
            <i class="fas fa-directions"></i>
        </button>
    </div>
</div>

<style>
.colitalia-property-map-wrapper {
    position: relative;
    margin: 20px 0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.colitalia-map-address {
    background: #fff;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.colitalia-map-address i {
    color: #e74c3c;
    font-size: 16px;
}

.colitalia-map {
    height: 400px;
    width: 100%;
    position: relative;
}

.colitalia-map-controls {
    position: absolute;
    top: 70px;
    right: 10px;
    display: flex;
    flex-direction: column;
    gap: 5px;
    z-index: 1000;
}

.colitalia-map-controls button {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.colitalia-map-controls button:hover {
    background: #f8f9fa;
    transform: scale(1.05);
}

.colitalia-no-map {
    text-align: center;
    padding: 40px 20px;
    background: #f8f9fa;
    border-radius: 8px;
    color: #666;
    font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
    .colitalia-map {
        height: 300px;
    }
    
    .colitalia-map-address {
        padding: 12px 15px;
        font-size: 13px;
    }
    
    .colitalia-map-controls {
        top: 60px;
        right: 5px;
    }
    
    .colitalia-map-controls button {
        width: 35px;
        height: 35px;
    }
}
</style>