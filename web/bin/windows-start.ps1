# Automaticke spustenie projektu na Windows (volane z START-WEB.bat).
# Nevyzaduje administratora (okrem pripadnej inštalacie VC++ runtime). PHP sa stiahne do priecinka tools\ z oficialneho zdroja (FFmpeg uz nie je potrebny).
$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
$Tools = Join-Path $Root 'tools'
$Storage = Join-Path $Root 'storage'
New-Item -ItemType Directory -Force $Storage | Out-Null
function Say($m) { Write-Host "[Dive kridla] $m" -ForegroundColor Green }
function Warn($m) { Write-Host "[Dive kridla] $m" -ForegroundColor Yellow }
. (Join-Path $PSScriptRoot 'windows-phpini.ps1')

$script:PhpExe = $null
$script:PhpArgs = @()
function Php { param([Parameter(ValueFromRemainingArguments = $true)]$a) & $script:PhpExe @script:PhpArgs @a }

function Test-PhpCandidate($exe, $phpArgs) {
    try {
        $out = & $exe @phpArgs (Join-Path $Root 'bin\check-env.php') 2>$null
        if ($LASTEXITCODE -ne 0 -or -not $out) { return $false }
        $parts = ($out | Out-String).Trim().Split('|')
        return $parts[1] -eq '1'
    } catch { return $false }
}

function Install-Php {
    Say 'Stahujem PHP z oficialneho zdroja windows.php.net ...'
    New-Item -ItemType Directory -Force $Tools | Out-Null
    $rel = Invoke-RestMethod 'https://windows.php.net/downloads/releases/releases.json'
    $names = @($rel.PSObject.Properties.Name)
    $key = if ($names -contains '8.3') { '8.3' } else { ($names | Where-Object { $_ -match '^\d+\.\d+$' } | Sort-Object { [version]$_ } -Descending | Select-Object -First 1) }
    $info = $rel.$key
    $bn = $info.PSObject.Properties.Name | Where-Object { $_ -match '^nts-vs\d+-x64$' } | Select-Object -First 1
    $build = $info.$bn
    $zipName = $build.zip.path
    $zip = Join-Path $Tools $zipName
    Invoke-WebRequest "https://windows.php.net/downloads/releases/$zipName" -OutFile $zip -UseBasicParsing
    if ((Get-FileHash $zip -Algorithm SHA256).Hash.ToLower() -ne $build.zip.sha256.ToLower()) { Remove-Item $zip; throw 'Kontrolny sucet stiahnuteho PHP nesedi.' }
    $dir = Join-Path $Tools 'php'
    if (Test-Path $dir) { Remove-Item $dir -Recurse -Force }
    Expand-Archive $zip $dir -Force
    Remove-Item $zip
    [void](Write-PortablePhpIni $dir $Storage)
}

function Install-VcRedist {
    Warn 'Chyba runtime Microsoft Visual C++. Instalujem ho (Windows sa moze opytat na potvrdenie - klikni Ano).'
    $f = Join-Path $env:TEMP 'vc_redist.x64.exe'
    Invoke-WebRequest 'https://aka.ms/vs/17/release/vc_redist.x64.exe' -OutFile $f -UseBasicParsing
    Start-Process $f -ArgumentList '/install', '/quiet', '/norestart' -Verb RunAs -Wait
}

# ---------- 1. PHP ----------
Say 'Kontrolujem PHP ...'
$portable = Join-Path $Tools 'php\php.exe'
$portableIni = Join-Path $Tools 'php\php.ini'
if (Test-Path $portable) { [void](Write-PortablePhpIni (Join-Path $Tools 'php') $Storage) }   # cesty podla aktualneho umiestnenia projektu
$sys = Get-Command php -ErrorAction SilentlyContinue
if ((Test-Path $portable) -and (Test-PhpCandidate $portable @('-c', $portableIni))) { $script:PhpExe = $portable; $script:PhpArgs = @('-c', $portableIni) }
elseif ($sys -and (Test-PhpCandidate $sys.Source @())) { $script:PhpExe = $sys.Source }
else {
    Install-Php
    if (-not (Test-PhpCandidate $portable @('-c', $portableIni))) { Install-VcRedist }
    if (-not (Test-PhpCandidate $portable @('-c', $portableIni))) { throw 'PHP sa nepodarilo sfunkcnit.' }
    $script:PhpExe = $portable; $script:PhpArgs = @('-c', $portableIni)
}
Say ('PHP OK: ' + (Php -r 'echo PHP_VERSION;'))

