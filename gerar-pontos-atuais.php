<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Sao_Paulo');

/* =========================================================
   CONEXÃO COM O BANCO db_ponto
========================================================= */

/*
 * Este arquivo conecta ao banco db_ponto
 * e cria a variável $con.
 */
require_once __DIR__ . '/config/database.php';

if (!isset($con) || !($con instanceof mysqli)) {
    die('A conexão com o banco db_ponto não foi criada.');
}

/* =========================================================
   CONEXÃO COM O BANCO PRINCIPAL db_mpd
========================================================= */

$conPrincipal = new mysqli(
    'localhost',
    'root',
    'usbw',
    'db_mpd'
);

if ($conPrincipal->connect_error) {
    die(
        'Erro ao conectar ao banco db_mpd: ' .
        $conPrincipal->connect_error
    );
}

$con->set_charset('utf8mb4');
$conPrincipal->set_charset('utf8mb4');

$con->query("SET time_zone = '-03:00'");
$conPrincipal->query("SET time_zone = '-03:00'");

/* =========================================================
   CONFIGURAÇÕES
========================================================= */

$idEmpresa = 5;

/*
 * true:
 * Atualiza registros que já existirem
 * para o mesmo e-mail e data.
 *
 * false:
 * Ignora registros já existentes.
 */
$atualizarExistentes = true;

/* =========================================================
   FUNÇÕES
========================================================= */

function criarHorario(
    int $hora,
    int $minutoInicial,
    int $minutoFinal
): string {
    $minuto = random_int(
        $minutoInicial,
        $minutoFinal
    );

    return sprintf(
        '%02d:%02d:00',
        $hora,
        $minuto
    );
}

function adicionarMinutos(
    string $horario,
    int $minutos
): string {
    $dataHorario = DateTime::createFromFormat(
        'H:i:s',
        $horario
    );

    if (!$dataHorario) {
        return $horario;
    }

    $dataHorario->modify("+{$minutos} minutes");

    return $dataHorario->format('H:i:s');
}

function calcularDiferencaMinutos(
    string $inicio,
    string $fim
): int {
    $inicioTimestamp = strtotime($inicio);
    $fimTimestamp = strtotime($fim);

    if (
        $inicioTimestamp === false ||
        $fimTimestamp === false
    ) {
        return 0;
    }

    return (int) round(
        ($fimTimestamp - $inicioTimestamp) / 60
    );
}

function gerarHorariosDoDia(): array
{
    /*
     * Entrada:
     * - maioria entre 07:50 e 08:15;
     * - alguns atrasos entre 08:16 e 08:35.
     */

    $chanceAtraso = random_int(1, 100);

    if ($chanceAtraso <= 15) {

        $entrada = criarHorario(
            8,
            16,
            35
        );

    } elseif (random_int(0, 1) === 0) {

        $entrada = criarHorario(
            7,
            50,
            59
        );

    } else {

        $entrada = criarHorario(
            8,
            0,
            15
        );
    }

    /*
     * Saída para intervalo:
     * entre 11:50 e 12:10.
     */

    if (random_int(0, 1) === 0) {

        $saidaIntervalo = criarHorario(
            11,
            50,
            59
        );

    } else {

        $saidaIntervalo = criarHorario(
            12,
            0,
            10
        );
    }

    /*
     * Intervalo:
     * entre 50 e 70 minutos.
     */

    $duracaoIntervalo = random_int(
        50,
        70
    );

    $retornoIntervalo = adicionarMinutos(
        $saidaIntervalo,
        $duracaoIntervalo
    );

    /*
     * Jornada trabalhada:
     * entre 7h50 e 8h20.
     */

    $totalMinutosTrabalhados = random_int(
        470,
        500
    );

    $minutosAntesIntervalo =
        calcularDiferencaMinutos(
            $entrada,
            $saidaIntervalo
        );

    $minutosDepoisIntervalo =
        $totalMinutosTrabalhados -
        $minutosAntesIntervalo;

    if ($minutosDepoisIntervalo < 180) {
        $minutosDepoisIntervalo = 240;
    }

    $saida = adicionarMinutos(
        $retornoIntervalo,
        $minutosDepoisIntervalo
    );

    return [
        'entrada' => $entrada,
        'saida_intervalo' => $saidaIntervalo,
        'retorno_intervalo' => $retornoIntervalo,
        'saida' => $saida
    ];
}

