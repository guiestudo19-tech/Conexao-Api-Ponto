<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

<<<<<<< HEAD
/**
 * Calcula a quantidade de segundos entre dois horários.
 * Também suporta registros que terminem depois da meia-noite.
 */
function diferencaSegundosHorario(?string $inicio, ?string $fim): int
{
    if (
        empty($inicio) ||
        empty($fim) ||
        $inicio === '00:00:00' ||
        $fim === '00:00:00'
    ) {
        return 0;
    }

    $inicioTimestamp = strtotime($inicio);
    $fimTimestamp = strtotime($fim);

    if ($inicioTimestamp === false || $fimTimestamp === false) {
        return 0;
    }

    /*
     * Caso o horário final seja menor que o inicial,
     * considera que terminou no dia seguinte.
     */
    if ($fimTimestamp < $inicioTimestamp) {
        $fimTimestamp += 86400;
    }

    return max(0, $fimTimestamp - $inicioTimestamp);
}

/**
 * Calcula os minutos efetivamente trabalhados.
 *
 * Quando existe intervalo:
 * entrada -> saída do almoço
 * retorno do almoço -> saída
 *
 * Quando não existe intervalo:
 * entrada -> saída
 */
function calcularMinutosTrabalhados(array $registro): int
{
    $entrada = $registro['entrada'] ?? null;
    $saidaAlmoco = $registro['saida_almoco'] ?? null;
    $retornoAlmoco = $registro['retorno_almoco'] ?? null;
    $saida = $registro['saida'] ?? null;

    if (empty($entrada) || empty($saida)) {
        return 0;
    }

    $possuiIntervalo =
        !empty($saidaAlmoco) &&
        !empty($retornoAlmoco);

    if ($possuiIntervalo) {
        $periodoManha = diferencaSegundosHorario(
            $entrada,
            $saidaAlmoco
        );

        $periodoTarde = diferencaSegundosHorario(
            $retornoAlmoco,
            $saida
        );

        return (int) round(
            ($periodoManha + $periodoTarde) / 60
        );
    }

    return (int) round(
        diferencaSegundosHorario($entrada, $saida) / 60
    );
}

/**
 * Formata minutos como horas e minutos.
 *
 * Exemplos:
 * 41  => 0h41min
 * 77  => 1h17min
 * 480 => 8h
 */
function formatarDuracaoMinutos(int $totalMinutos): string
{
    $totalMinutos = max(0, $totalMinutos);

    $horas = intdiv($totalMinutos, 60);
    $minutos = $totalMinutos % 60;

    if ($horas === 0 && $minutos === 0) {
        return '0h';
    }

    if ($horas === 0) {
        return $minutos . 'min';
    }

    if ($minutos === 0) {
        return $horas . 'h';
    }

    return $horas . 'h' . str_pad(
        (string) $minutos,
        2,
        '0',
        STR_PAD_LEFT
    ) . 'min';
}

$sql = "
    SELECT
        id,
        email,
        data,
        entrada,
        saida_almoco,
        retorno_almoco,
        saida,
        criado_em
    FROM registros_ponto
    ORDER BY data DESC, id DESC
";

$resultado = mysqli_query($con, $sql);

if (!$resultado) {
=======
try {

    $sql = "
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
    ";

    $result = mysqli_query($con, $sql);

    if (!$result) {
        throw new Exception(mysqli_error($con));
    }

    $dados = [];

    while ($row = mysqli_fetch_assoc($result)) {

        $dados[] = [
            'id' => (int) $row['id'],
            'email' => $row['email'],
            'data' => $row['data'],
            'entrada' => $row['entrada'],
            'saida_intervalo' => $row['saida_intervalo'],
            'retorno_intervalo' => $row['retorno_intervalo'],
            'saida' => $row['saida']
        ];
    }

    http_response_code(200);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE |
        JSON_PRETTY_PRINT
    );

} catch (Throwable $erro) {

>>>>>>> 028a19f (registro automatico e alguns erros resolvidos)
    http_response_code(500);

    echo json_encode(
        [
            'erro' => true,
<<<<<<< HEAD
            'mensagem' => 'Não foi possível consultar os registros.',
            'detalhes' => mysqli_error($con)
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

$dados = [];

while ($row = mysqli_fetch_assoc($resultado)) {
    $minutosTrabalhados = calcularMinutosTrabalhados($row);

    $dados[] = [
        'id' => (int) $row['id'],
        'email' => $row['email'],
        'data' => $row['data'],

        'entrada' => $row['entrada'],

        /*
         * Mantém os nomes usados anteriormente pela aplicação,
         * mesmo que no banco sejam chamados de almoço.
         */
        'saida_intervalo' => $row['saida_almoco'],
        'retorno_intervalo' => $row['retorno_almoco'],

        /*
         * Também retorna os nomes originais do banco.
         */
        'saida_almoco' => $row['saida_almoco'],
        'retorno_almoco' => $row['retorno_almoco'],

        'saida' => $row['saida'],

        /*
         * Valores seguros para cálculos.
         */
        'total_trabalhado_minutos' => $minutosTrabalhados,
        'total_trabalhado_formatado' => formatarDuracaoMinutos(
            $minutosTrabalhados
        ),

        'criado_em' => $row['criado_em']
    ];
}

mysqli_free_result($resultado);

echo json_encode(
    $dados,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);
=======
            'mensagem' => $erro->getMessage()
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_PRETTY_PRINT
    );
}
>>>>>>> 028a19f (registro automatico e alguns erros resolvidos)
