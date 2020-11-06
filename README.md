# Documentation technique de l'application Coaching

Application multi-réseaux de suivi des négociateurs, basée sur le [Symfony Starter Kit](https://gitlab.proprietes-privees.com/team/symfony-starter-kit).

Une documentation utilisateur est disponible dans [`templates/page/doc.html.twig`](templates/page/doc.html.twig).

## Utilisateurs

L'application gère des coachs ([`App\Entity\User\Coaches`](src/Entity/User/Coach.php)) et des négociateurs ([`App\Entity\User\Mandatary`](src/Entity/User/Mandatary.php)), mais seuls les coachs peuvent se connecter.

Les utilisateurs sont importés depuis les API des différents CRM par les commandes suivantes :

* coachs : [`app:user:import:coaches:pp`](src/Command/ImportUsers/ImportProprietesPriveesCoachesCommand.php)
* négociateurs : [`app:user:import:mandataries:pp`](src/Command/ImportUsers/ImportProprietesPriveesMandatariesCommand.php)

où `pp` est l'identifiant du réseau.

Pour importer correctement les données, il convient de jouer ces imports dans l'ordre suivant :

1. import des coachs
2. import des négociateurs

### Ajout d'un import utilisateurs

Créer une commande qui étend [`App\Command\ImportUsers\AbstractImportUsersCommand`](src/Command/ImportUsers/AbstractImportUsersCommand.php).

## Événements et indicateurs

Les négociateurs sont rattachés à des indicateurs ([`App\Entity\Indicator\AbstractIndicator`](src/Entity/Indicator/AbstractIndicator.php)) et des événements ([`App\Entity\Event\AbstractEvent`](src/Entity/Event/AbstractEvent.php)). Les indicateurs et les événements peuvent manuels (saisie via formulaire) ou automatiques (création automatique par import).

Les indicateurs portent des informations propres aux négociateurs et sont uniques pour chacun d'eux : il ne peut exister qu'un indicateur d'un même type par négociateur (un seul "démarches administratives", un seul "planning de prospection fait", un seul "en situation d'impayé", …). Les événements ponctuent la vie du négociateur et ne sont pas nécessairement uniques (un seul "entrée dans le réseau", un ou plusieurs "anniversaire d'entrée dans le réseau", un ou plusieurs "changement de contrat", …).

Les indicateurs et événements automatiques sont importés par les commandes suivantes :

* indicateurs : [`app:indicator:import:pp`](src/Command/ImportIndicators/ImportProprietesPriveesIndicatorsCommand.php)
* événements : [`app:event:import:pp`](src/Command/ImportEvents/ImportProprietesPriveesEventsCommand.php)

où `pp` est l'identifiant du réseau.

Pour importer correctement les données, il convient de jouer ces imports dans l'ordre suivant et après l'import des négociateurs :

1. import des indicateurs
2. import des événements

### Ajout d'un import d'indicateurs ou d'événements

Créer une commande qui étend [`App\Command\ImportIndicators\AbstractImportIndicatorsCommand`](src/Command/ImportIndicators/AbstractImportIndicatorsCommand.php) ou [`App\Command\ImportEvents\AbstractImportEventsCommand`](src/Command/ImportEvents/AbstractImportEventsCommand.php).

## Agendas

À travers les rendez-vous, l'application permet d'interagir avec les agendas des coachs et négociateurs.
Il existe aujourd'hui deux types d'agenda — Google Calendar et Zimbra — mais la configuration permet d'en ajouter de nouveaux (voir plus bas).

L'application n'interagit pas avec les agendas lorsqu'elle s'exécute en mode `dev` (voir variable d'environnement [`APP_ENV`](https://symfony.com/doc/4.1/configuration/environments.html#executing-an-application-in-different-environments)).

### Google Calendar (pour les coachs)

La synchronisation des rendez-vous avec l'agenda Google Calendar nécessite que les coachs soient en permanence authentifiés avec leur compte Google.

L'écouteur [`App\EventListener\GoogleApiTokenListener`](src/EventListener/GoogleApiTokenListener.php) gère l'authentification auprès de Google.

### Zimbra (pour les négociateurs)

La synchronisation des rendez-vous avec l'agenda Zimbra requiert que les mots de passe Zimbra des négociateurs soient stockés en clair par l'application. En effet, l'[API Zimbra](https://github.com/zimbra-api/zimbra-api) nécessite de s'identifier avec l'adresse mail et mot de passe des utilisateurs.

## SMS

Des SMS sont envoyés via l'API Sarbacane.

L'application n'envoie pas de SMS lorsqu'elle s'exécute en mode `dev` (voir variable d'environnement [`APP_ENV`](https://symfony.com/doc/4.1/configuration/environments.html#executing-an-application-in-different-environments)).

## Crontab

Certaines commandes sont destinées à être exécutées à intervalles réguliers par une crontab.
Dans le cas d'une installation sur les serveurs de production Amazon, le fichier [`crontab`](crontab) situé à la racine du projet pourra être utilisé.

[En savoir plus sur le fonctionnement des crontabs sur les serveurs de production Amazon.](https://gitlab.proprietes-privees.com/snippets/4#cronjobs)

## Configuration

La configuration de l'application est faite dans [`config/packages/app.yaml`](config/packages/app.yaml). Ce fichier est le point de départ pour la création de nouveaux réseaux, indicateurs, types d'événements et types d'agendas.

Cette configuration est fortement encadrée par [`App\DependencyInjection\Configuration`](src/DependencyInjection/Configuration.php) et [`App\DependencyInjection\AppExtension`](src/DependencyInjection/AppExtension.php), ce qui permet de paramétrer facilement le fichier en se laissant guider par les nombreuses exceptions de contrôle.

La configuration est documentée dans [`App\DependencyInjection\Configuration`](src/DependencyInjection/Configuration.php).

### Ajout d'un réseau

Se reporter à la documentation du [Symfony Starter Kit](https://gitlab.proprietes-privees.com/team/symfony-starter-kit#cr%C3%A9ation-dun-nouveau-r%C3%A9seau), puis :

* compléter la configuration [`config/packages/app.yaml`](config/packages/app.yaml)
* créer de nouveaux imports (coachs, négociateurs, indicateurs et événements) et les ajouter à la crontab
* ajouter les templates manquants

### Ajout d'un indicateur

Ajouter un indicateur dans le fichier [`config/packages/app.yaml`](config/packages/app.yaml), puis jouer un import d'indicateurs s'il s'agit d'un indicateur automatique.

### Ajout d'un type d'événements

Ajouter un type d'événements dans le fichier [`config/packages/app.yaml`](config/packages/app.yaml), puis jouer un import d'indicateurs s'il s'agit d'un type d'événements automatiques.

### Ajout d'un type d'agenda

Dans le fichier [`config/packages/app.yaml`](config/packages/app.yaml), ajouter un agenda et configurer les réseaux qui l'utiliseront.

Créer un helper de gestion pour cet agenda : c'est un service qui étend la classe abstraite [`App\Service\Calendar\AbstractCalendarHelper`](src/service/calendar/AbstractCalendarHelper.php) tout en implémentant l'interface [`App\Service\Calendar\CalendarHelperInterface`](src/service/calendar/CalendarHelperInterface.php). L'autowirer dans [`App\Service\EventHelper`](src/service/EventHelper.php) via [`config/services.yaml`](config/services.yaml) comme dans cet exemple :

```
App\Service\Calendar\CalendarHelperInterface $googleCalendarHelper: '@App\Service\Calendar\GoogleCalendarHelper'
App\Service\Calendar\CalendarHelperInterface $zimbraCalendarHelper: '@App\Service\Calendar\ZimbraCalendarHelper'
```
