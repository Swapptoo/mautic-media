<?php

namespace MauticPlugin\MauticMediaBundle\Report;

use MauticPlugin\MauticMediaBundle\Entity\StatRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;

class CostBreakdownChart
{
    /**
     * @var StatRepository
     */
    private $repo;

    /**
     * Pretty colors ooooooo.
     * @var array
     */
    private $providerColors = [
        'facebook' => [
            'backgroundColor'           => 'rgba(59,89,153 ,1 )',
            'borderColor'               => 'rgba(59,89,153 ,1 )',
            'pointHoverBackgroundColor' => 'rgba(59,89,153 ,1 )',
            'pointHoverBorderColor'     => 'rgba(59,89,153 ,1 )',
        ],
        'snapchat' => [
            'backgroundColor'           => 'rgba(255,252,0 ,1)',
            'borderColor'               => 'rgba(255,255,255 ,1)',
            'pointHoverBackgroundColor' => 'rgba(255,252,0 ,1)',
            'pointHoverBorderColor'     => 'rgba(255,252,0 ,1)',
        ],
        'bing' => [
            'backgroundColor'           => 'rgb(12, 132, 132)',
            'borderColor'               => 'rgb(51, 51, 51)',
            'pointHoverBackgroundColor' => 'rgba(51,170,51,0.75)',
            'pointHoverBorderColor'     => 'rgba(51,170,51,1)',
        ],
        'google' => [
            'backgroundColor'           => 'rgba(51,170,51,0.1)',
            'borderColor'               => 'rgba(51,170,51,0.8)',
            'pointHoverBackgroundColor' => 'rgba(51,170,51,0.75)',
            'pointHoverBorderColor'     => 'rgba(51,170,51,1)',
        ],
    ];

    /** @var EntityManager  */
    private $em;

    /**
     * CostBreakdownReport's constructor.
     * @param StatRepository $statRepository
     * @param EntityManager $em
     */
    public function __construct($statRepository, $em)
    {
        $this->repo = $statRepository;
        $this->em = $em;
    }

    /**
     * @param int $campaignId
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     *
     * @return array
     */
    public function getChart($campaignId, $dateFrom, $dateTo)
    {
        $timeInterval = DatePadder::getTimeUnitFromDateRange($dateFrom, $dateTo);
        $chartQueryHelper = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo, $timeInterval);
        $dbTimeInterval = $chartQueryHelper->translateTimeUnit($timeInterval);
        $report = $this->repo->getProviderCostBreakdown(
                    $campaignId,
                    $dateFrom,
                    $dateTo,
                    $timeInterval,
                    $dbTimeInterval
                );

        // Since the default report has all the providers in one array, we need
        // to group and separate based on the provider so we can properly pad
        // the report.
        $providers = [];
        foreach ($report as $row) {
            if (!isset($providers[$row['provider']])) {
                $providers[$row['provider']] = [];
            }
            $providers[$row['provider']][] = $row;
        }

        foreach ($providers as $key => $provider) {
            $providers[$key] = (new DatePadder($provider, 'date_time', $timeInterval))->pad($dateFrom, $dateTo);
        }

        // We can just pull the first provider to get it's date_time to use to
        // match up the report.
        $labels = array_column(reset($providers), 'date_time');
        $datasets = [];
        foreach ($providers as $name => $provider) {
            // Transform the report to be chart readable.
            $datasets[$name] = [
                'label' => ucfirst($name),
                'data' => array_column($provider, 'spend'),
            ];
            // If they have custom colors set, apply it.
            if (isset($this->providerColors[$row['provider']])) {
                $datasets[$name] = array_merge($datasets[$name], $this->providerColors[$name]);
            }
        }

        $report = [
            'labels' => $labels,
            'datasets' => array_values($datasets),
        ];

        return $report;
    }
}
