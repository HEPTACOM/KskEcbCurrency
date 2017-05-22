<?php

namespace KskEcbCurrency\Services;

use Enlight_Exception;
use GuzzleHttp\ClientInterface;
use Shopware\Components\HttpClient\GuzzleFactory;
use Shopware\Components\Logger;
use SimpleXMLElement;

class EcbConnector
{
    const URL_ECB_REFERENCE_RATES = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    /**
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var float[]
     */
    private $currencies = [];

    public function __construct(GuzzleFactory $guzzleFactory, Logger $logger)
    {
        $this->guzzleClient = $guzzleFactory->createClient();
        $this->logger = $logger;
    }

    public function fetch()
    {
        try {
            $eurofxref = $this->guzzleClient->get(static::URL_ECB_REFERENCE_RATES);

            if ($eurofxref->getStatusCode() !== 200) {
                throw new Enlight_Exception();
            }

            $xmlElement = new SimpleXMLElement($eurofxref->getBody());

            foreach($xmlElement->Cube->Cube->Cube as $rate) {
                $this->currencies[(string) $rate['currency']] = (float) $rate['rate'];
            }
        } catch (Enlight_Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
