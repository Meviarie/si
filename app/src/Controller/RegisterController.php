<?php
/**
 * Register controller.
 */
namespace Controller;

use Repository\RegisterRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Form\RegisterType;
use Symfony\Component\Security\Core\User\UserInterface;

class RegisterController implements ControllerProviderInterface
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

        $controller->match('/', [$this, 'editAction'])
            ->method('GET|POST')
            ->bind('register_index');

        $controller->match('/add', [$this, 'addAction'])
            ->method('GET|POST')
            ->bind('register_add');
        $controller->post('/edit', [$this, 'editAction'])
            ->bind('register_edit');
        return $controller;
    }

    public function indexAction(Application $app)
    {
        return $app['twig']->render(
            'register/register.html.twig'
        );
    }


    public function addAction(Application $app, Request $request)
    {
        $registerRepository = new RegisterRepository($app['db']);

        $register = [];
        $form = $app['form.factory']->createBuilder(RegisterType::class, $register)->getForm();
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $register  = $form->getData();
            $registerRepository->save($register, $app);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('auth_login'), 301);
        }

        return $app['twig']->render(
            'register/add.html.twig',
            [
                'register' => $register,
                'form' => $form->createView(),
            ]
        );
    }


    public function editAction(Application $app, Request $request)
    {
        try {
            $registerRepository = new RegisterRepository($app['db']);
            $token = $app['security.token_storage']->getToken();

            if (null !== $token && $token->getUser() instanceof UserInterface) {

                $register = $registerRepository->findOneById($id);
                $form = $app['form.factory']->createBuilder(
                    RegisterType::class,
                    $register
                )->getForm();
                $form->handleRequest($request);
            } else {
                $register = [];
                $form = $app['form.factory']->createBuilder(RegisterType::class, $register)->getForm();
                $form->handleRequest($request);
            }


            $register = [];

            if ($form->isSubmitted() && $form->isValid()) {

                $registerRepository->save($form->getData(),  $app);

                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_edited',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('register_index'), 301);
            }

            return $app['twig']->render(
                'register/edit.html.twig',
                [
                    'register' => $register,
                    'form' => $form->createView(),
                ]
            );
        } catch (DBALException $e) {
            $this->db->rollBack();
            throw $e;
        }

    }

}