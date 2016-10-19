<?php
/**
 * Beberlei Metrics
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace Beberlei\Metrics\Collector;

use PromPush\Client as PrometheusClient;


/**
 * Sends metrics to Prometheus PushGateway
 *
 * @package Beberlei\Metrics\Collector
 */
class Prometheus implements Collector, GaugeableCollector
{
    /** @var PrometheusClient */
    private $client;

    /** @var array */
    private $data;

    /** @var null|string */
    private $job;

    /** @var array */
    private $groups = [];

    /**
     * Prometheus constructor.
     *
     * @param PrometheusClient $client
     * @param string|null      $job
     * @param array            $groups
     */
    public function __construct($client, $job = null, array $groups = [])
    {
        $this->client = $client;
        $this->job    = $job;
        $this->groups = $groups;
    }

    /**
     * Collects given data
     *
     * @param string   $variable
     * @param mixed    $value
     * @param int|null $time
     */
    protected function pushData($variable, $value, $time = null)
    {
        $this->data[] = sprintf(
            is_float($value) ? "%s %.18f %d\n" : "%s %d %d\n",
            $variable,
            $value,
            $time ?: time()
        );
    }

    /**
     * Updates counter by given amount
     *
     * @param string $variable
     * @param int    $value
     */
    public function measure($variable, $value)
    {
        $this->pushData($variable, $value);
    }

    /**
     * Increments a counter
     *
     * @param string $variable
     */
    public function increment($variable)
    {
        $this->pushData($variable, 1);
    }

    /**
     * Decrements a counter
     *
     * @param string $variable
     */
    public function decrement($variable)
    {
        $this->pushData($variable, -1);
    }

    /**
     * Records a time measurement
     *
     * @param string $variable
     * @param int    $time
     */
    public function timing($variable, $time)
    {
        $this->pushData($variable, $time);
    }

    /**
     * Updates a gauge by an arbitrary amount
     *
     * @param string $variable
     * @param int    $value
     */
    public function gauge($variable, $value)
    {
        $this->pushData($variable, $value);
    }

    /**
     * Send the collected data to adapter backend
     */
    public function flush()
    {
        if (!$this->data) {
            return;
        }

        try {
            $this->client->replace($this->data, $this->job, $this->groups);
        } catch (\Exception $e) {
        }
    }

    /**
     * Removes all entries from backend
     */
    public function remove()
    {
        try {
            $this->client->delete($this->job, $this->groups);
        } catch (\Exception $e) {
        }
    }
}