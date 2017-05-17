<?php

class Songs extends ModelBase
{
  private $_data;
  protected static $table_name;
  public static $fields=array();
  public static $required_fields=array();

  public function __construct()
  {
    parent::__construct();

  }

  public function findSongGenres($song_id)
  {
    $sql = "SELECT name FROM genres ";
    $sql .= "WHERE genres.id IN (SELECT genre_id ";
    $sql .= "FROM song_genres as sg WHERE ";
    $sql .= "sg.song_id = ?) ORDER BY name ASC;";
    return $this->findBySQL($sql, array($song_id));
  }

  public function create($params=array())
  {
    $this->_data = $this->filterAttributes($params);
    //print_r($this->_data);
    if (isset($this->_data['title']) && isset($this->_data['artist_name'])
                                     && isset($this->_data['video_url']))
    {
      $this->_data['lyrics'] = static::fetchLyrics($this->_data['artist_name'], $this->_data["title"]);
      $post_attrs = array();
      $post_attrs['title'] = $this->_data['title'] . " - " . $this->_data['artist_name'];
      $post_attrs['description'] = $this->_data['lyrics'];
      $post_attrs['post_type'] = 'song';

      if (array_key_exists('author_id', $params))
      {
        $post_attrs['author_id'] = $params['author_id'];
      }

      $this->fetchSongType();
      $this->parseVideoId();
      if ($image_uri=$this->getSongImage())
      {
        $post_attrs['image'] = $image_uri;
      }

      $post = new Posts();

      try
      {
        $this->_data['post_id']=$post->create($post_attrs);
      }
      catch (Exception $e)
      {
        echo json_encode(Array('error' => $e->getMessage()));
        die("failed to create post object in DB");
      }
      $artist = new Artists();
      if($json_obj=json_decode($artist->findByName($this->_data['artist_name'])))
      {
        $this->_data['artist_id'] = $json_obj[0]->id;
      }
      else
      {
        $this->_data['artist_id'] = $artist->create(array(
                                          'name' => $this->_data['artist_name']
                                          ));
      }

      if ($album_details=$this->fetchSongDetails())
      {
        $album_details['artist_id'] = $this->_data['artist_id'];
        $album = new Albums();
        if ($album_obj=json_decode($album->findByName($album_details['title'])))
        {
          $this->_data['album_id'] = $album_obj[0]->id;
        }
        else
        {
          $this->_data['album_id'] = $album->create($album_details);
        }
      }

      try
      {
        $song_id = parent::create($this->_data);
        $song_genre = new Song_Genres();
        $genre = new Genres();

        if (!empty($this->_data['genres']))
        {
          foreach ($this->_data['genres'] as $g)
          {
            $g = ucwords($g);
            if ($json_obj=json_decode($genre->findByName($g)))
            {
              $song_genre->create(array('song_id' => $song_id,
                                         'genre_id' => $json_obj[0]->id));
            }
            else
            {
              $song_genre->create(array('song_id' => $song_id,
                          'genre_id' => $genre->create(array("name" => $g))));
            }
          }
        }
        return $song_id;

      }
      catch (Exception $e)
      {
        echo json_encode(Array('error' => $e->getMessage()));
        die("failed to create song object in DB");
      }
    }
  }

