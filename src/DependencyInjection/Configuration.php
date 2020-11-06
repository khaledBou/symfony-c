<?php

namespace App\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Déclaration de la configuration custom.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('app');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('networks')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('domain')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('keycloak_client_id') // L'identifiant de la configuration Keycloak en référence aux clients paramétrés dans knpu_oauth2_client.yaml
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('login_route') // Route correspondant à la page de connexion
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('redirect_url_after_logout')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('google_analytics_id')
                                ->isRequired()
                            ->end()
                            ->arrayNode('calendars')
                                ->scalarPrototype() // clé ('coach' ou 'mandatary'), valeur (un des agendas configurés)
                                ->end()
                            ->end()
                            ->scalarNode('zimbra_api_url') // URL de l'API Zimbra, pour les réseaux dont les négociateurs utilisent l'agenda Zimbra
                                ->isRequired()
                            ->end()
                            ->scalarNode('freshdesk_url') // URL de Freshdesk
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('calendars')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('helper') // le helper implémentant App\Service\Calendar\CalendarHelperInterface
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('indicators')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name') // nom de l'indicateur
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('entity') // l'entité implémentant App\Entity\Indicator\AbstractIndicator
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('form_type') // FormType
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('fill_method') // méthode de remplissage éventuelle, au sein de App\Command\ImportIndicatorsInterface (pour les indicateurs automatiques)
                                ->isRequired()
                            ->end()
                            ->scalarNode('group') // groupe d'affichage
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('networks') // réseaux pour lequel l'indicateur est disponible
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('events_filters')
                    ->scalarPrototype() // clé (textuelle et arbitraire), valeur (libellé du filtre)
                    ->end()
                ->end()
                ->arrayNode('events')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name') // nom de l'événement
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('entity') // l'entité implémentant App\Entity\Event\AbstractEvent
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('form_type') // FormType éventuel (pour les événements manuels)
                                ->isRequired()
                            ->end()
                            ->scalarNode('create_method') // méthode de création éventuelle, au sein de App\Command\ImportEventsInterface (pour les événements automatiques)
                                ->isRequired()
                            ->end()
                            ->scalarNode('template') // template d'affichage
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('networks') // réseaux pour lequel l'événement est disponible
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('filters') // filtres correspondant à l'événement
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
