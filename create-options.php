<?php
/**
 * Script pour créer les options manquantes
 */

// Inclure WordPress
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
} else {
    // Essayer un autre chemin
    $wp_load_path = dirname(dirname(dirname(__FILE__))) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        die("Impossible de trouver wp-load.php");
    }
}

// Options AJAX à créer
$ajax_options = array(
    'dep_ajax_pagination' => false,
    'dep_infinite_scroll' => false,
    'dep_load_more_button' => false,
    'dep_load_more_text' => 'Charger plus',
    'dep_loading_text' => 'Chargement...'
);

echo "<h2>Création des options AJAX pour ABC Pagination Divi</h2>";

foreach ($ajax_options as $option => $value) {
    if (get_option($option) === false) {
        add_option($option, $value);
        echo "<p>✅ Option '$option' créée avec la valeur: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "</p>";
    } else {
        echo "<p>ℹ️ Option '$option' existe déjà avec la valeur: " . (is_bool(get_option($option)) ? (get_option($option) ? 'true' : 'false') : get_option($option)) . "</p>";
    }
}

echo "<h3>Terminé !</h3>";
echo "<p><a href='" . admin_url('options-general.php?page=abc-pagination-divi') . "'>Aller à la page de configuration</a></p>";
?>
