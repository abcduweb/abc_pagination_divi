<?php
/**
 * ABC Pagination Divi - Version propre et simple
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class ABC_Pagination_Divi {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_styles'));
        add_action('wp_footer', array($this, 'add_pagination_and_positioning'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function enqueue_scripts() {
        // Charger CSS partout
        wp_enqueue_style('dep-style', plugins_url('assets/css/pagination.css', __FILE__), array(), '1.0.0');
        
        // Charger JavaScript AJAX si activé
        if (get_option('dep_enable_plugin', true)) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('dep-pagination', plugins_url('assets/js/pagination.js', __FILE__) . '?v=' . time(), array('jquery'), '1.0.1', true);
            
            // Passer les options au JavaScript
            wp_localize_script('dep-pagination', 'depOptions', array(
                'ajax_pagination' => get_option('dep_ajax_pagination', false),
                'infinite_scroll' => get_option('dep_infinite_scroll', false),
                'load_more_button' => get_option('dep_load_more_button', false),
                'load_more_text' => get_option('dep_load_more_text', 'Charger plus'),
                'loading_text' => get_option('dep_loading_text', 'Chargement...'),
                'debug' => array(
                    'ajax_option' => get_option('dep_ajax_pagination', 'NOT_FOUND'),
                    'infinite_option' => get_option('dep_infinite_scroll', 'NOT_FOUND'),
                    'load_more_option' => get_option('dep_load_more_button', 'NOT_FOUND')
                )
            ));
        }
    }
    
    public function add_styles() {
        if (get_option('dep_enable_plugin', true)) {
            // Désactiver le cache si AJAX est activé
            if (get_option('dep_ajax_pagination', false)) {
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
            }
            
            // Récupérer les valeurs d'espacement
            $margin_top = get_option('dep_margin_top', '40');
            $margin_bottom = get_option('dep_margin_bottom', '40');
            $padding_top = get_option('dep_padding_top', '0');
            $padding_bottom = get_option('dep_padding_bottom', '0');
            
            ?>
            <style>
            /* Cacher l'ancienne pagination */
            .et_pb_pagination, .pagination:not(.dep-pagination-wrapper) {
                display: none !important;
            }
            
            /* Notre pagination */
            .dep-pagination-wrapper {
                margin: <?php echo $margin_top; ?>px 0 <?php echo $margin_bottom; ?>px 0;
                padding: <?php echo $padding_top; ?>px 0 <?php echo $padding_bottom; ?>px 0;
                text-align: center;
                clear: both;
            }
            
            .dep-pagination {
                display: flex;
                justify-content: center;
                align-items: center;
                flex-wrap: wrap;
                gap: 8px;
                margin: 20px 0;
            }
            
            .dep-pagination .page-numbers {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 44px;
                height: 44px;
                padding: 0 12px;
                background: <?php echo get_option('dep_bg_color', '#f8f9fa'); ?>;
                border: 2px solid <?php echo get_option('dep_border_color', '#e9ecef'); ?>;
                border-radius: 8px;
                color: <?php echo get_option('dep_text_color', '#495057'); ?>;
                text-decoration: none;
                font-weight: 500;
                font-size: 14px;
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .dep-pagination .page-numbers:hover {
                background: <?php echo get_option('dep_primary_color', '#0073aa'); ?>;
                border-color: <?php echo get_option('dep_primary_color', '#0073aa'); ?>;
                color: #ffffff;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,115,170,0.3);
            }
            
            .dep-pagination .page-numbers.current {
                background: <?php echo get_option('dep_primary_color', '#0073aa'); ?>;
                border-color: <?php echo get_option('dep_primary_color', '#0073aa'); ?>;
                color: #ffffff;
                font-weight: 600;
                box-shadow: 0 4px 8px rgba(0,115,170,0.3);
            }
            
            .dep-pagination .page-numbers.prev, 
            .dep-pagination .page-numbers.next {
                min-width: auto;
                padding: 0 16px;
                font-weight: 600;
            }
            
            .dep-pagination .page-numbers.prev:hover, 
            .dep-pagination .page-numbers.next:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,115,170,0.3);
            }
            
            .page-info {
                display: block;
                margin-bottom: 15px;
                color: #6c757d;
                font-size: 14px;
                font-weight: 500;
            }
            </style>
            <?php
        }
    }
    
    public function add_pagination_and_positioning() {
        if (!get_option('dep_enable_plugin', true)) {
            return;
        }
        
        // Forcer l'affichage sur la page blog
        $is_blog_page = (strpos($_SERVER['REQUEST_URI'], '/blog') !== false);
        
        // Debug
        echo '<!-- DEP DEBUG: add_pagination_and_positioning appelé -->';
        echo '<!-- DEP DEBUG: is_blog_page = ' . ($is_blog_page ? 'true' : 'false') . ' -->';
        
        // Vérifier si on est sur une page avec pagination
        if ($this->has_pagination() || $is_blog_page) {
            global $wp_query;
            
            // Ne PAS forcer une nouvelle requête, utiliser celle qui existe déjà
            // La requête WordPress contient déjà le bon contexte (catégorie, taxonomie, etc.)
            
            $current = max(1, get_query_var('paged'));
            $total = $wp_query->max_num_pages;
            
            echo '<!-- DEP DEBUG: current = ' . $current . ', total = ' . $total . ' -->';
            echo '<!-- DEP DEBUG: found_posts = ' . $wp_query->found_posts . ' -->';
            echo '<!-- DEP DEBUG: post_count = ' . $wp_query->post_count . ' -->';
            
            if ($total > 1) {
                // Récupérer les options de style
                $pagination_style = get_option('dep_pagination_style', 'numeric');
                $prev_text = get_option('dep_prev_text', '← Précédent');
                $next_text = get_option('dep_next_text', 'Suivant →');
                
                // Générer la pagination HTML
                $pagination_html = '<nav class="dep-pagination-wrapper" aria-label="Pagination">';
                $pagination_html .= '<div class="dep-pagination">';
                
                // Info page (si activé)
                if (get_option('dep_show_info', true)) {
                    $pagination_html .= '<span class="page-info">Page ' . $current . ' sur ' . $total . '</span>';
                }
                
                // Style Précédent / Suivant uniquement
                if ($pagination_style === 'prev_next') {
                    if ($current > 1) {
                        $prev_url = get_pagenum_link($current - 1);
                        $pagination_html .= '<a class="page-numbers prev" href="' . esc_url($prev_url) . '">' . esc_html($prev_text) . '</a>';
                    }
                    
                    if ($current < $total) {
                        $next_url = get_pagenum_link($current + 1);
                        $pagination_html .= '<a class="page-numbers next" href="' . esc_url($next_url) . '">' . esc_html($next_text) . '</a>';
                    }
                }
                // Style Numérique (par défaut)
                elseif ($pagination_style === 'numeric') {
                    // Liens numériques
                    for ($i = 1; $i <= $total; $i++) {
                        if ($i == $current) {
                            $pagination_html .= '<span class="page-numbers current">' . $i . '</span>';
                        } else {
                            $pagination_html .= '<a class="page-numbers" href="' . esc_url($this->get_page_link($i)) . '">' . $i . '</a>';
                        }
                    }
                }
                // Style Les deux
                elseif ($pagination_style === 'both') {
                    // Bouton précédent
                    if ($current > 1) {
                        $prev_url = get_pagenum_link($current - 1);
                        $pagination_html .= '<a class="page-numbers prev" href="' . esc_url($prev_url) . '">' . esc_html($prev_text) . '</a>';
                    }
                    
                    // Liens numériques
                    for ($i = 1; $i <= $total; $i++) {
                        if ($i == $current) {
                            $pagination_html .= '<span class="page-numbers current">' . $i . '</span>';
                        } else {
                            $pagination_html .= '<a class="page-numbers" href="' . esc_url($this->get_page_link($i)) . '">' . $i . '</a>';
                        }
                    }
                    
                    // Bouton suivant
                    if ($current < $total) {
                        $next_url = get_pagenum_link($current + 1);
                        $pagination_html .= '<a class="page-numbers next" href="' . esc_url($next_url) . '">' . esc_html($next_text) . '</a>';
                    }
                }
                
                // Bouton "Charger plus" si activé
                if (get_option('dep_load_more_button', false) && $current < $total) {
                    $load_more_text = get_option('dep_load_more_text', 'Charger plus');
                    $next_url = get_pagenum_link($current + 1);
                    $pagination_html .= '</div>';
                    $pagination_html .= '<div class="dep-load-more-wrapper">';
                    $pagination_html .= '<button class="dep-load-more-btn" data-next-page="' . ($current + 1) . '" data-url="' . esc_url($next_url) . '">' . esc_html($load_more_text) . '</button>';
                    $pagination_html .= '</div>';
                } else {
                    $pagination_html .= '</div>';
                }
                
                $pagination_html .= '</nav>';
                
                // Afficher la pagination et utiliser JavaScript pour la positionner
                echo $pagination_html;
                ?>
                <script>
                jQuery(document).ready(function($) {
                    // Positionner la pagination après les articles
                    var $pagination = $('.dep-pagination-wrapper');
                    
                    console.log('Pagination found:', $pagination.length);
                    
                    // Essayer différents conteneurs Divi
                    if ($('.et_pb_blog_grid').length > 0) {
                        console.log('Using et_pb_blog_grid');
                        $('.et_pb_blog_grid').after($pagination);
                    } else if ($('.et_pb_posts').length > 0) {
                        console.log('Using et_pb_posts');
                        $('.et_pb_posts').after($pagination);
                    } else if ($('.et_pb_module.et_pb_blog').length > 0) {
                        console.log('Using et_pb_module.et_pb_blog');
                        $('.et_pb_module.et_pb_blog').last().after($pagination);
                    } else if ($('main').length > 0) {
                        console.log('Using main article');
                        $('main article').last().after($pagination);
                    } else if ($('.entry-content').length > 0) {
                        console.log('Using entry-content');
                        $('.entry-content').append($pagination);
                    } else {
                        console.log('No container found, keeping in footer');
                    }
                    
                    // S'assurer que la pagination est visible
                    $pagination.show();
                });
                </script>
                <?php
            } else {
                echo '<!-- DEP DEBUG: No pagination needed (total <= 1) -->';
            }
        } else {
            echo '<!-- DEP DEBUG: Not a pagination page -->';
        }
    }
    
    private function has_pagination() {
        global $wp_query;
        
        // Debug détaillé pour comprendre le problème
        echo '<!-- DEP DEBUG: URI = ' . $_SERVER['REQUEST_URI'] . ' -->';
        echo '<!-- DEP DEBUG: is_home = ' . (is_home() ? 'true' : 'false') . ' -->';
        echo '<!-- DEP DEBUG: is_archive = ' . (is_archive() ? 'true' : 'false') . ' -->';
        echo '<!-- DEP DEBUG: is_category = ' . (is_category() ? 'true' : 'false') . ' -->';
        echo '<!-- DEP DEBUG: is_tag = ' . (is_tag() ? 'true' : 'false') . ' -->';
        echo '<!-- DEP DEBUG: is_tax = ' . (is_tax() ? 'true' : 'false') . ' -->';
        echo '<!-- DEP DEBUG: is_page(blog) = ' . (is_page('blog') ? 'true' : 'false') . ' -->';
        echo '<!-- DEP DEBUG: pagename = ' . get_query_var('pagename') . ' -->';
        echo '<!-- DEP DEBUG: category_name = ' . get_query_var('category_name') . ' -->';
        echo '<!-- DEP DEBUG: tag = ' . get_query_var('tag') . ' -->';
        echo '<!-- DEP DEBUG: taxonomy = ' . get_query_var('taxonomy') . ' -->';
        echo '<!-- DEP DEBUG: term = ' . get_query_var('term') . ' -->';
        echo '<!-- DEP DEBUG: paged = ' . get_query_var('paged') . ' -->';
        echo '<!-- DEP DEBUG: max_num_pages = ' . $wp_query->max_num_pages . ' -->';
        echo '<!-- DEP DEBUG: found_posts = ' . $wp_query->found_posts . ' -->';
        echo '<!-- DEP DEBUG: post_count = ' . $wp_query->post_count . ' -->';
        echo '<!-- DEP DEBUG: posts_per_page = ' . $wp_query->get('posts_per_page') . ' -->';
        
        // Vérifier si on a une requête vide ou incorrecte
        if ($wp_query->post_count == 0 && $wp_query->found_posts > 0) {
            echo '<!-- DEP DEBUG: Requery needed - post_count=0 but found_posts>0 -->';
            return $this->fix_pagination_query();
        }
        
        // Vérifier si on a plusieurs pages
        if ($wp_query->max_num_pages <= 1) {
            echo '<!-- DEP DEBUG: Pas assez de pages -->';
            return false;
        }
        
        // Vérification large pour inclure toutes les pages avec pagination
        $has_pagination = (is_home() || is_archive() || is_category() || is_tag() || is_tax() || is_search() || is_page('blog') || get_query_var('pagename') === 'blog' || strpos($_SERVER['REQUEST_URI'], '/blog') !== false);
        
        echo '<!-- DEP DEBUG: has_pagination = ' . ($has_pagination ? 'true' : 'false') . ' -->';
        
        return $has_pagination;
    }
    
    private function fix_pagination_query() {
        global $wp_query;
        
        echo '<!-- DEP DEBUG: Attempting to fix query -->';
        
        // Reconstruire la requête avec le bon contexte
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page', 10),
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        );
        
        // Ajouter le contexte de catégorie
        if (is_category()) {
            $args['category_name'] = get_query_var('category_name');
            echo '<!-- DEP DEBUG: Category context: ' . get_query_var('category_name') . ' -->';
        } elseif (is_tag()) {
            $args['tag'] = get_query_var('tag');
            echo '<!-- DEP DEBUG: Tag context: ' . get_query_var('tag') . ' -->';
        } elseif (is_tax()) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => get_query_var('taxonomy'),
                    'field' => 'slug',
                    'terms' => get_query_var('term')
                )
            );
            echo '<!-- DEP DEBUG: Tax context: ' . get_query_var('taxonomy') . '=' . get_query_var('term') . ' -->';
        }
        
        // Créer la nouvelle requête
        $new_query = new WP_Query($args);
        
        echo '<!-- DEP DEBUG: New query found_posts: ' . $new_query->found_posts . ' -->';
        echo '<!-- DEP DEBUG: New query max_num_pages: ' . $new_query->max_num_pages . ' -->';
        
        // Remplacer la requête globale
        $wp_query = $new_query;
        
        // Retourner true si on a des pages
        return ($wp_query->max_num_pages > 1);
    }
    
    private function get_page_link($page) {
        // Utiliser la fonction WordPress native pour garantir des URLs correctes
        return get_pagenum_link($page);
    }
    
    public function add_admin_menu() {
        add_options_page(
            'ABC Pagination Divi',
            'ABC Pagination',
            'manage_options',
            'abc-pagination-divi',
            array($this, 'admin_page')
        );
    }
    
    public function register_settings() {
        register_setting('dep_settings', 'dep_enable_plugin');
        register_setting('dep_settings', 'dep_show_info');
        register_setting('dep_settings', 'dep_pagination_style');
        register_setting('dep_settings', 'dep_prev_text');
        register_setting('dep_settings', 'dep_next_text');
        register_setting('dep_settings', 'dep_primary_color');
        register_setting('dep_settings', 'dep_bg_color');
        register_setting('dep_settings', 'dep_border_color');
        register_setting('dep_settings', 'dep_text_color');
        register_setting('dep_settings', 'dep_margin_top');
        register_setting('dep_settings', 'dep_margin_bottom');
        register_setting('dep_settings', 'dep_padding_top');
        register_setting('dep_settings', 'dep_padding_bottom');
        
        // Options AJAX
        register_setting('dep_settings', 'dep_ajax_pagination');
        register_setting('dep_settings', 'dep_infinite_scroll');
        register_setting('dep_settings', 'dep_load_more_button');
        register_setting('dep_settings', 'dep_load_more_text');
        register_setting('dep_settings', 'dep_loading_text');
        
        // Vider le cache quand les options AJAX sont modifiées
        add_action('update_option_dep_ajax_pagination', array($this, 'clear_cache_on_ajax_change'));
        add_action('update_option_dep_infinite_scroll', array($this, 'clear_cache_on_ajax_change'));
        add_action('update_option_dep_load_more_button', array($this, 'clear_cache_on_ajax_change'));
        
        // Ajouter les champs hexadécimaux
        add_filter('pre_update_option_dep_primary_color', array($this, 'update_color_from_hex'), 10, 2);
        add_filter('pre_update_option_dep_bg_color', array($this, 'update_color_from_hex'), 10, 2);
        add_filter('pre_update_option_dep_border_color', array($this, 'update_color_from_hex'), 10, 2);
        add_filter('pre_update_option_dep_text_color', array($this, 'update_color_from_hex'), 10, 2);
    }
    
    public function clear_cache_on_ajax_change() {
        // Vider tous les caches quand les options AJAX changent
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Vider le cache des plugins populaires
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        if (function_exists('wp_fast_cache_bulk_delete_all')) {
            wp_fast_cache_bulk_delete_all();
        }
        
        // Forcer la regénération des fichiers CSS/JS
        // Supprimer les fichiers CSS/JS en cache si possible
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/cache';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
    
    public function update_color_from_hex($new_value, $old_value) {
        // Vérifier si un champ hexadécimal a été soumis
        $option_name = current_filter();
        $hex_field = str_replace('dep_', 'dep_', $option_name) . '_hex';
        
        if (isset($_POST[$hex_field])) {
            $hex_value = sanitize_text_field($_POST[$hex_field]);
            // Valider le format hexadécimal
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $hex_value)) {
                return $hex_value;
            }
        }
        
        return $new_value;
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>ABC Pagination Divi</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('dep_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th>
                            <label>
                                <input type="checkbox" name="dep_enable_plugin" value="1" <?php checked(get_option('dep_enable_plugin', 1)); ?>>
                                Activer la pagination améliorée
                            </label>
                        </th>
                        <td>
                            <p class="description">Cochez pour activer, décochez pour désactiver</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>
                            <label>
                                <input type="checkbox" name="dep_show_info" value="1" <?php checked(get_option('dep_show_info', 1)); ?>>
                                Afficher "Page X sur Y"
                            </label>
                        </th>
                        <td>
                            <p class="description">Désactivez pour masquer l'information de page</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="dep_pagination_style">Style de pagination</label></th>
                        <td>
                            <select name="dep_pagination_style" id="dep_pagination_style">
                                <option value="numeric" <?php selected(get_option('dep_pagination_style', 'numeric'), 'numeric'); ?>>Numérique (1, 2, 3...)</option>
                                <option value="prev_next" <?php selected(get_option('dep_pagination_style', 'numeric'), 'prev_next'); ?>>Précédent / Suivant</option>
                                <option value="both" <?php selected(get_option('dep_pagination_style', 'numeric'), 'both'); ?>>Les deux</option>
                            </select>
                            <p class="description">Choisissez le style de pagination à afficher</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="dep_prev_text">Texte "Précédent"</label></th>
                        <td>
                            <input type="text" name="dep_prev_text" id="dep_prev_text" value="<?php echo esc_attr(get_option('dep_prev_text', '← Précédent')); ?>" class="regular-text">
                            <p class="description">Texte pour le bouton précédent</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="dep_next_text">Texte "Suivant"</label></th>
                        <td>
                            <input type="text" name="dep_next_text" id="dep_next_text" value="<?php echo esc_attr(get_option('dep_next_text', 'Suivant →')); ?>" class="regular-text">
                            <p class="description">Texte pour le bouton suivant</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="dep_primary_color">Couleur principale</label></th>
                        <td>
                            <input type="color" name="dep_primary_color" id="dep_primary_color" value="<?php echo esc_attr(get_option('dep_primary_color', '#0073aa')); ?>">
                            <br><br>
                            <label for="dep_primary_color_hex">Ou code hexadécimal :</label>
                            <input type="text" name="dep_primary_color_hex" id="dep_primary_color_hex" value="<?php echo esc_attr(get_option('dep_primary_color', '#0073aa')); ?>" placeholder="#0073aa" pattern="^#[0-9A-Fa-f]{6}$" class="regular-text">
                            <p class="description">Choisissez une couleur ou entrez un code hexadécimal (ex: #8ab734)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="dep_bg_color">Couleur de fond</label></th>
                        <td>
                            <input type="color" name="dep_bg_color" id="dep_bg_color" value="<?php echo esc_attr(get_option('dep_bg_color', '#f8f9fa')); ?>">
                            <br><br>
                            <label for="dep_bg_color_hex">Ou code hexadécimal :</label>
                            <input type="text" name="dep_bg_color_hex" id="dep_bg_color_hex" value="<?php echo esc_attr(get_option('dep_bg_color', '#f8f9fa')); ?>" placeholder="#f8f9fa" pattern="^#[0-9A-Fa-f]{6}$" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="dep_border_color">Couleur des bordures</label></th>
                        <td>
                            <input type="color" name="dep_border_color" id="dep_border_color" value="<?php echo esc_attr(get_option('dep_border_color', '#e9ecef')); ?>">
                            <br><br>
                            <label for="dep_border_color_hex">Ou code hexadécimal :</label>
                            <input type="text" name="dep_border_color_hex" id="dep_border_color_hex" value="<?php echo esc_attr(get_option('dep_border_color', '#e9ecef')); ?>" placeholder="#e9ecef" pattern="^#[0-9A-Fa-f]{6}$" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="dep_text_color">Couleur du texte</label></th>
                        <td>
                            <input type="color" name="dep_text_color" id="dep_text_color" value="<?php echo esc_attr(get_option('dep_text_color', '#495057')); ?>">
                            <br><br>
                            <label for="dep_text_color_hex">Ou code hexadécimal :</label>
                            <input type="text" name="dep_text_color_hex" id="dep_text_color_hex" value="<?php echo esc_attr(get_option('dep_text_color', '#495057')); ?>" placeholder="#495057" pattern="^#[0-9A-Fa-f]{6}$" class="regular-text">
                            <p class="description">Choisissez une couleur ou entrez un code hexadécimal (ex: #495057)</p>
                        </td>
                    </tr>
                </table>
                
                <h2>Options avancées</h2>
                <?php
                // Debug : vérifier si les options existent
                $ajax_pagination = get_option('dep_ajax_pagination', 'NOT_FOUND');
                $infinite_scroll = get_option('dep_infinite_scroll', 'NOT_FOUND');
                $load_more_button = get_option('dep_load_more_button', 'NOT_FOUND');
                
                if ($ajax_pagination === 'NOT_FOUND') {
                    echo '<div style="background: #ffeb3b; padding: 10px; margin: 10px 0; border-left: 4px solid #f9a825;">';
                    echo '<strong>⚠️ Options AJAX non trouvées!</strong><br>';
                    echo 'Veuillez désactiver et réactiver le plugin pour créer les options manquantes.';
                    echo '</div>';
                }
                ?>
                <table class="form-table">
                    <tr>
                        <th>
                            <label>
                                <input type="checkbox" name="dep_ajax_pagination" value="1" <?php checked(get_option('dep_ajax_pagination', 0)); ?>>
                                Pagination AJAX (sans rechargement)
                            </label>
                        </th>
                        <td>
                            <p class="description">Navigation sans rechargement de page</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>
                            <label>
                                <input type="checkbox" name="dep_infinite_scroll" value="1" <?php checked(get_option('dep_infinite_scroll', 0)); ?>>
                                Défilement infini
                            </label>
                        </th>
                        <td>
                            <p class="description">Chargement automatique au scroll</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>
                            <label>
                                <input type="checkbox" name="dep_load_more_button" value="1" <?php checked(get_option('dep_load_more_button', 0)); ?>>
                                Bouton "Charger plus"
                            </label>
                        </th>
                        <td>
                            <p class="description">Alternative au défilement infini</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="dep_load_more_text">Texte du bouton "Charger plus"</label></th>
                        <td>
                            <input type="text" name="dep_load_more_text" id="dep_load_more_text" value="<?php echo esc_attr(get_option('dep_load_more_text', 'Charger plus')); ?>" class="regular-text">
                            <p class="description">Texte pour le bouton charger plus</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="dep_loading_text">Texte de chargement</label></th>
                        <td>
                            <input type="text" name="dep_loading_text" id="dep_loading_text" value="<?php echo esc_attr(get_option('dep_loading_text', 'Chargement...')); ?>" class="regular-text">
                            <p class="description">Texte affiché pendant le chargement</p>
                        </td>
                    </tr>
                </table>
                
                <h2>Espacement de la pagination</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="dep_margin_top">Marge supérieure (margin-top)</label></th>
                        <td>
                            <input type="number" name="dep_margin_top" id="dep_margin_top" value="<?php echo esc_attr(get_option('dep_margin_top', '40')); ?>" min="0" max="200" step="5">
                            <span class="description">px</span>
                            <p class="description">Espace avant la pagination</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="dep_margin_bottom">Marge inférieure (margin-bottom)</label></th>
                        <td>
                            <input type="number" name="dep_margin_bottom" id="dep_margin_bottom" value="<?php echo esc_attr(get_option('dep_margin_bottom', '40')); ?>" min="0" max="200" step="5">
                            <span class="description">px</span>
                            <p class="description">Espace après la pagination</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="dep_padding_top">Padding supérieur (padding-top)</label></th>
                        <td>
                            <input type="number" name="dep_padding_top" id="dep_padding_top" value="<?php echo esc_attr(get_option('dep_padding_top', '0')); ?>" min="0" max="100" step="5">
                            <span class="description">px</span>
                            <p class="description">Espace intérieur en haut de la pagination</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="dep_padding_bottom">Padding inférieur (padding-bottom)</label></th>
                        <td>
                            <input type="number" name="dep_padding_bottom" id="dep_padding_bottom" value="<?php echo esc_attr(get_option('dep_padding_bottom', '0')); ?>" min="0" max="100" step="5">
                            <span class="description">px</span>
                            <p class="description">Espace intérieur en bas de la pagination</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                // Synchroniser les champs de couleur avec les champs hexadécimaux
                function syncColorInputs(colorId, hexId) {
                    $('#' + colorId).on('input change', function() {
                        $('#' + hexId).val($(this).val());
                    });
                    
                    $('#' + hexId).on('input', function() {
                        var hex = $(this).val();
                        if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                            $('#' + colorId).val(hex);
                        }
                    });
                }
                
                syncColorInputs('dep_primary_color', 'dep_primary_color_hex');
                syncColorInputs('dep_bg_color', 'dep_bg_color_hex');
                syncColorInputs('dep_border_color', 'dep_border_color_hex');
                syncColorInputs('dep_text_color', 'dep_text_color_hex');
            });
            </script>
        </div>
        <?php
    }
}

// Initialiser
new ABC_Pagination_Divi();
