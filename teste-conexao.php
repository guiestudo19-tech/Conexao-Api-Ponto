<?php

$tentativas = [
    ["127.0.0.1", "root", "", "db_ponto"],
    ["localhost", "root", "", "db_ponto"],
    ["127.0.0.1", "root", "usbw", "db_ponto"],
    ["localhost", "root", "usbw", "db_ponto"],
];

foreach ($tentativas as $t) {
    [$host, $user, $pass, $db] = $t;

    $con = @mysqli_connect($host, $user, $pass, $db);

    echo "<hr>";
    echo "Testando: Host=$host | User=$user | Senha=" . ($pass === "" ? "vazia" : $pass) . " | Banco=$db<br>";

    if ($con) {
        echo "<strong style='color:green'>CONECTOU!</strong>";
        exit;
    } else {
        echo "<span style='color:red'>" . mysqli_connect_error() . "</span>";
    }
}