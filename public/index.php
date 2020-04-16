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

        $checkUrl = 'SELECT COUNT(url) FROM MyApp\Models\Url '
        .'WHERE url = :url:';

        $checkQuery = $app->modelsManager->executeQuery(
            $checkUrl,
            [
                'url' => $newUrl['url'],
            ]
        );

        $numberOfResults=($checkQuery[0]->readAttribute("0"));

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
        if($numberOfResults < 1){
            if ($status->success()) {
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
        }else{
            $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => 'Esa url ya esta registrada',
                ]
            );
        }            
        

        return $response;
    }
);

$app->post(
    '/create_comment',
    function () use ($app) {
        $newComment = $app->request->getPost();

        $checkUrl = 'SELECT COUNT(url) FROM MyApp\Models\Url '
        .'WHERE url = :url:';

        $checkQuery = $app->modelsManager->executeQuery(
            $checkUrl,
            [
                'url' => $newComment['url'],
            ]
        );

        $numberOfResults=($checkQuery[0]->readAttribute("0"));


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
                    'url_id' => $newComment['url'],
                    'comment' => $newComment['comment'],
                    'score' => $newComment['score'],
                ]
            )
        ;
        $response = new Response();
        if($numberOfResults > 0){
            if ($status->success()) {
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
        }else{
            $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => 'Url is not in database.',
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
        if ($confirm === '') {
            $response = new Response();
            return $response->setJsonContent(
                [
                'status' => 'ERROR',
                'messages' => 'Url is not in database.',
            ]
            );
        }
        if ($confirm === $url['url']) {

            //Saca el promedio de los comentarios
            $phqlAvg = 'SELECT AVG(score) FROM MyApp\Models\Comentario '
                .'WHERE url_id = :url:';

            $avgScore = $app->modelsManager->executeQuery(
                $phqlAvg,
                [
                    'url' => $confirm,
                ]
            );
            $avgScore = (round($avgScore[0]->readAttribute("0"), 1));

            //busca los primeros 10 comentarios
            $phqlSearchComment = 'SELECT * FROM MyApp\Models\Comentario '
                .'WHERE url_id = :url: LIMIT 10';

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
                $templatesComments = '';

                foreach ($data as $comment) {
                    $templatesComments .= $app->view->render(
                        'formulario/comment',
                        [
                            'comment'   => $comment[0],
                            'score' => $comment[1],
                        ]
                    );
                }
                $contenido = $app->view->render(
                    'formulario/some',
                    [
                        'url'   => $confirm,
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
