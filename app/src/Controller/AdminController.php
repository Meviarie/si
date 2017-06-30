<?php
/**
 * Admin controller.
 *
 */
namespace Controller;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Repository\AdminRepository;
use Form\PassType;
use Form\RoleType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Repository\UserRepository;

/**
 * Class AdminController.
 *
 */
class AdminController implements ControllerProviderInterface
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
            ->bind('admin_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
        ->value('page', 1)
        ->bind('admin_index_paginated');
        $controller->match('/{id}/edit_role', [$this, 'editroleAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('admin_edit_role');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('admin_edit');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('admin_delete');

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
        $adminRepository = new AdminRepository($app['db']);

        return $app['twig']->render(
            'admin/index.html.twig',
            ['paginator' => $adminRepository->findAllPaginated($page)]
        );
    }
    /**
     * Change password action.
     *
     * @param  Application $app
     * @param  $id
     * @param  Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Application $app, $id, Request $request)
    {
        $adminRepository = new AdminRepository($app['db']);
        $user = $adminRepository->findOneById($id);
        $data = [];

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('main_index'));
        }


        $form = $app['form.factory']->createBuilder(PassType::class, $data)->add('id', HiddenType::class, ['data'=>$id])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adminRepository->changeUsersPassword($form->getData(), $app);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('admin_index'),
                301
            );
        }

        return $app['twig']->render(
            'admin/password.html.twig',
            ['id'=>$user['id_login_data'],
                'form' => $form->createView(),]
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
        $adminRepository = new AdminRepository($app['db']);
        $admin = $adminRepository->findOneById($id);

        if (!$admin) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('admin_index'));
        }

        $form = $app['form.factory']->createBuilder(AdminType::class, $admin)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($admin->isSubmitted() && $form->isValid()) {
            $adminRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('admin_index'),
                301
            );
        }

        return $app['twig']->render(
            'admin/delete.html.twig',
            [
                'login_data' => $admin,
                'form' => $form->createView(),
            ]
        );
    }
    public function editroleAction(Application $app, $id, Request $request)
    {
        $adminRepository = new AdminRepository($app['db']);
        $user = $adminRepository->findOneById($id);
        $data = [];

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('main_index'));
        }


        $form = $app['form.factory']->createBuilder(RoleType::class, $data)->add('id', HiddenType::class, ['data'=>$id])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adminRepository->changeUsersRoles($form->getData(), $id);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('admin_index'),
                301
            );
        }

        return $app['twig']->render(
            'admin/role.html.twig',
            ['id'=>$user['id_login_data'],
                'form' => $form->createView(),]
        );
    }
}