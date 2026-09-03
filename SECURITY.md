[🇫🇷 Version française](SECURITY.fr.md) | 🇬🇧 English version

---

# Security Policy

## Supported versions

Only the latest release is actively maintained.

| Version | Supported |
|---|---|
| 3.x (latest) | ✅ |
| 2.x | ❌ |
| 1.x | ❌ |

## Reporting a vulnerability

Please **do not** open a public GitHub issue for security vulnerabilities.

Report them privately by opening a [GitHub Security Advisory](https://github.com/MarvinLeRouge/Summit-Stats/security/advisories/new)
or by contacting the maintainer directly via the GitHub profile.

Include as much detail as possible:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (optional)

You can expect an acknowledgement within 7 days.

## Scope

This project is a single-user personal tool. The attack surface is intentionally limited:
- No user registration or public-facing forms
- Authentication via Sanctum Bearer token (single user)
- No third-party integrations beyond OpenTopoData (read-only, optional)
