# Define a página de código do console para UTF-8
chcp 65001

# Caminho do diretório de logs
$logDirectory = "c:\TrocaSenha\logs"
$logFile = "$logDirectory\logPS.txt"

try {
    # Mensagem de log: Iniciando script
    "Iniciando script..." | Out-File -FilePath $logFile -Append -Encoding utf8

    # Redireciona a saída padrão e a saída de erro para o arquivo de log
    Start-Transcript -Path $logFile -Append

    # Lê os dados do arquivo "dados_usuario.txt"
    $dados = Get-Content -Path "c:\TrocaSenha\dados_usuario.txt" -Encoding UTF8
    $usuario, $novaSenha, $cpf = $dados -split '\n'

    try {
        # Mensagem de log: Tentando obter informações do usuário no AD
        "Tentando obter informações do usuário no AD..." | Out-File -FilePath $logFile -Append -Encoding utf8

        # Obter informações do usuário no AD, incluindo o CEP
        $user = Get-ADUser -Filter {SamAccountName -eq $usuario} -Properties SamAccountName, PostalCode, Enabled
        
        if ($null -eq $user) {
            # Mensagem de log: Usuário não encontrado no Active Directory
            "Usuário não encontrado no Active Directory." | Out-File -FilePath $logFile -Append -Encoding utf8
            exit 10  # Encerra o script com código de erro 1
        }

        if ($user.Enabled -eq $false) {
            # Mensagem de log: A conta de usuário está desabilitada
            "A conta de: $usuario está desabilitada. Favor entrar em contato com o Suporte!" | Out-File -FilePath $logFile -Append -Encoding utf8
            exit 11  # Encerra o script com código de erro 2
        }

        # Verificar se o CEP do AD coincide com o fornecido e remove espaços em branco.
        $cpf = $cpf.Trim()
        $userPostalCode = $user.PostalCode.Trim()

        if ($userPostalCode -ne $cpf) {
            # Mensagem de log: CPF divergente
            "CPF Divergente" | Out-File -FilePath $logFile -Append -Encoding utf8
            exit 12  # Encerra o script com código de erro 3 (indicando erro específico de CPF)
        }
         
        # Sua lógica para alterar a senha no Active Directory
        $MyPassword = ConvertTo-SecureString -AsPlainText -Force -String $novaSenha
        Set-ADAccountPassword -Identity $usuario -Reset -PassThru -NewPassword $MyPassword | Set-ADuser -ChangePasswordAtLogon $false
           
        # Mensagem de log: Senha alterada com sucesso
        "Senha Alterada Com Sucesso" | Out-File -FilePath $logFile -Append -Encoding utf8

    } catch {
        # Mensagem de log: Erro na alteração da senha
        "Erro Na Alteracao Da Senha" | Out-File -FilePath $logFile -Append -Encoding utf8
        $errorMessage = $_.Exception.Message
        "Erro Na Alteracao Da Senha: $errorMessage" | Out-File -FilePath $logFile -Append -Encoding utf8
    }

} catch {
    # Mensagem de log em caso de erro na criação do arquivo de log
    "Erro na criação do arquivo de log: $_" | Out-File -FilePath $logFile -Append -Encoding utf8
} finally {
    # Finaliza o registro no arquivo de log
    Stop-Transcript
}

# Retire o # da linha abaixo caso precise ver o que está acontecendo no terminal do Powershell. Quando terminar de debugar, comente a linha novamente. 
#Start-Sleep -Seconds 60
