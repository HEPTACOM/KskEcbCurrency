<?php

namespace KskEcbCurrency\Services;

use Doctrine\ORM\EntityRepository;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Currency;

class CurrencyImporter
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var string
     */
    private $pluginDir;

    /**
     * CurrencyImporter constructor.
     * @param $pluginDir
     * @param ModelManager $modelManager
     */
    public function __construct($pluginDir, ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
        $this->pluginDir = $pluginDir;
    }

    public function populate()
    {
        /** @var EntityRepository $currencyRepository */
        $currencyRepository = $this->modelManager->getRepository(Currency::class);

        $currencyFile = implode(DIRECTORY_SEPARATOR, [
            $this->pluginDir, 'Resources', 'data', 'currencies.json'
        ]);

        $rawCurrencies = json_decode(file_get_contents($currencyFile), true);

        foreach ($rawCurrencies as $rawCurrency) {
            list($currency, $name, $symbol) = $rawCurrency;

            if (($currencyRepository->findOneBy(['currency' => $currency])) !== null) {
                continue;
            }

            $currencyModel = new Currency();
            $currencyModel->fromArray([
                'currency' => $currency,
                'name' => $name,
                'symbol' => $symbol,
                'factor' => 1,
            ]);

            $this->modelManager->persist($currencyModel);
        }

        $this->modelManager->flush();
    }
}
