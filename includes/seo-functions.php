<?php
/**
 * Fonctions SEO pour ABC Pagination Divi
 * Améliore le référencement des pages paginées
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class ABC_Pagination_Divi_SEO {
    
    public function __construct() {
        add_action('wp_head', array($this, 'add_pagination_seo_meta'), 1);
        add_action('wp_head', array($this, 'add_rel_next_prev'), 2);
        add_filter('wp_title', array($this, 'modify_page_title'), 10, 2);
        add_filter('document_title_parts', array($this, 'modify_document_title'), 10);
        add_action('wp_head', array($this, 'add_canonical_url'), 3);
        add_action('wp_head', array($this, 'add_structured_data'), 4);
        add_filter('robots_txt', array($this, 'modify_robots_txt'), 10, 2);
        add_action('wp_head', array($this, 'add_meta_description'), 5);
    }
    
    /**
     * Ajoute les métadonnées SEO pour la pagination
     */
    public function add_pagination_seo_meta() {
        if (!is_paged()) {
            return;
        }
        
        global $wp_query;
        $current = max(1, get_query_var('paged'));
        $total = $wp_query->max_num_pages;
        
        // Meta robots pour éviter le contenu dupliqué
        if ($current > 1) {
            echo '<meta name="robots" content="noindex, follow">' . "\n";
        }
        
        // Meta description spécifique à la pagination
        $description = $this->get_pagination_description($current, $total);
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
    }
    
    /**
     * Ajoute les balises rel="next" et rel="prev"
     */
    public function add_rel_next_prev() {
        global $wp_query;
        
        if ($wp_query->max_num_pages <= 1) {
            return;
        }
        
        $current = max(1, get_query_var('paged'));
        $base_url = $this->get_base_pagination_url();
        
        // Rel prev
        if ($current > 1) {
            $prev_page = $current - 1;
            $prev_url = $this->build_page_url($base_url, $prev_page);
            echo '<link rel="prev" href="' . esc_url($prev_url) . '">' . "\n";
        }
        
        // Rel next
        if ($current < $wp_query->max_num_pages) {
            $next_page = $current + 1;
            $next_url = $this->build_page_url($base_url, $next_page);
            echo '<link rel="next" href="' . esc_url($next_url) . '">' . "\n";
        }
    }
    
    /**
     * Modifie le titre de la page pour inclure la pagination
     */
    public function modify_page_title($title, $sep) {
        if (!is_paged()) {
            return $title;
        }
        
        global $wp_query;
        $current = max(1, get_query_var('paged'));
        $total = $wp_query->max_num_pages;
        
        // Extraire le titre original
        $title_parts = explode($sep, $title);
        $main_title = trim($title_parts[0]);
        
        return sprintf('%s %s Page %d sur %d', $main_title, $sep, $current, $total);
    }
    
    /**
     * Modifie le titre du document (WordPress 4.4+)
     */
    public function modify_document_title($title) {
        if (!is_paged()) {
            return $title;
        }
        
        global $wp_query;
        $current = max(1, get_query_var('paged'));
        $total = $wp_query->max_num_pages;
        
        $title['title'] .= sprintf(' - Page %d sur %d', $current, $total);
        
        return $title;
    }
    
    /**
     * Ajoute l'URL canonique
     */
    public function add_canonical_url() {
        if (!is_paged()) {
            return;
        }
        
        $canonical_url = $this->get_current_page_url();
        echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
    }
    
    /**
     * Ajoute les données structurées pour la pagination
     */
    public function add_structured_data() {
        if (!is_paged()) {
            return;
        }
        
        global $wp_query;
        $current = max(1, get_query_var('paged'));
        $total = $wp_query->max_num_pages;
        
        $structured_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $this->get_page_title(),
            'url' => $this->get_current_page_url(),
            'hasPart' => array(
                '@type' => 'ItemList',
                'numberOfItems' => $wp_query->post_count,
                'itemListElement' => array()
            )
        );
        
        // Ajouter les liens vers les autres pages
        if ($current > 1) {
            $structured_data['previousPage'] = $this->build_page_url(
                $this->get_base_pagination_url(),
                $current - 1
            );
        }
        
        if ($current < $total) {
            $structured_data['nextPage'] = $this->build_page_url(
                $this->get_base_pagination_url(),
                $current + 1
            );
        }
        
        echo '<script type="application/ld+json">' . "\n";
        echo json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo '</script>' . "\n";
    }
    
    /**
     * Modifie robots.txt pour gérer la pagination
     */
    public function modify_robots_txt($output, $public) {
        if (!$public) {
            return $output;
        }
        
        // Ajouter des règles pour les pages paginées
        $pagination_rules = "\n# ABC Pagination Divi SEO Rules\n";
        $pagination_rules .= "User-agent: *\n";
        $pagination_rules .= "Disallow: */page/1/$\n"; // Éviter les doublons avec la page principale
        
        return $output . $pagination_rules;
    }
    
    /**
     * Ajoute une méta description optimisée pour la pagination
     */
    public function add_meta_description() {
        if (!is_paged()) {
            return;
        }
        
        global $wp_query;
        $current = max(1, get_query_var('paged'));
        $total = $wp_query->max_num_pages;
        
        $description = $this->get_pagination_description($current, $total);
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
    }
    
    /**
     * Génère une description pour la page paginée
     */
    private function get_pagination_description($current, $total) {
        $base_description = get_bloginfo('description');
        
        if (is_home() || is_front_page()) {
            return sprintf(
                'Découvrez nos articles - Page %d sur %d. %s',
                $current,
                $total,
                $base_description
            );
        } elseif (is_category()) {
            $category = get_category(get_query_var('cat'));
            return sprintf(
                'Articles dans la catégorie %s - Page %d sur %d. %s',
                $category->name,
                $current,
                $total,
                $base_description
            );
        } elseif (is_tag()) {
            $tag = get_tag(get_query_var('tag_id'));
            return sprintf(
                'Articles avec le tag %s - Page %d sur %d. %s',
                $tag->name,
                $current,
                $total,
                $base_description
            );
        } elseif (is_search()) {
            return sprintf(
                'Résultats de recherche pour "%s" - Page %d sur %d. %s',
                get_search_query(),
                $current,
                $total,
                $base_description
            );
        }
        
        return sprintf(
            'Page %d sur %d - %s',
            $current,
            $total,
            $base_description
        );
    }
    
    /**
     * Récupère l'URL de base pour la pagination
     */
    private function get_base_pagination_url() {
        if (is_home() || is_front_page()) {
            return home_url('/');
        } elseif (is_category()) {
            return get_category_link(get_query_var('cat'));
        } elseif (is_tag()) {
            return get_tag_link(get_query_var('tag_id'));
        } elseif (is_search()) {
            return home_url('?s=' . urlencode(get_search_query()));
        } elseif (is_post_type_archive()) {
            return get_post_type_archive_link(get_query_var('post_type'));
        } else {
            // Pour les autres types d'archives
            return get_pagenum_link(1, false);
        }
    }
    
    /**
     * Construit l'URL pour une page spécifique
     */
    private function build_page_url($base_url, $page) {
        if ($page <= 1) {
            return $base_url;
        }
        
        // Gérer différents formats d'URL
        if (strpos($base_url, '?') !== false) {
            // Format avec paramètres
            return add_query_arg('paged', $page, $base_url);
        } else {
            // Format avec réécriture d'URL
            return trailingslashit($base_url) . 'page/' . $page . '/';
        }
    }
    
    /**
     * Récupère l'URL de la page actuelle
     */
    private function get_current_page_url() {
        $current = max(1, get_query_var('paged'));
        $base_url = $this->get_base_pagination_url();
        
        return $this->build_page_url($base_url, $current);
    }
    
    /**
     * Récupère le titre de la page actuelle
     */
    private function get_page_title() {
        if (is_home() || is_front_page()) {
            return get_bloginfo('name');
        } elseif (is_category()) {
            return single_cat_title('', false);
        } elseif (is_tag()) {
            return single_tag_title('', false);
        } elseif (is_search()) {
            return 'Résultats pour: ' . get_search_query();
        } elseif (is_post_type_archive()) {
            return post_type_archive_title('', false);
        } else {
            return get_the_title();
        }
    }
}

