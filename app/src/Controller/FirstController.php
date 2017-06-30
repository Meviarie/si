<?php
/**
* First controller.
*
*/
namespace Controller;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Repository\FirstRepository;

/**
* Class FirstController.
*
*/
class FirstController implements ControllerProviderInterface
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
        $controller->match('/', [$this, 'indexAction'])
            ->bind('first_index');

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
        $FirstModel = new FirstRepository($app['db']);

        return $app['twig']->render(
            'si_projekt/index.html.twig',
            ['first' => $FirstModel->Test()]
        );
    }
}