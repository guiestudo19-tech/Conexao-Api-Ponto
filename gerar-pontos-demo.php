<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
|--------------------------------------------------------------------------
| GERADOR DE PONTOS DEMO - TECHNOVA
|--------------------------------------------------------------------------
| Este arquivo cria registros falsos no banco db_ponto.
| Depois o importar-pontos.php do Meu Ponto Diário puxa esses dados pela API.
|--------------------------------------------------------------------------
*/

header("Content-Type: text/html; charset=utf-8");

/*
|--------------------------------------------------------------------------
| CONEXÃO COM O BANCO db_ponto
|--------------------------------------------------------------------------
*/

$tentativas = [
    ["localhost", "root", "usbw", "db_ponto"],
    ["127.0.0.1", "root", "usbw", "db_ponto"],
    ["localhost", "root", "", "db_ponto"],
    ["127.0.0.1", "root", "", "db_ponto"],
];

$con = null;

foreach ($tentativas as $t) {
    [$host, $user, $pass, $db] = $t;

    $con = @new mysqli($host, $user, $pass, $db);

    if (!$con->connect_error) {
        break;
    }

    $con = null;
}

if (!$con) {
    die("
        <h2>Erro ao conectar no banco db_ponto</h2>
        <p>Verifique se o USBWebServer está ligado e se o banco <strong>db_ponto</strong> existe.</p>
    ");
}

$con->set_charset("utf8mb4");

/*
|--------------------------------------------------------------------------
| CRIA A TABELA SE NÃO EXISTIR
|--------------------------------------------------------------------------
*/

$sqlTabela = "
CREATE TABLE IF NOT EXISTS registros_ponto (
    id INT(11) NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    data DATE NOT NULL,
    entrada TIME DEFAULT NULL,
    saida_intervalo TIME DEFAULT NULL,
    retorno_intervalo TIME DEFAULT NULL,
    saida TIME DEFAULT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_email (email),
    INDEX idx_data (data),
    INDEX idx_email_data (email, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
";

if (!$con->query($sqlTabela)) {
    die("Erro ao criar/verificar tabela: " . $con->error);
}

/*
|--------------------------------------------------------------------------
| FUNCIONÁRIOS DEMO
|--------------------------------------------------------------------------
| Esses e-mails precisam bater com os usuários do banco db_mpd.
| Exemplo: maria@technova.com.br precisa existir na tabela usuarios.
|--------------------------------------------------------------------------
*/

$funcionarios = [
    "maria@technova.com.br",
    "pedro@technova.com.br",
    "ana@technova.com.br",
    "joao@technova.com.br",
    "carla@technova.com.br",
    "lucas@technova.com.br",
    "bianca@technova.com.br",
    "rafael@technova.com.br",
    "juliana@technova.com.br",
    "felipe@technova.com.br"
];

/*
|--------------------------------------------------------------------------
| CONFIGURAÇÕES
|--------------------------------------------------------------------------
*/

$diasParaGerar = 7;
$registrosCriados = 0;
$registrosAtualizados = 0;
$registrosIgnorados = 0;

$dataHoje = new DateTime();

/*
|--------------------------------------------------------------------------
| FUNÇÃO PARA GERAR HORÁRIOS
|--------------------------------------------------------------------------
*/

function gerarHorarioAleatorio($baseHora, $minutoMin, $minutoMax) {
    $minuto = rand($minutoMin, $minutoMax);
    return sprintf("%02d:%02d:00", $baseHora, $minuto);
}

/*
|--------------------------------------------------------------------------
| PREPARES
|--------------------------------------------------------------------------
*/

$stmtExiste = $con->prepare("
    SELECT id 
    FROM registros_ponto
    WHERE email = ?
    AND data = ?
    LIMIT 1
");

if (!$stmtExiste) {
    die("Erro prepare existe: " . $con->error);
}

$stmtInsert = $con->prepare("
    INSERT INTO registros_ponto (
        email,
        data,
        entrada,
        saida_intervalo,
        retorno_intervalo,
        saida
    )
    VALUES (?, ?, ?, ?, ?, ?)
");

if (!$stmtInsert) {
    die("Erro prepare insert: " . $con->error);
}

$stmtUpdate = $con->prepare("
    UPDATE registros_ponto
    SET
        entrada = ?,
        saida_intervalo = ?,
        retorno_intervalo = ?,
        saida = ?
    WHERE id = ?
");

if (!$stmtUpdate) {
    die("Erro prepare update: " . $con->error);
}

/*
|--------------------------------------------------------------------------
| GERA REGISTROS DOS ÚLTIMOS DIAS
|--------------------------------------------------------------------------
*/

foreach ($funcionarios as $email) {

    for ($i = 0; $i < $diasParaGerar; $i++) {

        $data = clone $dataHoje;
        $data->modify("-$i days");

        $dataFormatada = $data->format("Y-m-d");

        /*
        Ignora domingo para parecer mais realista
        0 = domingo
        */
        if ($data->format("w") == 0) {
            $registrosIgnorados++;
            continue;
        }

        /*
        Horários aleatórios realistas
        */
        $entrada = gerarHorarioAleatorio(8, 0, 25);
        $saidaIntervalo = gerarHorarioAleatorio(12, 0, 10);
        $retornoIntervalo = gerarHorarioAleatorio(13, 0, 15);
        $saida = gerarHorarioAleatorio(17, 0, 35);

        /*
        Alguns casos com atraso
        */
        if (rand(1, 100) <= 22) {
            $entrada = gerarHorarioAleatorio(8, 35, 59);
        }

        /*
        Alguns casos incompletos, ainda em andamento
        */
        if (rand(1, 100) <= 8 && $i == 0) {
            $saida = null;
        }

        /*
        Verifica se já existe ponto do funcionário na data
        */
        $stmtExiste->bind_param("ss", $email, $dataFormatada);
        $stmtExiste->execute();

        $resultado = $stmtExiste->get_result();

        if ($resultado->num_rows > 0) {

            $registro = $resultado->fetch_assoc();
            $idRegistro = (int)$registro['id'];

            $stmtUpdate->bind_param(
                "ssssi",
                $entrada,
                $saidaIntervalo,
                $retornoIntervalo,
                $saida,
                $idRegistro
            );

            if ($stmtUpdate->execute()) {
                $registrosAtualizados++;
            }

        } else {

            $stmtInsert->bind_param(
                "ssssss",
                $email,
                $dataFormatada,
                $entrada,
                $saidaIntervalo,
                $retornoIntervalo,
                $saida
            );

            if ($stmtInsert->execute()) {
                $registrosCriados++;
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Gerar Pontos Demo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    min-height:100vh;
    background:
        radial-gradient(circle at top left, rgba(37,99,235,.18), transparent 35%),
        radial-gradient(circle at top right, rgba(14,165,233,.14), transparent 30%),
        #f8fbff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-family:Arial, sans-serif;
}

.card-demo{
    max-width:760px;
    width:100%;
    background:white;
    border:1px solid #dbeafe;
    border-radius:28px;
    padding:36px;
    box-shadow:0 24px 70px rgba(15,23,42,.12);
}

.icon-box{
    width:68px;
    height:68px;
    border-radius:22px;
    background:linear-gradient(135deg,#2563eb,#1e40af);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:34px;
    margin-bottom:20px;
}

.stat{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:18px;
    padding:20px;
    text-align:center;
}

.stat h3{
    font-weight:800;
    margin-bottom:4px;
}

code{
    background:#f1f5f9;
    color:#1d4ed8;
    display:block;
    padding:12px;
    border-radius:12px;
    margin-top:8px;
    word-break:break-all;
}
</style>
</head>

<body>

<div class="card-demo">

    <div class="icon-box">
        <i class="bi bi-clock-history"></i>
    </div>

    <h1 class="fw-bold mb-2">
        Pontos demo gerados
    </h1>

    <p class="text-muted mb-4">
        Registros criados no banco <strong>db_ponto</strong> usando e-mails
        <strong>@technova.com.br</strong>.
    </p>

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="stat">
                <h3 class="text-success"><?= $registrosCriados ?></h3>
                <small class="text-muted">Criados</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat">
                <h3 class="text-primary"><?= $registrosAtualizados ?></h3>
                <small class="text-muted">Atualizados</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat">
                <h3 class="text-warning"><?= $registrosIgnorados ?></h3>
                <small class="text-muted">Ignorados</small>
            </div>
        </div>

    </div>

    <div class="alert alert-info rounded-4">
        <strong>Importante:</strong>
        agora abra o importador no projeto principal para puxar esses registros para o banco
        <strong>db_mpd</strong>.
    </div>

    <p class="mb-1">
        API esperada:
    </p>

    <code>
        http://localhost/Conexao-Api-Ponto/api/pontos.php
    </code>

    <p class="mb-1 mt-3">
        Importador:
    </p>

    <code>
        http://localhost/Meu-Ponto-Diario/importar-pontos.php?api=1
    </code>

    <div class="d-flex flex-wrap gap-2 mt-4">

        <a href="gerar-pontos-demo.php" class="btn btn-primary">
            <i class="bi bi-arrow-repeat"></i>
            Gerar novamente
        </a>

        <a href="api/pontos.php" class="btn btn-outline-primary">
            <i class="bi bi-braces"></i>
            Ver JSON da API
        </a>

    </div>

</div>

</body>
</html>