# Spusti PHP skript projektu pribalenym PHP (alebo systemovym) - pouzivaju ho DIAGNOSTIKA.bat, ZALOHA-DATABAZY.bat, OBNOVIT-DATABAZU.bat.
#   powershell -File bin\windows-php.ps1 bin\diagnostika.php [argumenty]
param([Parameter(Mandatory = $true)][string]$Script, [Parameter(ValueFromRemainingArguments = $true)]$Rest)
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
. (Join-Path $PSScriptRoot 'windows-phpini.ps1')
$portable = Join-Path $Root 'tools\php\php.exe'
if (Test-Path $portable) {
    $ini = Write-PortablePhpIni (Join-Path $Root 'tools\php') (Join-Path $Root 'storage')
    & $portable -c $ini $Script @Rest
} else {
    $sys = Get-Command php -ErrorAction SilentlyContinue
    if (-not $sys) { Write-Host 'PHP sa nenaslo. Najprv spusti START-WEB.bat (stiahne ho).' -ForegroundColor Yellow; exit 1 }
    & $sys.Source $Script @Rest
}
exit $LASTEXITCODE
