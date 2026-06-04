<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email'];
    $data = $_POST['data'];
    $entrada = $_POST['entrada'];
    $saida_almoco = $_POST['saida_almoco'];
    $retorno_almoco = $_POST['retorno_almoco'];
    $saida = $_POST['saida'];

    $stmt = $conn->prepare("
        INSERT INTO registros_ponto
        (
            email,
            data,
            entrada,
            saida_almoco,
            retorno_almoco,
            saida
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssss",
        $email,
        $data,
        $entrada,
        $saida_almoco,
        $retorno_almoco,
        $saida
    );

    $stmt->execute();

    echo "Registro salvo!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrar Ponto</title>
</head>
<body>

<h2>Registrar Ponto</h2>

<form method="POST">

    <label>Email</label><br>
    <input type="email" name="email" required><br><br>

    <label>Data</label><br>
    <input type="date" name="data" required><br><br>

    <label>Entrada</label><br>
    <input type="time" name="entrada"><br><br>

    <label>Saída Almoço</label><br>
    <input type="time" name="saida_almoco"><br><br>

    <label>Retorno Almoço</label><br>
    <input type="time" name="retorno_almoco"><br><br>

    <label>Saída</label><br>
    <input type="time" name="saida"><br><br>

    <button type="submit">
        Salvar
    </button>

</form>

</body>
</html>