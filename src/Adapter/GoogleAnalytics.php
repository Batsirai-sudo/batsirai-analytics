<?php

namespace Batsirai\Analytics\Adapter;

use Batsirai\Analytics\Adapter;
use Batsirai\Analytics\Event;
use Exception;

/**
 * Class GoogleAnalytics
 *
 * @package Batsirai\Analytics\Adapter
 */
class GoogleAnalytics extends Adapter
{
    /**
     *  Endpoint for Google Analytics
     *  @var string
     */
    public string $endpoint = 'https://www.google-analytics.com/collect';

    /**
     * Tracking ID for Google Analytics
     * @var string
     */
    private string $tid;

    /**
     * A unique identifer for Google Analytics
     * @var string
     */
    private string $cid;

    /**
     * @param string $tid
     * @param string $cid
     * Adapter configuration
     *
     */
    public function __construct(string $tid, string $cid)
    {
        $this->tid = $tid;
        $this->cid = $cid;
    }

    /**
     * Gets the name of the adapter.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Google Analytics';
    }

    /**
     * Creates an Event on the remote analytics platform.
     *
     * @param Event $event
     * @return bool
     * @throws Exception
     */
    public function send(Event $event): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if ($event->getType() !== 'pageview') {
            $event->setProps(array_merge($event->getProps(), ['action' => $event->getType()]));
            $event->setType('event');
        }

        if ($event->getProp('screenWidth') && $event->getProp('screenHeight')) {
            $event->setProps(array_merge($event->getProps(), ['screenResolution' => $event->getProp('screenWidth') . 'x' . $event->getProp('screenHeight')]));
        }

        $query = [
            'ec' => $event->getProp('category'),
            'ea' => $event->getProp('action'),
            'el' => $event->getName(),
            'ev' => $event->getValue(),
            'dh' => parse_url($event->getUrl())['host'],
            'dp' => parse_url($event->getUrl())['path'],
            'dt' => $event->getProp('documentTitle'),
            't' => $event->getType(),
            'uip' => $this->clientIP ?? "",
            'ua' => $this->userAgent ?? "",
            'sr' => $event->getProp('screenResolution'),
            'vp' => $event->getProp('viewportSize'),
            'dr' => $event->getProp('referrer'),
        ];

        if ($event->getProp('account')) {
            $query['cd1'] = $event->getProp('account');
        }

        $query = array_filter($query, static fn($value) => !is_null($value) && $value !== '');

        $result = $this->call('POST', $this->endpoint, [], array_merge([
            'tid' => $this->tid,
            'cid' => $this->cid,
            'v' => 1
        ], $query));

        // Parse Debug data
        if ($this->endpoint === "https://www.google-analytics.com/debug/collect") {
            return json_decode($result, true, 512, JSON_THROW_ON_ERROR)["hitParsingResult"][0]["valid"];
        }

        return true;
    }
}