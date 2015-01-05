<?php

class Event {
  public $id = NULL;
  public $title = NULL;
  public $subtitle = NULL;
  public $date = NULL;
  public $place = NULL;
  public $text_md = NULL;
  public $passkey = NULL;
  public $deadline = NULL;
  public $sequence_max = NULL;
  public $created_at = NULL;
  public $updated_at = NULL;

  public function is_valid() {
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
  public static function generate_pass($rawkey) {
    return $rawkey;
  }

  public function pass_check($pass) {
    return $this->generate_pass($pass) == $this->passkey;
  }

  public function update_times() {
    $this->updated_at = date ("Y-m-d H:i:s"); # current time in SQL
    if($this->created_at == NULL) {
      $this->created_at = $this->updated_at;
    }
  }

  public function edit_url($params) {
    return '/events/'.$this->id.'/edit?'.http_build_query($params);
  }

  public function inspect() {
    $array = get_object_vars($this);
    unset($array['passkey']);
    return $array;
  }
}

class Talk {
  public $id = NULL;
  public $title = NULL;
  public $team_name = NULL;
  public $members_json = NULL;
  public $members = NULL;
  public $text_md = NULL;
  public $links_json = NULL;
  public $passkey = NULL;
  public $img_url = NULL;
  public $status = 'ready';
  public $sequence = NULL;
  public $event = NULL;
  public $event_id = NULL;
  public $created_at = NULL;
  public $updated_at = NULL;

  public function is_valid() {
    if($this->title == NULL) {
      # 必要なデータがない場合は例外を投げてエラー報告してる
      throw new Exception('title required');
    }
    if($this->team_name == NULL) {
      throw new Exception('team name required');
    }
    if($this->members_json == NULL) {
      throw new Exception('members_json required');
    }
    if($this->text_md == NULL) {
      throw new Exception('text_md required');
    }
    if($this->status == NULL) {
      throw new Exception('status required');
    }
    if($this->event_id == NULL) {
      throw new Exception('event_id required');
    }
  }
  
  # TODO: inpl here later
  public static function generate_pass($rawkey) {
    return $rawkey;
  }
  
  public function pass_check($pass) {
    return $this->generate_pass($pass) == $this->passkey;
  }
  
  public function update_times() {
    $this->updated_at = date ("Y-m-d H:i:s"); # current time in SQL
    if($this->created_at == NULL) {
      $this->created_at = $this->updated_at;
    }
  }
  
  # XXX: カッコ悪い
  public function encode_json() {
    $this->members_json = json_encode($this->members);
    $this->links_json = json_encode($this->links);
  }
  
  public function decode_json() {
    $this->members = json_decode($this->members_json, true);
    $this->links = json_decode($this->links_json, true);
  }
  
  public function edit_url($params) {
    return '/talks/'.$this->id.'/edit?'.http_build_query($params);
  }

  public function inspect() {
    if($event == NULL) {
      fill_event_to_talk($this);
    }
    $this->decode_json();

    $array = get_object_vars($this);
    unset($array['passkey']);
    unset($array['members_json']);
    unset($array['links_json']);
    return $array;
  }
}
