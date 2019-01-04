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
 * Class Stat.
 */
class Stat
{
    /** @var int $id */
    private $id;

    /** @var int mediaAccountId */
    private $mediaAccountId = 0;
<<<<<<< HEAD

    /** @var \DateTime $dateAdded */
    private $dateAdded;

    /** @var float $spend */
    private $spend = 0;

    /** @var float $cpm */
    private $cpm = 0;

    /** @var float $cpc */
    private $cpc = 0;

    /** @var int $campaignId */
    private $campaignId = 0;

    /** @var int $clicks */
    private $clicks = 0;

    /** @var int $reach */
    private $reach = 0;

    /** @var string */
    private $providerCampaignId = '';

    /** @var string */
    private $providerCampaignName = '';

    /** @var string */
    private $providerAccountId = '';

    /** @var string */
    private $provider = '';

    /** @var string */
    private $providerAccountName = '';

    /** @var float */
    private $cpp = 0;

    /** @var float */
    private $ctr = 0;

    /** @var int */
    private $impressions = 0;

    /** @var string */
    private $providerAdId = '';

    /** @var string */
    private $providerAdName = '';

    /** @var string */
    private $providerAdsetName = '';

    /** @var string */
    private $providerAdsetId = '';

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('media_account_stats')
            ->setCustomRepositoryClass('MauticPlugin\MauticMediaBundle\Entity\StatRepository')
            ->addIndex(['campaign_id', 'date_added'], 'campaign_id_date_added'); // For getting total spend by date.

        $builder->addId();

        $builder->addDateAdded();

        $builder->addNamedField('campaignId', 'integer', 'campaign_id', false);

        $builder->addNamedField('provider', 'string', 'provider', false);

        $builder->addNamedField('mediaAccountId', 'integer', 'media_account_id', true);

        $builder->addNamedField('providerAccountId', 'string', 'provider_account_id', false);

        $builder->addNamedField('providerAccountName', 'string', 'provider_account_name', false);

        $builder->addNamedField('providerCampaignId', 'string', 'provider_campaign_id', false);

        $builder->addNamedField('providerCampaignName', 'string', 'provider_campaign_name', false);

        $builder->addNamedField('providerAdsetId', 'string', 'provider_adset_id', false);

        $builder->addNamedField('providerAdsetName', 'string', 'provider_adset_name', false);

        $builder->addNamedField('providerAdId', 'string', 'provider_ad_id', false);

        $builder->addNamedField('providerAdName', 'string', 'provider_ad_name', false);

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

        $builder->createField('cpp', 'decimal')
            ->precision(19)
            ->scale(4)
            ->build();

        $builder->createField('ctr', 'decimal')
            ->precision(19)
            ->scale(4)
            ->build();

        $builder->addNamedField('impressions', 'integer', 'impressions', false);

        $builder->addNamedField('clicks', 'integer', 'clicks', false);

        $builder->addNamedField('reach', 'integer', 'reach', false);

