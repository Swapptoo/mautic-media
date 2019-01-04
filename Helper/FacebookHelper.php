<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMediaBundle\Helper;

use Doctrine\ORM\EntityManager;
use FacebookAds\Api;
use FacebookAds\Cursor;
use FacebookAds\Http\Exception\AuthorizationException;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdAccountUser;
use FacebookAds\Object\User;
use FacebookAds\Object\Values\ReachFrequencyPredictionStatuses;
use MauticPlugin\MauticMediaBundle\Entity\MediaAccount;
use MauticPlugin\MauticMediaBundle\Entity\Stat;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class FacebookHelper.
 *
 * https://developers.facebook.com/docs/marketing-api/sdks/#install-facebook-sdks
 */
class FacebookHelper
{
    /** @var int Number of rate limit errors after which we abort. */
    public static $rateLimitMaxErrors = 60;

    /** @var int Number of seconds to sleep between looping API operations. */
    public static $betweenOpSleep = .2;

    /** @var int Number of seconds to sleep when we hit API rate limits. */
    public static $rateLimitSleep = 60;

    /** @var Api */
    private $client;

    /** @var User */
    private $user;

    /** @var string */
    private $providerAccountId;

    /** @var string */
    private $mediaAccountId;

    /** @var OutputInterface */
    private $output;

    /** @var array */
    private $errors = [];

    /** @var EntityManager */
    private $em;

    /** @var array */
    private $stats = [];

