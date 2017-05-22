<?php

namespace KskEcbCurrency;

use Enlight_Event_EventArgs;
use KskEcbCurrency\Services\EcbConnector;
use Shopware\Components\Plugin;

class KskEcbCurrency extends Plugin
{

    public function executeCronjob(Enlight_Event_EventArgs $args)
    {
        /** @var EcbConnector $connector */
        $connector = $this->container->get('ksk_ecb_currency.services.ecb_connector');
        $connector->fetch()->apply();
    }
}
