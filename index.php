<?php

require 'config/database.php';

$result = mysqli_query(
    $conn,
    "SELECT * FROM registros_ponto ORDER BY data DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registros de Ponto</title>
</head>
<body>

<h2>Registros</h2>

<table border="1">

<tr>
    <th>Email</th>
    <th>Data</th>
    <th>Entrada</th>
    <th>Saída Almoço</th>
    <th>Retorno</th>
    <th>Saída</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)): ?>

<tr>
    <td><?= $row['email'] ?></td>
    <td><?= $row['data'] ?></td>
    <td><?= $row['entrada'] ?></td>
    <td><?= $row['saida_almoco'] ?></td>
    <td><?= $row['retorno_almoco'] ?></td>
    <td><?= $row['saida'] ?></td>
</tr>

<?php endwhile; ?>

</table>

</body>
</html>