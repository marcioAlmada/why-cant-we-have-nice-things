<?php
namespace History\RequestsGatherer;

use History\Entities\Models\Request;
use History\Entities\Models\User;
use Illuminate\Contracts\Cache\Repository;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class RequestsGatherer
{
    /**
     * @var string
     */
    const DOMAIN = 'https://wiki.php.net/';

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * RequestsGatherer constructor.
     *
     * @param Repository $cache
     */
    public function __construct(Repository $cache)
    {
        $this->cache  = $cache;
        $this->output = new NullOutput();
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Get all the requests.
     */
    public function createRequests()
    {
        $crawler  = $this->getPageCrawler(static::DOMAIN.'/rfc');
        $requests = $crawler->filter('li.level1 a.wikilink1');

        $progress = new ProgressBar($this->output, $requests->count());
        $requests->each(function ($request) use ($progress) {
            $progress->advance();

            $link           = static::DOMAIN.$request->attr('href');
            $requestCrawler = $this->getPageCrawler($link);
            $name           = $this->getRequestName($requestCrawler);
            if (!$name) {
                return;
            }

            $request = Request::firstOrCreate([
                'name'      => $name,
                'condition' => trim($this->getVotingConditions($requestCrawler)),
                'link'      => $link,
            ]);

            $this->saveRequestVotes($request, $requestCrawler);
        });

        $progress->finish();
    }

    /**
     * @param \History\Entities\Models\Request $request
     * @param Crawler                          $crawler
     *
     * @return array
     */
    protected function saveRequestVotes(Request $request, Crawler $crawler)
    {
        $votes = [];

        $crawler
            ->filter('table.inline tr')
            ->reduce(function ($vote) {
                return $vote->filter('td.rightalign a')->count() > 0;
            })->each(function ($vote) use ($request, $votes) {
                $user  = $vote->filter('td.rightalign a')->text();
                $voted = $vote->filter('td:nth-child(2) img')->count() ? true : false;

                // Create user
                $user = User::firstOrCreate([
                    'name' => $user,
                ]);

                // Save vote for this request
                $votes[] = [
                    'request_id' => $request->id,
                    'user_id'    => $user->id,
                    'vote'       => $voted,
                ];
            });

        $request->votes()->saveMany($votes);
    }

    //////////////////////////////////////////////////////////////////////
    ////////////////////////////// HELPERS ///////////////////////////////
    //////////////////////////////////////////////////////////////////////

    /**
     * Extract the name of a request.
     *
     * @param Crawler $crawler
     *
     * @return string
     */
    protected function getRequestName(Crawler $crawler)
    {
        $title = $crawler->filter('h1');
        if (!$title->count()) {
            return;
        }

        // Remove some tags from title
        $title = $title->text();
        $title = str_replace('PHP RFC:', '', $title);
        $title = str_replace('RFC:', '', $title);
        $title = str_replace('Request for Comments:', '', $title);

        return trim($title);
    }

    // Get voting conditions
    /**
     * @param $requestCrawler
     *
     * @return mixed
     */
    protected function getVotingConditions(Crawler $requestCrawler)
    {
        $condition = $requestCrawler->filter('#proposed_voting_choices + div');
        if ($condition->count()) {
            return $condition->text();
        }

        $condition = $requestCrawler->filter('#vote + div p');
        if ($condition->count()) {
            return $condition->text();
        }
    }

    /**
     * @param string $link
     *
     * @return Crawler
     */
    protected function getPageCrawler($link)
    {
        $contents = $this->cache->rememberForever($link, function () use ($link) {
            return file_get_contents($link);
        });

        return new Crawler($contents);
    }
}
