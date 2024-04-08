<?php
session_start();

// Defina o fuso horário de acordo com a sua localização
date_default_timezone_set('America/Sao_Paulo');

function contarOcorrencias($logContent, $user) {
    $count = 0;
    $logLines = explode("\r\n\r\n", $logContent);

    foreach ($logLines as $line) {
        // Verifica se a linha contém a string desejada
        if (strpos($line, $user) !== false) {
            $count++;
        }
    }

    return $count;
}

function verificarLimiteTrocaSenha($user, $limite = 5) {
    $logDirectory = "c:\TrocaSenha\logs";
    $logFile = $logDirectory . DIRECTORY_SEPARATOR . date("Y-m-d") . "_log_acesso.txt";

    if (!file_exists($logFile)) {
        return true;
    }

    $logContent = file_get_contents($logFile);

    $ocorrencias = contarOcorrencias($logContent, $user);

    // Adicione um contador no arquivo
    $counterFile = $logDirectory . DIRECTORY_SEPARATOR . "counter.txt";
    $counter = (file_exists($counterFile)) ? intval(file_get_contents($counterFile)) : 0;
    $counter++;

    if ($counter >= 10) {
        // Dispare o e-mail com o log
        $to = "infraestrutura@odia.com.br";
        $subject = "Limite de Troca de Senha Atingido";
        $message = "O usuário $user atingiu o limite de troca de senha.\n\n" . $logContent;

        // Substitua "seu@email.com" pelo endereço de e-mail desejado
        mail($to, $subject, $message);

        // Zere o contador
        $counter = 0;
    }

    // Salve o contador no arquivo
    file_put_contents($counterFile, $counter);

    return $ocorrencias < $limite;
}


function validarCPF($cpf) {
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    // Verifica se o CPF possui 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }

    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Calcula o primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += $cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $digitoVerificador1 = ($resto < 2) ? 0 : 11 - $resto;

    // Calcula o segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += $cpf[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $digitoVerificador2 = ($resto < 2) ? 0 : 11 - $resto;

    // Verifica se os dígitos verificadores são iguais aos do CPF
    return $cpf[9] == $digitoVerificador1 && $cpf[10] == $digitoVerificador2;
}

function gerarSenhaAleatoria($tamanho = 20) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*()-_';

    // Garante pelo menos um número, uma letra maiúscula, uma letra minúscula e um caractere especial
   
    $senha = $caracteres[rand(0, 25)]; // Letra Minuscula
    $senha .= $caracteres[rand(26, 51)]; // Letra Maiuscula
    $senha .= $caracteres[rand(52, 61)]; // Número
    $senha .= $caracteres[rand(62, 68)]; // Caractere especial

    $length = strlen($caracteres);

    for ($i = 4; $i < $tamanho; $i++) {
        $senha .= $caracteres[rand(0, $length - 1)];
    }

    // Embaralhar a senha para torná-la mais aleatória
    $senha = str_shuffle($senha);

    return $senha;
}