# ---------- 2. FFmpeg (uz sa nestahuje) ----------
# Od 17. 11. 2026 su vsetky videa na webe z YouTube, FFmpeg web nepotrebuje (priecinok tools\ffmpeg bol zmazany kvoli miestu).
# Ak je FFmpeg nainstalovany v systeme alebo v tools\ffmpeg, pouzije sa len na nahlady videi v sukromnej admin galerii.
$found = if (Test-Path (Join-Path $Tools 'ffmpeg')) { Get-ChildItem (Join-Path $Tools 'ffmpeg') -Recurse -Filter ffmpeg.exe -ErrorAction SilentlyContinue | Select-Object -First 1 } else { $null }
if ($found) { $env:PATH = $found.DirectoryName + ';' + $env:PATH }

# ---------- 3. .env (konfiguracia a admin) ----------
$envFile = Join-Path $Root '.env'
if (-not (Test-Path $envFile)) { Copy-Item (Join-Path $Root '.env.example') $envFile }
$lines = [System.Collections.ArrayList]@(Get-Content $envFile -Encoding UTF8)
function Get-EnvVal($k) { foreach ($l in $lines) { if ($l -match "^\s*$k\s*=(.*)$") { return $Matches[1].Trim() } }; return '' }
function Set-EnvVal($k, $v) {
    for ($i = 0; $i -lt $lines.Count; $i++) { if ($lines[$i] -match "^\s*$k\s*=") { $lines[$i] = "$k=$v"; return } }
    [void]$lines.Add("$k=$v")
}
$newCreds = $false
if (-not (Get-EnvVal 'ADMIN_PASSWORD') -or -not (Get-EnvVal 'ADMIN_EMAIL') -or -not (Get-EnvVal 'ADMIN_USERNAME')) {
    $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'
    $rng = New-Object Security.Cryptography.RNGCryptoServiceProvider
    do {
        $bytes = New-Object byte[] 20; $rng.GetBytes($bytes)
        $pw = -join ($bytes | ForEach-Object { $alphabet[$_ % $alphabet.Length] })
    } while ($pw -notmatch '[a-z]' -or $pw -notmatch '[A-Z]' -or $pw -notmatch '\d')
    if (-not (Get-EnvVal 'ADMIN_USERNAME')) { Set-EnvVal 'ADMIN_USERNAME' 'admin' }
    if (-not (Get-EnvVal 'ADMIN_EMAIL')) { Set-EnvVal 'ADMIN_EMAIL' 'admin@biologicka-ochrana.local' }
    Set-EnvVal 'ADMIN_PASSWORD' $pw
    [IO.File]::WriteAllLines($envFile, [string[]]$lines, (New-Object Text.UTF8Encoding $false))
    $newCreds = $true
    $credFile = Join-Path ([Environment]::GetFolderPath('MyDocuments')) 'Biologicka-ochrana-admin-prihlasenie.txt'
    $txt = "Prihlasenie administratora - web Biologicka ochrana`r`n`r`nAdresa:  http://localhost:8000/prihlasenie`r`nE-mail:  $(Get-EnvVal 'ADMIN_EMAIL')`r`nHeslo:   $pw`r`n`r`n(Heslo je ulozene aj v subore .env v priecinku projektu. Tento subor nikomu neposielaj.)`r`n"
    [IO.File]::WriteAllText($credFile, $txt, (New-Object Text.UTF8Encoding $false))
    Say "Vytvoril som administratora. Udaje su v: $credFile"
    Warn "Na tomto pocitaci este nebol subor .env - preto ma administrator NOVE heslo (nie to z povodneho pocitaca)."
    Warn "Ucty z ineho pocitaca su v jeho databaze. Prenesies ich: ZALOHA-DATABAZY.bat tam, OBNOVIT-DATABAZU.bat tu (alebo skopiruj aj subor .env)."
}

