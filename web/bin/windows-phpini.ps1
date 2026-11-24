# Zapise php.ini pre pribalene PHP s cestami PODLA AKTUALNEHO UMIESTNENIA projektu.
# Predtym boli v php.ini natvrdo cesty z povodneho pocitaca (C:\Users\simih\...). Po skopirovani projektu inam
# (iny pouzivatel / priecinok) sa preto nenacitali rozsirenia (pdo_sqlite, mbstring...) a databaza nefungovala.
function Write-PortablePhpIni([string]$PhpDir, [string]$StorageDir) {
    $ini = @"
; Generuje bin\windows-phpini.ps1 pri kazdom spusteni - rucne zmeny sa prepisu.
extension_dir = "$PhpDir\ext"
extension=mbstring
extension=exif
extension=fileinfo
extension=gd
extension=openssl
extension=pdo_sqlite
extension=sqlite3
extension=pdo_pgsql
upload_max_filesize = 200M
post_max_size = 210M
max_file_uploads = 200
memory_limit = 768M
max_execution_time = 300
date.timezone = Europe/Bratislava
display_errors = Off
log_errors = On
error_log = "$StorageDir\php-error.log"
"@
    $path = Join-Path $PhpDir 'php.ini'
    if (-not (Test-Path (Join-Path $PhpDir 'ext\php_pdo_pgsql.dll'))) { $ini = $ini -replace "(?m)^extension=pdo_pgsql\r?\n", '' }
    [IO.File]::WriteAllText($path, $ini, (New-Object Text.UTF8Encoding $false))
    return $path
}
