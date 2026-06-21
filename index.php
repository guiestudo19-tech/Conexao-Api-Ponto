<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/config/database.php';

$result = mysqli_query(
    $con,
    "
        SELECT
            id,
            email,
            data,
            entrada,
            saida_intervalo,
            retorno_intervalo,
            saida
        FROM registros_ponto
        ORDER BY data DESC, id DESC
    "
);

if (!$result) {
    die('Erro na consulta: ' . mysqli_error($con));
}

function formatarHora(?string $hora): string
{
    if (empty($hora) || $hora === '00:00:00') {
        return '-';
    }

    return substr($hora, 0, 5);
}

function formatarData(?string $data): string
{
    if (empty($data)) {
        return '-';
    }

    return date('d/m/Y', strtotime($data));
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Registros de Ponto</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 30px;
            color: #1f2937;
        }

        .card {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background: #ffffff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        }

        h1 {
            color: #1e40af;
            margin-top: 0;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            margin-top: 0;
            margin-bottom: 22px;
        }

        .links {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .links a {
            display: inline-block;
            color: #ffffff;
            background: #2563eb;
            padding: 11px 16px;
            border-radius: 9px;
            font-weight: bold;
            text-decoration: none;
        }

        .links a:hover {
            background: #1d4ed8;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            background: #2563eb;
            color: #ffffff;
            padding: 13px 12px;
            text-align: left;
            white-space: nowrap;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #eff6ff;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #64748b;
        }

        .total {
            margin-top: 18px;
            color: #64748b;
            font-size: 14px;
        }

        @media (max-width: 600px) {

            body {
                padding: 15px;
            }

            .card {
                padding: 18px;
            }

            h1 {
                font-size: 24px;
            }
        }

    </style>

</head>

<body>

<div class="card">

    <h1>Registros do Sistema Externo</h1>

    <p class="subtitle">
        Registros armazenados no banco db_ponto.
    </p>

    <div class="links">

        <a href="registrar_ponto.php">
            Registrar ponto
        </a>

        <a href="api/pontos.php" target="_blank">
            Ver API JSON
        </a>

    </div>

    <div class="table-responsive">

        <table>

            <thead>

                <tr>
                    <th>ID</th>
                    <th>E-mail</th>
                    <th>Data</th>
                    <th>Entrada</th>
                    <th>Saída do intervalo</th>
                    <th>Retorno do intervalo</th>
                    <th>Saída</th>
                </tr>

            </thead>

            <tbody>

                <?php if (mysqli_num_rows($result) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <td>
                                <?= (int) $row['id'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row['email'] ?? ''
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    formatarData($row['data'] ?? null)
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    formatarHora($row['entrada'] ?? null)
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    formatarHora(
                                        $row['saida_intervalo'] ?? null
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    formatarHora(
                                        $row['retorno_intervalo'] ?? null
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    formatarHora($row['saida'] ?? null)
                                ) ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7" class="empty">
                            Nenhum registro de ponto encontrado.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <div class="total">

        Total de registros:

        <strong>
            <?= mysqli_num_rows($result) ?>
        </strong>

    </div>

</div>

</body>

</html>