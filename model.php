<?php
#######################
#  methods for model  #
#######################
# DBとの接続開始
function get_db_connection() {
  $pdo = new PDO("mysql:host=localhost;dbname=fmfes;charset=utf8mb4",
    "nobody",
    "nobody",
    array(PDO::ATTR_EMULATE_PREPARES => false)
  );
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  return $pdo;
}

# DBとの接続終了
# &$pdoでそのものを得る(コピーじゃなくて使ってるそのもの)
function close_db_connection(&$pdo) {
  $pdo = null;
}


# 便利メソッド
# データは必ずここから出し入れする

################
#    events    #
################

# eventをID指定で取得する
function find_event_by_id($id) {
  $pdo = get_db_connection();

  $stmt = $pdo->prepare('SELECT * FROM events
                         WHERE id=?');
  $stmt->execute(array($id));
  $event = $stmt->fetch();

  close_db_connection($pdo);

  return $event;
}

# eventを作成する
# array
#   - title: イベントタイトル(String, required)
#   - subtitle: テーマ、副題(String)   
#   - date: 開催日時(String, required, format=YYYY-MM-DD hh::mm:ss)
#   - place: 開催場所(String, required)
#   - text_md: 概要(String, required)
#   - deadline: 投稿締め切り(String, required, format=YYYY-MM-DD hh:mm:ss)
#
# 戻り値はID
function create_event($array) {
  # data check
  if(!array_key_exists($array['title'])) {
    # 必要なデータがない場合は例外を投げてエラー報告してる
    throw new Exception('title required');
  }
  if(!array_key_exists($array['date'])) {
    # value must be checked but not yet
    # TODO:check value
    throw new Exception('date required');
  }
  if(!array_key_exists($array['place'])) {
    throw new Exception('place required');
  }
  if(!array_key_exists($array['text_md'])) {
    throw new Exception('text_md required');
  }
  if(!array_key_exists($array['deadline'])) {
    throw new Exception('deadline required');
  }

  $passkey = "abcde"; # TODO: inpl here later
  $opendate = strtotime($array['date']);
  $created_at = date ("Y-m-d H:i:s"); # current time in SQL
  $updated_at = $created_at;
  
  $pdo = get_db_connection();
  $stmt = $pdo->prepare('INSERT INTO events (
      title,
      date,
      place,
      text_md,
      passkey,
      deadline,
      created_at,
      updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
  $stms->execute(array( #XXX: raw input
    $array['title'],
    $opendate,
    $array['place'],
    $array['text_md'],
    $passkey,
    $array['deadline'],
    $created_at,
    $updated_at));
  $id = $stmt->lastInsertId();
  
  close_db_connection($pdo);

  return $id;
}

# eventを更新する
# array
#   - title: イベントタイトル(String, required)
#   - subtitle: テーマ、副題(String)   
#   - date: 開催日時(String, required, format=YYYY-MM-DD hh::mm:ss)
#   - place: 開催場所(String, required)
#   - text_md: 概要(String, required)
#   - deadline: 投稿締め切り(String, required, format=YYYY-MM-DD hh:mm:ss)
#
# 戻り値はID?
function update_event_by_id($id, $array) {
  
}

###############
#    talks    #
###############

# talkをID指定で取得する
function find_talk_by_id($id) {
  $pdo = get_db_connection();

  $stmt = $pdo->prepare('SELECT * FROM talks
                         WHERE id=?');
  $stmt->execute(array($id));
  $event = $stmt->fetch();

  close_db_connection($pdo);

  return $event;
}

# event_idに紐づくtalkを取得する
# $event_id: イベントのID
# args:
#   offset: 何件目から読み込むか
#   limit: $offsetから何件読み込むか
# TODO:order byが欲しい気がする
function find_talks_by_event_id($event_id, $args) {
  $offset = 0;
  if(array_key_exists($args['offset'])) {
    $offset = $args['offset'];
  }
  
  $limit = 20;
  if(array_key_exists($args['limit'])) {
    $limit = $args['limit'];
  }

  $pdo = get_db_connection();
  $stmt = $pdo->prepare('SELECT * FROM talks
                         WHERE event_id=?
                         LIMIT ? OFFSET ?');
  $stmt->execute(array($event_id, $limit, $offset));
  $talks = $stmt->fetchAll();

  close_db_connection($pdo);

  return $talks;
}

# talkを作成する
function create_talk_with_event_id($event_id, $array) {
  # data check
  if(!array_key_exists($array['title'])) {
    # 必要なデータがない場合は例外を投げてエラー報告してる
    throw new Exception('title required');
  }
  if(!array_key_exists($array['date'])) {
    # value must be checked but not yet
    # TODO:check value
    throw new Exception('date required');
  }
  if(!array_key_exists($array['place'])) {
    throw new Exception('place required');
  }
  if(!array_key_exists($array['text_md'])) {
    throw new Exception('text_md required');
  }
  if(!array_key_exists($array['deadline'])) {
    throw new Exception('deadline required');
  }
  
  $passkey = "abcde"; # TODO: inpl here later
  $opendate = strtotime ($array['date']);
  $created_at = date ("Y-m-d H:i:s"); # current time in SQL
  $updated_at = $created_at;
  
  $pdo = get_db_connection();
  $stmt = $pdo->prepare('INSERT INTO events (
      title,
      date,
      place,
      text_md,
      passkey,
      deadline,
      created_at,
      updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
  $stms->execute(array( #XXX: raw input
    $array['title'],
    $opendate,
    $array['place'],
    $array['text_md'],
    $passkey,
    $array['deadline'],
    $created_at,
    $updated_at));
  $id = $stmt->lastInsertId();
  
  close_db_connection($pdo);
  
  return $id;
}

# talkを更新する
function update_talk_by_id($id, $array) {

}
