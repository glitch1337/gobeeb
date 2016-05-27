<?php

namespace AppBundle\Controller;

use AppBundle\Model\Feed;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultController
 *
 * Things to occupy myself with while waiting for a plane
 * @package AppBundle\Controller
 */
class DefaultController extends Controller
{
    private $feedUrl = __DIR__ . '../../../../web/feed.xml';

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig', [
            'show_current' => true,
            'show_prev' => true,
            'show_next' => true,
        ]);
    }

    /**
     * @Route("/bbcone/now", name="live_show")
     */
    public function nowAction(Request $request)
    {
        $feed = (new Feed('http://bleb.org/tv/data/rss.php?ch=bbc1&day=0'))->load();
        return $this->render('default/schedule.html.twig', [
            'title' => 'Now Showing',
            'feed' => $feed->getLive(),
            'show_current' => false,
            'show_prev' => true,
            'show_next' => true,
        ]);
    }

    /**
     * @Route("/bbcone/previous", name="prev_show")
     */
    public function previousAction(Request $request)
    {
        $feed = (new Feed('http://bleb.org/tv/data/rss.php?ch=bbc1&day=0'))->load();
        return $this->render('default/schedule.html.twig', [
            'title' => 'Previously',
            'feed' => $feed->getPrevious(),
            'show_current' => true,
            'show_prev' => false,
            'show_next' => true,
        ]);
    }

    /**
     * @Route("/bbcone/next", name="next_show")
     */
    public function nextAction(Request $request)
    {
        $feed = (new Feed('http://bleb.org/tv/data/rss.php?ch=bbc1&day=0'))->load();
        return $this->render('default/schedule.html.twig', [
            'title' => 'Up Next',
            'feed' => $feed->getNext(),
            'show_current' => true,
            'show_prev' => true,
            'show_next' => false,
        ]);
    }
}
