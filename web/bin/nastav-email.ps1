# Sprievodca nastavením odosielania e-mailov z kontaktného formulára (volá ho NASTAVIT-EMAIL.bat).
$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
function Say($m, $c = 'Green') { Write-Host $m -ForegroundColor $c }

$php = Join-Path $Root 'tools\php\php.exe'; $ini = Join-Path $Root 'tools\php\php.ini'
if (Test-Path $php) { $phpArgs = @('-c', $ini) } else { $php = 'php'; $phpArgs = @() }

Write-Host ''
Say '=== Nastavenie kontaktného formulára (odosielanie e-mailov cez Gmail) ===' 'Cyan'
Write-Host ''
Write-Host 'Potrebujete 2 veci:'
Write-Host '  1) Gmail adresu, z ktorej bude web posielať správy (napr. vasa.adresa@gmail.com)'
Write-Host '  2) "Heslo aplikácie" pre túto Gmail adresu - 16 písmen, ktoré vám vygeneruje Google.'
Write-Host '     (NIE je to vaše bežné heslo do Gmailu.)'
Write-Host ''
$open = Read-Host 'Mám vám teraz otvoriť stránku Google, kde heslo aplikácie vytvoríte? (A = áno / N = už ho mám)'
if ($open -match '^[aAyY]') {
    Start-Process 'https://myaccount.google.com/apppasswords'
    Write-Host ''
    Write-Host 'V prehliadači:' -ForegroundColor Yellow
    Write-Host '  - prihláste sa do Gmail účtu, z ktorého má web posielať správy'
    Write-Host '  - do políčka "Názov aplikácie" napíšte napr.:  Web Dive kridla'
    Write-Host '  - kliknite "Vytvoriť"'
    Write-Host '  - Google zobrazí 16 písmen v žltom rámčeku (napr. abcd efgh ijkl mnop) - skopírujte ich'
    Write-Host '  Ak stránka píše, že nastavenie nie je dostupné: najprv zapnite "Dvojstupňové overenie"'
    Write-Host '  (myaccount.google.com/signinoptions/twosv) a potom to skúste znova.'
    Write-Host ''
}
do { $user = (Read-Host 'Gmail adresa odosielateľa').Trim() } while ($user -notmatch '^[^@\s]+@[^@\s]+\.[^@\s]+$')
$sec = Read-Host 'Heslo aplikácie (16 písmen, môžete vložiť aj s medzerami; pri písaní sa nezobrazuje)' -AsSecureString
$pass = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec))
$to = (Read-Host 'Kam majú chodiť správy z formulára? (Enter = daniel.haraba@gmail.com)').Trim()
Write-Host ''
$env:MS_USER = $user; $env:MS_PASS = $pass; $env:MS_TO = $to
try { & $php @phpArgs (Join-Path $Root 'bin\mail-setup.php'); $code = $LASTEXITCODE }
finally { Remove-Item Env:MS_PASS -ErrorAction SilentlyContinue; $pass = $null }
Write-Host ''
if ($code -eq 0) { Say 'Kontaktný formulár na webe je pripravený. Web netreba reštartovať.' }
else { Say 'Nastavenie sa uložilo, ale test neprešiel - prečítajte si správu vyššie a spustite sprievodcu znova.' 'Yellow' }
exit $code
