<?php

class Event {
  public $id = NULL;
  public $title = NULL;
  public $subtitle = NULL;
  public $date = NULL;
  public $place = NULL;
  public $text_md = NULL;
  private $passkey = NULL;
  public $deadline = NULL;
  public $created_at = NULL;
  public $updated_at = NULL;

  public function isValid() {
    if($this->title == NULL) {
      # 必要なデータがない場合は例外を投げてエラー報告してる
      throw new Exception('title required');
    }
    if($this->date == NULL) {
      # value must be checked but not yet
      # TODO:check value
      throw new Exception('date required');
    }
    if($this->place == NULL) {
      throw new Exception('place required');
    }
    if($this->text_md == NULL) {
      throw new Exception('text_md required');
    }
    if($this->deadline == NULL) {
      throw new Exception('deadline required');
    }
  }
  
  # TODO: inpl here later
  public static function generatePass($rawkey) {
    return $rawkey;
  }

  public function passCheck($pass) {
    return $this->generatePass($pass) == $this->passkey;
  }

  public function updateTimes() {
    $this->updated_at = date ("Y-m-d H:i:s"); # current time in SQL
    if($this->created_at == NULL) {
      $this->created_at = $this->updated_at;
    }
  }

  public function editURL($passkey) {
    return '/events/'.$this->id.'/edit?key='.$passkey;
  }
}

class Talk {
  public $id = NULL;
  public $title = NULL;
  public $team_name = NULL;
  public $members_json = NULL;
  public $text_md = NULL;
  private $passkey = NULL;
  public $img_url = NULL;
  public $status = NULL;
  public $sequence = NULL;
  public $event = NULL;
  public $event_id = NULL;
  public $created_at = NULL;
  public $updated_at = NULL;

  public function isValid($pass) {
    return false;
  }
  
  # TODO: inpl here later
  public static function generatePass($rawkey) {
    return $rawkey;
  }
  
  public function passCheck($pass) {
    return $this->generatePass($pass) == $this->passkey;
  }
  
  public function updateTimes() {
    $this->updated_at = date ("Y-m-d H:i:s"); # current time in SQL
    if($this->created_at == NULL) {
      $this->created_at = $this->updated_at;
    }
  }
  
  public function editURL($passkey) {
    return '/talks/'.$this->id.'/edit?key='.$passkey;
  }
}
