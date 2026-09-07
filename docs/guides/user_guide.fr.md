🇫🇷 Version française | [🇬🇧 English version](user_guide.md)

---

# Guide utilisateur

Ce guide couvre l'utilisation de Summit Stats pour importer des traces GPX
de trail et de randonnée, et suivre votre progression dans le temps.

## Se connecter

Summit Stats est un outil mono-utilisateur (ou petit groupe) : authentifiez-
vous avec le mot de passe configuré pour votre compte afin d'obtenir un
token de session. Si vous êtes redirigé vers `/login`, c'est que votre token
a expiré ou n'est pas encore défini.

## Importer une activité

1. Depuis le tableau de bord, glissez-déposez (ou sélectionnez) un fichier
   GPX. Les fichiers exportés depuis Komoot, ou toute autre source GPX,
   sont pris en charge, y compris les traces C:Geo qui peuvent manquer de
   données d'altitude.
2. Si le fichier manque de données d'altitude, Summit Stats les enrichit
   automatiquement via l'API OpenTopoData avant l'analyse ; la progression
   de l'import est affichée en temps réel.
3. Une fois importée, l'activité est analysée automatiquement : la trace
   est segmentée par type de terrain (montée / plat / descente) et par
   classe de pente (5 catégories, du plat à l'extrême), et 22 statistiques
   sont calculées et stockées.
4. Vous pouvez modifier les métadonnées de l'activité (titre, type,
   environnement) à tout moment depuis sa page de détail.

## Comprendre vos statistiques

Chaque activité stocke 22 métriques, notamment :

- Vitesse totale et vitesse en mouvement (les pauses de plus de 30 secondes
  sont exclues du temps en mouvement)
- Vitesse d'ascension : moyenne, jusqu'au sommet, et sur le plus long
  segment non descendant
- Taux de descente
- La répartition en pourcentage de la montée et de la descente par classe
  de pente

Si vous avez besoin de recalculer les statistiques d'une activité (après un
changement de configuration par exemple), utilisez le bouton de recalcul sur
sa page de détail, ou lancez `php artisan stats:recalculate` pour recalculer
toutes les activités stockées d'un coup.

## Explorer une activité

La page de détail d'une activité affiche côte à côte le profil d'élévation
et une carte OSM interactive de la trace. Survoler un point du profil
d'élévation met en évidence le point correspondant sur la carte, et
inversement, pour repérer précisément où une pente ou une allure donnée a
eu lieu sur le parcours.

## Suivre sa progression

Les graphiques de progression du tableau de bord vous permettent de
composer votre propre vue de vos données :

- Choisissez la **métrique** à suivre (par ex. la vitesse d'ascension
  moyenne).
- Filtrez par **type d'activité**, **environnement**, **période**, et
  **plage de pente** (de/à), en combinant autant de filtres que nécessaire.
- Les graphiques se recalculent à la volée à chaque changement de filtre,
  pour répondre rapidement à des questions comme « suis-je plus rapide en
  trail qu'en randonnée sur des pentes modérées ? » sans avoir à exporter
  les données ailleurs.

## Historique des activités

La liste des activités est paginée et filtrable de la même façon que le
tableau de bord de progression, avec des statistiques résumées par activité
pour parcourir votre historique d'un coup d'œil avant d'en ouvrir une en
particulier.
