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

use FacebookAds\Api;
use FacebookAds\Cursor;
use FacebookAds\Http\Exception\AuthorizationException;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\AdAccountUser;
use FacebookAds\Object\User;
use FacebookAds\Object\Values\ReachFrequencyPredictionStatuses;
use MauticPlugin\MauticMediaBundle\Entity\MediaAccount;
use MauticPlugin\MauticMediaBundle\Entity\Stat;

/**
 * Class FacebookHelper.
 *
 * https://developers.facebook.com/docs/marketing-api/sdks/#install-facebook-sdks
 */
class FacebookHelper extends CommonProviderHelper
{
    /** @var Api */
    private $facebookApi;

    /** @var User */
    private $facebookUser;

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     *
     * @return $this|array
     */
    public function pullData(\DateTime $dateFrom, \DateTime $dateTo)
    {
        Cursor::setDefaultUseImplicitFetch(true);
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
                    // Since we are pulling one day at a time, these can be the same day for this provider.
                    $since->setTimeZone($timezone);
                    $until->setTimeZone($timezone);
                    $this->output->write(
                        MediaAccount::PROVIDER_FACEBOOK.' - Pulling hourly data - '.
                        $since->format('Y-m-d').' - '.
                        $self['name']
                    );

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
                        'ctr',
                        'impressions',
                        'clicks',
                    ];
                    $params = [
                        'level'      => 'ad',
                        'filtering'  => [
                            [
                                'field'    => 'spend',
                                'operator' => 'GREATER_THAN',
                                'value'    => '0',
                            ],
                        ],
                        'breakdowns' => [
                            'hourly_stats_aggregated_by_advertiser_time_zone',
                        ],
                        'sort'       => [
                            // 'date_start_descending',
                            'hourly_stats_aggregated_by_advertiser_time_zone_descending',
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
                            $stat->setMediaAccountId($this->mediaAccount->getId());

                            $stat->setDateAdded($date);

                            $campaignId = $this->campaignSettingsHelper->getAccountCampaignMap(
                                (string) $self['id'],
                                (string) $data['campaign_id'],
                                (string) $self['name'],
                                (string) $data['campaign_name']
                            );
                            if (is_int($campaignId)) {
                                $stat->setCampaignId($campaignId);
                            }

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
                            $stat->setCtr(floatval($data['ctr']));
                            $stat->setImpressions(intval($data['impressions']));
                            $stat->setClicks(intval($data['clicks']));

                            $this->addStatToQueue($stat, $spend);
                        }
                    );
                    $this->output->writeln(' - '.$self['currency'].' '.$spend);
                }
                $date->sub($oneDay);
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->outputErrors(MediaAccount::PROVIDER_FACEBOOK);
        }
        $this->saveQueue();

        return $this;
    }

    /**
     * @throws \Exception
     */
    private function authenticate()
    {
        if (!$this->facebookApi || !$this->facebookUser) {
            // Check mandatory credentials.
            if (!trim($this->providerClientId) || !trim($this->providerClientSecret) || !trim($this->providerToken)) {
                throw new \Exception(
                    'Missing credentials for this media account '.$this->providerAccountId.'.'
                );
            }

            // Configure the client session.
            Api::init($this->providerClientId, $this->providerClientSecret, $this->providerToken);
            $this->facebookApi = Api::instance();
            // $this->facebookApi->setLogger(new \FacebookAds\Logger\CurlLogger());
            Cursor::setDefaultUseImplicitFetch(true);

            // Authenticate and get the primary user ID in the same call.
            $me = $this->facebookApi->call('/me')->getContent();
            if (!$me || !isset($me['id'])) {
                throw new \Exception(
                    'Cannot discern Facebook user for account '.$this->providerAccountId.'. You likely need to reauthenticate.'
                );
            }
            $this->output->writeln('Logged in to Facebook as '.strip_tags($me['name']));
            $this->facebookUser = new AdAccountUser($me['id']);
        }
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
                $spend = 0;
                $self  = $account->getData();
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
                $fields = [
                    'campaign_id',
                    'campaign_name',
                    'spend',
                ];
                $params = [
                    'level'      => 'account',
                    'filtering'  => [
                        [
                            'field'    => 'spend',
                            'operator' => 'GREATER_THAN',
                            'value'    => '0',
                        ],
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
                    function ($data) use (&$spend, $account, &$accounts) {
                        $spend += $data['spend'];
                        if ($spend) {
                            $accounts[] = $account;
                            // I need to see the spend amount, keep spooling for comparison.
                            // return true;
                        }
                    }
                );
                $this->output->writeln(' - '.$self['currency'].' '.$spend);
            }
        );
        $this->output->writeln(
            MediaAccount::PROVIDER_FACEBOOK.' - Found '.count(
                $accounts
            ).' accounts active for media account '.$this->mediaAccount->getId().'.'
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
                foreach ($this->facebookUser->getAdAccounts($fields, $params) as $account) {
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
                    throw new \Exception('Too many request errors. '.$e->getMessage());
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
                foreach ($account->getInsights($fields, $params) as $cursor) {
                    $data = $cursor->getData();
                    if (is_callable($callback)) {
                        if ($callback($data)) {
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
                    $this->output->write('.');
                    $this->saveQueue();
                    sleep(self::$rateLimitSleep);
                }
            }
        } while (ReachFrequencyPredictionStatuses::MINIMUM_REACH_NOT_AVAILABLE === $code);
    }
}
