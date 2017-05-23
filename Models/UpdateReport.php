<?php

namespace KskEcbCurrency\Models;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use KskEcbCurrency\KskEcbCurrency;
use Shopware\Components\Model\ModelEntity;
use Shopware\Kernel;

/**
 * @ORM\Entity(repositoryClass="UpdateReportRepository")
 * @ORM\Table(name="ksk_ecb_currency_update_report")
 */
class UpdateReport extends ModelEntity
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $success;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $timestamp;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $updateStrategy;

    /**
     * UpdateReport constructor.
     */
    public function __construct()
    {
        /** @var Kernel $kernel */
        $kernel = Shopware()->Container()->get('kernel');
        /** @var KskEcbCurrency $bootstrap */
        $bootstrap = $kernel->getPlugins()[substr(strrchr(KskEcbCurrency::class, '\\'), 1)];

        $this->setSuccess(false);
        $this->setTimestamp(new DateTime());
        $this->setUpdateStrategy($bootstrap->getUpdateStrategy());
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess($success)
    {
        $this->success = $success;
    }

    /**
     * @return DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param DateTime $timestamp
     */
    public function setTimestamp(DateTime $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function getUpdateStrategy()
    {
        return $this->updateStrategy;
    }

    /**
     * @param string $updateStrategy
     */
    public function setUpdateStrategy($updateStrategy)
    {
        $this->updateStrategy = $updateStrategy;
    }
}
