<?php

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Di\FactoryDefault;
use Phalcon\Http\Response;
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt;

$loader = new Loader();
$loader->registerNamespaces(
    [
        'MyApp\Models' => '../app/models/',
    ]
);
$loader->register();

$container = new FactoryDefault();
$container->set(
    'db',
    function () {
        return new Mysql(
            [
                'host' => 'db',
                'username' => 'root',
                'password' => 'root',
                'dbname' => 'rating',
            ]
        );
    }
);

$container->set(
    'voltService',
    function ($view, $container) {
        $volt = new Volt($view, $container);

        $volt->setOptions(
            [
                'compiledPath'      => '../app/compiled-templates/',
                'compiledExtension' => '.compiled',
            ]
        );

        return $volt;
    }
);

$container->set(
    'view',
    function () {
        $view = new View();

        $view->setViewsDir('../app/views/');

        $view->registerEngines(
            [
                '.volt' => 'voltService',
            ]
        );

        return $view;
    }
);

$app = new Micro($container);

$app->post(
    '/crear_url',
    function () use ($app) {
        $newUrl = $app->request->getPost();
        $phql = 'INSERT INTO MyApp\Models\Url '
               .'(url) '
               .'VALUES '
               .'(:url:)'
        ;

        $status = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'url' => $newUrl['url'],
                ]
            )
        ;
        $response = new Response();

        if (true === $status->success()) {
            $response->setStatusCode(201, 'Created');

            $newUrl->id = $status->getModel()->id;

            $response->setJsonContent(
                [
                    'status' => 'OK',
                    'data' => $newUrl,
                ]
            );
        } else {
            $response->setStatusCode(409, 'Conflict');

            $errors = [];
            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

$app->post(
    '/read_url',
    function () use ($app) {
        $url = $app->request->getPost();
        $phql = 'SELECT * FROM MyApp\Models\Url '
        .'WHERE url LIKE :url: ORDER BY url';

        $urlData = $app->modelsManager->executeQuery(
            $phql,
            [
                'url' => '%' . $url['url'] . '%'
            ]
        );

        $confirm = '';

        foreach ($urlData as $item) {
            $confirm = $item->url;
        }
        $response = new Response();
        if ($confirm === $url['url'] ) {
            //Hacer que renderice el template con volt
            echo 'entro';
            
            $app->view->setVar('url', $confirm);
            $app->view->render('formulario','empty');
            $app->view->finish();

            print_r($app->view->getContent());die;
            //=================================
        }
    }
);

$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, 'Not Found')->sendHeaders();
    echo 'This is crazy, but this page was not found!';
});

try {
    $app->handle(
        $_SERVER['REQUEST_URI']
    );
} catch (\Exception $e) {
    echo 'Exception: ', $e->getMessage();
}
