<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "root",
    "db_ponto"
);

if (!$conn) {
    die("Erro na conexão: " . mysqli_connect_error());
}