// Fonctions helper pour les développeurs

/**
 * Vérifie si nous sommes sur une page paginée
 */
function dep_is_paged() {
    return is_paged();
}

/**
 * Récupère le numéro de page actuel
 */
function dep_get_current_page() {
    return max(1, get_query_var('paged'));
}

/**
 * Récupère le nombre total de pages
 */
function dep_get_total_pages() {
    global $wp_query;
    return $wp_query->max_num_pages;
}

/**
 * Récupère l'URL de base pour la pagination
 */
function dep_get_base_pagination_url() {
    if (is_home() || is_front_page()) {
        return home_url('/');
    } elseif (is_category()) {
        return get_category_link(get_query_var('cat'));
    } elseif (is_tag()) {
        return get_tag_link(get_query_var('tag_id'));
    } elseif (is_search()) {
        return home_url('?s=' . urlencode(get_search_query()));
    } elseif (is_post_type_archive()) {
        return get_post_type_archive_link(get_query_var('post_type'));
    } else {
        // Pour les autres types d'archives
        return get_pagenum_link(1, false);
    }
}

/**
 * Construit l'URL pour une page spécifique
 */
function dep_build_page_url($base_url, $page) {
    if ($page <= 1) {
        return $base_url;
    }
    
    // Gérer différents formats d'URL
    if (strpos($base_url, '?') !== false) {
        // Format avec paramètres
        return add_query_arg('paged', $page, $base_url);
    } else {
        // Format avec réécriture d'URL
        return trailingslashit($base_url) . 'page/' . $page . '/';
    }
}

