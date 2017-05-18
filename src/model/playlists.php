<?php

class Playlists extends ModelBase
{
  protected static $table_name;
  public static $fields=array();
  public static $required_fields=array();

  public function __construct()
  {
    parent::__construct();
  }

  public function findIsFeatured()
  {
    $where = array('is_featured', "=", "true");
    return $this->get($where);
  }

  public function findPlaylistSongs($pl_id)
  {
    $sql = "SELECT * FROM songs WHERE songs.id IN ";
    $sql .= "(SELECT song_id FROM playlist_songs as pls ";
    $sql .= "WHERE pls.playlist_id = ? ";
    $sql .= "ORDER BY position ASC);";
    return $this->findBySQL($sql, array($pl_id));
  }

  public function findById($id)
  {
    $pl_array = parent::findById($id);
    $song_array = $this->findPlaylistSongs($id);
    return array('playlist' => $pl_array, 'songs' => $song_array);
  }


  public function sampleSelect()
  {
    return $this->select(null, array('id', '=', '2'), null, array('name', "ASC"), array(1));
  }

  public function create($params=array())
  {
    if (isset($params['songs']) && isset($params['name'])
                                && isset($params['description'])
                                && isset($params['user_id']))
                                {
      $song_array = $params['songs'];
      $this->_data = static::filterAttributes($params);
      $post = new Posts();
      $post_array = array('title'=>$this->_data['name'],
                          'description'=>$this->_data['description'],
                          'post_type'=>"playlist",
                          'author_id'=>$this->_data['user_id']);
      try
      {
        $this->_data['post_id'] = $post->create($post_array);
      }
      catch (Exception $e)
      {
        echo json_encode(Array('error' => $e->getMessage()));
        die("failed to create post object in DB");
      }

      try
      {
        $this->_data['song_count'] = count($song_array);
        $playlist_id = parent::create($this->_data);
      }
      catch (Exception $e)
       {
        echo json_encode(Array('error' => $e->getMessage()));
        die("failed to create playlist object in DB");
      }

      try
      {
        $pl_songs = new Playlist_Songs();
        foreach (json_decode($song_array) as $pos=>$song_id)
        {
          $pl_songs->create(array('playlist_id'=>$playlist_id,
                                  'song_id'=>$song_id,
                                  'position'=>((int)$pos+1)));
        }
      }
      catch (Exception $e)
      {
        echo json_encode(Array('error' => $e->getMessage()));
        die("failed to add song to playlist");
      }
    }

    return $playlist_id;

  }


}
