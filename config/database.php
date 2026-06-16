<?php

mysqli_report(MYSQLI_REPORT_OFF);

$con = mysqli_connect(
    'localhost',
    'root',
    'usbw',
    'db_ponto'
);

if (!$con) {
    http_response_code(500);

    /*
     * Quando o arquivo for carregado pela API,
     * a resposta continuará sendo JSON.
     */
    $aceitaJson =
        isset($_SERVER['HTTP_ACCEPT']) &&
        str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

    if ($aceitaJson) {
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            [
                'erro' => true,
                'mensagem' => 'Erro ao conectar com o banco de dados.',
                'detalhes' => mysqli_connect_error()
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    die(
        'Erro ao conectar com o banco de dados: ' .
        htmlspecialchars(mysqli_connect_error())
    );
}

if (!mysqli_set_charset($con, 'utf8mb4')) {
    die(
        'Erro ao configurar UTF-8: ' .
        htmlspecialchars(mysqli_error($con))
    );
}