    /**
     * FacebookHelper constructor.
     *
     * @param                 $mediaAccountId
     * @param                 $providerAccountId
     * @param                 $providerClientId
     * @param                 $providerClientSecret
     * @param                 $providerToken
     * @param OutputInterface $output
     */
    public function __construct(
        $mediaAccountId,
        $providerAccountId,
        $providerClientId,
        $providerClientSecret,
        $providerToken,
        OutputInterface $output,
        EntityManager $em
    ) {
        $this->mediaAccountId    = $mediaAccountId;
        $this->providerAccountId = $providerAccountId;
        $this->output            = $output;
        $this->em                = $em;

        Api::init($providerClientId, $providerClientSecret, $providerToken);
        $this->client = Api::instance();

        // $this->client->setLogger(new \FacebookAds\Logger\CurlLogger());
        Cursor::setDefaultUseImplicitFetch(true);
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     *
     * @return array
     *
     * @throws \Exception
     */
    public function pullData(\DateTime $dateFrom, \DateTime $dateTo)
    {
        try {
            $this->authenticate();
            $accounts = $this->getActiveAccounts($dateFrom, $dateTo);
            if (!$accounts) {
                return $this->stats;
            }

            // Using the active accounts, go backwards through time one day at a time to pull hourly data.
            $date   = clone $dateTo;
            $oneDay = new \DateInterval('P1D');
            while ($date >= $dateFrom) {
                /** @var AdAccount $account */
                foreach ($accounts as $account) {
                    $spend    = 0;
                    $self     = $account->getData();
                    $timezone = new \DateTimeZone($self['timezone_name']);
                    $since    = clone $date;
                    $until    = clone $date;
                    $this->output->write(
                        MediaAccount::PROVIDER_FACEBOOK.' - Pulling hourly data - '.
                        $since->format('Y-m-d').' - '.
                        $self['name']
                    );
                    $since->setTimeZone($timezone);
                    $until->setTimeZone($timezone)->add($oneDay);

                    // Specify the time_range in the relative timezone of the Ad account to make sure we get back the data we need.
                    $fields = [
                        'ad_id',
                        'ad_name',
                        'adset_id',
                        'adset_name',
                        'campaign_id',
                        'campaign_name',
                        'spend',
                        'cpm',
                        'cpc',
                        'cpp', // Always null at ad level?
                        'ctr',
                        'impressions',
                        'clicks',
                        'reach', // Always null at ad level?
                    ];
                    $params = [
                        'level'      => 'ad',
                        // 'filtering'  => [
                        //     [
                        //         'field'    => 'spend',
                        //         'operator' => 'GREATER_THAN',
                        //         'value'    => '0',
                        //     ],
                        // ],
                        'breakdowns' => [
                            'hourly_stats_aggregated_by_advertiser_time_zone',
                        ],
                        'time_range' => [
                            'since' => $since->format('Y-m-d'),
                            'until' => $until->format('Y-m-d'),
                        ],
                    ];
                    $this->getInsights(
                        $account,
                        $fields,
                        $params,
                        function ($data) use (&$spend, $timezone, $self) {
                            // Convert the date to our standard.
                            $time = substr($data['hourly_stats_aggregated_by_advertiser_time_zone'], 0, 8);
                            $date = \DateTime::createFromFormat(
                                'Y-m-d H:i:s',
                                $data['date_start'].' '.$time,
                                $timezone
                            );
                            $stat = new Stat();
                            $stat->setMediaAccountId($this->mediaAccountId);

                            $stat->setDateAdded($date);

                            // @todo - To be mapped based on settings of the Media Account.
                            // $stat->setCampaignId(0);

                            $provider = MediaAccount::PROVIDER_FACEBOOK;
                            $stat->setProvider($provider);

                            $stat->setProviderAccountId($self['id']);
                            $stat->setproviderAccountName($self['name']);

                            $stat->setProviderCampaignId($data['campaign_id']);
                            $stat->setProviderCampaignName($data['campaign_name']);

                            $stat->setProviderAdsetId($data['adset_id']);
                            $stat->setproviderAdsetName($data['ad_name']);

                            $stat->setProviderAdId($data['ad_id']);
                            $stat->setproviderAdName($data['ad_name']);

                            $stat->setCurrency($self['currency']);
                            $stat->setSpend(floatval($data['spend']));
                            $stat->setCpm(floatval($data['cpm']));
                            $stat->setCpc(floatval($data['cpc']));
                            $stat->setCpp(floatval($data['cpp']));
                            $stat->setCtr(floatval($data['ctr']));
                            $stat->setImpressions(intval($data['impressions']));
                            $stat->setClicks(intval($data['clicks']));
                            $stat->setReach(intval($data['reach']));

                            // Don't bother saving stat records without valuable data.
                            if (
                                $stat->getSpend()
                                || $stat->getCpm()
                                || $stat->getCpc()
                                || $stat->getCpp()
                                || $stat->getCtr()
                                || $stat->getImpressions()
                                || $stat->getClicks()
                                || $stat->getReach()
                            ) {
                                // Uniqueness to match the unique_by_ad constraint.
                                $key               = implode(
                                    '|',
                                    [
                                        $date->getTimestamp(),
                                        $provider,
                                        $this->mediaAccountId,
                                        $data['ad_id'],
                                    ]
                                );
                                $this->stats[$key] = $stat;
                                if (0 === count($this->stats) % 100) {
                                    $this->saveQueue();
                                }
                                $spend += $data['spend'];
                            }
                        }
                    );
                    $this->output->writeln(' - '.$self['currency'].' '.$spend);
                }
                $date->sub($oneDay);
            }
        } catch (\Exception $e) {
            $this->output->writeln('<error>'.MediaAccount::PROVIDER_FACEBOOK.' - '.$e->getMessage().'</error>');
        }
        $this->saveQueue();

        return $this->stats;
    }

    /**
     * @throws \Exception
     */
    private function authenticate()
    {
        // Authenticate and get the primary user ID.
        $me = $this->client->call('/me')->getContent();
        if (!$me || !isset($me['id'])) {
            throw new \Exception(
                'Cannot discern Facebook user for account '.$this->providerAccountId.'. You likely need to reauthenticate.'
            );
        }
        $this->output->writeln('Logged in to Facebook as '.strip_tags($me['name']));
        $this->user = new AdAccountUser($me['id']);
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getActiveAccounts(\DateTime $dateFrom, \DateTime $dateTo)
    {
        // Find Ad accounts this user has access to with activity in this date range to reduce overall API call count.

        $accounts = [];
        /* @var AdAccount $account */
        $this->getAdAccounts(
            function ($account) use (&$accounts, $dateTo, $dateFrom) {
                $spend  = 0;
                $self   = $account->getData();
                $fields = [
                    'campaign_id',
                    'campaign_name',
                    'spend',
                ];
                $params = [
                    'level'     => 'account',
                    'filtering' => [
                        [
                            'field'    => 'spend',
                            'operator' => 'GREATER_THAN',
                            'value'    => '0',
                        ],
                    ],
                ];
                $this->output->write(
                    MediaAccount::PROVIDER_FACEBOOK.' - Checking for activity - '.
                    $dateFrom->format('Y-m-d').' ~ '.$dateTo->format('Y-m-d').' - '.
                    $self['name']
                );
                $timezone = new \DateTimeZone($self['timezone_name']);
                $since    = clone $dateFrom;
                $until    = clone $dateTo;
                $since->setTimeZone($timezone);
                $until->setTimeZone($timezone);
                // Specify the time_range in the relative timezone of the Ad account to make sure we get back the data we need.
                $params['time_range'] = [
                    'since' => $since->format('Y-m-d'),
                    'until' => $until->format('Y-m-d'),
                ];
                $this->getInsights(
                    $account,
                    $fields,
                    $params,
                    function ($data) use (&$spend, $account, &$accounts) {
                        $spend += $data['spend'];
                        if ($spend) {
                            $accounts[] = $account;

                            return true;
                        }
                    }
                );
                $this->output->writeln(' - '.$self['currency'].' '.$spend);
            }
        );
        $this->output->writeln(
            MediaAccount::PROVIDER_FACEBOOK.' - Found '.count($accounts).' accounts active during this time frame.'
        );

        return $accounts;
    }

    /**
     * @param $callback
     *
     * @throws \Exception
     */
    private function getAdAccounts($callback)
    {
        $code   = null;
        $fields = [
            'id',
            'timezone_name',
            'name',
            'currency',
        ];
        $params = [];

        do {
            try {
                $code = null;
                foreach ($this->user->getAdAccounts($fields, $params) as $account) {
                    if (is_callable($callback)) {
                        if ($callback($account)) {
                            break;
                        }
                    }
                    sleep(self::$betweenOpSleep);
                }
            } catch (AuthorizationException $e) {
                $this->errors[] = $e->getMessage();
                $code           = $e->getCode();
                if (count($this->errors) > self::$rateLimitMaxErrors) {
                    throw new \Exception('Too many request errors.');
                }
                if (ReachFrequencyPredictionStatuses::MINIMUM_REACH_NOT_AVAILABLE === $code) {
                    $this->output->write('⌛');
                    sleep(self::$rateLimitSleep);
                }
            }
        } while (ReachFrequencyPredictionStatuses::MINIMUM_REACH_NOT_AVAILABLE === $code);
    }

    /**
     * @param $account
     * @param $fields
     * @param $params
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getInsights(AdAccount $account, $fields, $params, $callback)
    {
        $code = null;

        do {
            try {
                $code = null;

                /** @var \FacebookAds\Cursor $cursor */
                $cursor = $account->getInsights($fields, $params);
                $cursor->setUseImplicitFetch(true);
                $cursor->end();

                // Iterate through insights in reverse order so that we always prioritize new data above old.
                while ($cursor->valid()) {
                    $data = $cursor->current()->getData();
                    if (is_callable($callback)) {
                        if ($callback($data)) {
                            break;
                        }
                    }
                    // $this->output->write('.');
                    sleep(self::$betweenOpSleep);
                    $cursor->prev();
                }
            } catch (AuthorizationException $e) {
                $this->errors[] = $e->getMessage();
                $code           = $e->getCode();
                if (count($this->errors) > self::$rateLimitMaxErrors) {
                    throw new \Exception('Too many request errors.');
                }
                if (ReachFrequencyPredictionStatuses::MINIMUM_REACH_NOT_AVAILABLE === $code) {
                    $this->output->write('.');
                    $this->saveQueue();
                    sleep(self::$rateLimitSleep);
                }
            }
        } while (ReachFrequencyPredictionStatuses::MINIMUM_REACH_NOT_AVAILABLE === $code);
    }

    /**
     * Save all the stat entities in queue.
     */
    private function saveQueue()
    {
        if ($this->stats) {
            if (!$this->em->isOpen()) {
                $this->em = $this->em->create(
                    $this->em->getConnection(),
                    $this->em->getConfiguration(),
                    $this->em->getEventManager()
                );
            }
            $this->em->getRepository('MauticMediaBundle:Stat')
                ->saveEntities($this->stats);

            $this->stats = [];
            $this->em->clear(Stat::class);
        }
    }
}
