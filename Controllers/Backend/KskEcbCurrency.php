<?php declare(strict_types=1);

class Shopware_Controllers_Backend_KskEcbCurrency extends Shopware_Controllers_Backend_ExtJs
{
    public function populateCurrenciesAction()
    {
        $currencyImporter = $this->container->get('ksk_ecb_currency.services.currency_importer');
        $currencyImporter->populate();

        $this->View()->assign([
            'success' => true,
            'data' => [],
        ]);
    }
}
