# ============================================================
# SYSINECOLARA · Correr TODAS las colecciones y exportar JUnit (Windows)
#
# Uso:
#   pwsh -File pruebas\scripts\run-all.ps1
#
# Salida: results\*.xml (un JUnit por colección).
# ============================================================
$ErrorActionPreference = 'Stop'

$Root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$Env  = Join-Path $Root 'pruebas\environments\SysInescolara Local.postman_environment.json'
$Out  = Join-Path $Root 'results'

if (-not (Get-Command newman -ErrorAction SilentlyContinue)) {
    Write-Error 'newman no está instalado. Ejecuta: npm install -g newman'
    exit 127
}

$BaseUrl = if ($env:POSTMAN_BASE_URL) { $env:POSTMAN_BASE_URL } else { 'http://localhost/sys-inescolara' }
$Usuario = if ($env:POSTMAN_USUARIO) { $env:POSTMAN_USUARIO } else { 'admin' }
$Password = $env:POSTMAN_PASSWORD
$ViewerPassword = $env:POSTMAN_VIEWER_PASSWORD

New-Item -ItemType Directory -Force -Path $Out | Out-Null

$Collections = Get-ChildItem (Join-Path $Root 'pruebas\collections\*\*.postman_collection.json') | Sort-Object FullName
if ($Collections.Count -eq 0) {
    Write-Error 'No se encontraron colecciones en pruebas\collections\*\'
    exit 1
}

$failed = @()
foreach ($Collection in $Collections) {
    $Name = $Collection.BaseName -replace '\.postman_collection$', ''
    Write-Host "=== $Name ==="
    $Xml = Join-Path $Out "$Name.xml"
    Remove-Item $Xml -ErrorAction SilentlyContinue
    # Ojo: '--reporters' necesita la lista entre comillas; sin ellas PowerShell
    # la pasa como "cli junit" y newman aborta sin correr nada.
    newman run $Collection.FullName `
        -e $Env `
        --env-var "baseUrl=$BaseUrl" `
        --env-var "usuario=$Usuario" `
        --env-var "password=$Password" `
        --env-var "viewerPassword=$ViewerPassword" `
        --reporters 'cli,junit' `
        --reporter-junit-export $Xml
    if ($LASTEXITCODE -ne 0) { $failed += $Name }
    if (-not (Test-Path $Xml)) { $failed += "$Name (sin JUnit)" }
}

if ($failed.Count -gt 0) {
    Write-Error ("Colecciones con fallo: " + ($failed -join ', '))
    exit 1
}
Write-Host "Resultados JUnit en: $Out"
