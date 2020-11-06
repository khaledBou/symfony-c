<?php

namespace App\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Injection de la configuration personnalisée.
 */
class AppExtension extends Extension
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws InvalidConfigurationException En cas d'erreur de configuration
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->validateConfiguration($config);

        // Injecte la configuration dans les parameters
        $rootName = 'app';
        $container->setParameter($rootName, $config);
        $this->setConfigAsParameters($container, $config, $rootName);
    }

    /**
     * Injecte la configuration dans les parameters.
     * Permet d'accéder aux valeurs de la configuration
     * comme si elles étaient définies dans les parameters.
     *
     * @param ContainerBuilder $container
     * @param array            $params
     * @param string           $rootName
     *
     * @return void
     */
    private function setConfigAsParameters(ContainerBuilder &$container, array $params, $rootName): void
    {
        foreach ($params as $key => $value) {
            $name = sprintf('%s.%s', $rootName, $key);
            $container->setParameter($name, $value);
            if (is_array($value)) {
                $this->setConfigAsParameters($container, $value, $name);
            }
        }
    }

    /**
     * Valide la configuration.
     *
     * @param array $config
     *
     * @return void
     *
     * @throws InvalidConfigurationException
     */
    private function validateConfiguration(array $config): void
    {
        // Validation de la configuration des réseaux
        foreach ($config['networks'] as $networkId => $networkConfig) {
            // Validation des agendas
            if (!array_key_exists('coach', $networkConfig['calendars']) || !array_key_exists('mandatary', $networkConfig['calendars'])) {
                throw new InvalidConfigurationException(sprintf(
                    'The path "app.networks.%s.calendars" must define both "coach" and "mandatary" keys.',
                    $networkId
                ));
            }
            foreach ($networkConfig['calendars'] as $key => $calendarId) {
                if (!in_array($key, ['coach', 'mandatary'])) {
                    throw new InvalidConfigurationException(sprintf(
                        'The key "%s" configured at path "app.networks.%s.calendars" must be "coach" or "mandatary".',
                        $key,
                        $networkId
                    ));
                }
                if (null !== $calendarId && !isset($config['calendars'][$calendarId])) {
                    throw new InvalidConfigurationException(sprintf(
                        'The calendar "%s" configured at path "app.networks.%s.calendars.%s" does not exist.',
                        $calendarId,
                        $networkId,
                        $key
                    ));
                }
                switch ($calendarId) {
                    case 'zimbra':
                        if (null === $networkConfig['zimbra_api_url']) {
                            throw new InvalidConfigurationException(sprintf(
                                'The Zimbra calendar configured at path "app.networks.%s.calendars.%s" requires the path "app.networks.%s.zimbra_api_url" to be defined.',
                                $networkId,
                                $key,
                                $networkId
                            ));
                        }
                        break;
                }
            }
        }

        // Validation de la configuration des agendas
        foreach ($config['calendars'] as $key => $value) {
            // Validation du helper
            if (!class_exists($value['helper'])) {
                throw new InvalidConfigurationException(sprintf(
                    'The helper "%s" configured at path "app.calendars.%s.helper" does not exist.',
                    $value['helper'],
                    $key
                ));
            }
        }

        // Validation de la configuration commune aux indicateurs et aux événements
        foreach ([
            'indicators',
            'events',
        ] as $what) {
            foreach ($config[$what] as $key => $value) {
                // Validation de l'entité
                if (!class_exists($value['entity'])) {
                    throw new InvalidConfigurationException(sprintf(
                        'The entity "%s" configured at path "app.%s.%s.entity" does not exist.',
                        $value['entity'],
                        $what,
                        $key
                    ));
                }
                // Validation des réseaux
                foreach ($value['networks'] as $networkId) {
                    if (!isset($config['networks'][$networkId])) {
                        throw new InvalidConfigurationException(sprintf(
                            'The network "%s" configured at path "app.%s.%s.networks" is not configured.',
                            $networkId,
                            $what,
                            $key
                        ));
                    }
                }
            }
        }

        // Validation de la configuration des événements
        foreach ($config['events'] as $key => $value) {
            // Validation du FormType
            if (null !== $value['form_type'] && !class_exists($value['form_type'])) {
                throw new InvalidConfigurationException(sprintf(
                    'The form type "%s" configured at path "app.events.%s.form_type" does not exist.',
                    $value['form_type'],
                    $key
                ));
            }
            // Validation des filtres
            foreach ($value['filters'] as $filterId) {
                if (!isset($config['events_filters'][$filterId])) {
                    throw new InvalidConfigurationException(sprintf(
                        'The filter "%s" configured at path "app.events.%s.filters" must be defined at path "app.event_filters".',
                        $filterId,
                        $key
                    ));
                }
            }
        }

        // Validation de la configuration des indicateurs
        foreach ($config['indicators'] as $key => $value) {
            // Validation du FormType
            if (!class_exists($value['form_type'])) {
                throw new InvalidConfigurationException(sprintf(
                    'The form type "%s" configured at path "app.indicators.%s.form_type" does not exist.',
                    $value['form_type'],
                    $key
                ));
            }
        }
    }
}
