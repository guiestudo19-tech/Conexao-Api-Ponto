<?php

header('Content-Type: application/json');

require '../config/database.php';

$sql = "
SELECT
    email,
    data,
    entrada,
    saida_almoco,
    retorno_almoco,
    saida
FROM registros_ponto
ORDER BY data DESC
";

$result = mysqli_query($conn, $sql);

$dados = [];

while ($row = mysqli_fetch_assoc($result)) {
    $dados[] = $row;
}

echo json_encode($dados);