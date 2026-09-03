🇫🇷 Version française | [🇬🇧 English version](api_endpoints.md)

---

# Endpoints API

| Méthode | Route | Auth | Description |
|---|---|---|---|
| `POST` | `/api/login` | — | Authentification par mot de passe, retourne un token Sanctum |
| `POST` | `/api/logout` | Bearer | Révocation du token courant |
| `POST` | `/api/activities` | Bearer | Import GPX + analyse automatique |
| `GET` | `/api/activities` | Bearer | Liste paginée (filtres disponibles) |
| `GET` | `/api/activities/{id}` | Bearer | Détail + segments |
| `PUT` | `/api/activities/{id}` | Bearer | Mise à jour des métadonnées |
| `DELETE` | `/api/activities/{id}` | Bearer | Suppression activité + fichier GPX |
| `POST` | `/api/activities/{id}/recalculate` | Bearer | Recalcul des stats depuis le GPX brut |
| `GET` | `/api/activities/{id}/track` | Bearer | Points GPS bruts (carte + profil altimétrique) |
| `GET` | `/api/stats` | Bearer | Données de progression pour graphes |

**Exemple — `/api/stats`**

```
GET /api/stats?metric=avg_ascent_speed_mh&type=trail&slope_min=15&slope_max=35&date_from=2024-01-01
```

```json
{
  "data": [
    { "date": "2024-03-15", "value": 423.5, "activity_title": "Aiguilles Rouges" },
    { "date": "2024-04-02", "value": 512.0, "activity_title": "Col de Balme" }
  ],
  "meta": { "metric": "avg_ascent_speed_mh", "unit": "m/h", "count": 2 }
}
```