/**
 * Récupère l'URL de la page actuelle
 */
function dep_get_current_page_url() {
    $current = dep_get_current_page();
    $base_url = dep_get_base_pagination_url();
    
    return dep_build_page_url($base_url, $current);
}

/**
 * Génère les balises hreflang pour les pages paginées
 */
function dep_add_hreflang_tags() {
    if (!is_paged()) {
        return;
    }
    
    global $wp_query;
    $current = dep_get_current_page();
    $total = dep_get_total_pages();
    
    // Ajouter hreflang pour la page actuelle
    $current_url = dep_get_current_page_url();
    echo '<link rel="alternate" hreflang="' . get_bloginfo('language') . '" href="' . esc_url($current_url) . '">' . "\n";
    
    // Ajouter hreflang x-default pour la première page
    if ($current > 1) {
        $first_page_url = dep_build_page_url(dep_get_base_pagination_url(), 1);
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($first_page_url) . '">' . "\n";
    }
}

/**
 * Ajoute les balises Open Graph pour les réseaux sociaux
 */
function dep_add_open_graph_tags() {
    if (!is_paged()) {
        return;
    }
    
    $current = dep_get_current_page();
    $total = dep_get_total_pages();
    $url = dep_get_current_page_url();
    $title = get_the_title() . ' - Page ' . $current . ' sur ' . $total;
    
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    
    if (is_single()) {
        echo '<meta property="og:type" content="article">' . "\n";
    } else {
        echo '<meta property="og:type" content="website">' . "\n";
    }
}

// Initialiser les fonctions SEO
new ABC_Pagination_Divi_SEO();

// Ajouter les hooks supplémentaires
add_action('wp_head', 'dep_add_hreflang_tags', 6);
add_action('wp_head', 'dep_add_open_graph_tags', 7);
