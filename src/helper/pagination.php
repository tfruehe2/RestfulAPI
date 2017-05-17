<?php

class Pagination
{
  public $current_page,
         $per_page,
         $total_count;

  public function __construct($page=1, $per_page=20, $total_count=0)
  {
    $this->current_page = $page;
    $this->per_page = (int)$per_page;
    $this->total_count = (int)$total_count;
  }

  public function offset()
  {
    return ($this->current_page -1) * $this->per_page;
  }

  public function totalPages()
  {
    return ceil($this->total_count/$this->per_page);
  }

  public function previousPage()
  {
    return $this->hasPreviousPage() ? $this->current_page - 1 : false;
  }

  public function nextPage()
  {
    return $this->hasNextPage() ? $this->current_page + 1 : false;
  }

  public function hasPreviousPage()
  {
    return $this->current_page -1 >= 1 ? true : false;
  }

  public function hasNextPage()
  {
    return $this->current_page + 1 <= $this->totalPages() ? true : false;
  }


}
