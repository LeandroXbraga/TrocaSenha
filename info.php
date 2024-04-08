<?php
session_start();
?>

<!DOCTYPE html>
<html lang="">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>Troca Senha - Resultado</title>
    <style>
        body {
            background-image: url('/images/Odia.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        h1 {
            text-align: center;
            color: white;
        }

        p {
            font-size: 24px;
            color: white;
        }

        button {
            margin-top: 20px;
            padding: 10px;
            font-size: 16px;
            cursor: pointer;
            background-color: white;
            color: black;
            border: none;
            border-radius: 20px;
        }

        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<?php

if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    echo "<h1>$mensagem</h1>";
 
    if (isset($_SESSION['senha_gerada'])) {
        $senha = $_SESSION['senha_gerada'];
        $tamanhoFonte = 50;
        echo "<p>Sua nova senha é: <span style='color: yellow; font-size: {$tamanhoFonte}px;'><b>$senha</b></span></p>";
        // Lembre-se de remover a senha da variável de sessão após exibi-la, se necessário
        unset($_SESSION['senha_gerada']);
    }

} elseif (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $codigoErro = isset($_SESSION['codigoErro']) ? $_SESSION['codigoErro'] : '';
    echo "<h1>Erro $codigoErro:</h1>";
    echo "<p>$mensagem</p>";
} else {
    echo "<h1>Nenhuma mensagem de erro encontrada.</h1>";
}

// Limpar variáveis de sessão após usá-las
unset($_SESSION['mensagem']);
unset($_SESSION['codigoErro']);

?>
<!-- Botão para voltar à página inicial -->
<button onclick="voltarParaPaginaInicial()">Voltar à Página Inicial</button>

<script>
    function voltarParaPaginaInicial() {
        // Você pode ajustar o caminho conforme necessário
        window.location.href = "/";
    }
</script>

</body>
</html>