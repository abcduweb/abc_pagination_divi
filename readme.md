# ABC Pagination Divi

Un plugin WordPress complet pour améliorer la pagination dans le thème Divi avec des options de personnalisation avancées, un meilleur SEO et une expérience utilisateur moderne.

## 🚀 Fonctionnalités principales

### 🎨 Personnalisation avancée
- **3 styles de pagination** : Numéros + Suivant/Précédent, Suivant/Précédent uniquement, Numéros uniquement
- **Textes personnalisables** : Modifiez les textes "Suivant", "Précédent", "Charger plus"
- **Informations de page** : Affichage "Page X sur Y" optionnel
- **Design moderne** : Boutons arrondis, animations, effets hover

### ⚡ Modes de navigation
- **Pagination AJAX** : Navigation sans rechargement de page
- **Défilement infini** : Chargement automatique au scroll
- **Bouton "Charger plus"** : Chargement manuel des articles suivants
- **Pagination classique** : Compatible avec tous les navigateurs

### 🔸 Optimisation SEO
- **Balises rel="next"/rel="prev"** : Indique aux moteurs de recherche la structure de pagination
- **Meta robots optimisées** : Évite le contenu dupliqué sur les pages paginées
- **Titres de pages optimisés** : Inclusion automatique "Page X sur Y"
- **URLs canoniques** : Chaque page paginée a son URL canonique
- **Données structurées** : Schema.org pour les collections paginées
- **Meta descriptions** : Descriptions uniques pour chaque page

### 📱 Responsive & Accessible
- **Design adaptatif** : Optimisé pour mobile, tablette et desktop
- **Accessibilité WCAG** : Labels ARIA, navigation clavier, contraste
- **Mode sombre** : Support automatique du thème sombre
- **Impression** : Masquage automatique de la pagination à l'impression

## 📦 Installation

1. Téléchargez le dossier du plugin
2. Uploadez-le dans `/wp-content/plugins/`
3. Activez le plugin depuis l'administration WordPress
4. Configurez les options dans `Réglages > Pagination Divi`

## ⚙️ Configuration

### Options de style
- **Type de pagination** : Choisissez entre 3 styles
- **Textes personnalisés** : Modifiez les libellés des boutons
- **Informations de page** : Activez/désactivez l'affichage "Page X sur Y"

### Options avancées (en cours de développement)
- **Pagination AJAX** : Navigation sans rechargement
- **Défilement infini** : Chargement automatique au scroll
- **Bouton "Charger plus"** : Alternative au défilement infini
- **Textes de chargement** : Personnalisez les messages

## 🎯 Utilisation

### Intégration automatique
Le plugin fonctionne automatiquement avec :
- Les pages d'accueil (home/blog)
- Les archives de catégories
- Les archives de tags
- Les pages de recherche
- Les archives personnalisées

### Intégration manuelle
Pour les développeurs, vous pouvez utiliser les fonctions helper :

```php
// Afficher la pagination personnalisée
<?php dep_custom_pagination(); ?>

// Avec des options personnalisées
<?php dep_custom_pagination(array(
    'style' => 'next_and_number',
    'prev_text' => '← Précédent',
    'next_text' => 'Suivant →',
    'show_page_info' => true,
    'ajax' => true
)); ?>

// Vérifier si nous sommes sur une page paginée
<?php if (dep_is_paged()) : ?>
    <p>Page actuelle : <?php echo dep_get_current_page(); ?></p>
<?php endif; ?>
```

## 🔧 Personnalisation CSS

Le plugin utilise des classes CSS spécifiques que vous pouvez surcharger :

```css
/* Conteneur principal */
.dep-pagination-wrapper { }

/* Pagination */
.dep-pagination { }

/* Boutons */
.dep-pagination .page-numbers { }
.dep-pagination .page-numbers:hover { }
.dep-pagination .page-numbers.current { }

/* Bouton "Charger plus" */
.dep-load-more-btn { }

/* Messages de chargement */
.dep-loading { }
.dep-loading-spinner { }
```

## 📊 Performance

### Optimisations intégrées
- **Chargement conditionnel** : Scripts et CSS chargés uniquement sur les pages avec pagination
- **Préchargement** : Les pages adjacentes sont préchargées pour une navigation plus rapide
- **Lazy loading** : Compatible avec les images en lazy loading
- **Minification** : Scripts optimisés pour un chargement rapide

### Mesures de performance
- **Score Lighthouse** : Amélioration du score de performance
- **Core Web Vitals** : Optimisation pour LCP, FID, CLS
- **Cache** : Compatible avec les systèmes de cache (WP Rocket, W3 Total Cache, etc.)

## 🌐 SEO

### Améliorations SEO automatiques
- **Évitement du duplicate content** : Meta robots `noindex, follow` sur les pages > 1
- **Structure hiérarchique** : Balises next/prev pour indiquer la structure
- **Titres optimisés** : Format "Titre - Page X sur Y"
- **Descriptions uniques** : Meta descriptions spécifiques à chaque page
- **URLs canoniques** : Chaque page a son URL canonique

### Données structurées
```json
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Titre de la page",
  "url": "https://example.com/page/2/",
  "hasPart": {
    "@type": "ItemList",
    "numberOfItems": 10
  },
  "previousPage": "https://example.com/page/1/",
  "nextPage": "https://example.com/page/3/"
}
```

## 🔧 Développement

### Hooks disponibles
```php
// Après le chargement d'une page (AJAX)
add_action('dep.pageLoaded', function($data) {
    // $data['page'] : numéro de la page chargée
    // $data['append'] : true si ajout, false si remplacement
    // $data['content'] : contenu chargé
});

// Filtrer les arguments de pagination
add_filter('wp_link_pages_args', function($args) {
    // Personnaliser les arguments
    return $args;
});
```

### Fonctions helper
- `dep_is_paged()` : Vérifie si c'est une page paginée
- `dep_get_current_page()` : Récupère le numéro de page actuel
- `dep_get_total_pages()` : Récupère le nombre total de pages
- `dep_custom_pagination()` : Affiche la pagination personnalisée

## 🐛 Dépannage

### Problèmes courants
1. **La pagination ne s'affiche pas** : Vérifiez que vous êtes sur une page avec pagination (archive, recherche, etc.)
2. **L'AJAX ne fonctionne pas** : Vérifiez qu'il n'y a pas de conflit JavaScript
3. **Les styles ne s'appliquent pas** : Videz le cache de votre navigateur et du site

### Support
- **Documentation** : Consultez ce fichier README
- **Forum** : Support communautaire WordPress
- **GitHub** : Reportez les bugs sur le dépôt du plugin

## 📝 Mises à jour

### Version 1.0.0
- ✅ Pagination personnalisée
- ✅ Modes AJAX, infinite scroll, load more
- ✅ Optimisations SEO complètes
- ✅ Design responsive et accessible
- ✅ Panneau d'administration complet

### Roadmap
- 🔄 Support des custom post types
- 🔄 Thèmes de pagination prédéfinis
- 🔄 Analytics intégrés
- 🔄 Support AMP
- 🔄 Multilingue avancé

## 📄 Licence

Ce plugin est sous licence GPL v2 ou ultérieure.

## 👨‍💻 Auteur

Développé par **ABCduWeb** - Spécialiste WordPress et Divi

🌐 [https://abcduweb.fr](https://abcduweb.fr)
