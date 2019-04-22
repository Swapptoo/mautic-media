<?php

namespace MauticPlugin\MauticMediaBundle\Report;

/**
 * Pads out dates in a report's result to match up with other charts.
 */
class DatePadder
{
    
    /** @var array  */
    private $intervalMap = [
            'H' => ['hour', 'Y-m-d H:00'],
            'd' => ['day', 'Y-m-d'],
            'W' => ['week', 'Y \w\e\e\k W'],
            'Y' => ['year', 'Y'],
            'm' => ['minute', 'Y-m-d H:i'],
            's' => ['second', 'Y-m-d H:i:s'],
        ];

    /**
     * The report to pad out.
     * @var array
     */
    private $report;

    /**
     * The array key
     * @var string
     */
    private $dateKey;

    /**
     * The time unit used to pad the report.
     * @var string
     */
    private $timeUnit;

    /**
     * @param array $report
     * @param string $dateKey The key in the $report array that contains the
     * @param string $timeUnit
     * dates.
     */
    public function __construct($report, $dateKey, $timeUnit)
    {
        $this->report = $report;
        $this->dateKey = $dateKey;
        $this->timeUnit = $timeUnit;
    }

    /**
     * @param \DateTime $dateFrom
     * $param \DateTime $dateTo
     */
    public function getPaddedResults($dateFrom, $dateTo)
    {
        // Sort and pad-out the results to match the other charts.
        $interval    = \DateInterval::createFromDateString('1 '.$this->intervalMap[$this->timeUnit][0]);
        $periods     = new \DatePeriod($dateFrom, $interval, $dateTo);
        $updatedData = [];
        $filler = reset($this->report);
        foreach ($periods as $period) {
            $dateToCheck   = $period->format($this->intervalMap[$this->timeUnit][1]);
            $dataKey       = array_search($dateToCheck, array_column($this->report, $this->dateKey));

            $updatedData[] = array_merge(
                    (false !== $dataKey) ? $this->report[$dataKey] : $filler,
                    [$this->dateKey => $dateToCheck],
                );
        }

        return $updatedData;
    }
}
