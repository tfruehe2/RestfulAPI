<?php

class Posts extends ModelBase
{
  protected static $table_name;
  public static $fields=array();
  public static $required_fields=array();

  public function __construct()
  {
    parent::__construct();
  }

  public function incrementViewCount($post_id)
  {
    $sql = "UPDATE posts SET views=views+1 WHERE posts.id = ?";
    $this->findBySQL($sql, array($post_id));
  }

  public function likeCount($post_id)
  {
    $sql = "SELECT COUNT(*) FROM likes WHERE likes.post_id = ?;";
    return $this->findBySQL($sql, array($post_id));
  }

  public function commentCount($post_id)
  {
    $sql = "SELECT COUNT(*) FROM comments WHERE comments.post_id = ?;";
    return $this->findBySQL($sql, array($post_id));
  }

  public function create($params=array())
  {
    $params['slug'] = slugify($params['title']);
    return parent::create($params);
  }


}
