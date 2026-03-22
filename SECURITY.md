# Security Policy

🇫🇷 [Version française](#version-française)

---

## 🇬🇧 English version

### Supported versions

Only the latest release is actively maintained.

| Version | Supported |
|---|---|
| 2.x (latest) | ✅ |
| 1.x | ❌ |

### Reporting a vulnerability

Please **do not** open a public GitHub issue for security vulnerabilities.

Report them privately by opening a [GitHub Security Advisory](https://github.com/MarvinLeRouge/Summit-Stats/security/advisories/new)
or by contacting the maintainer directly via the GitHub profile.

Include as much detail as possible:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (optional)

You can expect an acknowledgement within 7 days.

### Scope

This project is a single-user personal tool. The attack surface is intentionally limited:
- No user registration or public-facing forms
- Authentication via Sanctum Bearer token (single user)
- No third-party integrations beyond OpenTopoData (read-only, optional)

---

## 🇫🇷 Version française

### Versions supportées

Seule la dernière version est activement maintenue.

| Version | Supportée |
|---|---|
| 2.x (latest) | ✅ |
| 1.x | ❌ |

### Signaler une vulnérabilité

Merci de **ne pas** ouvrir une issue GitHub publique pour signaler une faille de sécurité.

Signalez-la en privé via un [GitHub Security Advisory](https://github.com/MarvinLeRouge/Summit-Stats/security/advisories/new)
ou en contactant le mainteneur directement via le profil GitHub.

Inclure autant de détails que possible :
- Description de la vulnérabilité
- Étapes pour reproduire
- Impact potentiel
- Correction suggérée (optionnel)

Un accusé de réception sera envoyé sous 7 jours.

### Périmètre

Ce projet est un outil personnel mono-utilisateur. La surface d'attaque est intentionnellement limitée :
- Pas d'inscription ni de formulaire exposé publiquement
- Authentification par token Bearer Sanctum (utilisateur unique)
- Aucune intégration tierce en dehors d'OpenTopoData (lecture seule, optionnelle)
