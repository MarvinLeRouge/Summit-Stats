[🇫🇷 Version française](api_endpoints.fr.md) | 🇬🇧 English version

---

# API endpoints

| Method | Route | Auth | Description |
|---|---|---|---|
| `POST` | `/api/login` | — | Authenticate with password, returns a Sanctum token |
| `POST` | `/api/logout` | Bearer | Revoke the current token |
| `POST` | `/api/activities` | Bearer | GPX import + automatic analysis |
| `GET` | `/api/activities` | Bearer | Paginated list (filters available) |
| `GET` | `/api/activities/{id}` | Bearer | Detail + segments |
| `PUT` | `/api/activities/{id}` | Bearer | Update metadata |
| `DELETE` | `/api/activities/{id}` | Bearer | Delete activity + GPX file |
| `POST` | `/api/activities/{id}/recalculate` | Bearer | Recalculate stats from raw GPX |
| `GET` | `/api/activities/{id}/track` | Bearer | Raw track points (map + elevation profile) |
| `GET` | `/api/stats` | Bearer | Progression data for charts |

**Example — `/api/stats`**

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
