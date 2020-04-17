<?php

namespace MyApp\Models;

use Phalcon\Mvc\Model;

class Comentario extends Model
{
    public $id;
    public $url_id;
    public $score;
    public $comment;
}
