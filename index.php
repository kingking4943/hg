<?php
/**
 * Security file - Prevent direct access
 * 
 * @package ColitaliaRealEstate
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Redirect to WordPress home
wp_redirect(home_url());
exit;
