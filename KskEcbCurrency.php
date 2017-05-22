<?php

namespace KskEcbCurrency;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Enlight_Event_EventArgs;
use KskEcbCurrency\Models\InternalStorage;
use KskEcbCurrency\Services\EcbConnector;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;

class KskEcbCurrency extends Plugin
{
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
        /** @var ModelManager $models */
        $models = $this->container->get('models');
        $tool = new SchemaTool($models);

        $schema = [
            $models->getClassMetadata(InternalStorage::class),
        ];

        try {
            $tool->createSchema($schema);
        } catch (ToolsException $exception) {
            try {
                $tool->updateSchema($schema, true);
            } catch (DBALException $exception) {
                // TODO handle exception
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_KskEcbCurrency_UpdateEurofxref' => 'executeCronjob',
        ];
    }

    public function executeCronjob(Enlight_Event_EventArgs $args)
    {
        /** @var EcbConnector $connector */
        $connector = $this->container->get('ksk_ecb_currency.services.ecb_connector');
        $connector->fetch()->apply();
    }
}
