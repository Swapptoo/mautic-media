<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class Summary.
 */
class Summary
{
    /** @var int $id */
    private $id;

    /** @var int mediaAccountId */
    private $mediaAccountId = 0;

    /** @var \DateTime $dateAdded */
    private $dateAdded;

    /** @var \DateTime $dateModified */
    private $dateModified;

    /** @var float $spend */
    private $spend = 0;

    /** @var float $cpm */
    private $cpm = 0;

    /** @var float $cpc */
    private $cpc = 0;

    /** @var int $clicks */
    private $clicks = 0;

    /** @var string */
    private $providerAccountId = '';

    /** @var string */
    private $provider = '';

    /** @var string */
    private $providerAccountName = '';

    /** @var float */
    private $ctr = 0;

    /** @var int */
    private $impressions = 0;

    /** @var string */
    private $currency = 'USD';

    /** @var bool */
    private $complete = false;

    /** @var bool */
    private $final = false;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('media_account_stats');

        $builder->addId();

        $builder->addDateAdded();

        $builder->addNamedField('dateModified', 'datetime', 'date_modified', false);

        $builder->addNamedField('provider', 'string', 'provider', false);

        $builder->addNamedField('mediaAccountId', 'integer', 'media_account_id', true);

        $builder->addNamedField('providerAccountId', 'string', 'provider_account_id', false);

        $builder->addNamedField('providerAccountName', 'string', 'provider_account_name', false);

        $builder->addNamedField('currency', 'string', 'currency', false);

        $builder->createField('spend', 'decimal')
            ->precision(19)
            ->scale(4)
            ->build();

        $builder->createField('cpc', 'decimal')
            ->precision(19)
            ->scale(4)
            ->build();

        $builder->createField('cpm', 'decimal')
            ->precision(19)
            ->scale(4)
            ->build();

        $builder->createField('ctr', 'decimal')
            ->precision(19)
            ->scale(4)
            ->build();

        $builder->addNamedField('impressions', 'integer', 'impressions', false);

        $builder->addNamedField('clicks', 'integer', 'clicks', false);

        $builder->addNamedField('complete', 'boolean', 'complete', false);

        $builder->addNamedField('final', 'boolean', 'final', false);

        $builder->addUniqueConstraint(
            [
                'date_added',
                'provider',
                'provider_account_id',
            ],
            'unique_by_account'
        );

        $builder->addIndex(
            [
                'date_added',
                'provider',
                'media_account_id',
            ],
            'campaign_mapping'
        );
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getCpm()
    {
        return $this->cpm;
    }

    /**
     * @param float $cpm
     *
     * @return $this
     */
    public function setCpm($cpm)
    {
        $this->cpm = $cpm;

        return $this;
    }

    /**
     * @return float
     */
    public function getCpc()
    {
        return $this->cpc;
    }

    /**
     * @param float $cpc
     *
     * @return $this
     */
    public function setCpc($cpc)
    {
        $this->cpc = $cpc;

        return $this;
    }

    /**
     * @return float
     */
    public function getSpend()
    {
        return $this->spend;
    }

    /**
     * @param float $spend
     *
     * @return $this
     */
    public function setSpend($spend)
    {
        $this->spend = $spend;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     *
     * @return Summary
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateModified()
    {
        return $this->dateModified;
    }

    /**
     * @param mixed $dateModified
     *
     * @return Summary
     */
    public function setDateModified($dateModified)
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * @return int
     */
    public function getMediaAccountId()
    {
        return $this->mediaAccountId;
    }

    /**
     * @param int $mediaAccountId
     *
     * @return $this
     */
    public function setMediaAccountId($mediaAccountId)
    {
        $this->mediaAccountId = $mediaAccountId;

        return $this;
    }

    /**
     * @return int
     */
    public function getClicks()
    {
        return $this->clicks;
    }

    /**
     * @param int $clicks
     *
     * @return Summary
     */
    public function setClicks($clicks)
    {
        $this->clicks = $clicks;

        return $this;
    }

    /**
     * @return int
     */
    public function getImpressions()
    {
        return $this->impressions;
    }

    /**
     * @param int $impressions
     *
     * @return Summary
     */
    public function setImpressions($impressions)
    {
        $this->impressions = $impressions;

        return $this;
    }

    /**
     * @return int
     */
    public function getProviderAccountId()
    {
        return $this->providerAccountId;
    }

    /**
     * @param int $providerAccountId
     *
     * @return Summary
     */
    public function setProviderAccountId($providerAccountId)
    {
        $this->providerAccountId = $providerAccountId;

        return $this;
    }

    /**
     * @return string
     */
    public function getProviderAccountName()
    {
        return $this->providerAccountName;
    }

    /**
     * @param int $providerAccountName
     *
     * @return $this
     */
    public function setProviderAccountName($providerAccountName)
    {
        $this->providerAccountName = $providerAccountName;

        return $this;
    }

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param float $provider
     *
     * @return $this
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @return float
     */
    public function getCtr()
    {
        return $this->ctr;
    }

    /**
     * @param float $ctr
     *
     * @return $this
     */
    public function setCtr($ctr)
    {
        $this->ctr = $ctr;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return bool
     */
    public function getComplete()
    {
        return $this->complete;
    }

    /**
     * @param $complete
     *
     * @return $this
     */
    public function setComplete($complete)
    {
        $this->complete = $complete;

        return $this;
    }

    /**
     * @return bool
     */
    public function getFinal()
    {
        return $this->complete;
    }

    /**
     * @param $final
     *
     * @return $this
     */
    public function setFinal($final)
    {
        $this->final = $final;

        return $this;
    }
}
