<?php

namespace KskEcbCurrency;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Enlight_Controller_Request_Request;
use Enlight_Event_EventArgs;
use Exception;
use KskEcbCurrency\Models\UpdateReport;
use KskEcbCurrency\Services\EcbConnector;
use Shopware\Components\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Components\Plugin\DBALConfigReader;

/**
 * Class KskEcbCurrency
 * @package KskEcbCurrency
 */
class KskEcbCurrency extends Plugin
{
    const UPDATE_STRATEGY_LIVE = 'live';

    const UPDATE_STRATEGY_CACHE = 'cache';

    const UPDATE_STRATEGY_CRON = 'cron';

    public function install(InstallContext $context)
    {
        $this->updateModels();
    }

    public function update(UpdateContext $context)
    {
        $this->updateModels();
    }

    private function updateModels()
    {
        /** @var ModelManager $modelManager */
        $modelManager = $this->container->get('models');
        $tool = new SchemaTool($modelManager);

        $schema = [
            $modelManager->getClassMetadata(UpdateReport::class),
        ];

        try {
            $tool->createSchema($schema);
        } catch (ToolsException $exception) {
            try {
                $tool->updateSchema($schema, true);
            } catch (DBALException $exception) {
                /** @var Logger $logger */
                $logger = $this->container->get('pluginlogger');
                $logger->error($exception->getMessage());
            }
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_KskEcbCurrency_UpdateEurofxref' => 'executeCronjob',
            'Enlight_Controller_Front_RouteShutdown' => 'executeLiveUpdate',
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function executeCronjob(Enlight_Event_EventArgs $args)
    {
        if ($this->getUpdateStrategy() !== static::UPDATE_STRATEGY_CRON) {
            return;
        }

        $this->doUpdate();
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function executeLiveUpdate(Enlight_Event_EventArgs $args)
    {
        /** @var Enlight_Controller_Request_Request $request */
        $request = $args->get('request');

        if ($request->getModuleName() !== 'frontend') {
            return;
        }

        if ($this->getUpdateStrategy() !== static::UPDATE_STRATEGY_LIVE) {
            return;
        }

        $this->doUpdate();
    }

    /**
     * @return string
     */
    public function getUpdateStrategy()
    {
        /** @var DBALConfigReader $configReader */
        $configReader = $this->container->get('shopware.plugin.config_reader');
        $config = $configReader->getByPluginName($this->getName());

        if (in_array($config['update_strategy'], [
            static::UPDATE_STRATEGY_LIVE,
            static::UPDATE_STRATEGY_CACHE,
            static::UPDATE_STRATEGY_CRON,
        ])) {
            return $config['update_strategy'];
        } else {
            return static::UPDATE_STRATEGY_LIVE;
        }
    }

    private function doUpdate()
    {
        $updateReport = new UpdateReport();

        try {
            /** @var EcbConnector $connector */
            $connector = $this->container->get('ksk_ecb_currency.services.ecb_connector');
            $connector->fetch()->apply();

            $updateReport->setSuccess(true);
        } catch (Exception $exception) {
            /** @var Logger $logger */
            $logger = $this->container->get('pluginlogger');
            $logger->error($exception->getMessage());
        }

        /** @var ModelManager $modelManager */
        $modelManager = $this->container->get('models');
        $modelManager->persist($updateReport);
        $modelManager->flush($updateReport);
    }
}
