<?php
/**
 * Contacts controller.
 *
 */
namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Repository\ContactsRepository;
use Repository\TagRepository;
use Repository\UserRepository;
use Form\ContactsType;

/**
 * Class MainController.
 *
 */
class ContactsController implements ControllerProviderInterface
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
            ->bind('contacts_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('contacts_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('contacts_view');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('contacts_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('contacts_edit');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('contacts_delete');

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
        $ContactsModel = new ContactsRepository($app['db']);

        return $app['twig']->render(
            'contacts/index.html.twig',
            ['paginator' => $ContactsModel->findAllPaginated($page)]
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
    $contactsRepository = new ContactsRepository($app['db']);

    return $app['twig']->render(
        'contacts/view.html.twig',
        ['contacts' => $contactsRepository->findOneById($id)]
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

    $contact = [];

    $form = $app['form.factory']->createBuilder(
        ContactsType::class,
        $contact,
        ['tag_repository' => new TagRepository($app['db'])]
    )->getForm();
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $contactsRepository = new contactsRepository($app['db']);
        $contactsRepository->save($form->getData(), $id);

        $app['session']->getFlashBag()->add(
            'messages',
            [
                'type' => 'success',
                'message' => 'message.element_successfully_added',
            ]
        );

        return $app->redirect($app['url_generator']->generate('contacts_index'), 301);
    }

    return $app['twig']->render(
        'contacts/add.html.twig',
        [
            'contacts' => $contact,
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
    $contactsRepository = new contactsRepository($app['db']);
    $contact = $contactsRepository->findOneById($id);

    if (!$contact) {
        $app['session']->getFlashBag()->add(
            'messages',
            [
                'type' => 'warning',
                'message' => 'message.record_not_found',
            ]
        );

        return $app->redirect($app['url_generator']->generate('contacts_index'));
    }

    $form = $app['form.factory']->createBuilder(
        ContactsType::class,
        $contact,
        ['tag_repository' => new TagRepository($app['db'])]
    )->getForm();
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $contactsRepository->save($form->getData());

        $app['session']->getFlashBag()->add(
            'messages',
            [
                'type' => 'success',
                'message' => 'message.element_successfully_edited',
            ]
        );

        return $app->redirect($app['url_generator']->generate('contacts_index'), 301);
    }

    return $app['twig']->render(
        'contacts/edit.html.twig',
        [
            'contacts' => $contact,
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
    $contactsRepository = new ContactsRepository($app['db']);
    $contact = $contactsRepository->findOneById($id);

    if (!$contact) {
        $app['session']->getFlashBag()->add(
            'messages',
            [
                'type' => 'warning',
                'message' => 'message.record_not_found',
            ]
        );

        return $app->redirect($app['url_generator']->generate('contacts_index'));
    }

    $form = $app['form.factory']->createBuilder(FormType::class, $contact)->add('id', HiddenType::class)->getForm();
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $contactsRepository->delete($form->getData());

        $app['session']->getFlashBag()->add(
            'messages',
            [
                'type' => 'success',
                'message' => 'message.element_successfully_deleted',
            ]
        );

        return $app->redirect(
            $app['url_generator']->generate('contacts_index'),
            301
        );
    }

    return $app['twig']->render(
        'contacts/delete.html.twig',
        [
            'contacts' => $contact,
            'form' => $form->createView(),
        ]
    );
  }
}