  public function fetchLyrics($artist_name, $song_name)
  {
    require_once "./includes/simple_html_dom.php";
    require_once "./../SECRETS/secrets.php";
  	$base_url = "https://api.genius.com";
  	$context = stream_context_create(array(
  		 'http' => array (
  				 'header' => 'Authorization: Bearer ' . $GENIUS_AUTH_TOKEN
  			)
  	));

  	function parseLyricsFromUrl($url)
    {
  		$html = new simple_html_dom();
  		$html->load_file((string)$url);
  		return preg_replace("(\r)", "<br />", $html->find('lyrics', 0)->plaintext);
  	}

  	$query = rawurlencode($artist_name . " " . $song_name);
  	$search_url = $base_url."/search?q=".$query;
  	$result = file_get_contents($search_url, false, $context);
  	$json = json_decode($result, true);

  	$song_info = null;
  	foreach ($json["response"]["hits"] as $hit)
    {
  		if (strpos($hit["result"]["primary_artist"]["name"], $artist_name) !==false)
      {
  			$song_info = $hit;
  			break;
  		}
  	}
  	if($song_info)
    {
  		$song_api_path = $song_info["result"]["api_path"];
  		$song_url = $base_url . $song_api_path;
  		$result = file_get_contents($song_url, false, $context);
  		$json = json_decode($result, true);
  		$path = $json["response"]["song"]["path"];
  		$url = "https://genius.com" . $path;
  		return parseLyricsFromUrl($url);
  	}

  	return "Sorry!\nThe lyrics for this song are unavailable";

  }

  public function fetchSongDetails()
  {
    require_once "./../SECRETS/secrets.php";
    $MG_AUTH_TOKEN = "a27d7fc31239f30bbf0f547174f190ea";
    $artist_name = $this->_data['artist_name'];
    $base_url = "http://api.musicgraph.com/api/v2/track/";
  	$url_params = "&title=" . urlencode($this->_data["title"]);
    $url_params .= "&artist_name=" . urlencode($artist_name);
  	$search_url = $base_url . "search?api_key=". $MG_AUTH_TOKEN . $url_params;
    $result = file_get_contents($search_url, false);
    $json = json_decode($result, true);
    if ($artist_name === "Jay Z")
    {
      $artist_name = "Jay-Z";
    }
    $album = $this->earliestAlbumAppearance($json['data'], $artist_name);
    unset($json);
    $album_details=array();
    if (!empty($album))
    {
      $album_details['title'] = $album['album_title'];
      $album_details['year_released'] = $album['original_release_year'];
      $album_details['artist_name'] = $artist_name;
      $this->_data['track_index'] = $album['track_index'];
      return $album_details;
    }
    else
    {
      echo "No Album Was Found";
      return false;
    }


  }

  public function parseVideoId()
  {
    $matches = array();
    switch ($this->_data['song_type'])
    {
      case 'youtube':
        if(preg_match('%(?<=/watch\?v=)(\S{11})%', $this->_data['video_url'],
                                                                    $matches))
        {
          $this->_data['video_id'] = $matches[1];
        }
        break;
      case 'soundcloud':
        //if(preg_match())
        break;
      default:
        break;
    }

  }

  public function fetchSongType()
  {
    $matches = array();
    if (preg_match('%(?:https?://)?(youtube|soundcloud).com%',
                          $this->_data['video_url'], $matches))
                          {
      $this->_data['song_type'] = $matches[1];
    }
    return null;
  }

  public function getSongImage()
  {
    switch ($this->_data['song_type'])
    {
      case 'youtube':
        $url = "http://img.youtube.com/vi/" . $this->_data['video_id'] . "/0.jpg";
        $jpg_sauce = file_get_contents($url);
        $fpath = STATIC_PATH . "/images/post_images/yt";
        $fpath .= $this->_data['video_id'].".jpg";
        $f = fopen($fpath, "w");
        if (fwrite($f, $jpg_sauce))
        {
          fclose($f);
          return $fpath;
        }
        throw new Exception("could not find youtube jpeg for video");
        break;
      case 'soundcloud':
        break;
      default:
        break;
    }

  }

  public function earliestAlbumAppearance($json, $artist_name)
  {
  	$element=array();
  	foreach ($json as $el)
    {
  		if (contains($artist_name, $el['artist_name'])
  				&& contains($this->_data['title'], $el['title']))
          {
  			if(empty($element))
        {
  				$element = $el;
  			}
        elseif ($el['original_release_year'] < $element['original_release_year'])
        {
  				$element = $el;
  			}
  		}
  	}
  	return $element;
  }


}
