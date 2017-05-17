<?php

class Post extends Controller
{

  public function __construct()
  {
    parent::__construct();
  }

  public function __toString()
  {
    return "Post Object";
  }

  // public function GET($request) {
  //   echo static::$model->likeCount(4);
  //   echo static::$model->commentCount(4);
  //   echo static::$model->incrementViewCount(4);
  // }



}
