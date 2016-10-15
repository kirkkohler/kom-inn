<?php

use Symfony\Component\HttpFoundation\Request;

$app->post('/api/match', function(Request $request) use ($app, $types) {
    $r = $request->request;
    $data = [
        'guest_id' => $r->get('guest_id'),
        'host_id'  => $r->get('host_id'),
        'comment'  => $r->get('comment'),
        'updated'  => new DateTime('now'),
        'created'  => new DateTime('now')
    ];
    $result = $app['db']->insert('matches', $data, $types);
    if (!$result) {
        return $app->json(['result' => false]);
    }
    return $app->json(['result' => true]);
});

$app->get('/api/matches', function(Request $request) use ($app) {
    $status = 0; // matched
    $sql = "SELECT * FROM matches WHERE status = ?";
    $matches = $app['db']->fetchAll($sql, [(int) $status]);
    foreach ($matches as $k => $match) {

        $sql = "SELECT people.*, hosts.user_id FROM people, hosts WHERE people.id = hosts.user_id AND people.id = ?";
        $matches[$k]['host'] = $app['db']->fetchAssoc($sql, [(int) $match['host_id']]);

        $sql = "SELECT people.*, guests.food_concerns FROM people, guests WHERE people.id = guests.user_id AND people.id = ?";
        $matches[$k]['guest'] = $app['db']->fetchAssoc($sql, [(int) $match['guest_id']]);
    }
    return $app->json($matches);
});
