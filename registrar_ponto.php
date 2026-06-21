<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/config/database.php';

$mensagem = '';
$erro = '';

$emailSelecionado = trim($_POST['email'] ?? '');
$dataSelecionada = $_POST['data'] ?? date('Y-m-d');
$horarioSelecionado = $_POST['horario'] ?? '';

$acoesPermitidas = [
    'entrada' => 'Entrada',
    'saida_intervalo' => 'Saída para intervalo',
    'retorno_intervalo' => 'Retorno do intervalo',
    'saida' => 'Saída'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $data = trim($_POST['data'] ?? '');
    $horario = trim($_POST['horario'] ?? '');
    $acao = trim($_POST['acao'] ?? '');

    if ($email === '') {

        $erro = 'Digite o e-mail do funcionário.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = 'Digite um e-mail válido.';

    } elseif ($data === '') {

        $erro = 'Escolha a data do registro.';

    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {

        $erro = 'A data informada é inválida.';

    } elseif ($horario === '') {

        $erro = 'Escolha o horário.';

    } elseif (!preg_match('/^\d{2}:\d{2}$/', $horario)) {

        $erro = 'O horário informado é inválido.';

    } elseif (!array_key_exists($acao, $acoesPermitidas)) {

        $erro = 'Ação inválida.';

    } else {

        $emailSelecionado = $email;
        $dataSelecionada = $data;
        $horarioSelecionado = $horario;

        $horarioBanco = $horario . ':00';

        /*
        |--------------------------------------------------------------------------
        | BUSCA REGISTRO DO FUNCIONÁRIO NA DATA
        |--------------------------------------------------------------------------
        */

        $stmtBusca = $con->prepare("
            SELECT
                id,
                email,
                data,
                entrada,
                saida_intervalo,
                retorno_intervalo,
                saida
            FROM registros_ponto
            WHERE email = ?
              AND data = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        if (!$stmtBusca) {
            die('Erro ao preparar busca: ' . $con->error);
        }

        $stmtBusca->bind_param(
            'ss',
            $email,
            $data
        );

        $stmtBusca->execute();

        $registro = $stmtBusca
            ->get_result()
            ->fetch_assoc();

        $stmtBusca->close();

        /*
        |--------------------------------------------------------------------------
        | CRIA REGISTRO DO DIA CASO NÃO EXISTA
        |--------------------------------------------------------------------------
        */

        if (!$registro) {

            $stmtInsert = $con->prepare("
                INSERT INTO registros_ponto (
                    email,
                    data
                )
                VALUES (?, ?)
            ");

            if (!$stmtInsert) {
                die('Erro ao preparar inserção: ' . $con->error);
            }

            $stmtInsert->bind_param(
                'ss',
                $email,
                $data
            );

            if ($stmtInsert->execute()) {

                $idRegistro = (int) $con->insert_id;

                $registro = [
                    'id' => $idRegistro,
                    'email' => $email,
                    'data' => $data,
                    'entrada' => null,
                    'saida_intervalo' => null,
                    'retorno_intervalo' => null,
                    'saida' => null
                ];

            } else {

                $erro = 'Erro ao criar o registro do dia: ' .
                    $stmtInsert->error;
            }

            $stmtInsert->close();

        } else {

            $idRegistro = (int) $registro['id'];
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDA A ORDEM DOS REGISTROS
        |--------------------------------------------------------------------------
        */

        if ($erro === '') {

            $valorAtual = $registro[$acao] ?? null;

            if (!empty($valorAtual)) {

                $erro =
                    $acoesPermitidas[$acao] .
                    ' já foi registrada em ' .
                    date('d/m/Y', strtotime($data)) .
                    ' às ' .
                    date('H:i', strtotime($valorAtual)) .
                    '.';

            } elseif (
                $acao === 'saida_intervalo' &&
                empty($registro['entrada'])
            ) {

                $erro = 'Registre a entrada antes da saída para intervalo.';

            } elseif (
                $acao === 'retorno_intervalo' &&
                empty($registro['saida_intervalo'])
            ) {

                $erro = 'Registre a saída para intervalo antes do retorno.';

            } elseif (
                $acao === 'saida' &&
                empty($registro['entrada'])
            ) {

                $erro = 'Registre a entrada antes da saída.';

            } elseif (
                $acao === 'saida' &&
                !empty($registro['saida_intervalo']) &&
                empty($registro['retorno_intervalo'])
            ) {

                $erro = 'Registre o retorno do intervalo antes da saída.';

            }
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDA OS HORÁRIOS
        |--------------------------------------------------------------------------
        */

        if ($erro === '') {

            if (
                $acao === 'saida_intervalo' &&
                !empty($registro['entrada']) &&
                strtotime($horarioBanco) <= strtotime($registro['entrada'])
            ) {

                $erro = 'A saída para intervalo deve ser depois da entrada.';

            } elseif (
                $acao === 'retorno_intervalo' &&
                !empty($registro['saida_intervalo']) &&
                strtotime($horarioBanco) <=
                strtotime($registro['saida_intervalo'])
            ) {

                $erro = 'O retorno deve ser depois da saída para intervalo.';

            } elseif (
                $acao === 'saida' &&
                !empty($registro['retorno_intervalo']) &&
                strtotime($horarioBanco) <=
                strtotime($registro['retorno_intervalo'])
            ) {

                $erro = 'A saída deve ser depois do retorno do intervalo.';

            } elseif (
                $acao === 'saida' &&
                empty($registro['retorno_intervalo']) &&
                !empty($registro['entrada']) &&
                strtotime($horarioBanco) <= strtotime($registro['entrada'])
            ) {

                $erro = 'A saída deve ser depois da entrada.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | ATUALIZA O CAMPO ESCOLHIDO
        |--------------------------------------------------------------------------
        |
        | O nome da coluna não pode ser enviado diretamente pelo usuário.
        | Ele só é aceito porque foi validado no array $acoesPermitidas.
        |
        */

        if ($erro === '') {

            $sqlUpdate = "
                UPDATE registros_ponto
                SET {$acao} = ?
                WHERE id = ?
            ";

            $stmtUpdate = $con->prepare($sqlUpdate);

            if (!$stmtUpdate) {
                die('Erro ao preparar atualização: ' . $con->error);
            }

            $stmtUpdate->bind_param(
                'si',
                $horarioBanco,
                $idRegistro
            );

            if ($stmtUpdate->execute()) {

                $mensagem =
                    $acoesPermitidas[$acao] .
                    ' registrada com sucesso em ' .
                    date('d/m/Y', strtotime($data)) .
                    ' às ' .
                    htmlspecialchars($horario) .
                    '.';

                $horarioSelecionado = '';

            } else {

                $erro = 'Erro ao registrar o ponto: ' .
                    $stmtUpdate->error;
            }

            $stmtUpdate->close();
        }
    }
}

/*
|--------------------------------------------------------------------------
| BUSCA O REGISTRO SELECIONADO PARA EXIBIR NA TELA
|--------------------------------------------------------------------------
*/

$registroAtual = null;

if (
    $emailSelecionado !== '' &&
    filter_var($emailSelecionado, FILTER_VALIDATE_EMAIL) &&
    $dataSelecionada !== ''
) {

    $stmtAtual = $con->prepare("
        SELECT
            id,
            email,
            data,
            entrada,
            saida_intervalo,
            retorno_intervalo,
            saida
        FROM registros_ponto
        WHERE email = ?
          AND data = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($stmtAtual) {

        $stmtAtual->bind_param(
            'ss',
            $emailSelecionado,
            $dataSelecionada
        );

        $stmtAtual->execute();

        $registroAtual = $stmtAtual
            ->get_result()
            ->fetch_assoc();

        $stmtAtual->close();
    }
}

function formatarHorario(?string $horario): string
{
    if (empty($horario)) {
        return '--:--';
    }

    return substr($horario, 0, 5);
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

    <title>Registrar Ponto</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        body {
            min-height: 100vh;
            margin: 0;
            background: #f4f7fb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            padding: 25px;
        }

        .ponto-card {
            width: 100%;
            max-width: 540px;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, .08);
            padding: 32px;
        }

        .titulo {
            color: #1e40af;
            font-weight: 700;
        }

        .data-atual {
            background: #eff6ff;
            color: #1e40af;
            padding: 12px 15px;
            border-radius: 12px;
            font-weight: 600;
        }

        .btn-ponto {
            min-height: 55px;
            font-weight: 600;
        }

        .registro-resumo {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
        }

        .registro-item {
            display: flex;
            justify-content: space-between;
            padding: 7px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .registro-item:last-child {
            border-bottom: 0;
        }

    </style>

</head>

<body>

<div class="ponto-card">

    <div class="mb-4">

        <h1 class="titulo h3 mb-2">
            <i class="bi bi-clock-history me-2"></i>
            Registrar Ponto
        </h1>

        <p class="text-muted mb-0">
            Registre manualmente os horários do funcionário.
        </p>

    </div>

    <div class="data-atual mb-4">

        <i class="bi bi-calendar3 me-2"></i>

        Data atual:

        <?= date('d/m/Y') ?>

    </div>

    <?php if ($mensagem !== ''): ?>

        <div class="alert alert-success rounded-3">

            <i class="bi bi-check-circle me-2"></i>

            <?= htmlspecialchars($mensagem) ?>

        </div>

    <?php endif; ?>

    <?php if ($erro !== ''): ?>

        <div class="alert alert-danger rounded-3">

            <i class="bi bi-exclamation-triangle me-2"></i>

            <?= htmlspecialchars($erro) ?>

        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">

            <label class="form-label fw-semibold">
                E-mail
            </label>

            <input
                type="email"
                name="email"
                class="form-control form-control-lg"
                placeholder="funcionario@email.com"
                value="<?= htmlspecialchars($emailSelecionado) ?>"
                required
            >

        </div>

        <div class="mb-3">

            <label class="form-label fw-semibold">
                Data do registro
            </label>

            <input
                type="date"
                name="data"
                class="form-control form-control-lg"
                value="<?= htmlspecialchars($dataSelecionada) ?>"
                required
            >

        </div>

        <div class="mb-4">

            <label class="form-label fw-semibold">
                Horário do registro
            </label>

            <input
                type="time"
                name="horario"
                class="form-control form-control-lg"
                value="<?= htmlspecialchars($horarioSelecionado) ?>"
                required
            >

        </div>

        <div class="row g-2">

            <div class="col-6">

                <button
                    type="submit"
                    name="acao"
                    value="entrada"
                    class="btn btn-primary btn-ponto w-100"
                >
                    <i class="bi bi-box-arrow-in-right me-1"></i>
                    Entrada
                </button>

            </div>

            <div class="col-6">

                <button
                    type="submit"
                    name="acao"
                    value="saida_intervalo"
                    class="btn btn-primary btn-ponto w-100"
                >
                    <i class="bi bi-cup-hot me-1"></i>
                    Saída Intervalo
                </button>

            </div>

            <div class="col-6">

                <button
                    type="submit"
                    name="acao"
                    value="retorno_intervalo"
                    class="btn btn-primary btn-ponto w-100"
                >
                    <i class="bi bi-arrow-return-left me-1"></i>
                    Retorno Intervalo
                </button>

            </div>

            <div class="col-6">

                <button
                    type="submit"
                    name="acao"
                    value="saida"
                    class="btn btn-primary btn-ponto w-100"
                >
                    <i class="bi bi-box-arrow-right me-1"></i>
                    Saída
                </button>

            </div>

        </div>

    </form>

    <?php if ($registroAtual): ?>

        <div class="registro-resumo mt-4">

            <h2 class="h6 fw-bold text-primary mb-3">
                Registro do dia
            </h2>

            <div class="registro-item">

                <span>Entrada</span>

                <strong>
                    <?= formatarHorario($registroAtual['entrada']) ?>
                </strong>

            </div>

            <div class="registro-item">

                <span>Saída do intervalo</span>

                <strong>
                    <?= formatarHorario(
                        $registroAtual['saida_intervalo']
                    ) ?>
                </strong>

            </div>

            <div class="registro-item">

                <span>Retorno do intervalo</span>

                <strong>
                    <?= formatarHorario(
                        $registroAtual['retorno_intervalo']
                    ) ?>
                </strong>

            </div>

            <div class="registro-item">

                <span>Saída</span>

                <strong>
                    <?= formatarHorario($registroAtual['saida']) ?>
                </strong>

            </div>

        </div>

    <?php endif; ?>

    <div class="text-center mt-4">

        <a
            href="index.php"
            class="text-decoration-none fw-semibold me-3"
        >
            Ver registros
        </a>

        <a
            href="api/pontos.php"
            class="text-decoration-none fw-semibold"
        >
            Ver API
        </a>

    </div>

</div>

</body>

</html>