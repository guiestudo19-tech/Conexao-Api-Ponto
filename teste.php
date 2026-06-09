<?php

$con = mysqli_connect(
    "localhost",
    "root",
    "",
    "mysql"
);

if ($con) {
    echo "Conectou!";
} else {
    echo mysqli_connect_error();
}