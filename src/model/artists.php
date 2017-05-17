<?php

class Artists extends ModelBase
{
  private $_data;
  protected static $table_name;
  public static $fields=array();
  public static $required_fields=array();

  public function __construct()
  {
    parent::__construct();
  }

  public function findArtistGenres($artist_id)
  {
    $sql = "SELECT name FROM genres ";
    $sql .= "WHERE genres.id IN (SELECT genre_id ";
    $sql .= "FROM artist_genres as ag WHERE ";
    $sql .= "sg.artist_id = ?) ORDER BY name ASC;";
    return $this->findBySQL($sql, array($artist_id));
  }

  public function create($params=array())
  {
    if (isset($params['name'])) {
      $this->_data['name'] = $params['name'];
      $this->fetchArtistDetails();

      try
      {
        $artist_id = parent::create($this->_data);
        $artist_genre = new Artist_Genres();
        $genre = new Genres();

        foreach ($this->_data['genres'] as $g)
        {
          $g = ucwords($g);
          if ($json_obj=json_decode($genre->findByName($g)))
          {
            $artist_genre->create(array('artist_id' => $artist_id,
                                       'genre_id' => $json_obj[0]->id));
          }
          else
          {
            $artist_genre->create(array('artist_id' => $artist_id,
                                       'genre_id' => $genre->create(array('name'=>$g))));
          }
        }

        return $artist_id;
      }
      catch (Exception $e)
      {
        echo json_encode(Array('error' => $e->getMessage()));
        die("failed to create artist object in DB");
      }
    }
    return null;

  }

  public function fetchArtistDetails()
  {
    require_once "./includes/simple_html_dom.php";
    $name = $this->filterName($this->_data["name"]);
    $html = wikiWebScrape($name);
    $wiki_table = parseWikiTable($html);
    $fpath = STATIC_PATH . "/images/artist_images/" . $name;
    $this->_data['image'] = parseWikiImageFromTable($wiki_table, $fpath);

    foreach($wiki_table->find("tr") as $e)
    {
      $this->parseArtistInfo($e);
    }
  }


  public function parseArtistInfo($e)
  {
    switch(rtrim($e->children(0)->plaintext, " "))
    {
      case $this->_data["name"]:
      	break;
      case "Origin":
        $this->_data['origin'] = $e->children(1)->plaintext;
      	break;
      case "Born":
      	if ($nickname=$e->find("span[class=nickname]",0))
        {
      		$this->_data['full_name'] = removeNonLetterCharacters($nickname->plaintext);
      	}
      	if ($bday=$e->find("span[class=bday]",0))
        {
      		$this->_data['DOB'] = $bday->plaintext;
      	}
      	if ($birthplace=$e->find("span[class=birthplace]",0))
        {
          if (empty($this->_data['origin'])) {

            $this->_data['origin'] = $birthplace->plaintext;
          }
          else
          {
            $this->_data['birthplace'] = $birthplace->plaintext;
          }
      	}
      	break;
      case "Genres":
      	$genre_array=array();
      	foreach ($e->find("li") as $el)
        {
      		$genre_array[] = removeNonLetterCharacters($el->plaintext);
      	}
      	if (empty($genre_array))
        {
      		$genre_array = explode(",", $e->children(1)->plaintext);
      	}
        $this->_data['genres'] = $genre_array;
      	break;
      case "Years active":
        //$ya = rtrim(removeNonNumberChars($e->children(1)->plaintext), " ");
        //$years_active = preg_split("/[-–—]/", $e->children(1)->plaintext);
        preg_match_all("/(\b\d{4}\b|present)/", $e->children(1)->plaintext, $years_active);
        $this->_data['year_formed'] = array_shift($years_active[0]);
        if (end($years_active[0]) !=="present")
        {
          $this->_data['year_disbanded'] = array_pop($years_active[0]);
        }
      	break;
      case "Website":
        $this->_data['website'] = $e->children(1)->plaintext;
      	break;
      default:
        if (preg_match("/Years/", $e->children(0)->plaintext))
        {
          preg_match_all("/\b\d{4}\b/", $e->children(1)->plaintext, $years_active);
          $this->_data['year_formed'] = array_shift($years_active[0]);
          if (end($years_active[0]) !=="present")
          {
            $this->_data['year_disbanded'] = array_pop($years_active[0]);
          }
        }
        elseif (preg_match("/Home/", $e->children(0)->plaintext))
        {
          if (empty($this->_data["origin"]))
          {
            $this->_data['origin'] = $e->children(1)->plaintext;
          }
        }
      	break;
    }

  }

  public function filterName($name)
  {
    $name = ucwords($name);
    if (strpos($name, " ") !== false)
    {
      $name = str_replace(" ", "_", $name);
    }
    if (strpos($name, "$") !== false)
    {
  		$name = (strpos($name, "A\$AP") !== false) ? str_replace("$", "S", $name)
  																						 : str_replace("$", "s", $name);
  	}
    return $name;
  }


}
