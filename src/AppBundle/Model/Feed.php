<?php
namespace AppBundle\Model;

use GuzzleHttp\Client;

/**
 * Created by PhpStorm.
 * User: Toby Liddicoat
 * Date: 27/05/2016
 */
class Feed
{
    private $position = 0;

    /**
     * [
     *   'start' => @integer,
     *   'end' => @integer,
     *   'duration' => @string,
     *   'title' => @string,
     *   'description' => @string
     * ]
     * @var null|array
     */
    private $schedule = null;

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $current_date;

    /**
     * @var string
     */
    private $titleExpression = '/(?<hour>[0-9]{2})(?<minute>[0-9]{2}) : (?<title>[\w\W]+)/iu';

    /**
     * @return mixed
     */
    public function __construct($source = null)
    {
        $this->source = $source;
        $this->current_date = date('Y-m-d');
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function load()
    {
        if (!isset($this->source))
            throw new \Exception('Now $source for this feed has been configured');

        if (!isset($this->schedule)) {
            $this->schedule = [];

            $client = new Client();
            $response = $client->request('GET', $this->source, []);

            if ($response->getHeaderLine('content-type') !== 'application/rss+xml')
                throw new \Exception('$source is not an RSS feed');

            $doc = new \DOMDocument();
            $doc->loadXML($response->getBody());
            $xpath = new \DOMXPath($doc);
            $items = $xpath->query('/rss/channel/item');

            for ($index = 0; $index < $items->length; $index++) {
                $next = $index < $items->length ? $items->item($index + 1) : null;
                $this->parseItem($items->item($index), $next);
            }
        }

        return $this;
    }

    /**
     * @param \DOMElement $item
     * @param \DOMElement|null $next
     */
    protected function parseItem(\DOMElement $item, \DOMElement $next = null)
    {
        $start = null;
        $end = null;
        $duration = null;
        $title = null;
        $description = null;

        // Match up current item
        if ($item instanceof \DOMElement) {
            if (preg_match($this->titleExpression, $item->getElementsByTagName('title')->item(0)->nodeValue, $currentItem)) {
                $start = $this->current_date . ' ' . $currentItem['hour'] . ':' . $currentItem['minute'];
                $title = $currentItem['title'];
                $description = $item->getElementsByTagName('description')->item(0)->nodeValue;

                // Match up next item
                if ($next instanceof \DOMElement) {
                    if (preg_match($this->titleExpression, $next->getElementsByTagName('title')->item(0)->nodeValue, $nextItem)) {

                        if (($nextItem['hour'] . $nextItem['minute']) < ($currentItem['hour'] . $currentItem['minute'])) {
                            $this->current_date = date('Y-m-d', strtotime($this->current_date . ' +1 DAY'));
                        }

                        $end = $this->current_date . ' ' . $nextItem['hour'] . ':' . $nextItem['minute'];

                        $startTime = new \DateTime($start);
                        $endTime = new \DateTime($end);
                        $diff = $startTime->diff($endTime, true);

                        if ($diff->h > 0) {
                            $duration = $diff->format('%hhrs %imins');
                        } else {
                            $duration = $diff->format('%imins');
                        }
                    }
                }
            }
        }

        $start = strtotime($start);
        $end = strtotime($end);
        $now = time();

        array_push($this->schedule, (object)[
            'start' => $start,
            'end' => $end,
            'duration' => $duration,
            'title' => $title,
            'description' => $description,
            'durationInMins' => (($end - $start) / 60) . 'mins',
            'refresh' => $end - time(),
            'live' => ($now >= $start && $now <= $end)
        ]);
    }

    /**
     * @return mixed
     */
    public function getLive()
    {
        $scheduleSize = count($this->schedule);
        for ($index = 0; $index < $scheduleSize; $index++) {
            if($this->schedule[$index]->live) {
                $this->position = $index;
                return $this->schedule[$index];
            }
        }
    }

    /**
     * @return mixed
     */
    public function getPrevious()
    {
        $this->getLive();
        if ($this->position > 0) {
            return $this->schedule[--$this->position];
        }
    }

    /**
     * @return mixed
     */
    public function getNext()
    {
        $this->getLive();
        $scheduleSize = count($this->schedule);
        if ($this->position < $scheduleSize) {
            return $this->schedule[++$this->position];
        }
    }

}