# ---------- 4. Databaza ----------
Say 'Pripravujem databazu ...'
Php bin/migrate.php
if ($LASTEXITCODE -ne 0) { throw 'Migracia databazy zlyhala.' }
# Vytvori administratora, ak neexistuje, a ak sa ADMIN_PASSWORD / ADMIN_EMAIL v .env zmenilo, bezpecne ho prenesie do databazy.
Php bin/sync-admin-password.php --if-changed
if ($LASTEXITCODE -ne 0) { Warn 'Heslo administratora sa nepodarilo synchronizovat s .env (pozri spravu vyssie). Web sa spusti s povodnym heslom.' }
$adminMail = Get-EnvVal 'ADMIN_EMAIL'
if ($adminMail) { Say "Prihlasenie administratora: $adminMail + heslo ADMIN_PASSWORD zo suboru .env" }

# ---------- 5. Server ----------
$pidFile = Join-Path $Storage 'server.pid'
$portFile = Join-Path $Storage 'server.port'
if ((Test-Path $pidFile) -and (Test-Path $portFile)) {
    $oldPid = [int](Get-Content $pidFile)
    $proc = Get-Process -Id $oldPid -ErrorAction SilentlyContinue
    if ($proc -and $proc.ProcessName -like 'php*') {
        $port = [int](Get-Content $portFile)
        Say "Server uz bezi na porte $port."
        Start-Process "http://localhost:$port"
        exit 0
    }
}
$port = 8000
while ((Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue) -and $port -lt 8030) { Warn "Port $port je obsadeny, skusam dalsi."; $port++ }
$q = { param($s) '"' + $s + '"' }
$argList = @()
if ($script:PhpArgs.Count) { $argList += @('-c', (& $q $portableIni)) }
$argList += @('-d', 'upload_max_filesize=200M', '-d', 'post_max_size=210M', '-S', "localhost:$port", 'router.php')
$p = Start-Process -FilePath $script:PhpExe -ArgumentList $argList -WorkingDirectory $Root -WindowStyle Hidden -PassThru `
    -RedirectStandardOutput (Join-Path $Storage 'server-out.log') -RedirectStandardError (Join-Path $Storage 'server.log')
$ok = $false
for ($i = 0; $i -lt 30; $i++) {
    Start-Sleep -Milliseconds 500
    try { $r = Invoke-WebRequest "http://localhost:$port/" -UseBasicParsing -TimeoutSec 3; if ($r.StatusCode -eq 200) { $ok = $true; break } } catch {}
}
if (-not $ok) { throw "Server sa nespustil. Pozri storage\server.log" }
Set-Content $pidFile $p.Id
Set-Content $portFile $port
Say "Web bezi na http://localhost:$port  (ukoncis ho cez STOP-WEB.bat)"
Say "Kontrola databazy a prihlasovania: DIAGNOSTIKA.bat alebo http://localhost:$port/diagnostika.php"
if ($newCreds) {
    $cf = Join-Path ([Environment]::GetFolderPath('MyDocuments')) 'Biologicka-ochrana-admin-prihlasenie.txt'
    (Get-Content $cf -Raw).Replace('localhost:8000', "localhost:$port") | Set-Content $cf -Encoding UTF8
    Start-Process notepad.exe (Join-Path ([Environment]::GetFolderPath('MyDocuments')) 'Biologicka-ochrana-admin-prihlasenie.txt') }
Start-Process "http://localhost:$port"
