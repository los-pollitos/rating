<?php

use MyApp\Models\Comentario;
use MyApp\Models\Url;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Di\FactoryDefault;
use Phalcon\Http\Response;
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\View\Simple;
use Phalcon\Config;

$config = new Config(require '../config/config.php');

$loader = new Loader();
$loader->registerNamespaces(
    [
        'MyApp\Models' => $config->phalcon->modelsDir,
    ]
);

$loader->register();

$container = new FactoryDefault();
$container->set(
    'db',
    function () use ($config) {
        return new Mysql(
            $config->database->toArray()
        );
    }
);

$container->set(
    'view',
    function () use ($config) {
        $view =  new Simple();
        $view->setViewsDir($config->phalcon->viewsDir);

        return $view;

    }
);

$app = new Micro($container);

$app->post(
    '/create_url',
    function () use ($app) {
        $datos = $app->request->getPost();
        $newURL = $datos['url'];
        $response = new Response();

        if (empty($newURL)) {
            return $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => 'Debes ingresar una url.',
                ]
            );
        }
        $checkUrl = Url::findFirst(
            [
                "url = '$newURL'",
            ]
        );

        if ($checkUrl) {
            return $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => 'Esa url ya esta registrada',
                ]
            );
        }

        $url = new Url();
        $url->url = $newURL;

        if ($url->save()) {
            $response->setStatusCode(201, 'Created');

            $response->setJsonContent(
                [
                    'status' => 'OK',
                    'data' => $url,
                ]
            );
        } else {
            $response->setStatusCode(409, 'Conflict');

            $errors = [];
            foreach ($url->getMessages() as $message) {
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
        $datos = $app->request->getPost();
        $url = $datos['url'];
        $response = new Response();

        if (empty($url)) {
            return $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => 'Debes ingresar una url.',
                ]
            );
        }

        $checkUrl = Url::findFirst(
            [
                "url = '$url'",
            ]
        );
        
        if (!$checkUrl) {
            return $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => 'Url is not in database.',
                    ]
            );
        }

        if ($checkUrl->url === $url) {
            $stringHtml = '';

            $numberOfComments = Comentario::count(
                [
                    "url_id = '$url'",
                    'column' => 'score',
                ]
            );

            if ($numberOfComments < 1) {
                $contenido = $app->view->render(
                    'formulario/empty',
                    [
                        'url'   => $url,
                    ]
                );

            } else {

            //Saca el promedio de los comentarios
            $avgScore = Comentario::average(
                [
                    "url_id = '$url'",
                    'column' => 'score',
                ]
            );
            $avgScore = (round($avgScore, 1));

            //busca los primeros 10 comentarios
            $comments = Comentario::find(
                [
                    "url_id = '$url'",
                    'order' => 'id',
                    'limit' => 10,
                ]
            );

                $templatesComments = '';

                foreach ($comments as $comment) {
                    $templatesComments .= $app->view->render(
                    'formulario/comment',
                    [
                        'comment'   => $comment->comment,
                        'score' => $comment->score,
                    ]
                );
                }
                $contenido = $app->view->render(
                'formulario/some',
                [
                    'url'   => $url,
                    'score' => $avgScore,
                    'content' => $templatesComments,
                ]
            );
            }

            $stringHtml = $app->view->render(
            'formulario/base',
            [
                'contenido'   => $contenido,
            ]
        );
            return $stringHtml;
        }
    }
);

$app->post(
    '/create_comment',
    function () use ($app) {
        $datosPost = $app->request->getPost();
        
        $response = new Response();
        if (empty($datosPost['url'])) {
            return $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => 'Debes ingresar una url.',
                ]
            );
        }

        if (empty($datosPost['score'])) {
            return $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => 'Debes ingresar una valoracion.',
                ]
            );
        }

        $urlToFind = $datosPost['url'];

        $checkUrl = Url::findFirst(
            [
                "url = '$urlToFind'",
            ]
            );

        if (!$checkUrl) {
            return $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => 'Url is not in database.',
                ]
            );
        }

        $newComment = new Comentario();

        $newComment->url_id = $datosPost['url'];
        $newComment->comment = $datosPost['comment'];
        $newComment->score = $datosPost['score'];

        if ($newComment->save()) {
            $response->setStatusCode(201, 'Created');

            $response->setJsonContent(
                [
                            'status' => 'OK',
                            'data' => $newComment,
                        ]
            );
        } else {
            $response->setStatusCode(409, 'Conflict');

            $errors = [];
            foreach ($newComment->getMessages() as $message) {
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
