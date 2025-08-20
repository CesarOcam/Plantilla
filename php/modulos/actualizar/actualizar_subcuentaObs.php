<?php
session_start();
include_once('../conexion.php');
header('Content-Type: application/json');

try {
    $partidaId = isset($_POST['obsPartidaId']) ? (int) $_POST['obsPartidaId'] : 0;
    $observaciones = isset($_POST['Observaciones']) ? trim($_POST['Observaciones']) : '';

    if($partidaId <= 0) throw new Exception('ID de partida inválido');

    $stmt = $con->prepare("UPDATE conta_partidaspolizas SET Observaciones = :obs WHERE Partida = :id");
    $stmt->bindValue(':obs', $observaciones, PDO::PARAM_STR);
    $stmt->bindValue(':id', $partidaId, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['status'=>'ok','message'=>'Observación actualizada correctamente']);

} catch(Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
