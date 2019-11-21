<?php
ignore_user_abort(true);
session_write_close();

require 'vendor/autoload.php';
require 'CallbackStream.php';
$app = new \Slim\App();

/*$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    $newresp = $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, X-Apptoken')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE,PATCH, OPTIONS');

    return $newresp;
});*/
function cleanDB()
{
    file_put_contents("db.txt", json_encode([]));
}
$app->get('/stresstest', function ($request, $response) {
  return $response->withHeader('Content-Type', 'application/json')
        ->withJson(["Status" => "ok"]);
});
$app->get('/cleardb', function ($request, $response) {
    cleanDB();
});
$app->put('/registerclient', function ($request, $response) {
    $parsedBody = $request->getBody();
    // DBStuff ...

    // now as simple FileVersion
    if (!file_exists('db.txt')) {
        cleanDB();
    }
    $data = json_decode($parsedBody);
    $existingdata = json_decode(file_get_contents('db.txt'));
    $existingdata[] = $data;
    file_put_contents("db.txt", json_encode($existingdata));
    return $response->withHeader('Content-Type', 'application/json')
        ->withJson(["Status" => "ok"]);
});
$app->get('/subscribe', function ($request, $response) {
    try {
        /*

        $lastEventId = intval(isset($_SERVER["HTTP_LAST_EVENT_ID"]) ? $_SERVER["HTTP_LAST_EVENT_ID"] : 0);
        if ($lastEventId === 0) {
        // resume from a previous event
        $lastEventId = floatval(isset($_GET["lastEventId"]) ? $_GET["lastEventId"] : 0);
        }
        */
        if (!file_exists('db.txt')) {
            cleanDB();
        }
        $stream = new CallbackStream(function () {
            // Watchdog | Semaphore ?
            while (true) {
                if (connection_aborted()) {
                    file_put_contents("ng-sse-exit.txt", date('r'));
                    exit();
                } else {
                    //do db stuff
                    $time = date('r');
                    echo "event:registeredclients\n";
                    echo "data:" . file_get_contents('db.txt') . "\n\n";
                    ob_flush();
                    flush();
                    sleep(2); //SLEEPTIME -> can also set by request
                  }
            }
            echo "event:stopped\n";
            echo "data: END-OF-STREAM\n\n"; // Give browser a signal to stop re-opening connection
            ob_get_flush();
            flush();
            sleep(1); // give browser enough time to close connection

        });
        return $response->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withBody($stream);

    } catch (Exception $ex) {
        file_put_contents("error.txt", json_encode($ex));
    }

});//->setOutputBuffering(false)
$app->run();
