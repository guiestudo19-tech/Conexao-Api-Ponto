
<?php

/*
|--------------------------------------------------------------------------
| API JSON DOS REGISTROS DE PONTO
|--------------------------------------------------------------------------
*/

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function enviarResposta($dados, $codigo)
{
    http_response_code($codigo);

    $json = json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_PRETTY_PRINT
    );

    if ($json === false) {
        echo '{"erro":true,"mensagem":"Erro ao gerar JSON."}';
    } else {
        echo $json;
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| DESATIVA EXCEÇÕES AUTOMÁTICAS DO MYSQLI
|--------------------------------------------------------------------------
*/

mysqli_report(MYSQLI_REPORT_OFF);

/*
|--------------------------------------------------------------------------
| TENTATIVAS DE CONEXÃO
|--------------------------------------------------------------------------
*/

$tentativas = array(
    array('localhost', 'root', 'usbw', 'db_ponto'),
    array('127.0.0.1', 'root', 'usbw', 'db_ponto'),
    array('localhost', 'root', '', 'db_ponto'),
    array('127.0.0.1', 'root', '', 'db_ponto')
);

$con = null;
$erroConexao = '';

foreach ($tentativas as $dadosConexao) {

    $host = $dadosConexao[0];
    $usuario = $dadosConexao[1];
    $senha = $dadosConexao[2];
    $banco = $dadosConexao[3];

    $teste = @mysqli_connect(
        $host,
        $usuario,
        $senha,
        $banco
    );

    if ($teste) {
        $con = $teste;
        break;
    }

    $erroConexao = mysqli_connect_error();
}

if (!$con) {

    enviarResposta(
        array(
            'erro' => true,
            'mensagem' => 'Não foi possível conectar ao banco db_ponto.',
            'detalhes' => $erroConexao
        ),
        500
    );
}

mysqli_set_charset($con, 'utf8mb4');

/*
|--------------------------------------------------------------------------
| VERIFICA A TABELA
|--------------------------------------------------------------------------
*/

$verificarTabela = mysqli_query(
    $con,
    "SHOW TABLES LIKE 'registros_ponto'"
);

if (!$verificarTabela) {

    enviarResposta(
        array(
            'erro' => true,
            'mensagem' => 'Erro ao verificar a tabela registros_ponto.',
            'detalhes' => mysqli_error($con)
        ),
        500
    );
}

if (mysqli_num_rows($verificarTabela) === 0) {

    enviarResposta(
        array(
            'erro' => true,
            'mensagem' => 'A tabela registros_ponto não existe no banco db_ponto.'
        ),
        404
    );
}

/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

$email = isset($_GET['email'])
    ? trim($_GET['email'])
    : '';

$data = isset($_GET['data'])
    ? trim($_GET['data'])
    : '';

$where = array();

if ($email !== '') {

    $emailSeguro = mysqli_real_escape_string(
        $con,
        $email
    );

    $where[] =
        "LOWER(TRIM(email)) = " .
        "LOWER(TRIM('" . $emailSeguro . "'))";
}

if ($data !== '') {

    if (
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $data
        )
    ) {
        enviarResposta(
            array(
                'erro' => true,
                'mensagem' => 'A data deve estar no formato YYYY-MM-DD.'
            ),
            400
        );
    }

    $dataSegura = mysqli_real_escape_string(
        $con,
        $data
    );

    $where[] = "data = '" . $dataSegura . "'";
}

/*
|--------------------------------------------------------------------------
| CONSULTA
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        email,
        data,
        entrada,
        saida_intervalo,
        retorno_intervalo,
        saida,
        criado_em
    FROM registros_ponto
";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= "
    ORDER BY
        data DESC,
        entrada ASC,
        id DESC
";

$resultado = mysqli_query(
    $con,
    $sql
);

if (!$resultado) {

    enviarResposta(
        array(
            'erro' => true,
            'mensagem' => 'Erro ao consultar os registros.',
            'detalhes' => mysqli_error($con),
            'consulta' => $sql
        ),
        500
    );
}

/*
|--------------------------------------------------------------------------
| MONTA OS REGISTROS
|--------------------------------------------------------------------------
*/

$registros = array();

while (
    $registro = mysqli_fetch_assoc($resultado)
) {

    $registros[] = array(
        'id' => (int) $registro['id'],
        'email' => $registro['email'],
        'data' => $registro['data'],
        'entrada' => $registro['entrada'],
        'saida_intervalo' => $registro['saida_intervalo'],
        'retorno_intervalo' => $registro['retorno_intervalo'],
        'saida' => $registro['saida'],
        'criado_em' => $registro['criado_em']
    );
}

mysqli_free_result($resultado);
mysqli_close($con);

/*
|--------------------------------------------------------------------------
| RESPOSTA FINAL
|--------------------------------------------------------------------------
*/

enviarResposta(
    array(
        'erro' => false,
        'total' => count($registros),
        'registros' => $registros
    ),
    200
);
