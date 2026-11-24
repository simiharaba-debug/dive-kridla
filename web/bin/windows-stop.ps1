# Ukonci iba server spusteny tymto projektom (podla storage\server.pid).
$Root = Split-Path -Parent $PSScriptRoot
$pidFile = Join-Path $Root 'storage\server.pid'
$portFile = Join-Path $Root 'storage\server.port'
if (-not (Test-Path $pidFile)) { Write-Host 'Server nebezi (nenasiel som subor s PID).'; exit 0 }
$id = [int](Get-Content $pidFile)
$proc = Get-Process -Id $id -ErrorAction SilentlyContinue
if ($proc -and $proc.ProcessName -like 'php*') { Stop-Process -Id $id -Force; Write-Host "Server (PID $id) bol ukonceny." } else { Write-Host 'Server uz nebezi.' }
Remove-Item $pidFile, $portFile -ErrorAction SilentlyContinue
# zapise rozpracovane zmeny databazy do storage\app.sqlite (bezpecne kopirovanie / zaloha)
try { & (Join-Path $PSScriptRoot 'windows-php.ps1') 'bin\db-checkpoint.php' } catch {}
