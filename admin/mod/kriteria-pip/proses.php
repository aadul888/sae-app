<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
  require_once'../../../library/config.php';
  require_once('../../../library/function.php');
  require_once'../../login/user.php';

switch (@$_GET['action']){
  // --- Kriteria PIP API ---
  case 'get_kriteria':
    $id = !empty($_GET['id']) ? $_GET['id'] : null;
    if(function_exists('convert')){
      $maybe = @convert('decrypt', $id);
      if($maybe) $id = $maybe;
    }
    $stmt = $connection->prepare("SELECT * FROM kriteria_pip WHERE id=? LIMIT 1");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $q = $stmt->get_result();
    header('Content-Type: application/json; charset=utf-8');
    if($q && $q->num_rows){
      echo json_encode(['success'=>true,'data'=>$q->fetch_assoc()]);
    } else {
      http_response_code(404);
      echo json_encode(['success'=>false,'message'=>'Not found']);
    }
    $stmt->close();
    exit;

  case 'save_kriteria':
    // handle both insert and update
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $rawId = $id;
    if(function_exists('convert') && $id !== ''){
      $maybe = @convert('decrypt', $id);
      if($maybe) $rawId = $maybe;
    }
    $nama = isset($_POST['nama_kriteria']) ? trim($_POST['nama_kriteria']) : '';
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
    $poin = isset($_POST['poin']) ? trim($_POST['poin']) : '0';

    if($rawId === '' || $rawId === null){
      // insert
      $stmt = $connection->prepare("INSERT INTO kriteria_pip (nama_kriteria, deskripsi, poin) VALUES (?, ?, ?)");
      $stmt->bind_param('sss', $nama, $deskripsi, $poin);
      header('Content-Type: application/json; charset=utf-8');
      if($stmt->execute() === false){
        echo json_encode(['success'=>false,'message'=>$stmt->error]);
      } else {
        echo json_encode(['success'=>true,'id'=>$connection->insert_id]);
      }
      $stmt->close();
      exit;
    } else {
      // update
      $stmt = $connection->prepare("UPDATE kriteria_pip SET nama_kriteria=?, deskripsi=?, poin=? WHERE id=?");
      $stmt->bind_param('ssss', $nama, $deskripsi, $poin, $rawId);
      header('Content-Type: application/json; charset=utf-8');
      if($stmt->execute() === false){
        echo json_encode(['success'=>false,'message'=>$stmt->error]);
      } else {
        echo json_encode(['success'=>true]);
      }
      $stmt->close();
      exit;
    }

  case 'delete_kriteria':
    $id = !empty($_POST['id']) ? htmlentities($_POST['id']) : null;
    $rawId = $id;
    if(function_exists('convert')){
      $maybe = @convert('decrypt', $id);
      if($maybe) $rawId = $maybe;
    }
    $stmt = $connection->prepare("DELETE FROM kriteria_pip WHERE id=?");
    $stmt->bind_param('s', $rawId);
    header('Content-Type: application/json; charset=utf-8');
    if($stmt->execute() === false){
      echo json_encode(['success' => false, 'message' => $stmt->error]);
    } else {
      echo json_encode(['success' => true]);
    }
    $stmt->close();
    exit;

  case 'list_kriteria':
    // Return all kriteria (id and nama_kriteria)
    $res = $connection->query("SELECT id, nama_kriteria FROM kriteria_pip ORDER BY id ASC");
    $out = [];
    if($res){
      while($row = $res->fetch_assoc()) $out[] = $row;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'data' => $out]);
    exit;
}
}