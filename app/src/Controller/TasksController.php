<?php
/**
 * Tasks controller.
 *
 */
namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Repository\TasksRepository;
use Repository\TagRepository;
use Repository\UserRepository;
use Form\TaskType;

/**
 * Class MainController.
 *
 */
class TasksController implements ControllerProviderInterface
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
            ->bind('tasks_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('tasks_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('tasks_view');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('tasks_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('tasks_edit');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('tasks_delete');

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
        $tasksRepository = new TasksRepository($app['db']);

        return $app['twig']->render(
            'tasks/index.html.twig',
            ['paginator' => $tasksRepository->findAllPaginated($page)]
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
        $tasksRepository = new TasksRepository($app['db']);

        return $app['twig']->render(
            'tasks/view.html.twig',
            ['tasks' => $tasksRepository->findOneById($id)]
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

        $task = [];

        $form = $app['form.factory']->createBuilder(
            TaskType::class,
            $task,
            ['tag_repository' => new TagRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tasksRepository = new TasksRepository($app['db']);
            $tasksRepository->save($form->getData(), $id);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('tasks_index'), 301);
        }

        return $app['twig']->render(
            'tasks/add.html.twig',
            [
                'tasks' => $task,
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
        $tasksRepository = new TasksRepository($app['db']);
        $task = $tasksRepository->findOneById($id);

        if (!$task) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('tasks_index'));
        }

        $form = $app['form.factory']->createBuilder(
            TaskType::class,
            $task,
            ['tag_repository' => new TagRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tasksRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('tasks_index'), 301);
        }

        return $app['twig']->render(
            'tasks/edit.html.twig',
            [
                'tasks' => $task,
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
        $tasksRepository = new TasksRepository($app['db']);
        $task = $tasksRepository->findOneById($id);

        if (!$task) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('tasks_index'));
        }

        $form = $app['form.factory']->createBuilder(TaskType::class, $task)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($task->isSubmitted() && $form->isValid()) {
            $tasksRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('tasks_index'),
                301
            );
        }

        return $app['twig']->render(
            'tasks/delete.html.twig',
            [
                'tasks' => $task,
                'form' => $form->createView(),
            ]
        );
    }
}