<?php

namespace crons\tasks;


use app\Mailer;

class PurgeInactive
{

    /** @var \app\Cli */
    protected $app;
    /** @var array */
    protected $counters;

    public function __construct(\app\Cli $app)
    {
        $this->app = $app;
        $counter_keys = ["EMAIL", "DELETE", "PURGE", "ERROR", "TOTAL"];
        $fn = function ($o, $v) { $o[$v] = 0; return $o; };
        $this->counters = array_reduce($counter_keys, $fn, []);
    }

    /**
     *
     */
    public function task() : void
    {
        $app = $this->app;

        // Get all active users that hasnt been updated in the last 6 months
        $sql = "SELECT * FROM people WHERE updated < DATE_ADD(CURDATE(), INTERVAL - 6 MONTH) AND status = 1 ORDER BY id ASC";
        $people = $this->getPeople($sql);
        foreach ($people as $person) {
            try {

                $deleted = $app['dry'] || $this->app['people']->setToExpired($person['id']);
                if ($deleted) {
                    $this->counters['DELETE']++;
                }
                // @TODO Check type is HOST;
                $sent = $app['dry'] || $this->app['mailer']->sendHostExpired($person);
                if ($sent) {
                    $this->counters['EMAIL']++;
                    $this->app->verbose("Sent mail to [{$person['id']}] {$person['name']}");
                }
                $this->app->verbose("Person [{$person['id']}] {$person['name']} - Soft deleted");
            } catch (\app\Exception $e) {
                $app->error("ERROR! " . $e->getMessage());
                $this->counters['ERROR']++;
            } catch (\Exception $e) {
                error_log("Failed to handle person {$person['id']} : " . $e->getMessage());
                $app->verbose(" ");
                $this->counters['ERROR']++;
                $this->counters['TOTAL']++;
                break;
            }
            $app->verbose(" ");
            $this->counters['TOTAL']++;
        }

        // Get all soft deleted users that hasnt been updated in the last year
        $sql = "SELECT * FROM people WHERE updated < DATE_ADD(CURDATE(), INTERVAL - 1 YEAR) AND status = -1 ORDER BY id ASC";
        $people = $this->getPeople($sql);
        foreach ($people as $person) {
            try {
                $purged = $app['dry'] || $this->app['people']->delete($person['id']);
                if ($purged) {
                    $this->counters['PURGE']++;
                }
                $this->app->verbose("Person [{$person['id']}] {$person['name']} - Purged");
            } catch (\app\Exception $e) {
                $app->error("ERROR! " . $e->getMessage());
                $this->counters['ERROR']++;
            } catch (\Exception $e) {
                error_log("Failed to handle person {$person['id']} : " . $e->getMessage());
                $app->verbose(" ");
                $this->counters['ERROR']++;
                $this->counters['TOTAL']++;
                break;
            }
            $app->verbose(" ");
            $this->counters['TOTAL']++;
        }

        $app['logger']->debug(__CLASS__ . " RESULTS: " . http_build_query($this->counters, '', ' | '));
        $this->outputResults();
    }

    private function getPeople($sql) : array
    {
        $app = $this->app;
        if (isset($app['max'])) {
            $limit = (int) $app['max'];
            $sql .= "LIMIT {$limit}";
        }

        $app['logger']->info("SQL [ $sql ] - by [{$app['PHP_AUTH_USER']}]");
        $app->verbose("SQL [ $sql ] - by [{$app['PHP_AUTH_USER'] }]", "");
        $people = $app['db']->fetchAll($sql);
        $total = count($people);
        $app->verbose("Found {$total} active people", "");
        return $people;
    }

    protected function outputResults() : void
    {
        $app = $this->app;
        $app->verbose(" ", " Handled: " . $this->counters['TOTAL']);
        $app->verbose("  Emails: " . $this->counters['EMAIL']);
        $app->verbose("  Deleted: " . $this->counters['DELETE']);
        $app->verbose("  Purged: " . $this->counters['PURGE']);
        if ($this->counters['ERROR'])
            $app->verbose("  Errors: " . $this->counters['ERROR']);
    }
}
