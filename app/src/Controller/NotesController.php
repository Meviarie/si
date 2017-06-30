<?php
/**
 * Notes controller.
 *
 */
namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Repository\NotesRepository;
use Repository\TagRepository;
use Repository\UserRepository;
use Form\NoteType;

/**
 * Class NotesController.
 *
 */
class NotesController implements ControllerProviderInterface
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
            ->bind('notes_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('notes_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('notes_view');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('notes_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('notes_edit');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('notes_delete');

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
        $notesRepository = new NotesRepository($app['db']);

        return $app['twig']->render(
            'notes/index.html.twig',
            ['paginator' => $notesRepository->findAllPaginated($page)]
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
        $notesRepository = new NotesRepository($app['db']);

        return $app['twig']->render(
            'notes/view.html.twig',
            ['notes' => $notesRepository->findOneById($id)]
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

        $note = [];

        $form = $app['form.factory']->createBuilder(
            NoteType::class,
            $note,
            ['tag_repository' => new TagRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $notesRepository = new NotesRepository($app['db']);
            $notesRepository->save($form->getData(), $id);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('notes_index'), 301);
        }

        return $app['twig']->render(
            'notes/add.html.twig',
            [
                'notes' => $note,
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
        $notesRepository = new NotesRepository($app['db']);
        $note = $notesRepository->findOneById($id);

        if (!$note) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('notes_index'));
        }

        $form = $app['form.factory']->createBuilder(
            NoteType::class,
            $note,
            ['tag_repository' => new TagRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $notesRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('notes_index'), 301);
        }

        return $app['twig']->render(
            'notes/edit.html.twig',
            [
                'notes' => $note,
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
        $notesRepository = new NotesRepository($app['db']);
        $note = $notesRepository->findOneById($id);

        if (!$note) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('notes_index'));
        }

        $form = $app['form.factory']->createBuilder(NoteType::class, $note)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($note->isSubmitted() && $form->isValid()) {
            $notesRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('notes_index'),
                301
            );
        }

        return $app['twig']->render(
            'notes/delete.html.twig',
            [
                'notes' => $note,
                'form' => $form->createView(),
            ]
        );
    }
}