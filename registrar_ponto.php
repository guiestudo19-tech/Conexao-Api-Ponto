<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

$mensagem = '';
$erro = '';

$dataSelecionada = $_POST['data'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $data = $_POST['data'] ?? date('Y-m-d');
    $horario = $_POST['horario'] ?? '';
    $acao = $_POST['acao'] ?? '';

    $acoesPermitidas = [
        'entrada' => 'Entrada',
        'saida_almoco' => 'Saída para almoço',
        'retorno_almoco' => 'Retorno do almoço',
        'saida' => 'Saída'
    ];

    if ($email === '') {
        $erro = 'Digite o e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';
    } elseif ($data === '') {
        $erro = 'Escolha a data.';
    } elseif ($horario === '') {
        $erro = 'Escolha o horário.';
    } elseif (!array_key_exists($acao, $acoesPermitidas)) {
        $erro = 'Ação inválida.';
    } else {

        $dataSelecionada = $data;
        $horarioBanco = $horario . ':00';

        $stmtBusca = $con->prepare("
            SELECT *
            FROM registros_ponto
            WHERE email = ?
            AND data = ?
            LIMIT 1
        ");

        if (!$stmtBusca) {
            die("Erro prepare busca: " . $con->error);
        }

        $stmtBusca->bind_param("ss", $email, $data);
        $stmtBusca->execute();

        $registro = $stmtBusca->get_result()->fetch_assoc();

        if (!$registro) {

            $stmtInsert = $con->prepare("
                INSERT INTO registros_ponto (
                    email,
                    data
                )
                VALUES (?, ?)
            ");

            if (!$stmtInsert) {
                die("Erro prepare insert: " . $con->error);
            }

            $stmtInsert->bind_param("ss", $email, $data);

            if (!$stmtInsert->execute()) {
                $erro = "Erro ao criar registro do dia: " . $stmtInsert->error;
            }

            $idRegistro = $con->insert_id;

        } else {
            $idRegistro = $registro['id'];
        }

        if (!$erro) {

            $stmtVerifica = $con->prepare("
                SELECT $acao
                FROM registros_ponto
                WHERE id = ?
            ");

            if (!$stmtVerifica) {
                die("Erro prepare verificação: " . $con->error);
            }

            $stmtVerifica->bind_param("i", $idRegistro);
            $stmtVerifica->execute();

            $valorAtual = $stmtVerifica->get_result()->fetch_assoc()[$acao] ?? null;

            if (!empty($valorAtual)) {

                $erro = $acoesPermitidas[$acao] . " já foi registrada em " . date('d/m/Y', strtotime($data)) . " às " . date('H:i', strtotime($valorAtual)) . ".";

            } else {

                $sqlUpdate = "
                    UPDATE registros_ponto
                    SET $acao = ?
                    WHERE id = ?
                ";

                $stmtUpdate = $con->prepare($sqlUpdate);

                if (!$stmtUpdate) {
                    die("Erro prepare update: " . $con->error);
                }

                $stmtUpdate->bind_param("si", $horarioBanco, $idRegistro);

                if ($stmtUpdate->execute()) {
                    $mensagem = $acoesPermitidas[$acao] . " registrada com sucesso em " . date('d/m/Y', strtotime($data)) . " às " . htmlspecialchars($horario) . ".";
                } else {
                    $erro = "Erro ao registrar ponto: " . $stmtUpdate->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Registrar Ponto</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f7fb;
    min-height: 100vh;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card {
    background: white;
    width: 450px;
    padding: 32px;
    border-radius: 18px;
    box-shadow: 0 15px 40px rgba(0,0,0,.08);
}

h1 {
    margin-top: 0;
    color: #1e40af;
}

.data-hoje {
    background: #eff6ff;
    color: #1e40af;
    padding: 12px;
    border-radius: 12px;
    font-weight: bold;
    margin-bottom: 18px;
}

label {
    font-weight: bold;
}

input {
    width: 100%;
    padding: 13px;
    margin: 10px 0 18px;
    border: 1px solid #ddd;
    border-radius: 10px;
    box-sizing: border-box;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

button {
    border: none;
    background: #2563eb;
    color: white;
    padding: 14px;
    border-radius: 10px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #1d4ed8;
}

button.saida {
    background: #0f172a;
}

button.almoco {
    background: #f59e0b;
}

button.retorno {
    background: #16a34a;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
}

.links {
    margin-top: 18px;
    text-align: center;
}

.links a {
    color: #2563eb;
    text-decoration: none;
    font-weight: bold;
}
</style>
</head>

<body>

<div class="card">

    <h1>Registrar Ponto</h1>

    <div class="data-hoje">
        Data atual: <?= date('d/m/Y') ?>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert-success"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert-error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST">

        <label>E-mail</label>
        <input type="email" name="email" placeholder="funcionario@email.com" required>

        <label>Data do registro</label>
        <input type="date" name="data" value="<?= htmlspecialchars($dataSelecionada) ?>" required>

        <label>Horário do registro</label>
        <input type="time" name="horario" required>

        <div class="grid">
            <button type="submit" name="acao" value="entrada">
                Entrada
            </button>

            <button type="submit" name="acao" value="saida_almoco" class="almoco">
                Saída Almoço
            </button>

            <button type="submit" name="acao" value="retorno_almoco" class="retorno">
                Retorno Almoço
            </button>

            <button type="submit" name="acao" value="saida" class="saida">
                Saída
            </button>
        </div>

    </form>

    <div class="links">
        <a href="index.php">Ver registros</a> |
        <a href="api/pontos.php">Ver API</a>
    </div>

</div>

</body>
</html>