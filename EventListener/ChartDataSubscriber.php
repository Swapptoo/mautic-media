<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMediaBundle\EventListener;

use DateInterval;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use MauticPlugin\MauticContactLedgerBundle\Event\ChartDataAlterEvent;
use MauticPlugin\MauticContactLedgerBundle\MauticContactLedgerEvents;
use MauticPlugin\MauticMediaBundle\Model\MediaAccountModel;

/**
 * Class ChartDataSubscriber.
 */
class ChartDataSubscriber extends CommonSubscriber
{
    /**
     * @var MediaAccountModel
     */
    protected $model;

    /**
     * ChartDataSubscribe constructor.
     *
     * @param MediaAccountModel $model
     */
    public function __construct(MediaAccountModel $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [MauticContactLedgerEvents::CHART_DATA_ALTER => ['onChartDataAlter', 0]];
    }

    /**
     * @param ChartDataAlterEvent $event
     */
    public function onChartDataAlter(ChartDataAlterEvent $event)
    {
        switch ($event->getChartName()) {
            case 'campaign.revenue.chart':
                $this->modifyCampaignrevenueChart($event);
                break;

            case 'campaign.revenue.datatable':
                //do nothing right now
                break;
        }
    }

    /**
     * @param $event
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function modifyCampaignRevenueChart($event)
    {
        $params     = $event->getParams();
        $campaignId = $params['campaign']->getId();
        $from       = $params['dateFrom'];
        $to         = $params['dateTo'];

        // fix single day date ranges
        if ($from == $to) {
            $to->modify('+1 day - 1 second');
        }

        $data       = $event->getData();

        $statRepo  = $this->model->getStatRepository();
        $spendData = $statRepo->getCampaignSpend(
            $campaignId,
            $from,
            $to,
            ['dbunit' => $params['dbunit'], 'unit' => $params['unit']]
        );
        if (!empty($spendData)) {
            $mergedData = $this->mergeSpendData(
                $data,
                $spendData,
                ['from' => $from, 'to' => $to, 'dbunit' => $params['dbunit'], 'unit' => $params['unit']]
            );
            $event->setData($mergedData);
        }
    }

    /**
     * @param array $data
     * @param array $spendData
     */
    private function mergeSpendData($data, $spendData, $args)
    {
        $intervalMap = [
            'H' => ['hour', 'Y-m-d H:00'],
            'd' => ['day', 'Y-m-d'],
            'W' => ['week', 'Y \w\e\e\k W'],
            'Y' => ['year', 'Y'],
            'm' => ['minute', 'Y-m-d H:i'],
            's' => ['second', 'Y-m-d H:i:s'],
        ];

        $interval      = DateInterval::createFromDateString('1 '.$intervalMap[$args['unit']][0]);
        $periods       = new \DatePeriod($args['from'], $interval, $args['to']);
        $updatedData   = [];
        $iteratorCount = 0;
        foreach ($periods as $period) {
            $dateToCheck = $period->format($intervalMap[$args['unit']][1]);
            $dataKey     = array_search($dateToCheck, array_column($data, 'label'));
            $spendKey    = array_search($dateToCheck, array_column($spendData, 'spendDate'));
            if (false !== $dataKey) {
                $updatedData[$iteratorCount] = [
                    'label'   => $dateToCheck,
                    'cost'    => $data[$dataKey]['cost'],
                    'revenue' => $data[$dataKey]['revenue'],
                    'profit'  => $data[$dataKey]['profit'],
                ];
            }

            if (false !== $spendKey) {
                $cost    = false !== $dataKey ? $data[$dataKey]['cost'] + $spendData[$spendKey]['spend'] : $spendData[$spendKey]['spend'];
                $profit  = false !== $dataKey ? $data[$dataKey]['profit'] - $spendData[$spendKey]['spend'] : 0 - $spendData[$spendKey]['spend'];
                $revenue = false !== $dataKey ? $data[$dataKey]['revenue'] : 0;

                $updatedData[$iteratorCount] = [
                    'label'   => $dateToCheck,
                    'cost'    => $cost,
                    'revenue' => $revenue,
                    'profit'  => $profit,
                ];
            }
            if (false !== $dataKey || false !== $spendKey) {
                ++$iteratorCount;
            }
        }

        return $updatedData;
    }
}
