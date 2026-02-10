<?php
/**
 * ABC Pagination Divi - Version ultra-simple
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class ABC_Pagination_Divi_Simple {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_styles'));
        add_action('wp_footer', array($this, 'add_pagination_script'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Debug pour vérifier l'option au chargement
        add_action('wp', function() {
            $posts_per_page = get_option('dep_posts_per_page', 'not set');
            error_log('DEP: dep_posts_per_page option = ' . $posts_per_page);
        });
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
    
    public function add_pagination_script() {
        // Forcer l'affichage sur les pages spécifiques
        $uri = $_SERVER['REQUEST_URI'];
        $should_show = (strpos($uri, '/actualites') !== false || strpos($uri, '/blog') !== false || strpos($uri, '/page/') !== false);
        
        if (!get_option('dep_enable_plugin', true) || !$should_show) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('DEP: Script loaded for:', '<?php echo $uri; ?>');
            
            // Cacher l'ancienne pagination
            $('.et_pb_pagination, .pagination:not(.dep-pagination-wrapper)').hide();
            
            // Créer notre pagination personnalisée
            var currentUrl = window.location.href;
            var currentPage = 1;
            var totalPages = 0;
            var categorySlug = '';
            
            console.log('DEP: Current URL:', currentUrl);
            
            // Extraire le numéro de page de l'URL
            var pageMatch = currentUrl.match(/\/page\/(\d+)\//);
            if (pageMatch) {
                currentPage = parseInt(pageMatch[1]);
                console.log('DEP: Page detected from URL:', currentPage);
            } else {
                currentPage = 1;
                console.log('DEP: No page number in URL, assuming page 1');
            }
            
            // Détecter la catégorie depuis l'URL
            if (currentUrl.includes('/actualites')) {
                categorySlug = 'actualites';
                console.log('DEP: Category detected from URL:', categorySlug);
            } else if (currentUrl.includes('/blog')) {
                console.log('DEP: Blog page detected');
            }
            
            // Compter les articles sur la page actuelle
            var articleCount = $('article, .post, .et_pb_post').length;
            console.log('DEP: Articles found:', articleCount);
            
            // Calculer le nombre de pages
            if (articleCount > 0) {
                if (categorySlug === 'actualites') {
                    // Pour actualites, estimer qu'il y a plusieurs pages
                    var postsPerPage = <?php echo get_option('dep_posts_per_page', get_option('posts_per_page', 10)); ?>;
                    totalPages = Math.max(currentPage + 1, 5); // Au moins 5 pages
                    console.log('DEP: Estimated pages for actualites:', totalPages, 'posts per page:', postsPerPage);
                } else {
                    // Pour le blog
                    var totalPosts = <?php echo wp_count_posts()->publish; ?>;
                    var postsPerPage = <?php echo get_option('dep_posts_per_page', get_option('posts_per_page', 10)); ?>;
                    totalPages = Math.ceil(totalPosts / postsPerPage);
                    console.log('DEP: Total pages for blog:', totalPages, 'posts per page:', postsPerPage);
                }
            }
            
            console.log('DEP: Final pagination info:', {
                current: currentPage,
                total: totalPages,
                url: currentUrl,
                category: categorySlug,
                articles: articleCount
            });
            
            // Créer la pagination si on a plusieurs pages
            if (totalPages > 1) {
                console.log('DEP: Creating pagination HTML');
                
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
                    console.log('DEP: Added prev button:', prevUrl);
                }
                
                // Pages numériques
                if (paginationStyle === 'numeric' || paginationStyle === 'both') {
                    var startPage = Math.max(1, currentPage - 1);
                    var endPage = Math.min(totalPages, currentPage + 1);
                    
                    for (var i = startPage; i <= endPage; i++) {
                        if (i === currentPage) {
                            paginationHtml += '<span class="page-numbers current">' + i + '</span>';
                        } else {
                            var pageUrl = buildPageUrl(currentUrl, i, categorySlug);
                            paginationHtml += '<a class="page-numbers" href="' + pageUrl + '">' + i + '</a>';
                        }
                    }
                    console.log('DEP: Added numeric pages from', startPage, 'to', endPage);
                }
                
                // Bouton suivant
                if (currentPage < totalPages) {
                    var nextUrl = buildPageUrl(currentUrl, currentPage + 1, categorySlug);
                    paginationHtml += '<a class="page-numbers next" href="' + nextUrl + '">' + nextText + '</a>';
                    console.log('DEP: Added next button:', nextUrl);
                }
                
                paginationHtml += '</div>';
                paginationHtml += '</nav>';
                
                console.log('DEP: Pagination HTML created');
                
                // Insérer la pagination après les articles
                var $pagination = $(paginationHtml);
                
                if ($('.et_pb_blog_grid').length > 0) {
                    console.log('DEP: Inserting after .et_pb_blog_grid');
                    $('.et_pb_blog_grid').after($pagination);
                } else if ($('.et_pb_posts').length > 0) {
                    console.log('DEP: Inserting after .et_pb_posts');
                    $('.et_pb_posts').after($pagination);
                } else if ($('main').length > 0) {
                    console.log('DEP: Inserting after main article');
                    $('main article').last().after($pagination);
                } else if ($('.entry-content').length > 0) {
                    console.log('DEP: Inserting in .entry-content');
                    $('.entry-content').append($pagination);
                } else {
                    console.log('DEP: No container found, appending to body');
                    $('body').append($pagination);
                }
                
                $pagination.show();
                console.log('DEP: Pagination inserted and shown');
            } else {
                console.log('DEP: No pagination needed (totalPages <= 1)');
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
    
    private function should_show_pagination() {
        // Forcer l'affichage sur les pages spécifiques
        $uri = $_SERVER['REQUEST_URI'];
        $should_show = (strpos($uri, '/actualites') !== false || strpos($uri, '/blog') !== false || strpos($uri, '/page/') !== false);
        
        // Toujours afficher sur ces URLs spécifiques
        $force_show = (
            strpos($uri, '/actualites') !== false ||
            strpos($uri, '/contenu-interactif-gamification') !== false ||
            strpos($uri, '/blog') !== false ||
            strpos($uri, '/page/') !== false ||
            is_home() || is_archive() || is_category() || is_tag() || is_tax() || is_search()
        );
        
        // Debug pour voir pourquoi la décision est prise
        echo '<!-- DEP DEBUG: URI = ' . $uri . ' -->';
        echo '<!-- DEP DEBUG: Force show = ' . ($force_show ? 'true' : 'false') . ' -->';
        echo '<!-- DEP DEBUG: contains /actualites = ' . (strpos($uri, '/actualites') !== false ? 'true' : 'false') . ' -->';
        echo '<!-- DEP DEBUG: contains /blog = ' . (strpos($uri, '/blog') !== false ? 'true' : 'false') . ' -->';
        
        return $force_show;
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Pagination Divi',
            'Pagination Divi',
            'manage_options',
            'divi-enhanced-pagination',
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
        register_setting('dep_settings', 'dep_posts_per_page');
        
        // Ajouter les champs hexadécimaux
        add_filter('pre_update_option_dep_primary_color', array($this, 'update_color_from_hex'), 10, 2);
        add_filter('pre_update_option_dep_bg_color', array($this, 'update_color_from_hex'), 10, 2);
        add_filter('pre_update_option_dep_border_color', array($this, 'update_color_from_hex'), 10, 2);
        add_filter('pre_update_option_dep_text_color', array($this, 'update_color_from_hex'), 10, 2);
        
        // Appliquer le nombre d'articles par page personnalisé
        add_action('pre_get_posts', array($this, 'custom_posts_per_page'));
    }
    
    public function custom_posts_per_page($query) {
        if (!is_admin() && $query->is_main_query()) {
            // Appliquer sur toutes les pages avec pagination
            if (is_home() || is_archive() || is_category() || is_tag() || is_tax() || is_search() || 
                strpos($_SERVER['REQUEST_URI'], '/blog') !== false || 
                strpos($_SERVER['REQUEST_URI'], '/actualites') !== false) {
                
                $custom_posts_per_page = get_option('dep_posts_per_page', 0);
                if ($custom_posts_per_page > 0) {
                    $query->set('posts_per_page', $custom_posts_per_page);
                    error_log('DEP: Setting posts_per_page to ' . $custom_posts_per_page . ' for URI: ' . $_SERVER['REQUEST_URI']);
                }
            }
        }
        return $query;
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
            <h1>Pagination Divi Améliorée</h1>
            
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
                        <th><label for="dep_posts_per_page">Articles par page</label></th>
                        <td>
                            <input type="number" name="dep_posts_per_page" id="dep_posts_per_page" value="<?php echo esc_attr(get_option('dep_posts_per_page', '')); ?>" min="1" max="50" step="1" class="regular-text">
                            <span class="description">articles</span>
                            <p class="description">
                                <strong>Laissez vide pour utiliser la valeur par défaut de WordPress (<?php echo get_option('posts_per_page', 10); ?> articles)</strong><br>
                                Cette option modifie le nombre d'articles affichés sur les pages blog, catégories, tags et archives.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h2>Personnalisation des couleurs</h2>
                <table class="form-table">
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
new ABC_Pagination_Divi_Simple();
