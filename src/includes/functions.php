<?php

function escape($string)
{
  return htmlentities($string, ENT_QUOTES, 'UFT-8');
}

function sanitize_output($value)
{
  return htmlspecialchars(strip_tags($value));
}

function slugify($text)
{
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
  $text = preg_replace('~[^-\w]+~', '', $text);
  $text = trim($text, '-');
  $text = preg_replace('~-+~', '-', $text);
  $text = strtolower($text);
  if (empty($text))
  {
    return 'n-a';
  }

  return $text;
}

function removeNonLetterCharacters($str)
{
	return preg_replace("/[^A-Za-z ]/", '', $str);
}

function removeNonNumberChars($str)
{
	return preg_replace("/[^0-9]/",' ', $str);
}

function contains($substr, $str)
{
  return strpos($str, $substr) !== false;
}

function wikiWebScrape($page_title)
{
  if (strpos($page_title, " ") !== false)
  {
    $page_title = str_replace(" ", "_", $page_title);
  }
  $base_url = "https://en.wikipedia.org/wiki/";
  $html = new simple_html_dom();
  $html->load_file($base_url . $page_title);
  return $html;
}

function parseWikiTable($html)
{
  $wiki_table = new simple_html_dom();
  try
  {
    $wiki_table->load($html->find("table.infobox",0));
    unset($html);
  }
  catch (Error $e)
  {
    echo json_encode(Array('error' => $e->getMessage()));
    die("This Wiki page DNE!");
  }
  return $wiki_table;
}


function parseWikiImageFromTable($wiki_table, $image_path)
{
  foreach ($wiki_table->find('a[class=image]') as $e)
   {
    if(preg_match('#\bsrc="(.+?)(?=\.(jpg|JPG)")#', $e->innertext, $groups))
    {
      $jpg_sauce = file_get_contents("http:" . $groups[1] . "." .$groups[2]);
      $fpath = $image_path . "." .$groups[2];
      $f = fopen($fpath, "w");
      if (fwrite($f, $jpg_sauce)) {
        fclose($f);
        return $fpath;
      }
      else
      {
        throw new Exception("Wiki Image failed to save");
      }
    }
    else
    {
      return null;
    }
  }
}

function fetchWikiImage($page_title, $image_path)
{
  $html = wikiWebScrape($page_title);
  $wiki_table = parseWikiTable($html);
  return parseWikiImageFromTable($wiki_table, $image_path);
}
