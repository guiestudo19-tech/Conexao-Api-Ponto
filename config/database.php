<?php

$con = mysqli_connect(
    "localhost",
    "root",
    "usbw",
    "db_ponto"
);

if (!$con) {
    die("Erro na conexão: " . mysqli_connect_error());
}

mysqli_set_charset($con, "utf8mb4");