<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Controller\MainController;
use Controller\BookmarkController;
use Controller\TagController;
use Controller\ContactsController;
use Controller\EventsController;
use Controller\NotesController;
use Controller\TasksController;
use Controller\AuthController;
use Controller\AdminController;
use Controller\RegisterController;

$app->mount('/main', new MainController());

$app->mount('/contacts', new ContactsController());

$app->mount('/events', new EventsController());

$app->mount('/notes', new NotesController());

$app->mount('/tasks', new TasksController());

$app->mount('/bookmark', new BookmarkController());

$app->mount('/tag', new TagController());

$app->mount('/auth', new AuthController());

$app->mount('/register', new RegisterController());

$app->mount('/admin', new AdminController());

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
    })
    ->bind('homepage')
;


$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
