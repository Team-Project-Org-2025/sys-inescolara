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

$Collections = Get-ChildItem (Join-Path $Root 'pruebas\collections\*\*.postman_collection.json')
if ($Collections.Count -eq 0) {
    Write-Error 'No se encontraron colecciones en pruebas\collections\*\'
    exit 1
}

foreach ($Collection in $Collections) {
    $Name = $Collection.BaseName -replace '\.postman_collection$', ''
    Write-Host "=== $Name ==="
    newman run $Collection.FullName `
        -e $Env `
        --env-var "baseUrl=$BaseUrl" `
        --env-var "usuario=$Usuario" `
        --env-var "password=$Password" `
        --env-var "viewerPassword=$ViewerPassword" `
        --reporters cli,junit `
        --reporter-junit-export (Join-Path $Out "$Name.xml")
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}

Write-Host "Resultados JUnit en: $Out"
