# Admin Dashboard — Design Spec
Date: 2026-04-16

## Objectif

Ajouter une page dashboard admin accessible via la navigation principale, affichant les statistiques globales clés pour les administrateurs : utilisateurs, chiffre d'affaires, remboursements, restaurants et demandes vendeur.

---

## Architecture

### Approche retenue
Option A — Controller dédié `AdminDashboardController`, cohérent avec le pattern des autres controllers admin existants (`AdminUserController`, `AdminSalesController`, etc.).

### Fichiers impactés

| Fichier | Action |
|---|---|
| `src/Controller/Admin/AdminDashboardController.php` | Créer — route `GET /admin/dashboard`, nom `app_admin_dashboard`, `#[IsGranted('ROLE_ADMIN')]` |
| `templates/admin/dashboard/index.html.twig` | Créer — page dashboard avec cards de statistiques |
| `templates/Components/headers/_primary_nav.html.twig` | Modifier — ajout du lien "Vue d'ensemble" en première position admin |
| `src/Repository/OrderRepository.php` | Modifier — ajout `getDashboardStats(): array` |
| `src/Repository/UserRepository.php` | Modifier — ajout `getDashboardStats(): array` |

---

## Données affichées

Cinq cards disposées en grille responsive (2 colonnes mobile, 3-5 desktop) :

| Card | Valeur principale | Sous-texte |
|---|---|---|
| **Utilisateurs** | Total comptes | `{n} acheteurs · {n} vendeurs` |
| **Chiffre d'affaires** | Somme `totalAmount` (commandes abouties) | `{n} commandes` |
| **Remboursements** | Total toutes demandes | `{n} en attente` |
| **Restaurants** | Total publiés / programmés / en cours | `{n} en modération` |
| **Demandes vendeur** | Total toutes demandes | `{n} en attente` |

---

## Requêtes

### `UserRepository::getDashboardStats(): array`
Une seule requête DQL avec `COUNT` sur tous les utilisateurs. Le filtrage par rôle se fait en PHP (cohérent avec `findForAdmin()` existant qui fait pareil — les rôles sont stockés en JSON).

Retourne :
```php
[
    'total' => int,
    'buyers' => int,   // ROLE_USER (sans ROLE_VENDOR ni ROLE_ADMIN)
    'vendors' => int,  // ROLE_VENDOR
]
```

### `OrderRepository::getDashboardStats(): array`
Une requête DQL : `SUM(o.totalAmount)` + `COUNT(o.id)` sur statuts `PAYEE`, `REMBOURSEMENT_PARTIEL`, `REMBOURSEE`.

Retourne :
```php
[
    'totalRevenue' => float,
    'orderCount' => int,
]
```

### Refunds
- Total : `COUNT` simple sur toutes les demandes (nouvelle méthode `countAll()` dans `RefundRepository`)
- En attente : réutilise `countPendingForAdmin()` déjà existant

### Restaurants
- Actifs (publiés/programmés/en cours) : `COUNT` sur statuts `PUBLIE`, `PROGRAMME`, `EN_COURS`
- En modération : réutilise `countPendingValidation()` déjà existant

### VendorRequests
- Total : `COUNT` simple sur toutes les demandes (nouvelle méthode `countAll()` dans `VendorRequestRepository`)
- En attente : réutilise `countPendingForAdmin()` déjà existant

---

## Navigation

Dans `_primary_nav.html.twig`, ajout d'une entrée en première position parmi les liens `ROLE_ADMIN` :

```twig
{ path: path('app_admin_dashboard'), label: 'Vue d\'ensemble', icon: 'layout-dashboard', role: 'ROLE_ADMIN' }
```

L'icône `layout-dashboard` suit la convention des icônes Lucide déjà utilisées dans la nav.

---

## Template

Le template `admin/dashboard/index.html.twig` étend `base.html.twig`, suit le style des pages admin existantes (header avec badge "Administration", cards avec classes Tailwind cohérentes avec le design system en place).

Pas de JS, pas de polling — les données sont rendues côté serveur à chaque chargement de page.

---

## Hors scope

- Graphiques / courbes d'évolution temporelle
- Comparaisons par période (7j / 30j)
- Mise à jour en temps réel via WebSocket ou polling AJAX
- Export CSV des données
