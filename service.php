<?php
require_once "model.php";

#######################
#  methods for model  #
#######################
# DBとの接続開始
function get_db_connection() {
  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));
  $server = $url["host"];
  $username = $url["user"];
  $password = $url["pass"];
  $db = substr($url["path"], 1);

  $pdo = new PDO("mysql:host=".$server.";dbname=".$db.";charset=utf8mb4",
    $username,
    $password,
    array(PDO::ATTR_EMULATE_PREPARES => false)
  );
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
  $stmt->setFetchMode(PDO::FETCH_CLASS, 'Event');
  $stmt->execute(array($id));
  $event = $stmt->fetch(PDO::FETCH_CLASS);

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
function create_event($params) {
  $event = new Event;
  $event->title = $params['title'];
  $event->subtitle = $params['subtitle'];
  $event->date = $params['date'];
  $event->place = $params['place'];
  $event->text_md = $params['text_md'];
  $event->passkey = $event->generate_pass("abcdef");
  $event->deadline = $params['deadline'];
  # $passkey = $event->generatePass($params['key']);
  $event->update_times();
  
  # data check
  try {
    $event->is_valid();
  } catch (Exception $e) {
    fputs(STDOUT,  var_dump($event));
    return -1;
  }

  $pdo = get_db_connection();
  $stmt = $pdo->prepare('INSERT INTO events(
      title,
      subtitle,
      date,
      place,
      text_md,
      passkey,
      deadline,
      created_at,
      updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute(array( #XXX: raw input
    $event->title,
    $event->subtitle,
    $event->date,
    $event->place,
    $event->text_md,
    $event->passkey,
    $event->deadline,
    $event->created_at,
    $event->updated_at));
  $id = $pdo->lastInsertId();
  
  close_db_connection($pdo);

  return $id;
}

# eventを更新する
# id: 更新するeventのID(Integer, required)
# array
#   - title: イベントタイトル(String, required)
#   - subtitle: テーマ、副題(String)
#   - date: 開催日時(String, required, format=YYYY-MM-DD hh::mm:ss)
#   - place: 開催場所(String, required)
#   - text_md: 概要(String, required)
#   - deadline: 投稿締め切り(String, required, format=YYYY-MM-DD hh:mm:ss)
#
# 戻り値はID?
function update_event_by_id($id, $params) {
  try {
    $event = find_event_by_id($id);
  } catch (Exception $e) {
    return -1;
  }
  
  $event->title = $params['title'];
  $event->subtitle = $params['subtitle'];
  $event->date = $params['date'];
  $event->place = $params['place'];
  $event->text_md = $params['text_md'];
  $event->deadline = $params['deadline'];
  $event->update_times();

  try {
    $event->is_valid();
  } catch (Exception $e) {
    return -1;
  }  
  
  $pdo = get_db_connection();
  $stmt = $pdo->prepare('UPDATE events
      SET
        title=?,
        subtitle=?,
        date=?,
        place=?,
        text_md=?,
        deadline=?,
        created_at=?,
        updated_at=?
      WHERE
        id=?');
  $stmt->execute(array( #XXX: raw input
    $event->title,
    $event->subtitle,
    $event->date,
    $event->place,
    $event->text_md,
    $event->deadline,
    $event->created_at,
    $event->updated_at,
    $event->id));
  $id = $pdo->lastInsertId();
  
  close_db_connection($pdo);
  
  return $id;
}

###############
#    talks    #
###############

# talkをID指定で取得する
function find_talk_by_id($id) {
  $pdo = get_db_connection();

  $stmt = $pdo->prepare('SELECT * FROM talks
                         WHERE id=?');
  $stmt->setFetchMode(PDO::FETCH_CLASS, 'Talk');
  $stmt->execute(array($id));
  $event = $stmt->fetch(PDO::FETCH_CLASS);
  
  close_db_connection($pdo);

  $event->decode_json();

  return $event;
}

function fill_event_to_talk(&$talk) {
  $pdo = get_db_connection();

  $event = find_event_by_id($talk->event_id);
  if($event == NULL) {
    throw new Exception('event not found');
  }
  $talk->event = $event;
  
  close_db_connection($pdo);
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
  $stmt->setFetchMode(PDO::FETCH_CLASS, 'Talk');
  $stmt->execute(array($event_id, $limit, $offset));
  $talks = $stmt->fetchAll(PDO::FETCH_CLASS);
  
  close_db_connection($pdo);

  foreach ($talks as &$talk) {
    $talk->decode_json();
  }

  return $talks;
}

# talkを作成する
# event_id: 紐づくEventのID(Integer, required)
# array
#   - title: タイトル(String, required)
#   - team_name: チーム名(String, required)
#   - text_md: 概要(String, required)
#   - img_url: キャッチ画像(String, required)
#   - link[0-9]+: 関連リンク(String, required)
#   - member[0-9]+_name: メンバー名(String, required)
#   - member[0-9]+_post: メンバーの役職(String, required)
#
# 戻り値はID
function create_talk_with_event_id($event_id, $params) {
  $event = find_event_by_id($event_id);
  if($event == NULL) {
    throw new Exception('event not found');
  }

  dump($event);

  $links = array_filter($params, function($k) {
    return preg_match('/^link[0-9]+$/', $k);
  }, ARRAY_FILTER_USE_KEY);
  
  $members_name = array_filter($params, function($k) {
    return preg_match('/^member[0-9]+_name$/', $k);
  }, ARRAY_FILTER_USE_KEY);
  
  $members_role = array_filter($params, function($k) {
    return preg_match('/^member[0-9]+_role$/', $k);
  }, ARRAY_FILTER_USE_KEY);

  $members = array_map(function($name, $role) {
    return array("name" => $name, "role" => $role);
  }, $members_name, $members_role);
  
  $talk = new Talk;
  $talk->title = $params['title'];
  $talk->team_name = $params['team_name'];
  $talk->text_md = $params['text_md'];
  $talk->img_url = $params['img_url'];
  $talk->sequence = $event->sequence_max + 1;
  $talk->event_id = $event_id;
  $talk->passkey = $talk->generate_pass("abcdef");
  $talk->members = $members;
  $talk->links = $links;
  $talk->encode_json();
  $talk->update_times();

  try {
    $talk->is_valid();
  } catch (Exception $e) {
    fputs(STDOUT, $e->getMessage());
    fputs(STDOUT, var_dump($talk));
  }

  $pdo = get_db_connection();
  $pdo->beginTransaction();

  $stmt = $pdo->prepare('UPDATE events
      SET
        sequence_max=?
      WHERE
        id=?');
  $stmt->execute(array(
    $event->sequence_max+1,
    $event->id));

  $stmt2 = $pdo->prepare('INSERT INTO talks (
      title,
      team_name,
      members_json,
      text_md,
      links_json,
      passkey,
      img_url,
      status,
      sequence,
      event_id,
      created_at,
      updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt2->execute(array( #XXX: raw input
    $talk->title,
    $talk->team_name,
    $talk->members_json,
    $talk->text_md,
    $talk->links_json,
    $talk->passkey,
    $talk->img_url,
    $talk->status,
    $talk->sequence,
    $talk->event_id,
    $talk->created_at,
    $talk->updated_at));
  
  $id = $pdo->lastInsertId();
  if($pdo->commit() === FALSE) {
    close_db_connection($pdo);
    return -1;
  };
  
  close_db_connection($pdo);
  
  return $id;
}

# talkを更新する
# id: 更新するtalkのID(Integer, required)
# array
#   - title: タイトル(String, required)
#   - team_name: チーム名(String, required)
#   - text_md: 概要(String, required)
#   - img_url: キャッチ画像(String)
#   - link[0-9]+: 関連リンク(String)
#   - member[0-9]+_name: メンバー名(String, required)
#   - member[0-9]+_role: メンバーの役職(String, required)
#
# 戻り値はID
function update_talk_by_id($id, $params) {
  try {
    $talk = find_talk_by_id($id);
  } catch (Exception $e) {
    return -1;
  }

  $links = array_filter($params, function($k) {
    return preg_match('/^link[0-9]+$/', $k) === 0;
  }, ARRAY_FILTER_USE_KEY);
  
  $members_name = array_filter($params, function($k) {
    return preg_match('/^member[0-9]+_name$/', $k) === 0;
  }, ARRAY_FILTER_USE_KEY);
  
  $members_role = array_filter($params, function($k) {
    return preg_match('/^member[0-9]+_role$/', $k) === 0;
  }, ARRAY_FILTER_USE_KEY);

  $members = array_map(function($name, $role) {
    return array("name" => $name, "role" => $role);
  }, $members_name, $members_role);

  $talk->title = $params['title'];
  $talk->team_name = $params['team_name'];
  $talk->text_md = $params['text_md'];
  $talk->img_url = $params['img_url'];
  $talk->sequence = $params['title'];
  $talk->event_id = $event_id;
  $talk->members = $members;
  $talk->links = $links;
  $talk->encode_json();
  $talk->update_times();
  try {
    $talk->is_valid();
  } catch (Exception $e) {
    fputs(STDOUT, var_dump($talk));
  }
  $pdo = get_db_connection();

  $stmt = $pdo->prepare('INSERT INTO events (
      title,
      team_name,
      members_json,
      text_md,
      links_json,
      passkey,
      img_url,
      status,
      sequence,
      event_id,
      created_at,
      updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute(array( #XXX: raw input
    $talk->title,
    $talk->team_name,
    $talk->members_json,
    $talk->text_md,
    $talk->links_json,
    $talk->passkey,
    $talk->img_url,
    $talk->status,
    $event->sequence_max+1,
    $talk->event_id,
    $talk->created_at,
    $talk->updated_at));
  
  $id = $stmt->lastInsertId();
  
  close_db_connection($pdo);
  
  return $id;
}
