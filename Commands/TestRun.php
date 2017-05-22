<?php

namespace KskEcbCurrency\Commands;

use KskEcbCurrency\Services\EcbConnector;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestRun extends ShopwareCommand
{
    protected function configure()
    {
        $this->setName('ksk:ecb_currency:test_run');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EcbConnector $connector */
        $connector = $this->container->get('ksk_ecb_currency.services.ecb_connector');
        $connector->fetch()->apply();
    }
}
