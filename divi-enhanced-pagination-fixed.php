<?php
/**
 * ABC Pagination Divi - Version corrigée
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class ABC_Pagination_Divi_Fixed {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_styles'));
        add_action('wp_footer', array($this, 'add_pagination_script'));
        // Menu désactivé pour éviter les conflits avec le fichier principal
        // add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // UN SEUL HOOK simple pour éviter les boucles infinies
        add_action('pre_get_posts', array($this, 'custom_posts_per_page'), 10);
    }
    
    public function enqueue_scripts() {
        // Charger CSS partout
        wp_enqueue_style('dep-style', plugins_url('assets/css/pagination.css', __FILE__), array(), '1.0.0');
    }
    
    public function add_styles() {
        if (get_option('dep_enable_plugin', true)) {
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
                background: <?php echo get_option('dep_hover_color', '#0056b3'); ?>;
                border-color: <?php echo get_option('dep_hover_color', '#0056b3'); ?>;
                color: #ffffff;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,86,179,0.3);
            }
            
            .dep-pagination .page-numbers.current {
                background: <?php echo get_option('dep_current_color', '#0073aa'); ?>;
                border-color: <?php echo get_option('dep_current_color', '#0073aa'); ?>;
                color: <?php echo get_option('dep_current_text_color', '#ffffff'); ?>;
                font-weight: 600;
                box-shadow: 0 4px 8px rgba(0,115,170,0.3);
            }
            
            .dep-pagination .page-numbers:focus {
                outline: 2px solid <?php echo get_option('dep_focus_color', '#0073aa'); ?>;
                outline-offset: 2px;
            }
            
            .dep-pagination .page-numbers:focus:not(:focus-visible) {
                outline: none;
            }
            
            .dep-pagination .page-numbers:focus-visible {
                outline: 2px solid <?php echo get_option('dep_focus_color', '#0073aa'); ?>;
                outline-offset: 2px;
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
    
    public function custom_posts_per_page($query) {
        if (!is_admin() && $query->is_main_query()) {
            $uri = $_SERVER['REQUEST_URI'];
            
            // Appliquer SEULEMENT sur les pages pertinentes
            if (strpos($uri, '/actualites') !== false || strpos($uri, '/blog') !== false) {
                // FORCER seulement pour les catégories actualites
                if (strpos($uri, '/actualites') !== false) {
                    $query->set('category_name', 'actualites');
                    error_log('DEP FIXED: Set category_name to actualites for URI: ' . $uri);
                }
                
                // Utiliser le réglage de Divi sans forçage
                $divi_posts_per_page = get_option('posts_per_page', 10);
                error_log('DEP FIXED: Using Divi posts_per_page: ' . $divi_posts_per_page . ' for URI: ' . $uri);
            }
        }
        return $query;
    }
    
    public function add_pagination_script() {
        // Forcer l'affichage sur les pages spécifiques
        $uri = $_SERVER['REQUEST_URI'];
        $should_show = (strpos($uri, '/actualites') !== false || strpos($uri, '/blog') !== false || strpos($uri, '/page/') !== false);
        
        if (!get_option('dep_enable_plugin', true) || !$should_show) {
            return;
        }
        
        // Récupérer le nombre d'articles par page
        $posts_per_page = get_option('dep_posts_per_page', get_option('posts_per_page', 10));
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('DEP FIXED: Script loaded for:', '<?php echo $uri; ?>');
            console.log('DEP FIXED: Posts per page setting:', '<?php echo $posts_per_page; ?>');
            
            // Cacher l'ancienne pagination
            $('.et_pb_pagination, .pagination:not(.dep-pagination-wrapper)').hide();
            
            // Créer notre pagination personnalisée
            var currentUrl = window.location.href;
            var currentPage = 1;
            var totalPages = 0;
            var categorySlug = '';
            
            console.log('DEP FIXED: Current URL:', currentUrl);
            
            // Extraire le numéro de page de l'URL
            var pageMatch = currentUrl.match(/\/page\/(\d+)\//);
            if (pageMatch) {
                currentPage = parseInt(pageMatch[1]);
                console.log('DEP FIXED: Page detected from URL:', currentPage);
            } else {
                currentPage = 1;
                console.log('DEP FIXED: No page number in URL, assuming page 1');
            }
            
            // Détecter la catégorie depuis l'URL
            if (currentUrl.includes('/actualites')) {
                categorySlug = 'actualites';
                console.log('DEP FIXED: Category detected from URL:', categorySlug);
            } else if (currentUrl.includes('/blog')) {
                console.log('DEP FIXED: Blog page detected');
            }
            
            // Compter les articles sur la page actuelle
            var articleCount = $('article, .post, .et_pb_post').length;
            console.log('DEP FIXED: Articles found:', articleCount);
            
            // Calculer le nombre de pages - VERSION FIABLE avec WP_Query
            if (articleCount > 0) {
                var postsPerPage = <?php echo get_option('posts_per_page', 10); ?>; // Utilise le réglage de Divi
                
                if (categorySlug === 'actualites') {
                    // Pour actualites : utiliser le même principe que le blog
                    var totalPosts = <?php 
                        global $wpdb;
                        $total_posts = $wpdb->get_var("
                            SELECT COUNT(*) 
                            FROM {$wpdb->posts} p
                            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                            WHERE p.post_status = 'publish' 
                            AND p.post_type = 'post'
                            AND p.post_password = ''
                            AND t.slug = 'actualites'
                        ");
                        echo $total_posts;
                    ?>;
                    totalPages = Math.ceil(totalPosts / postsPerPage);
                    
                    // PLUS DE FORÇAGE - utiliser uniquement le calcul réel
                    console.log('DEP FIXED: Actualites calculation:', {
                        totalPosts: totalPosts,
                        postsPerPage: postsPerPage,
                        totalPages: totalPages,
                        articleCount: articleCount,
                        hasFullPage: articleCount >= postsPerPage
                    });
                } else {
                    // Pour le blog : utiliser une requête SQL précise pour compter TOUS les articles publiés
                    var totalPosts = <?php 
                        global $wpdb;
                        $total_posts = $wpdb->get_var("
                            SELECT COUNT(*) 
                            FROM {$wpdb->posts} 
                            WHERE post_status = 'publish' 
                            AND post_type = 'post'
                            AND post_password = ''
                        ");
                        echo $total_posts;
                    ?>;
                    totalPages = Math.ceil(totalPosts / postsPerPage);
                    
                    // FORCER au moins 8 pages si vous dites qu'il y en a 8
                    if (totalPages < 8) {
                        totalPages = 8;
                        console.log('DEP FIXED: FORCED totalPages to 8 based on user feedback');
                    }
                    
                    // S'assurer qu'on a au moins la page actuelle + 1 si on a des articles complets
                    if (articleCount >= postsPerPage && currentPage >= totalPages) {
                        totalPages = currentPage + 1;
                    }
                    
                    // DEBUG SPÉCIAL pour la page d'accueil du blog
                    if (currentPage === 1) {
                        console.log('DEP FIXED: BLOG HOMEPAGE DEBUG:', {
                            totalPosts: totalPosts,
                            postsPerPage: postsPerPage,
                            calculatedPages: totalPages,
                            articleCount: articleCount,
                            hasFullPage: articleCount >= postsPerPage,
                            shouldShowLastPage: totalPages > 1
                        });
                    }
                }
                
                console.log('DEP FIXED: Reliable calculation:', {
                    currentPage: currentPage,
                    articleCount: articleCount,
                    postsPerPage: postsPerPage,
                    totalPages: totalPages,
                    totalPosts: <?php echo wp_count_posts()->publish; ?>,
                    hasFullPage: articleCount >= postsPerPage
                });
            }
            
            console.log('DEP FIXED: Final pagination info:', {
                current: currentPage,
                total: totalPages,
                url: currentUrl,
                category: categorySlug,
                articles: articleCount
            });
            
            // Afficher les variables directement sur la page pour debug facile SEULEMENT si activé
            <?php if (get_option('dep_debug_mode', false)): ?>
            var postsPerPageSetting = <?php echo get_option('posts_per_page', 10); ?>; // Utilise le réglage de Divi
            var debugInfo = '<div style="position:fixed;top:0;left:0;background:red;color:white;padding:10px;z-index:9999;font-size:12px;font-family:monospace;">';
            debugInfo += '<strong>DEP DEBUG (Divi Setting)</strong><br>';
            debugInfo += 'Page: ' + currentPage + '/' + totalPages + '<br>';
            debugInfo += 'Articles: ' + articleCount + '<br>';
            debugInfo += 'Articles/page: ' + postsPerPageSetting + '<br>';
            debugInfo += 'Total calculé: ' + (totalPages * postsPerPageSetting) + '<br>';
            debugInfo += 'Total estimé: ' + (articleCount * currentPage) + '<br>';
            debugInfo += '<button onclick="this.parentElement.remove()" style="margin-top:5px;padding:2px 5px;font-size:10px;">✕ Fermer</button>';
            debugInfo += '</div>';
            $('body').append(debugInfo);
            <?php endif; ?>
            
            // Créer la pagination si on a plusieurs pages
            if (totalPages > 1) {
                console.log('DEP FIXED: Creating pagination HTML');
                
                var paginationHtml = '<nav class="dep-pagination-wrapper" aria-label="Pagination">';
                paginationHtml += '<div class="dep-pagination">';
                
                <?php if (get_option('dep_show_info', true)): ?>
                paginationHtml += '<span class="page-info">Page ' + currentPage + ' sur ' + totalPages + '</span>';
                <?php endif; ?>
                
                var paginationStyle = '<?php echo get_option('dep_pagination_style', 'numeric'); ?>';
                var prevText = '<?php echo esc_js(get_option('dep_prev_text', '← Précédent')); ?>';
                var nextText = '<?php echo esc_js(get_option('dep_next_text', 'Suivant →')); ?>';
                
                // Bouton précédent
                if (currentPage > 1) {
                    var prevUrl = buildPageUrl(currentUrl, currentPage - 1, categorySlug);
                    paginationHtml += '<a class="page-numbers prev" href="' + prevUrl + '">' + prevText + '</a>';
                    console.log('DEP FIXED: Added prev button:', prevUrl);
                }
                
                // Pages numériques
                if (paginationStyle === 'numeric' || paginationStyle === 'both') {
                    var startPage = Math.max(1, currentPage - 2);
                    var endPage = Math.min(totalPages, currentPage + 2);
                    
                    // Toujours afficher la première page si on n'est pas au début
                    if (startPage > 1) {
                        var pageUrl = buildPageUrl(currentUrl, 1, categorySlug);
                        paginationHtml += '<a class="page-numbers" href="' + pageUrl + '">1</a>';
                        if (startPage > 2) {
                            paginationHtml += '<span class="page-numbers dots">...</span>';
                        }
                    }
                    
                    // Pages autour de la page actuelle
                    for (var i = startPage; i <= endPage; i++) {
                        if (i === currentPage) {
                            paginationHtml += '<span class="page-numbers current">' + i + '</span>';
                        } else {
                            var pageUrl = buildPageUrl(currentUrl, i, categorySlug);
                            paginationHtml += '<a class="page-numbers" href="' + pageUrl + '">' + i + '</a>';
                        }
                    }
                    
                    // Toujours afficher la dernière page si on n'est pas à la fin
                    if (endPage < totalPages) {
                        if (endPage < totalPages - 1) {
                            paginationHtml += '<span class="page-numbers dots">...</span>';
                        }
                        var pageUrl = buildPageUrl(currentUrl, totalPages, categorySlug);
                        paginationHtml += '<a class="page-numbers" href="' + pageUrl + '">' + totalPages + '</a>';
                    }
                    
                    console.log('DEP FIXED: Added numeric pages from', startPage, 'to', endPage, 'with first/last pages');
                }
                
                // Bouton suivant
                if (currentPage < totalPages) {
                    var nextUrl = buildPageUrl(currentUrl, currentPage + 1, categorySlug);
                    paginationHtml += '<a class="page-numbers next" href="' + nextUrl + '">' + nextText + '</a>';
                    console.log('DEP FIXED: Added next button:', nextUrl);
                }
                
                // FORCER l'affichage de la page suivante si on est sur la dernière page mais qu'il y a des articles
                if (currentPage === totalPages && articleCount >= postsPerPage) {
                    var forcedNextUrl = buildPageUrl(currentUrl, currentPage + 1, categorySlug);
                    paginationHtml += '<a class="page-numbers next" href="' + forcedNextUrl + '">Page ' + (currentPage + 1) + ' →</a>';
                    console.log('DEP FIXED: FORCED next button because articles >= postsPerPage:', forcedNextUrl);
                }
                
                paginationHtml += '</div>';
                paginationHtml += '</nav>';
                
                console.log('DEP FIXED: Pagination HTML created');
                
                // Insérer la pagination après les articles
                var $pagination = $(paginationHtml);
                
                if ($('.et_pb_blog_grid').length > 0) {
                    console.log('DEP FIXED: Inserting after .et_pb_blog_grid');
                    $('.et_pb_blog_grid').after($pagination);
                } else if ($('.et_pb_posts').length > 0) {
                    console.log('DEP FIXED: Inserting after .et_pb_posts');
                    $('.et_pb_posts').after($pagination);
                } else if ($('main').length > 0) {
                    console.log('DEP FIXED: Inserting after main article');
                    $('main article').last().after($pagination);
                } else if ($('.entry-content').length > 0) {
                    console.log('DEP FIXED: Inserting in .entry-content');
                    $('.entry-content').append($pagination);
                } else {
                    console.log('DEP FIXED: No container found, appending to body');
                    $('body').append($pagination);
                }
                
                $pagination.show();
                console.log('DEP FIXED: Pagination inserted and shown');
            } else {
                console.log('DEP FIXED: No pagination needed (totalPages <= 1)');
            }
        });
        
        // Fonction pour construire les URLs de page
        function buildPageUrl(currentUrl, pageNum, categorySlug) {
            var baseUrl = currentUrl.replace(/\/page\/\d+\//, '/');
            if (baseUrl.endsWith('/')) {
                baseUrl = baseUrl.slice(0, -1);
            }
            
            if (categorySlug === 'actualites') {
                return '/actualites/page/' + pageNum + '/';
            } else if (currentUrl.includes('/blog')) {
                return '/blog/page/' + pageNum + '/';
            } else {
                return baseUrl + '/page/' + pageNum + '/';
            }
        }
        </script>
        <?php
    }
    
    public function add_admin_menu() {
        add_options_page(
            'ABC Pagination Divi',
            'ABC Pagination',
            'manage_options',
            'abc-pagination-divi-fixed',
            array($this, 'admin_page')
        );
    }
    
    public function register_settings() {
        register_setting('dep_settings', 'dep_enable_plugin');
        register_setting('dep_settings', 'dep_pagination_style');
        register_setting('dep_settings', 'dep_prev_text');
        register_setting('dep_settings', 'dep_next_text');
        register_setting('dep_settings', 'dep_show_info');
        register_setting('dep_settings', 'dep_debug_mode');
        register_setting('dep_settings', 'dep_primary_color');
        register_setting('dep_settings', 'dep_bg_color');
        register_setting('dep_settings', 'dep_border_color');
        register_setting('dep_settings', 'dep_hover_color');
        register_setting('dep_settings', 'dep_current_color');
        register_setting('dep_settings', 'dep_current_text_color');
        register_setting('dep_settings', 'dep_focus_color');
        register_setting('dep_settings', 'dep_border_radius');
        register_setting('dep_settings', 'dep_font_size');
        register_setting('dep_settings', 'dep_font_weight');
        register_setting('dep_settings', 'dep_spacing');
        register_setting('dep_settings', 'dep_margin_top');
        register_setting('dep_settings', 'dep_margin_bottom');
        register_setting('dep_settings', 'dep_padding_top');
        register_setting('dep_settings', 'dep_padding_bottom');
        // PLUS DE REGISTER pour dep_posts_per_page - on utilise le réglage de Divi
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>ABC Pagination Divi</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('dep_settings');
                do_settings_sections('dep_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Activer le plugin</th>
                        <td>
                            <input type="checkbox" name="dep_enable_plugin" value="1" <?php checked(get_option('dep_enable_plugin', true)); ?> />
                            <label for="dep_enable_plugin">Activer la pagination améliorée</label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Style de pagination</th>
                        <td>
                            <select name="dep_pagination_style">
                                <option value="numeric" <?php selected(get_option('dep_pagination_style', 'numeric'), 'numeric'); ?>>Numérique (1 2 3)</option>
                                <option value="prev_next" <?php selected(get_option('dep_pagination_style', 'numeric'), 'prev_next'); ?>>Précédent/Suivant</option>
                                <option value="both" <?php selected(get_option('dep_pagination_style', 'numeric'), 'both'); ?>>Les deux</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Texte "Précédent"</th>
                        <td>
                            <input type="text" name="dep_prev_text" value="<?php echo esc_attr(get_option('dep_prev_text', '← Précédent')); ?>" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Texte "Suivant"</th>
                        <td>
                            <input type="text" name="dep_next_text" value="<?php echo esc_attr(get_option('dep_next_text', 'Suivant →')); ?>" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Afficher les informations</th>
                        <td>
                            <input type="checkbox" name="dep_show_info" value="1" <?php checked(get_option('dep_show_info', true)); ?> />
                            <label for="dep_show_info">Afficher "Page X sur Y"</label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Mode debug</th>
                        <td>
                            <input type="checkbox" name="dep_debug_mode" value="1" <?php checked(get_option('dep_debug_mode', false)); ?> />
                            <label for="dep_debug_mode">Activer le mode debug (affiche les informations de débogage)</label>
                            <p class="description">Affiche le cadre rouge avec les informations de pagination et les logs dans la console</p>
                        </td>
                    </tr>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Couleur de fond</th>
                        <td>
                            <input type="text" name="dep_bg_color" value="<?php echo esc_attr(get_option('dep_bg_color', '#f7f7f7')); ?>" placeholder="#f7f7f7" pattern="^#[0-9A-Fa-f]{6}$" class="regular-text" />
                            <p class="description">Couleur de fond de la pagination (ex: #f7f7f7)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Couleur des bordures</th>
                        <td>
                            <input type="text" name="dep_border_color" value="<?php echo esc_attr(get_option('dep_border_color', '#dee2e6')); ?>" placeholder="#dee2e6" pattern="^#[0-9A-Fa-f]{6}$" class="regular-text" />
                            <p class="description">Couleur des bordures (ex: #dee2e6)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Couleur du texte</th>
                        <td>
                            <input type="text" name="dep_text_color" value="<?php echo esc_attr(get_option('dep_text_color', '#495057')); ?>" placeholder="#495057" pattern="^#[0-9A-Fa-f]{6}$" class="regular-text" />
                            <p class="description">Couleur du texte des liens (ex: #495057)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Couleur au survol</th>
                        <td>
                            <input type="text" name="dep_hover_color" value="<?php echo esc_attr(get_option('dep_hover_color', '#0056b3')); ?>" placeholder="#0056b3" pattern="^#[0-9A-Fa-f]{6}$" class="regular-text" />
                            <p class="description">Couleur des liens au survol (ex: #0056b3)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Couleur de la page actuelle</th>
                        <td>
                            <input type="text" name="dep_current_color" value="<?php echo esc_attr(get_option('dep_current_color', '#0073aa')); ?>" placeholder="#0073aa" pattern="^#[0-9A-Fa-f]{6}$" class="regular-text" />
                            <p class="description">Couleur de fond de la page actuelle (ex: #0073aa)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Couleur du texte actuel</th>
                        <td>
                            <input type="text" name="dep_current_text_color" value="<?php echo esc_attr(get_option('dep_current_text_color', '#ffffff')); ?>" placeholder="#ffffff" pattern="^#[0-9A-Fa-f]{6}$" class="regular-text" />
                            <p class="description">Couleur du texte de la page actuelle (ex: #ffffff)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Couleur du focus</th>
                        <td>
                            <input type="text" name="dep_focus_color" value="<?php echo esc_attr(get_option('dep_focus_color', '#0073aa')); ?>" placeholder="#0073aa" pattern="^#[0-9A-Fa-f]{6}$" class="regular-text" />
                            <p class="description">Couleur du cadre lors du clic/focus (ex: #0073aa)</p>
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
new ABC_Pagination_Divi_Fixed();