        // Presume that Ad IDs are unique for all providers, and if not we must make it so.
        $builder->addUniqueConstraint(
            [
                'date_added',
                'provider',
                'media_account_id',
                'provider_ad_id',
            ],
            'unique_by_ad'
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
     * @return Stat
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

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
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @param int $campaignId
     *
     * @return Stat
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    /**
     * @return int
     */
    public function getClicks()
    {
        return $this->clicks;
    }
=======

    /** @var \DateTime $dateAdded */
    private $dateAdded;

    /** @var float $spend */
    private $spend = 0;

    /** @var float $cpm */
    private $cpm = 0;

    /** @var float $cpc */
    private $cpc = 0;
>>>>>>> origin/master

    /**
     * @param int $clicks
     *
     * @return Stat
     */
    public function setClicks($clicks)
    {
        $this->clicks = $clicks;

<<<<<<< HEAD
        return $this;
    }
=======
    /** @var string */
    private $providerCampaignId = '';

    /** @var string */
    private $providerCampaignName = '';

    /** @var string */
    private $providerAccountId = '';

    /** @var string */
    private $provider = '';

    /** @var string */
    private $providerAccountName = '';
>>>>>>> origin/master

    /**
     * @return int
     */
    public function getImpressions()
    {
<<<<<<< HEAD
        return $this->impressions;
    }
=======
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('media_account_stats')
            ->setCustomRepositoryClass('MauticPlugin\MauticMediaBundle\Entity\StatRepository')
            ->addIndex(['campaign_id', 'date_added'], 'campaign_id_date_added'); // For getting total spend by date.
>>>>>>> origin/master

    /**
     * @param int $impressions
     *
     * @return Stat
     */
    public function setImpressions($impressions)
    {
        $this->impressions = $impressions;

<<<<<<< HEAD
        return $this;
    }

    /**
     * @return int
     */
    public function getReach()
    {
        return $this->reach;
    }

    /**
     * @param int $reach
     *
     * @return Stat
     */
    public function setReach($reach)
    {
        $this->reach = $reach;

        return $this;
    }

    /**
     * @return int
     */
    public function getProviderCampaignId()
    {
        return $this->providerCampaignId;
    }

    /**
     * @param int $providerCampaignId
     *
     * @return Stat
     */
    public function setProviderCampaignId($providerCampaignId)
    {
        $this->providerCampaignId = $providerCampaignId;

        return $this;
    }

    /**
     * @return int
     */
    public function getProviderCampaignName()
    {
        return $this->providerCampaignName;
    }

    /**
     * @param int $providerCampaignName
     *
     * @return Stat
     */
    public function setProviderCampaignName($providerCampaignName)
    {
        $this->providerCampaignName = $providerCampaignName;

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
     * @return Stat
     */
    public function setProviderAccountId($providerAccountId)
    {
        $this->providerAccountId = $providerAccountId;
=======
        $builder->addDateAdded();

        $builder->addNamedField('campaignId', 'integer', 'campaign_id', false);

        $builder->addNamedField('provider', 'string', 'provider', false);

        $builder->addNamedField('mediaAccountId', 'integer', 'media_account_id', true);

        $builder->addNamedField('providerCampaignId', 'string', 'provider_campaign_id', false);

        $builder->addNamedField('providerCampaignName', 'string', 'provider_campaign_name', false);

        $builder->addNamedField('providerAccountId', 'string', 'provider_account_id', false);

        $builder->addNamedField('providerAccountName', 'string', 'provider_account_name', false);

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

        $builder->addUniqueConstraint(
            ['date_added', 'provider', 'media_account_id', 'provider_campaign_id'],
            'date_added_provider_account_campaign'
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
>>>>>>> origin/master

        return $this;
    }

    /**
<<<<<<< HEAD
     * @return string
     */
    public function getProviderAccountName()
    {
        return $this->providerAccountName;
    }

    /**
     * @param int $providerAccountName
=======
     * @return float
     */
    public function getCpc()
    {
        return $this->cpc;
    }

    /**
     * @param float $cpc
>>>>>>> origin/master
     *
     * @return $this
     */
<<<<<<< HEAD
    public function setProviderAccountName($providerAccountName)
    {
        $this->providerAccountName = $providerAccountName;
=======
    public function setCpc($cpc)
    {
        $this->cpc = $cpc;
>>>>>>> origin/master

        return $this;
    }

    /**
     * @return string
     */
<<<<<<< HEAD
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
=======
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
>>>>>>> origin/master

        return $this;
    }

    /**
     * @return float
     */
    public function getCpp()
    {
        return $this->cpp;
    }

    /**
     * @param float $cpp
     *
     * @return $this
     */
    public function setCpp($cpp)
    {
        $this->cpp = $cpp;

        return $this;
    }

    /**
     * @return float
     */
<<<<<<< HEAD
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
=======
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
>>>>>>> origin/master

        return $this;
    }

    /**
<<<<<<< HEAD
     * @return float
     */
    public function getProviderAdId()
    {
        return $this->providerAdId;
    }

    /**
     * @param string $providerAdId
=======
     * @return int
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @param int $campaignId
>>>>>>> origin/master
     *
     * @return $this
     */
<<<<<<< HEAD
    public function setProviderAdId($providerAdId)
    {
        $this->providerAdId = $providerAdId;
=======
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
>>>>>>> origin/master

        return $this;
    }

    /**
     * @return float
     */
<<<<<<< HEAD
    public function getProviderAdName()
    {
        return $this->providerAdName;
    }

    /**
     * @param string $providerAdName
=======
    public function getProviderCampaignId()
    {
        return $this->providerCampaignId;
    }

    /**
     * @param int $providerCampaignId
>>>>>>> origin/master
     *
     * @return Stat
     */
<<<<<<< HEAD
    public function setProviderAdName($providerAdName)
    {
        $this->providerAdName = $providerAdName;
=======
    public function setProviderCampaignId($providerCampaignId)
    {
        $this->providerCampaignId = $providerCampaignId;
>>>>>>> origin/master

        return $this;
    }

    /**
     * @return float
     */
<<<<<<< HEAD
    public function getProviderAdsetName()
    {
        return $this->providerAdsetName;
    }

    /**
     * @param string $providerAdsetName
=======
    public function getProviderCampaignName()
    {
        return $this->providerCampaignName;
    }

    /**
     * @param int $providerCampaignName
>>>>>>> origin/master
     *
     * @return $this
     */
<<<<<<< HEAD
    public function setProviderAdsetName($providerAdsetName)
    {
        $this->providerAdsetName = $providerAdsetName;
=======
    public function setProviderCampaignName($providerCampaignName)
    {
        $this->providerCampaignName = $providerCampaignName;
>>>>>>> origin/master

        return $this;
    }

    /**
     * @return float
     */
<<<<<<< HEAD
    public function getProviderAdsetId()
    {
        return $this->providerAdsetId;
    }

    /**
     * @param string $providerAdsetId
=======
    public function getProviderAccountId()
    {
        return $this->providerAccountId;
    }

    /**
     * @param int $providerAccountId
>>>>>>> origin/master
     *
     * @return $this
     */
<<<<<<< HEAD
    public function setProviderAdsetId($providerAdsetId)
    {
        $this->providerAdsetId = $providerAdsetId;
=======
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
     * @return Stat
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
>>>>>>> origin/master

        return $this;
    }


}
