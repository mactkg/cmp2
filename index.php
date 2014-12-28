<?php
require('./vendor/autoload.php');

$app = new \Slim\Slim(array(
            'view' => new Slim\Views\Twig(),
            'templates.path' => 'views',
            'cookies.encrypt' => true,
            'cookies.httponly' => true # it prevent from accessing cookie via javascript
           ));
$app->add(new Slim\Extras\Middleware\CsrfGuard());
$view = $app->view();
$view->setTemplatesDirectory('./views');

require_once 'model.php';
require_once 'helper.php';

session_start();

###############
#  FRONT:top  #
###############

$app->get('/', function () use ($app) {
  $app->render('index.html', array('title' => 'タイトル', 'body' => 'welcome'));
});


##################
#  FRONT:evnets  #
##################

$app->get('/events/:id', function ($id) use ($app) {
  $event = find_event_by_id($id);
  $app->render('event.html', array('event' => $event));
})->conditions(array('id' => '[0-9]+'));


// mapはいろいろなHTTPメソッドを受け取れる
// 最後にvia()でHTTPメソッドを指定する
// 今回は必ずkeyが必要なので１つにまとめてみた
$app->map('/events/:id/edit', function ($id) use ($app) {
  
  // key check
  if(!array_key_exists('key', $app->request->params())) {
    $app->halt(401, 'key required');
  }
  
  $event = find_event_by_id($id);
  if($app->request->params('key') != $event['passkey']) {
    $app->halt(403);
  }
  
  if ($app->request->isGet()) {
    $app->render('event_form.html', array('post_to' => '/events/'.$event['id'].'/edit?key='.$event['passkey'],'event' => $event));
  } else if ($app->request->isPost()) {
    // update event
    $app->redirect('/events/' . $id);
  }
})->via('GET', 'POST')
  ->conditions(array('id' => '[0-9]+'));


$app->map('/events/new', function () use ($app) {
  if ($app->request->isGet()) {
    $app->render('event_form.html', array('post_to' => '/events/new'));
  } else if ($app->request->isPost()) {
    $id = create_event($app->request->params());
    $app->redirect('/events/'.$id);
  }
})->via('GET', 'POST');


#########################
#  FRONT:presentations  #
#########################

$app->get('/presentations/:id', function ($id) use ($app) {
  $app->render('index.html', array('title' => $id, 'body' => 'presentation'));
})->conditions(array('id' => '[0-9]+'));


$app->get('/presentations/:id/edit', function ($id) use ($app) {
  $app->render('index.html', array('title' => $id, 'body' => 'edit presentation'));
})->conditions(array('id' => '[0-9]+'));


$app->get('/presentations/new', function ($id) use ($app) {
  $app->redirect('/presentations' . $id . '/edit');
});


#################
#      API      #
#################

$app->group('/api', function() use ($app) {
  
  #################
  #  API::Events  #
  #################

  $app->get('/events/:id', function ($id) use ($app) {
    $event = find_event_by_id($id);
    
    if ($event) {
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode($event, JSON_UNESCAPED_UNICODE);
    } else {
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(array('error' => 'event is not found'));
    }
  })->conditions(array('id' => '[0-9]+'));
  
  
  $app->get('/events/:id/talks', function ($id) use ($app) {
    $talks = find_talks_by_event_id($id, $app->request->params());
    
    if ($talks) {
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode($talks, JSON_UNESCAPED_UNICODE);
    } else {
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(array('error' => 'event is not found'));
    }
  })->conditions(array('id' => '[0-9]+'));
  
  
  $app->post('/events/:id/edit', function ($id) use ($app) {
    update_event_by_id($id, $array);
    $app->render('index.html', array('title' => 'API', 'body' => 'edit event'));
  })->conditions(array('id' => '[0-9]+'));
  
  
  $app->post('/events/new', function () use ($app) {
    $id = create_event($app->request()->params());
    $event = find_event_by_id($id);
    
    json_responce($app, $event);
  });
  
  
  ########################
  #  API::presentations  #
  ########################
  
  $app->get('/talks/:id', function ($id) use ($app) {
    $talk = find_talk_by_id($id);
    
    if ($talk) {
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode($talk, JSON_UNESCAPED_UNICODE);
    } else {
      $app->response()->status(404);
    }
  })->conditions(array('id' => '[0-9]+'));
  
  
  $app->post('/talks/:id/edit', function ($id) use ($app) {
    update_talk_by_id($id, $array);
    $app->render('index.html', array('title' => 'API', 'body' => 'edit talk'));
  })->conditions(array('id' => '[0-9]+'));
  
  
  $app->post('/talks/new', function () use ($app) {
    $input = json_decode($app->request()->getBody());
    create_talk_with_event_id($event_id, $array);
    $app->render('index.html', array('title' => 'API', 'body' => 'create talk'));
  });
  
});

###########
#  おまけ  #
###########

$app->get('/route', function() use ($app) {
  $app->render('route.html', array());
});

$app->run();
