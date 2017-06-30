<?php
/**
 * Events controller.
 *
 */
namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Repository\EventsRepository;
use Repository\TagRepository;
use Repository\UserRepository;
use Form\EventType;

/**
 * Class MainController.
 *
 */
class EventsController implements ControllerProviderInterface
{
    /**
     * Routing settings.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return \Silex\ControllerCollection Result
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])
            ->bind('events_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('events_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('events_view');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('events_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('events_edit');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('events_delete');

        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return string Response
     */
    public function indexAction(Application $app, $page = 1)
    {
        $eventsRepository = new EventsRepository($app['db']);

        return $app['twig']->render(
            'events/index.html.twig',
            ['paginator' => $eventsRepository->findAllPaginated($page)]
        );
    }
    /**
     * View action.
     *
     * @param \Silex\Application $app Silex application
     * @param string             $id  Element Id
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function viewAction(Application $app, $id)
    {
        $eventsRepository = new EventsRepository($app['db']);

        return $app['twig']->render(
            'events/view.html.twig',
            ['events' => $eventsRepository->findOneById($id)]
        );
    }
    /**
     * Add action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function addAction(Application $app, Request $request)
    {
	$token = $app['security.token_storage']->getToken();

		if (null !== $token) {
   			 $user = $token->getUser();
		}

	$userRepository = new UserRepository($app['db']);
        $user = $userRepository->loadUserByLogin($user);
	$id = $user['id_login_data'];
	//dump($user);

        $event = [];

        $form = $app['form.factory']->createBuilder(
            EventType::class,
            $event,
            ['tag_repository' => new TagRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eventsRepository = new EventsRepository($app['db']);
            $eventsRepository->save($form->getData(), $id);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('events_index'), 301);
        }

        return $app['twig']->render(
            'events/add.html.twig',
            [
                'events' => $event,
                'form' => $form->createView(),
            ]
        );
    }
    /**
     * Edit action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function editAction(Application $app, $id, Request $request)
    {
        $eventsRepository = new EventsRepository($app['db']);
        $event = $eventsRepository->findOneById($id);

        if (!$event) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('events_index'));
        }

        $form = $app['form.factory']->createBuilder(
            EventType::class,
            $event,
            ['tag_repository' => new TagRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eventsRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('events_index'), 301);
        }

        return $app['twig']->render(
            'events/edit.html.twig',
            [
                'events' => $event,
                'form' => $form->createView(),
            ]
        );
    }
    /**
     * Delete action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function deleteAction(Application $app, $id, Request $request)
    {
        $eventsRepository = new EventsRepository($app['db']);
        $event = $eventsRepository->findOneById($id);

        if (!$event) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('events_index'));
        }

        $form = $app['form.factory']->createBuilder(EventType::class, $event)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($event->isSubmitted() && $form->isValid()) {
            $eventsRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('events_index'),
                301
            );
        }

        return $app['twig']->render(
            'events/delete.html.twig',
            [
                'events' => $event,
                'form' => $form->createView(),
            ]
        );
    }
}

