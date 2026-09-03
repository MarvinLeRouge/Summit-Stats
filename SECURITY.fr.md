🇫🇷 Version française | [🇬🇧 English version](SECURITY.md)

---

# Politique de sécurité

## Versions supportées

Seule la dernière version est activement maintenue.

| Version | Supportée |
|---|---|
| 3.x (latest) | ✅ |
| 2.x | ❌ |
| 1.x | ❌ |

## Signaler une vulnérabilité

Merci de **ne pas** ouvrir une issue GitHub publique pour signaler une faille de sécurité.

Signalez-la en privé via un [GitHub Security Advisory](https://github.com/MarvinLeRouge/Summit-Stats/security/advisories/new)
ou en contactant le mainteneur directement via le profil GitHub.

Inclure autant de détails que possible :
- Description de la vulnérabilité
- Étapes pour reproduire
- Impact potentiel
- Correction suggérée (optionnel)

Un accusé de réception sera envoyé sous 7 jours.

## Périmètre

Ce projet est un outil personnel mono-utilisateur. La surface d'attaque est intentionnellement limitée :
- Pas d'inscription ni de formulaire exposé publiquement
- Authentification par token Bearer Sanctum (utilisateur unique)
- Aucune intégration tierce en dehors d'OpenTopoData (lecture seule, optionnelle)
