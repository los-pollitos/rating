<?php

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Di\FactoryDefault;
use Phalcon\Http\Response;
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\View\Simple;

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
    'view',
    function () {
        $view =  new Simple();

        $view->setViewsDir('../app/views/');

        return $view;
    }
);

$app = new Micro($container);

$app->post(
    '/create_url',
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
    '/create_comment/{url_id}',
    function ($url_id) use ($app) {
        $newComment = $app->request->getPost();
        $phql = 'INSERT INTO MyApp\Models\Comentario '
               .'(url_id, comment, score) '
               .'VALUES '
               .'(:url_id:, :comment:, :score:)'
        ;

        $status = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'url_id' => $url_id,
                    'comment' => $newComment['comment'],
                    'score' => $newComment['score'],
                ]
            )
        ;
        $response = new Response();

        if (true === $status->success()) {
            $response->setStatusCode(201, 'Created');

            $newComment->id = $status->getModel()->id;

            $response->setJsonContent(
                [
                    'status' => 'OK',
                    'data' => $newComment,
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

        $stringHtml = '';

        if ($confirm === $url['url']) {
            $phqlSearchComment = 'SELECT * FROM MyApp\Models\Comentario '
                .'WHERE url_id = :url:';
            
            $comments = $app->modelsManager->executeQuery(
                $phqlSearchComment,
                [
                    'url' => $confirm,
                ]
            );

            $data = [];
            foreach ($comments as $comment) {
                $data [] =
                    [
                        $comment->comment,
                        $comment->score,
                    ];
            }

            if (empty($data)) {
                $contenido = $app->view->render(
                    'formulario/empty',
                    [
                        'url'   => $confirm,
                    ]
                );
            } else {
                $commentCount = 0;
                $commentScore = 0;
                $templatesComments = '';
                foreach ($data as $comment) {
                    $templatesComments .= $app->view->render(
                        'formulario/comment',
                        [
                            'comment'   => $comment[0],
                            'score' => $comment[1],
                        ]
                    );
                    $commentCount += 1;
                    $commentScore += $comment[1];
                }
                $promScore = $commentScore / $commentCount;
                $contenido = $app->view->render(
                    'formulario/some',
                    [
                        'url'   => $confirm,
                        'score' => $promScore,
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
        }

        return $stringHtml;
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