function obterDiasUteisDaSemanaAtual(): array
{
    $fusoHorario = new DateTimeZone(
        'America/Sao_Paulo'
    );

    $hoje = new DateTime(
        'now',
        $fusoHorario
    );

    $numeroDiaSemana = (int) $hoje->format('N');

    $segundaFeira = clone $hoje;

    $segundaFeira->modify(
        '-' . ($numeroDiaSemana - 1) . ' days'
    );

    $dias = [];

    for ($i = 0; $i < 5; $i++) {

        $data = clone $segundaFeira;

        $data->modify("+{$i} days");

        /*
         * Não cria pontos em datas futuras.
         */
        if (
            $data->format('Y-m-d') >
            $hoje->format('Y-m-d')
        ) {
            continue;
        }

        $dias[] = $data->format('Y-m-d');
    }

    return $dias;
}

function formatarDataBrasil(string $data): string
{
    return date(
        'd/m/Y',
        strtotime($data)
    );
}

/* =========================================================
   VERIFICA SE AS TABELAS EXISTEM
========================================================= */

$resultadoTabelaFuncionarios =
    $conPrincipal->query("
        SHOW TABLES LIKE 'funcionarios'
    ");

if (
    !$resultadoTabelaFuncionarios ||
    $resultadoTabelaFuncionarios->num_rows === 0
) {
    die(
        'A tabela funcionarios não existe no banco db_mpd.'
    );
}

$resultadoTabelaUsuarios =
    $conPrincipal->query("
        SHOW TABLES LIKE 'usuarios'
    ");

if (
    !$resultadoTabelaUsuarios ||
    $resultadoTabelaUsuarios->num_rows === 0
) {
    die(
        'A tabela usuarios não existe no banco db_mpd.'
    );
}

$resultadoTabelaRegistros =
    $con->query("
        SHOW TABLES LIKE 'registros_ponto'
    ");

if (
    !$resultadoTabelaRegistros ||
    $resultadoTabelaRegistros->num_rows === 0
) {
    die(
        'A tabela registros_ponto não existe no banco db_ponto.'
    );
}

/* =========================================================
   BUSCA FUNCIONÁRIOS DA EMPRESA 5
========================================================= */

/*
 * O nome e o id vêm da tabela funcionarios.
 * O e-mail vem da tabela usuarios.
 */

$stmtFuncionarios = $conPrincipal->prepare("
    SELECT DISTINCT
        f.id_funcionario,
        f.nome,
        u.email
    FROM funcionarios AS f

    INNER JOIN usuarios AS u
        ON u.id_funcionario = f.id_funcionario
       AND u.id_empresa = f.id_empresa

    WHERE f.id_empresa = ?
      AND u.email IS NOT NULL
      AND TRIM(u.email) <> ''
      AND u.status = 'ativo'

    ORDER BY f.nome ASC
");

if (!$stmtFuncionarios) {
    die(
        'Erro ao preparar a busca dos funcionários: ' .
        $conPrincipal->error
    );
}

$stmtFuncionarios->bind_param(
    'i',
    $idEmpresa
);

$stmtFuncionarios->execute();

$resultadoFuncionarios =
    $stmtFuncionarios->get_result();

$funcionarios = [];

while (
    $funcionario =
    $resultadoFuncionarios->fetch_assoc()
) {
    $funcionarios[] = $funcionario;
}

$stmtFuncionarios->close();

if (empty($funcionarios)) {
    die(
        'Nenhum funcionário ativo com e-mail foi encontrado ' .
        'na empresa ' .
        $idEmpresa .
        '.'
    );
}

/* =========================================================
   OBTÉM AS DATAS DA SEMANA ATUAL
========================================================= */

$diasUteis = obterDiasUteisDaSemanaAtual();

if (empty($diasUteis)) {
    die(
        'Não existem dias úteis disponíveis nesta semana.'
    );
}

/* =========================================================
   PREPARES DO BANCO db_ponto
========================================================= */

$stmtVerificar = $con->prepare("
    SELECT id
    FROM registros_ponto
    WHERE email = ?
      AND data = ?
    ORDER BY id DESC
    LIMIT 1
");

if (!$stmtVerificar) {
    die(
        'Erro ao preparar a verificação: ' .
        $con->error
    );
}

$stmtInserir = $con->prepare("
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

if (!$stmtInserir) {
    die(
        'Erro ao preparar a inserção: ' .
        $con->error
    );
}

$stmtAtualizar = $con->prepare("
    UPDATE registros_ponto
    SET
        entrada = ?,
        saida_intervalo = ?,
        retorno_intervalo = ?,
        saida = ?
    WHERE id = ?
");

if (!$stmtAtualizar) {
    die(
        'Erro ao preparar a atualização: ' .
        $con->error
    );
}

/* =========================================================
   GERA OS REGISTROS
========================================================= */

$totalInseridos = 0;
$totalAtualizados = 0;
$totalIgnorados = 0;
$totalErros = 0;

$detalhes = [];

$con->begin_transaction();

try {

    foreach ($funcionarios as $funcionario) {

        $nome = trim(
            $funcionario['nome'] ?? ''
        );

        $email = trim(
            $funcionario['email'] ?? ''
        );

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $totalErros++;

            $detalhes[] = [
                'tipo' => 'erro',
                'nome' => $nome,
                'data' => '',
                'mensagem' =>
                    'E-mail inválido: ' . $email
            ];

            continue;
        }

        foreach ($diasUteis as $data) {

            $horarios = gerarHorariosDoDia();

            $entrada =
                $horarios['entrada'];

            $saidaIntervalo =
                $horarios['saida_intervalo'];

            $retornoIntervalo =
                $horarios['retorno_intervalo'];

            $saida =
                $horarios['saida'];

            /*
             * Verifica se já existe um registro
             * para o mesmo e-mail e data.
             */

            $stmtVerificar->bind_param(
                'ss',
                $email,
                $data
            );

            $stmtVerificar->execute();

            $registroExistente =
                $stmtVerificar
                    ->get_result()
                    ->fetch_assoc();

            if ($registroExistente) {

                if (!$atualizarExistentes) {

                    $totalIgnorados++;

                    $detalhes[] = [
                        'tipo' => 'ignorado',
                        'nome' => $nome,
                        'data' => $data,
                        'mensagem' =>
                            'O registro já existia.'
                    ];

                    continue;
                }

                $idRegistro =
                    (int) $registroExistente['id'];

                $stmtAtualizar->bind_param(
                    'ssssi',
                    $entrada,
                    $saidaIntervalo,
                    $retornoIntervalo,
                    $saida,
                    $idRegistro
                );

                if (!$stmtAtualizar->execute()) {
                    throw new Exception(
                        'Erro ao atualizar o ponto de ' .
                        $email .
                        ': ' .
                        $stmtAtualizar->error
                    );
                }

                $totalAtualizados++;

                $detalhes[] = [
                    'tipo' => 'atualizado',
                    'nome' => $nome,
                    'data' => $data,
                    'mensagem' =>
                        'Registro atualizado com sucesso.'
                ];

            } else {

                $stmtInserir->bind_param(
                    'ssssss',
                    $email,
                    $data,
                    $entrada,
                    $saidaIntervalo,
                    $retornoIntervalo,
                    $saida
                );

                if (!$stmtInserir->execute()) {
                    throw new Exception(
                        'Erro ao inserir o ponto de ' .
                        $email .
                        ': ' .
                        $stmtInserir->error
                    );
                }

                $totalInseridos++;

                $detalhes[] = [
                    'tipo' => 'inserido',
                    'nome' => $nome,
                    'data' => $data,
                    'mensagem' =>
                        'Registro inserido com sucesso.'
                ];
            }
        }
    }

    $con->commit();

} catch (Throwable $erroExecucao) {

    $con->rollback();

    die(
        'A operação foi cancelada. Erro: ' .
        htmlspecialchars(
            $erroExecucao->getMessage()
        )
    );
}

/* =========================================================
   FECHA OS PREPARES
========================================================= */

$stmtVerificar->close();
$stmtInserir->close();
$stmtAtualizar->close();

$conPrincipal->close();

/* =========================================================
   DADOS PARA EXIBIÇÃO
========================================================= */

$primeiroDia = reset($diasUteis);
$ultimoDia = end($diasUteis);

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Gerar pontos atuais</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            <h1 class="h3 fw-bold text-primary mb-2">

                <i class="bi bi-clock-history me-2"></i>

                Pontos atuais gerados

            </h1>

            <p class="text-muted">

                Funcionários da empresa

                <strong>
                    <?= htmlspecialchars((string) $idEmpresa) ?>
                </strong>

                processados no banco db_ponto.

            </p>

            <div class="alert alert-info rounded-3">

                <i class="bi bi-calendar3 me-2"></i>

                Período gerado:

                <strong>
                    <?= formatarDataBrasil($primeiroDia) ?>
                </strong>

                até

                <strong>
                    <?= formatarDataBrasil($ultimoDia) ?>
                </strong>

            </div>

            <div class="row g-3 mb-4">

                <div class="col-md-3">

                    <div class="border rounded-3 p-3 h-100">

                        <small class="text-muted">
                            Funcionários
                        </small>

                        <div class="fs-4 fw-bold">

                            <?= count($funcionarios) ?>

                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="border rounded-3 p-3 h-100">

                        <small class="text-muted">
                            Inseridos
                        </small>

                        <div class="fs-4 fw-bold text-success">

                            <?= $totalInseridos ?>

                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="border rounded-3 p-3 h-100">

                        <small class="text-muted">
                            Atualizados
                        </small>

                        <div class="fs-4 fw-bold text-primary">

                            <?= $totalAtualizados ?>

                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="border rounded-3 p-3 h-100">

                        <small class="text-muted">
                            Ignorados/erros
                        </small>

                        <div class="fs-4 fw-bold text-danger">

                            <?= $totalIgnorados + $totalErros ?>

                        </div>

                    </div>

                </div>

            </div>

            <div
                class="border rounded-3"
                style="max-height: 450px; overflow-y: auto;"
            >

                <?php foreach ($detalhes as $detalhe): ?>

                    <div class="p-3 border-bottom">

                        <div class="fw-semibold">

                            <?= htmlspecialchars(
                                $detalhe['nome']
                            ) ?>

                        </div>

                        <?php if ($detalhe['data'] !== ''): ?>

                            <small class="text-muted">

                                <?= formatarDataBrasil(
                                    $detalhe['data']
                                ) ?>

                            </small>

                        <?php endif; ?>

                        <div class="small mt-1">

                            <?= htmlspecialchars(
                                $detalhe['mensagem']
                            ) ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <div class="mt-4">

                <a
                    href="index.php"
                    class="btn btn-primary"
                >
                    Ver registros
                </a>

                <a
                    href="api/pontos.php"
                    class="btn btn-outline-primary"
                >
                    Ver API
                </a>

            </div>

        </div>

    </div>

</div>

</body>

</html>