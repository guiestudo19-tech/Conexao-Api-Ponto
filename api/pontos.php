<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

$sql = "
    SELECT
        email,
        data,
        entrada,
        saida_almoco,
        retorno_almoco,
        saida
    FROM registros_ponto
    ORDER BY data DESC, id DESC
";

$result = mysqli_query($con, $sql);

if (!$result) {
    http_response_code(500);

    echo json_encode([
        "erro" => true,
        "mensagem" => mysqli_error($con)
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$dados = [];

while ($row = mysqli_fetch_assoc($result)) {
    $dados[] = $row;
}

echo json_encode($dados, JSON_UNESCAPED_UNICODE);