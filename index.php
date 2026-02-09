<?php
/**
 * Plugin Name: ABC Pagination Divi
 * Plugin URI: https://abcduweb.fr/abc-pagination/
 * Description: Améliore la pagination pour Divi avec des options de personnalisation avancées et un meilleur SEO
 * Version: 1.0.0
 * Author: ABCduWeb
 * Author URI: https://abcduweb.fr
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: abc-pagination-divi
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * Tags: divi, pagination, seo, ajax, infinite-scroll, wordpress
 */

// Securité
if (!defined('ABSPATH')) {
    exit;
}

// Vérification de la version PHP
if (version_compare(PHP_VERSION, '7.4', '<')) {
    deactivate_plugins(plugin_basename(__FILE__));
    wp_die(__('Ce plugin nécessite PHP 7.4 ou supérieur.', 'divi-enhanced-pagination'));
}

// Vérification de la version WordPress
if (version_compare($GLOBALS['wp_version'], '5.0', '<')) {
    deactivate_plugins(plugin_basename(__FILE__));
    wp_die(__('Ce plugin nécessite WordPress 5.0 ou supérieur.', 'divi-enhanced-pagination'));
}

// Inclusion des fichiers du plugin
require_once plugin_dir_path(__FILE__) . 'divi-enhanced-pagination-fixed.php';
require_once plugin_dir_path(__FILE__) . 'includes/seo-functions.php';

// Activation/Désactivation du plugin
register_activation_hook(__FILE__, 'dep_activate');
register_deactivation_hook(__FILE__, 'dep_deactivate');

/**
 * Fonction d'activation
 */
function dep_activate() {
    // Options par défaut
    $defaults = array(
        'dep_enable_plugin' => true,
        'dep_show_info' => true,
        'dep_pagination_style' => 'numeric',
        'dep_prev_text' => '← Précédent',
        'dep_next_text' => 'Suivant →',
        'dep_primary_color' => '#0073aa',
        'dep_bg_color' => '#f8f9fa',
        'dep_border_color' => '#e9ecef',
        'dep_text_color' => '#495057',
        'dep_margin_top' => '40',
        'dep_margin_bottom' => '40',
        'dep_padding_top' => '0',
        'dep_padding_bottom' => '0',
        'dep_posts_per_page' => ''
    );
    
    foreach ($defaults as $option => $value) {
        if (get_option($option) === false) {
            add_option($option, $value);
        }
    }
}

/**
 * Fonction de désactivation
 */
function dep_deactivate() {
    // Vider les caches
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // Réécrire les règles
    flush_rewrite_rules();
}

/**
 * Fonction de désinstallation
 */
register_uninstall_hook(__FILE__, 'dep_uninstall');

function dep_uninstall() {
    // Nettoyer les options
    delete_option('dep_enable_plugin');
    delete_option('dep_show_info');
    delete_option('dep_primary_color');
    delete_option('dep_bg_color');
    delete_option('dep_border_color');
    delete_option('dep_text_color');
}
