<?php

class Albums extends ModelBase
{
  protected static $table_name;
  public static $fields=array();
  public static $required_fields=array();

  public function __construct()
  {
    parent::__construct();
  }

  public function create($params=array())
  {
    $this->_data = $this->filterAttributes($params);
    $this->fetchAlbumThumbnail();
    return parent::create($this->_data);
  }


  public function fetchAlbumThumbnail()
  {
  	$params = rawurlencode($this->_data['artist_name']." ".$this->_data['title']);
  	$url = "https://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=";
  	$url .= $params . "&format=json";
  	$result = file_get_contents($url, false);
  	$json = json_decode($result, true);
    foreach($json['query']['search'] as $result)
    {
     if (levenshtein($this->_data['title'], $result["title"]) < 5)
     {
       $path = STATIC_PATH."/images/album_images/";
       $path .= $this->_data['artist_name'] . " - " . $this->_data['title'];
       $this->_data['image'] = fetchWikiImage($result["title"], $path);
     }
    }
  }


}
