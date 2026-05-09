# Resources du module Parametrage

Ce dossier contient toutes les ressources API pour les entités du module Parametrage.

## 📁 Fichiers créés

### Resources individuelles
- `BanqueResource.php` - Resource pour l'entité Banque
- `DevisesResource.php` - Resource pour l'entité Devises
- `PaysResource.php` - Resource pour l'entité Pays
- `FournisseurPaiementResource.php` - Resource pour l'entité FournisseurPaiement
- `PaysDeviseResource.php` - Resource pour l'entité PaysDevise
- `ZoneResource.php` - Resource pour l'entité Zone

### Collections
- `BanqueCollection.php` - Collection pour les banques
- `PaysCollection.php` - Collection pour les pays

## 🚀 Utilisation dans les contrôleurs

### Exemple d'utilisation simple

```php
use Modules\Parametrage\Resources\PaysResource;
use Modules\Parametrage\Resources\PaysCollection;

// Dans votre controller
public function index()
{
    $pays = Pays::with(['paysDevises.devise', 'zones'])->paginate(10);
    return new PaysCollection($pays);
}

public function show($id)
{
    $pays = Pays::with(['paysDevises.devise', 'zones', 'banques'])->findOrFail($id);
    return new PaysResource($pays);
}
```

### Exemple avec relations conditionnelles

```php
public function index()
{
    $pays = Pays::withCount(['paysDevises', 'zones', 'banques'])
        ->when(request()->include_relations, function ($query) {
            $query->with(['paysDevises', 'zones', 'banques']);
        })
        ->paginate(10);
    
    return new PaysCollection($pays);
}
```

## 📊 Format des réponses

### Resource individuelle
```json
{
    "id": 1,
    "libelle": "Côte d'Ivoire",
    "code": "CI",
    "phone_length": 10,
    "iso": "CIV",
    "full_phone_example": "CIXXXXXXXX",
    "pays_devises": [...],
    "zones": [...],
    "created_at": "2024-01-28T10:00:00.000000Z",
    "updated_at": "2024-01-28T10:00:00.000000Z"
}
```

### Collection
```json
{
    "data": [...],
    "meta": {
        "total": 50,
        "count": 10,
        "per_page": 10,
        "current_page": 1,
        "total_pages": 5
    }
}
```

## 🔧 Fonctionnalités spéciales

### PaysResource
- `full_phone_example` : Exemple de numéro complet
- Relations chargées : `pays_devises`, `zones`, `banques`
- Compteurs : `pays_devises_count`, `zones_count`, `banques_count`

### FournisseurPaiementResource
- `type_label` : Libellé du type (Mobile Money, Banque, etc.)
- `statut_label` : Libellé du statut (Actif, Inactif)
- `cle_secrete_api` : Masquée pour la sécurité (`***`)

### ZoneResource
- `type_zone_label` : Libellé du type de zone
- Support des coordonnées géographiques

### PaysDeviseResource
- `est_devise_nationale` : Détecte si c'est la devise nationale

## 🎯 Bonnes pratiques

1. **Utiliser `whenLoaded()`** pour les relations conditionnelles
2. **Utiliser `whenNotNull()`** pour les champs optionnels
3. **Ajouter des compteurs** avec `withCount()` pour optimiser
4. **Masquer les données sensibles** (clés API, mots de passe)
5. **Utiliser les collections** pour les réponses paginées

## 📝 Notes

- Toutes les ressources incluent `deleted_at` quand disponible
- Les timestamps sont inclus par défaut
- Les relations sont chargées uniquement quand nécessaire
- Les collections incluent les métadonnées de pagination