function gerarLog($tipo, $codigoErro, $mensagem, $user = null, $senha = null, $cpf = null) {
    $logDirectory = "c:\TrocaSenha\logs";

    // Cria o diretório se não existir
    if (!file_exists($logDirectory)) {
        mkdir($logDirectory, 0777, true);
    }

    // Obtém o caminho completo do arquivo de log para o dia atual
    $logFile = $logDirectory . DIRECTORY_SEPARATOR . date("Y-m-d") . "_log_acesso.txt";

    // 'a' abre o arquivo para escrita, posicionando o ponteiro no final do arquivo
    $logFileHandler = fopen($logFile, 'a');

    // Adiciona a nova mensagem ao arquivo de log
    $log = date("Y-m-d H:i:s") . " - $tipo";

    // Adiciona código de erro e mensagem se estiverem presentes
    if (!empty($codigoErro)) {
        $log .= " - $codigoErro - $mensagem";
    }

    // Adiciona as informações ao log com verificação de nulidade
    $log .= " - Usuário: " . ($user !== null ? $user : "null");
    $log .= " - Senha: " . ($senha !== null ? $senha : "null");
    $log .= " - CPF: " . ($cpf !== null ? $cpf : "null");

    // Adiciona uma mensagem única para o sucesso
    $log .= "\r\n\r\n"; // Modificação na quebra de linha

    fwrite($logFileHandler, $log);

    // Fecha o arquivo
    fclose($logFileHandler);

    // Limpa os logs antigos (mais de 30 dias)
    $thirtyDaysAgo = strtotime('-30 days');
    $logFiles = glob($logDirectory . DIRECTORY_SEPARATOR . '*.txt');

    foreach ($logFiles as $file) {
        $fileTime = strtotime(substr(basename($file), 0, 10));

        if ($fileTime < $thirtyDaysAgo) {
            unlink($file);
        }
    }
}
$mensagem = "";
$codigoErro ="";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST["textbox1"];
    $cpf = $_POST["textbox2"];
    $senha = "";

    // Verifica se o limite de troca de senha não foi atingido
    if (!verificarLimiteTrocaSenha($user)) {
        $mensagem = "Limite de tentativas atingido pelo usuário. Entre em contato com o Suporte!";
        $codigoErro = "13";
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['codigoErro'] = $codigoErro;
        $_SESSION['cpf'] = $cpf;
        gerarLog("Erro:", $codigoErro, $mensagem, $user ?: 'null', 'null', $cpf ?: 'null');
        header("Location: info.php");
        exit();
    }

    // Gera uma senha aleatória
    $senha = gerarSenhaAleatoria();
   
    if (empty($user)) {
        $mensagem = "Usuário vazio";
        $codigoErro = "1";

        // Adiciona as informações à sessão
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['codigoErro'] = $codigoErro;
        $_SESSION['cpf'] = $cpf;

        // Chama a função gerarLog com os parâmetros corretos
        gerarLog("Erro", $codigoErro, $mensagem, $user ?: 'null', 'null', $cpf ?: 'null');
        header("Location: info.php");
        exit();

     } elseif (empty($cpf)) {
        $mensagem = "CPF Vazio";
        $codigoErro = "2";

        // Adiciona as informações à sessão
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['codigoErro'] = $codigoErro;
        $_SESSION['cpf'] = $cpf;

        // Chama a função gerarLog com os parâmetros corretos
        gerarLog("Erro!", $codigoErro, $mensagem, $user ?: 'null', 'null', $cpf ?: 'null');
        header("Location: info.php");
        exit();

    } elseif (!validarCPF($cpf)) {
        $mensagem = "CPF inválido.";
        $codigoErro = "3";
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['codigoErro'] = $codigoErro;
        $_SESSION['cpf'] = $cpf;
        gerarLog("Erro:", $codigoErro, $mensagem, $user ?: 'null', 'null', $cpf ?: 'null');
        header("Location: info.php");
        exit();

    } elseif (empty($senha)) {
        $mensagem = "A nova senha não pode estar vazia.";
        $codigoErro = "4";
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['codigoErro'] = $codigoErro;
        $_SESSION['cpf'] = $cpf;
        gerarLog("Erro:", $codigoErro, $mensagem, $user ?: 'null', 'null', $cpf ?: 'null');
        header("Location: info.php");
        exit();

    } elseif (strlen($senha) < 20) {
        $mensagem = "A nova senha deve ter pelo menos 20 caracteres!";
        $codigoErro = "5";
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['codigoErro'] = $codigoErro;
        $_SESSION['cpf'] = $cpf;
        gerarLog("Erro:", $codigoErro, $mensagem, $user ?: 'null', 'null', $cpf ?: 'null');
        header("Location: info.php");
        exit();

    } elseif (!preg_match('/[0-9]/', $senha)) {
        $mensagem = "A nova senha deve conter pelo menos um número!";
        $codigoErro = "6";
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['codigoErro'] = $codigoErro;
        $_SESSION['cpf'] = $cpf;
        gerarLog("Erro:", $codigoErro, $mensagem, $user ?: 'null', 'null', $cpf ?: 'null');
        header("Location: info.php");
        exit();

    } elseif (!preg_match('/[!@#$%&*()\-_]/', $senha)) {
        $mensagem = "A nova senha deve conter pelo menos um caracter especial!";
        $codigoErro = "7";
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['codigoErro'] = $codigoErro;
        $_SESSION['cpf'] = $cpf;
        gerarLog("Erro:", $codigoErro, $mensagem, $user ?: 'null', 'null', $cpf ?: 'null');
        header("Location: info.php");
        exit();

    } elseif (!preg_match('/[A-Z]/', $senha)) {
        $mensagem = "A nova senha deve conter pelo menos uma letra maiúscula!";
        $codigoErro = "8";
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['codigoErro'] = $codigoErro;
        $_SESSION['cpf'] = $cpf;
        gerarLog("Erro:", $codigoErro, $mensagem, $user ?: 'null', 'null', $cpf ?: 'null');
        header("Location: info.php");
        exit();

    } elseif (!preg_match('/[a-z]/', $senha)) {
        $mensagem = "A nova senha deve conter pelo menos uma letra minúscula!";
        $codigoErro = "9";
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['codigoErro'] = $codigoErro;
        $_SESSION['cpf'] = $cpf;
        gerarLog("Erro:", $codigoErro, $mensagem, $user ?: 'null', 'null', $cpf ?: 'null');
        header("Location: info.php");
        exit();

    } else {
        $filename = "dados_usuario.txt";
        $data = "$user\n$senha\n$cpf";

        if ($file = fopen($filename, 'w')) {
            fwrite($file, $data);
            fclose($file);

            $command = 'powershell.exe -File "c:\TrocaSenha\AlterarSenha.ps1" 2>&1';
            exec($command, $output, $returnValue);

            $outputString = implode("\n", $output);
            
            // Verifica se houve erro na execução do PowerShell
            if ($returnValue == 10) {
                $_SESSION['usuario'] = $user;
                $_SESSION['mensagem'] = "Usuário não encontrado no AD.";
                gerarLog("Erro", "10", "Usuário não encontrado no AD.", $user ?: 'null', $senha ?: 'null', $cpf ?: 'null');
                session_write_close();
                header("Location: info.php");
                exit();

            } elseif ($returnValue == 11) {
                $_SESSION['usuario'] = $user;
                $_SESSION['mensagem'] = "Usuário Desabilitado no AD! Favor entrar em contato com o Suporte!";
                gerarLog("Erro", "11", "Usuário Desabilitado.", $user ?: 'null', $senha ?: 'null', $cpf ?: 'null');
                session_write_close();
                header("Location: info.php");
                exit();

            } elseif ($returnValue == 12) {
                $_SESSION['usuario'] = $user;
                $_SESSION['mensagem'] = "Verifique o Seu CPF! Caso esteja correto, entre em contato com o suporte!";
                gerarLog("Sucesso!", "12", "CPF Divergente", $user ?: 'null', $senha ?: 'null', $cpf ?: 'null');
                session_write_close();
                header("Location: info.php");
                exit();
                
            } else {
               // Se chegou até aqui, considera como sucesso
               // Armazena a senha na variável de sessão
               $_SESSION['usuario'] = $user;
               $_SESSION['senha_gerada'] = $senha;
               $_SESSION['mensagem'] = "Tudo certo $user. Anote a sua nova senha!";
               gerarLog("Sucesso!", "", "", $user ?: 'null', $senha ?: 'null', $cpf ?: 'null');

            // Certifique-se de salvar as variáveis de sessão antes do redirecionamento
            session_write_close();

            // Redireciona para info.php
            header("Location: info.php");
            exit();
            }
        }
    }  
}

error_reporting(E_ALL);
ini_set('display_errors', '1');
?>