<?php

namespace KskEcbCurrency\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\XmlParseException;
use KskEcbCurrency\Exceptions\CurrencyNodeNotFoundException;
use KskEcbCurrency\Exceptions\ResourceNotReachableException;
use Shopware\Components\HttpClient\GuzzleFactory;
use Shopware\Components\Logger;
use Shopware\Components\Model\ModelManager;

/**
 * Class EcbConnector
 * @package KskEcbCurrency\Services
 */
class EcbConnector
{
    /**
     *
     */
    const URL_ECB_REFERENCE_RATES = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    /**
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var float[]
     */
    private $currencies = [];

    /**
     * EcbConnector constructor.
     * @param GuzzleFactory $guzzleFactory
     * @param ModelManager $modelManager
     * @param Logger $logger
     */
    public function __construct(GuzzleFactory $guzzleFactory, ModelManager $modelManager, Logger $logger)
    {
        $this->guzzleClient = $guzzleFactory->createClient();
        $this->modelManager = $modelManager;
        $this->logger = $logger;
    }

    /**
     * Populates the internal storage with ecb
     * euro foreign exchange reference rates.
     */
    public function fetch()
    {
        try {
            $xmlSource = $this->guzzleClient->get(static::URL_ECB_REFERENCE_RATES);

            if ($xmlSource->getStatusCode() !== 200) {
                throw new ResourceNotReachableException();
            }

            if (!isset($xmlSource->xml()->Cube->Cube->Cube)) {
                throw new CurrencyNodeNotFoundException();
            }

            foreach($xmlSource->xml()->Cube->Cube->Cube as $eurofxref) {
                $this->currencies[(string) $eurofxref['currency']] = (float) $eurofxref['rate'];
            }
        } catch (ResourceNotReachableException $exception) {
            $this->logger->error($exception->getMessage());
        } catch (XmlParseException $exception) {
            $this->logger->error($exception->getMessage());
        } catch (CurrencyNodeNotFoundException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
