<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
  require_once'../../../library/config.php';
  include('../../../library/function.php');
  require_once'../../login/user.php';

switch (@$_GET['action']){
  // --- Kriteria PIP API ---
  case 'get_kriteria':
    $id = !empty($_GET['id']) ? $_GET['id'] : null;
    if(function_exists('convert')){
      $maybe = @convert('decrypt', $id);
      if($maybe) $id = $maybe;
    }
    $id_clean = $connection->real_escape_string($id);
    $q = $connection->query("SELECT * FROM kriteria_pip WHERE id='$id_clean' LIMIT 1");
    header('Content-Type: application/json; charset=utf-8');
    if($q && $q->num_rows){
      echo json_encode(['success'=>true,'data'=>$q->fetch_assoc()]);
    } else {
      http_response_code(404);
      echo json_encode(['success'=>false,'message'=>'Not found']);
    }
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
    $nama_c = $connection->real_escape_string($nama);
    $deskripsi_c = $connection->real_escape_string($deskripsi);
    $poin_c = $connection->real_escape_string($poin);

    if($rawId === '' || $rawId === null){
      // insert
      $ins = "INSERT INTO kriteria_pip (nama_kriteria, deskripsi, poin) VALUES ('$nama_c', '$deskripsi_c', '$poin_c')";
      header('Content-Type: application/json; charset=utf-8');
      if($connection->query($ins) === false){
        echo json_encode(['success'=>false,'message'=>$connection->error]);
      } else {
        echo json_encode(['success'=>true,'id'=>$connection->insert_id]);
      }
      exit;
    } else {
      // update
      $id_clean = $connection->real_escape_string($rawId);
      $upd = "UPDATE kriteria_pip SET nama_kriteria='$nama_c', deskripsi='$deskripsi_c', poin='$poin_c' WHERE id='$id_clean'";
      header('Content-Type: application/json; charset=utf-8');
      if($connection->query($upd) === false){
        echo json_encode(['success'=>false,'message'=>$connection->error]);
      } else {
        echo json_encode(['success'=>true]);
      }
      exit;
    }

  case 'delete_kriteria':
    $id = !empty($_POST['id']) ? htmlentities($_POST['id']) : null;
    $rawId = $id;
    if(function_exists('convert')){
      $maybe = @convert('decrypt', $id);
      if($maybe) $rawId = $maybe;
    }
    $id_clean = $connection->real_escape_string($rawId);
    $del = "DELETE FROM kriteria_pip WHERE id='$id_clean'";
    header('Content-Type: application/json; charset=utf-8');
    if($connection->query($del) === false){
      echo json_encode(['success' => false, 'message' => $connection->error]);
    } else {
      echo json_encode(['success' => true]);
    }
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