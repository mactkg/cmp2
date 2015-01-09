<?php
require('./vendor/autoload.php');

use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine;

$app = new \Slim\Slim(array(
            'view' => new Slim\Views\Twig(),
            'templates.path' => 'views',
            'cookies.encrypt' => true,
            'cookies.httponly' => true # it prevent from accessing cookie via javascript
           ));
$app->add(new Slim\Extras\Middleware\CsrfGuard());
$view = $app->view();
$view->setTemplatesDirectory('./views');

$engine = new MarkdownEngine\MichelfMarkdownEngine();
$view->parserExtensions = array(
  new MarkdownExtension($engine),
);

require_once 'model.php';
require_once 'service.php';
require_once 'helper.php';

session_start();

###############
#  FRONT:top  #
###############

$app->get('/', function () use ($app) {
  $events = array_reverse(find_events());
  $app->render('index.html', array('events' => $events));
});


##################
#  FRONT:evnets  #
##################

$app->get('/events/:id', function ($id) use ($app) {

  $event = find_event_by_id($id);
  $talks = find_talks_by_event_id($id, array());

  if (!$event) {
    $app->notFound();
  } else {
    $app->render('event.html', array('event' => $event, 'talks' => $talks));
  }

})->conditions(array('id' => '[0-9]+'));


// mapはいろいろなHTTPメソッドを受け取れる
// 最後にvia()でHTTPメソッドを指定する
// 今回は必ずkeyが必要なので１つにまとめてみた
$app->map('/events/:id/edit', function ($id) use ($app) {

  // key check
  if(!array_key_exists('key', $app->request->params())) {
    $app->halt(400, 'key required');
  }

  $event = find_event_by_id($id);
  $params = $app->request->params();
  $pass = $params['key'];
  if (!$event->pass_check($pass)) {
    $app->halt(403);
  }

  if ($app->request->isGet()) {
    $app->render('event_form.html', array('post_to' => $event->edit_url($params), 'event' => $event));
  } else if ($app->request->isPost()) {
    $result = update_event_by_id($id, $app->request->params());

    if($result == -1) {
      $app->flash('error', 'エラーが発生しました。');
    } else {
      $url = $app->request->getUrl().$event->edit_url(array("key" => $event->passkey));
      $app->flash('message', '編集が完了しました。 <a href="'.$url.'">'.$url.'</a> から編集できます。');
    }

    $app->redirect('/events/' . $id);
  }
})->via('GET', 'POST')
  ->conditions(array('id' => '[0-9]+'));


$app->map('/events/new', function () use ($app) {
  if ($app->request->isGet()) {
    $app->render('event_form.html', array('post_to' => '/events/new'));
  } else if ($app->request->isPost()) {
    $params = $app->request->params();
    $id = create_event($app->request->params());

    if($id == -1) {
      $app->flash('error', 'エラーが発生しました。');
      $app->redirect('/');
    } else {
      $event = find_event_by_id($id);
      $url = $app->request->getUrl().$event->edit_url(array("key" => $event->passkey));
      $app->flash('message', '編集が完了しました。 <a href="'.$url.'">'.$url.'</a> から編集できます。');
      $app->redirect('/events/'.$event->id);
    }
  }
})->via('GET', 'POST');


#################
#  FRONT:talks  #
#################

$app->get('/talks/:id', function ($id) use ($app) {
  $talk = find_talk_by_id($id);
  if($talk == false) {
    $app->notFound();
  }

  fill_event_to_talk($talk);
  $app->render('talk.html', array('talk' => $talk));
})->conditions(array('id' => '[0-9]+'));


$app->map('/talks/:id/edit', function ($id) use ($app) {

  // key check
  if (!array_key_exists('key', $app->request->params())) {
    $app->halt(400, 'key required');
  }

  $talk = find_talk_by_id($id);
  $params = $app->request->params();
  $pass = $params['key'];
  if(!$talk->pass_check($pass)) {
    $app->halt(403);
  }

  if ($app->request->isGet()) {
    $app->render('talk_form.html', array('post_to' => $talk->edit_url($params), 'talk' => $talk));
  } else if ($app->request->isPost()) {
    $id = update_talk_by_id($id, $params);
    if($id == -1) {
      $app->flash('error', 'エラーが発生しました。');
      $app->redirect('/');
    } else {
      $url = $app->request->getUrl().$talk->edit_url(array("key" => $talk->passkey));
      $app->flash('message', '編集が完了しました。 <a href="'.$url.'">'.$url.'</a> から編集できます。');
      $app->redirect('/talks/' . $talk->id);
    }
  }
})->via('GET', 'POST')
  ->conditions(array('id' => '[0-9]+'));


$app->map('/talks/new', function () use ($app) {
  if (!array_key_exists('event_id', $app->request->params())) {
    $app->halt(400, 'event_id required');
  }
  
  $params = $app->request->params();
  $event_id = $params['event_id'];
  if ($app->request->isGet()) {
    $app->render('talk_form.html', array('event_id' => $event_id, 'post_to' => '/talks/new?'.http_build_query($params)));
  } else if ($app->request->isPost()) {
    $id = create_talk_with_event_id($event_id, $params);
    if($id == -1) {
      $app->flash('error', 'エラーが発生しました。');
      $app->redirect('/');
    } else {
      $talk = find_talk_by_id($id);
      $url = $app->request->getUrl().$talk->edit_url(array("key" => $talk->passkey));
      $app->flash('message', '編集が完了しました。 <a href="'.$url.'">'.$url.'</a> から編集できます。');
      $app->redirect('/talks/'.$id);
    }
  }
})->via('GET', 'POST');


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
      echo json_encode(array());
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

$app->notFound(function () use ($app) {
  $app->flash('error', 'sorry! page is not found');
  $app->redirect('/');
});


###########
#  おまけ  #
###########

$app->get('/route', function() use ($app) {
  $app->render('route.html', array());
});

$app->run();
