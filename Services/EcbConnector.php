<?php declare(strict_types=1);

namespace KskEcbCurrency\Services;

use Doctrine\ORM\EntityRepository;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\XmlParseException;
use KskEcbCurrency\Exceptions\CurrencyNodeNotFoundException;
use KskEcbCurrency\Exceptions\ResourceNotReachableException;
use Shopware\Components\HttpClient\GuzzleFactory;
use Shopware\Components\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Currency;

class EcbConnector
{
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
     * @var array
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var float[]
     */
    private $ecbCurrencies = [];

    public function __construct(
        GuzzleFactory $guzzleFactory,
        ModelManager $modelManager,
        array $config,
        Logger $logger
    ) {
        $this->guzzleClient = $guzzleFactory->createClient();
        $this->modelManager = $modelManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Populates the internal storage with ecb
     * euro foreign exchange reference rates.
     *
     * @return $this
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

            foreach ($xmlSource->xml()->Cube->Cube->Cube as $eurofxref) {
                $this->ecbCurrencies[(string) $eurofxref['currency']] = (float) $eurofxref['rate'];
            }
        } catch (ResourceNotReachableException $exception) {
            $this->logger->error($exception->getMessage());
        } catch (XmlParseException $exception) {
            $this->logger->error($exception->getMessage());
        } catch (CurrencyNodeNotFoundException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $this;
    }

    /**
     * Writes ecb euro foreign exchange reference
     * rates as factor into the currencies that
     * exist in the database. Currencies are
     * matched by their ISO-Code (ISO 4217).
     * If a surcharge is configured, the factors
     * are first multiplied by the surcharge
     * (calculated as percent).
     *
     * @return $this
     */
    public function apply()
    {
        /** @var EntityRepository $repository */
        $repository = $this->modelManager->getRepository(Currency::class);

        /** @var Currency[] $shopCurrencies */
        $shopCurrencies = $repository->findAll();

        foreach ($shopCurrencies as $shopCurrency) {
            if (!array_key_exists($shopCurrency->getCurrency(), $this->ecbCurrencies)) {
                continue;
            }

            $securitySurcharge = $this->config['security_surcharge'];

            $shopCurrency->setFactor(
                $this->ecbCurrencies[$shopCurrency->getCurrency()] * (1 + $securitySurcharge / 100)
            );
        }

        $this->modelManager->flush();

        return $this;
    }
}
