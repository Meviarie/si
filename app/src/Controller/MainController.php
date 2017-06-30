<?php
/**
* Main controller.
*
*/
namespace Controller;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Repository\MainRepository;

/**
* Class MainController.
*
*/
class MainController implements ControllerProviderInterface
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
            ->method('GET|POST')
            ->bind('main_index');

        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return string Response
     */
    public function indexAction(Application $app)
    {
        $MainModel = new MainRepository($app['db']);

        return $app['twig']->render(
            'main/index.html.twig',
            ['main' => $MainModel->Date()]
        );
    }
}