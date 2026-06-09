<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

$result = mysqli_query(
    $con,
    "SELECT * FROM registros_ponto ORDER BY data DESC, id DESC"
);

if (!$result) {
    die("Erro na consulta: " . mysqli_error($con));
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Registros de Ponto</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f7fb;
    padding: 30px;
}

.card {
    background: #fff;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,.08);
}

h1 {
    color: #1e40af;
}

a {
    color: #2563eb;
    font-weight: bold;
    text-decoration: none;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th {
    background: #2563eb;
    color: white;
    padding: 12px;
}

td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
}

tr:nth-child(even) {
    background: #f8fafc;
}
</style>
</head>

<body>

<div class="card">

    <h1>Registros do Sistema Externo</h1>

    <p>
        <a href="registrar_ponto.php">Registrar ponto</a> |
        <a href="api/pontos.php">Ver API JSON</a>
    </p>

    <table>
        <thead>
            <tr>
                <th>Email</th>
                <th>Data</th>
                <th>Entrada</th>
                <th>Saída Almoço</th>
                <th>Retorno Almoço</th>
                <th>Saída</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['data']))) ?></td>
                    <td><?= htmlspecialchars($row['entrada'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['saida_almoco'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['retorno_almoco'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['saida'] ?? '-') ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>

</body>
</html>