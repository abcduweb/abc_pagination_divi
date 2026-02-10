jQuery(document).ready(function($) {
    'use strict';
    
    // Variables globales
    let isLoading = false;
    let currentPage = 1;
    let totalPages = 0;
    
    // Initialisation
    initPagination();
    
    function initPagination() {
        // Récupérer les informations de pagination depuis la page
        const pageInfo = $('.page-info').text();
        const match = pageInfo.match(/Page (\d+) sur (\d+)/);
        if (match) {
            currentPage = parseInt(match[1]);
            totalPages = parseInt(match[2]);
        }
        
        // Activer la pagination AJAX si activée
        if (depOptions.ajax_pagination) {
            enableAjaxPagination();
        }
        
        // Activer le défilement infini si activé
        if (depOptions.infinite_scroll) {
            enableInfiniteScroll();
        }
        
        // Activer le bouton "Charger plus" si activé
        if (depOptions.load_more_button) {
            enableLoadMore();
        }
    }
    
    function enableAjaxPagination() {
        $('.dep-pagination .page-numbers').on('click', function(e) {
            e.preventDefault();
            
            if (isLoading || $(this).hasClass('current') || $(this).hasClass('dots')) {
                return;
            }
            
            const href = $(this).attr('href');
            const url = new URL(href);
            const page = url.searchParams.get('paged') || url.pathname.match(/\/page\/(\d+)/);
            
            if (page) {
                loadPage(page, false);
            }
        });
    }
    
    function enableInfiniteScroll() {
        let throttleTimer;
        
        $(window).on('scroll', function() {
            if (isLoading) return;
            
            clearTimeout(throttleTimer);
            throttleTimer = setTimeout(function() {
                if (isNearBottom()) {
                    loadNextPage();
                }
            }, 100);
        });
    }
    
    function enableLoadMore() {
        $('.dep-load-more-btn').on('click', function(e) {
            e.preventDefault();
            
            if (isLoading) return;
            
            const $btn = $(this);
            const nextPage = parseInt($btn.data('current')) + 1;
            const total = parseInt($btn.data('total'));
            
            if (nextPage <= total) {
                loadPage(nextPage, true);
                $btn.data('current', nextPage);
                
                if (nextPage >= total) {
                    $btn.fadeOut();
                }
            }
        });
    }
    
    function isNearBottom() {
        const scrollPosition = $(window).scrollTop();
        const windowHeight = $(window).height();
        const documentHeight = $(document).height();
        
        return scrollPosition + windowHeight >= documentHeight - 500;
    }
    
    function loadPage(page, append = false) {
        if (isLoading) return;
        
        isLoading = true;
        showLoading();
        
        // Construire l'URL de la page à charger
        const currentUrl = window.location.href;
        const url = new URL(currentUrl);
        
        // Gérer différents formats d'URL
        let pageUrl;
        if (url.pathname.includes('/page/')) {
            pageUrl = url.pathname.replace(/\/page\/\d+/, '/page/' + page) + url.search;
        } else {
            pageUrl = url.pathname + '/page/' + page + url.search;
        }
        
        // Requête AJAX pour charger le contenu
        $.ajax({
            url: pageUrl,
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                const $response = $(response);
                const newContent = $response.find('.et_pb_post, .post, article').length > 0 ? 
                    $response.find('.et_pb_post, .post, article') : 
                    $response.find('.entry-content, .post-content').children();
                
                const newPagination = $response.find('.dep-pagination-wrapper');
                
                if (append && newContent.length > 0) {
                    // Mode append (pour "Charger plus" ou défilement infini)
                    newContent.addClass('dep-new-articles').hide();
                    $('.et_pb_blog_grid, .posts-container, main').append(newContent);
                    newContent.fadeIn(600);
                    
                    // Mettre à jour la pagination
                    $('.dep-pagination-wrapper').replaceWith(newPagination);
                    
                    // Réinitialiser les événements
                    initPagination();
                    
                    // Mettre à jour l'URL sans recharger
                    if (history.pushState) {
                        history.pushState({page: page}, '', pageUrl);
                    }
                } else if (!append && newContent.length > 0) {
                    // Mode remplacement (pour pagination AJAX classique)
                    const container = $('.et_pb_blog_grid, .posts-container, main');
                    container.html(newContent);
                    container.addClass('dep-new-articles');
                    
                    // Mettre à jour la pagination
                    $('.dep-pagination-wrapper').replaceWith(newPagination);
                    
                    // Réinitialiser les événements
                    initPagination();
                    
                    // Mettre à jour l'URL
                    if (history.pushState) {
                        history.pushState({page: page}, '', pageUrl);
                    }
                    
                    // Scroller en haut
                    $('html, body').animate({
                        scrollTop: $('.dep-pagination-wrapper').offset().top - 100
                    }, 500);
                }
                
                currentPage = page;
                
                // Déclencher un événement personnalisé
                $(document).trigger('dep.pageLoaded', {
                    page: page,
                    append: append,
                    content: newContent
                });
                
            },
            error: function(xhr, status, error) {
                console.error('Erreur lors du chargement de la page:', error);
                showError('Une erreur est survenue lors du chargement des articles.');
            },
            complete: function() {
                isLoading = false;
                hideLoading();
            }
        });
    }
    
    function loadNextPage() {
        const nextPage = currentPage + 1;
        if (nextPage <= totalPages) {
            loadPage(nextPage, true);
        }
    }
    
    function showLoading() {
        if (depOptions.infinite_scroll) {
            // Afficher un spinner en bas de page
            if (!$('.dep-loading').length) {
                const loadingHtml = `
                    <div class="dep-loading">
                        <div class="dep-loading-spinner"></div>
                        <div class="dep-loading-text">${depOptions.loading_text}</div>
                    </div>
                `;
                $('.dep-pagination-wrapper').before(loadingHtml);
            }
        } else if (depOptions.load_more_button) {
            $('.dep-load-more-btn').addClass('loading');
        }
    }
    
    function hideLoading() {
        $('.dep-loading').remove();
        $('.dep-load-more-btn').removeClass('loading');
    }
    
    function showError(message) {
        const errorHtml = `
            <div class="dep-error" style="
                background: #f8d7da;
                color: #721c24;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                text-align: center;
                border: 1px solid #f5c6cb;
            ">
                ${message}
            </div>
        `;
        $('.dep-pagination-wrapper').before(errorHtml);
        
        // Auto-supprimer après 5 secondes
        setTimeout(function() {
            $('.dep-error').fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Gestion de l'historique du navigateur
    $(window).on('popstate', function(e) {
        if (e.originalEvent.state && e.originalEvent.state.page) {
            loadPage(e.originalEvent.state.page, false);
        }
    });
    
    // Amélioration de l'accessibilité
    $('.dep-pagination .page-numbers').each(function() {
        const $this = $(this);
        const ariaLabel = $this.hasClass('current') ? 
            'Page actuelle, page ' + $this.text() : 
            'Aller à la page ' + $this.text();
        $this.attr('aria-label', ariaLabel);
    });
    
    $('.dep-load-more-btn').attr('aria-label', 'Charger plus d\'articles');
    
    // Préchargement des pages adjacentes (optionnel)
    function preloadAdjacentPages() {
        if (currentPage > 1) {
            preloadPage(currentPage - 1);
        }
        if (currentPage < totalPages) {
            preloadPage(currentPage + 1);
        }
    }
    
    function preloadPage(page) {
        const currentUrl = window.location.href;
        const url = new URL(currentUrl);
        let pageUrl;
        
        if (url.pathname.includes('/page/')) {
            pageUrl = url.pathname.replace(/\/page\/\d+/, '/page/' + page) + url.search;
        } else {
            pageUrl = url.pathname + '/page/' + page + url.search;
        }
        
        // Précharger en utilisant une requête légère
        $.get(pageUrl, function() {
            // Page préchargée avec succès
        });
    }
    
    // Activer le préchargement après un délai
    setTimeout(preloadAdjacentPages, 2000);
    
    // Analytics tracking (si Google Analytics est disponible)
    function trackPageView(page) {
        if (typeof gtag !== 'undefined') {
            gtag('config', 'GA_MEASUREMENT_ID', {
                page_path: window.location.pathname + '/page/' + page
            });
        } else if (typeof ga !== 'undefined') {
            ga('send', 'pageview', window.location.pathname + '/page/' + page);
        }
    }
    
    // Suivre les changements de page
    $(document).on('dep.pageLoaded', function(e, data) {
        trackPageView(data.page);
    });
});
