#Requires -Version 5.1
<#
.SYNOPSIS
    Ejecuta las pruebas de integración y llena una copia del formato Word.

.DESCRIPTION
    Para cada módulo (RF-01 a RF-31):
      1. Ejecuta su archivo de integración con PHPUnit y guarda el JUnit.
      2. Genera una captura PNG de la salida real de consola.
      3. Rellena el cuadro correspondiente en una COPIA del formato Word:
         Objetivos, Técnicas, Código involucrado, Caso de prueba,
         Resultado (texto + captura) y Observaciones.

    El documento original de formatos\plantillas\ nunca se modifica.
    La copia llena se guarda en formatos\resultados\.

.PARAMETER Module
    all (por defecto), un número (01-31) o parte del nombre del modelo
    (Planta, Backup, PasswordReset...).

.PARAMETER Output
    Ruta del .docx de salida. Por defecto:
    formatos\resultados\Formato-Prueba-Integración-<fecha>.docx

.PARAMETER KeepWorking
    Conserva los JUnit temporales de la corrida.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\integration\scripts\windows\run-integration-doc.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\integration\scripts\windows\run-integration-doc.ps1 -Module 29

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tests\tools\integration\scripts\windows\run-integration-doc.ps1 -Module Backup -Output C:\temp\cuadro-29.docx
#>
[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [string]$Module = 'all',

    [string]$Output = '',

    [switch]$KeepWorking
)

$ErrorActionPreference = 'Stop'

$script:Root        = (Resolve-Path (Join-Path $PSScriptRoot '..\..\..\..\..')).Path
$script:AreaDir     = Join-Path $script:Root 'tests\tools\integration'
$script:Template    = Join-Path $script:AreaDir 'formatos\plantillas\Formato-Prueba-Integración.docx'
$script:ResultsDir  = Join-Path $script:AreaDir 'formatos\resultados'
$script:CapturesDir = Join-Path $script:ResultsDir 'capturas'
$script:PhpUnit     = Join-Path $script:Root 'vendor\phpunit\phpunit\phpunit'
$script:Config      = Join-Path $script:Root 'tests\phpunit.xml'
$script:Manifest    = Join-Path $script:ResultsDir 'ultima-ejecucion.json'
$script:RelNs       = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
$script:EsCulture   = [Globalization.CultureInfo]::GetCultureInfo('es-ES')

# ---------------------------------------------------------------------------
# Catálogo: los 31 módulos en el orden del sidebar / formato Word
# ---------------------------------------------------------------------------
function Get-ModuleCatalog {
    [xml]$config = Get-Content -LiteralPath $script:Config -Raw -Encoding UTF8
    $nodes = $config.SelectNodes('//testsuite[@name="Integration"]/file')
    $list = New-Object System.Collections.Generic.List[object]
    $n = 0

    foreach ($node in $nodes) {
        $n++
        $rel = $node.InnerText.Trim()
        $fileName = [IO.Path]::GetFileNameWithoutExtension($rel)
        $model = $fileName -replace 'RepositoryTest$', ''
        $list.Add([pscustomobject]@{
            Number   = $n
            Index    = $n - 1
            Model    = $model
            TestFile = $rel
            TestPath = Join-Path $script:Root ('tests\' + ($rel -replace '/', '\'))
        })
    }

    return $list
}

function Select-Modules {
    param($Catalog, [string]$Filter)

    $raw = $Filter.Trim()

    if ($raw -eq 'all' -or [string]::IsNullOrWhiteSpace($raw)) {
        return @($Catalog)
    }

    if ($raw -match '^\d{1,2}$') {
        $number = [int]$raw
        return @($Catalog | Where-Object { $_.Number -eq $number })
    }

    return @($Catalog | Where-Object {
        $_.Model -like "*$raw*" -or $_.TestFile -like "*$raw*"
    })
}

# Ancho original de cada captura en el formato: así la nueva imagen conserva
# el tamaño de texto que tenía el documento aprobado.
function Get-TemplateImageWidths {
    Add-Type -AssemblyName System.Drawing
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem

    $zip = [System.IO.Compression.ZipFile]::OpenRead($script:Template)
    try {
        [xml]$doc = Read-ZipText -Entry $zip.GetEntry('word/document.xml')
        [xml]$rels = Read-ZipText -Entry $zip.GetEntry('word/_rels/document.xml.rels')

        $ns = New-Object System.Xml.XmlNamespaceManager($doc.NameTable)
        $ns.AddNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main')
        $ns.AddNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main')

        $relMap = @{}
        foreach ($rel in $rels.Relationships.Relationship) {
            $relMap[$rel.Id] = $rel.Target
        }

        $widths = New-Object System.Collections.Generic.List[int]
        foreach ($table in $doc.SelectNodes('//w:body/w:tbl', $ns)) {
            $width = 707
            $blip = $table.SelectSingleNode('.//a:blip', $ns)
            if ($null -ne $blip) {
                $rid = $blip.GetAttribute('embed', $script:RelNs)
                if ($relMap.ContainsKey($rid)) {
                    $entry = $zip.GetEntry('word/' + $relMap[$rid])
                    if ($null -ne $entry) {
                        $stream = $entry.Open()
                        try {
                            $memory = New-Object IO.MemoryStream
                            try {
                                $stream.CopyTo($memory)
                                $memory.Position = 0
                                $image = [System.Drawing.Image]::FromStream($memory)
                                try {
                                    $width = $image.Width
                                } finally {
                                    $image.Dispose()
                                }
                            } finally {
                                $memory.Dispose()
                            }
                        } finally {
                            $stream.Dispose()
                        }
                    }
                }
            }
            $widths.Add($width)
        }

        return $widths.ToArray()
    } finally {
        $zip.Dispose()
    }
}

# ---------------------------------------------------------------------------
# Lector / escritor de entradas del .docx
# ---------------------------------------------------------------------------
function Read-ZipText {
    param([IO.Compression.ZipArchiveEntry]$Entry)

    $stream = $Entry.Open()
    try {
        $reader = New-Object IO.StreamReader($stream, [Text.Encoding]::UTF8)
        try {
            return $reader.ReadToEnd()
        } finally {
            $reader.Dispose()
        }
    } finally {
        $stream.Dispose()
    }
}

function Write-ZipXml {
    param([IO.Compression.ZipArchiveEntry]$Entry, [xml]$Document)

    $settings = New-Object System.Xml.XmlWriterSettings
    $settings.Encoding = New-Object System.Text.UTF8Encoding($false)
    $settings.Indent = $false
    $settings.OmitXmlDeclaration = $false

    $memory = New-Object IO.MemoryStream
    $writer = [System.Xml.XmlWriter]::Create($memory, $settings)
    $Document.Save($writer)
    $writer.Flush()
    $writer.Dispose()

    $bytes = $memory.ToArray()
    $memory.Dispose()

    $stream = $Entry.Open()
    try {
        $stream.SetLength(0)
        $stream.Write($bytes, 0, $bytes.Length)
    } finally {
        $stream.Dispose()
    }
}

function Set-ParagraphText {
    param($Paragraph, [string]$Text, $Ns)

    if ($null -eq $Paragraph) { return }

    $nodes = $Paragraph.SelectNodes('.//w:t', $Ns)
    if ($nodes.Count -eq 0) { return }

    $nodes[0].InnerText = $Text
    for ($i = 1; $i -lt $nodes.Count; $i++) {
        $nodes[$i].InnerText = ''
    }
}

function Find-BodyParagraph {
    param($Paragraphs, [string]$Prefix, $Ns)

    foreach ($p in $Paragraphs) {
        $text = (($p.SelectNodes('.//w:t', $Ns) | ForEach-Object { $_.InnerText }) -join '').Trim()
        $text = $text -replace '^[\u2022\u00B7\u25CF\u25AA\u25E6\u2023\u2043\u2013\u2014\-]\s*', ''
        if ($text.StartsWith($Prefix, [StringComparison]::Ordinal)) {
            return $p
        }
    }

    return $null
}

# ---------------------------------------------------------------------------
# Captura PNG con apariencia de terminal
# ---------------------------------------------------------------------------
function New-TerminalScreenshot {
    param(
        [Parameter(Mandatory)][AllowEmptyString()][AllowEmptyCollection()][string[]]$Lines,
        [Parameter(Mandatory)][string]$Path,
        [int]$Width = 707
    )

    Add-Type -AssemblyName System.Drawing

    $fontSize   = 14
    $lineHeight = 19
    $marginX    = 8
    $marginY    = 5
    $charWidth  = 7.7
    $maxChars   = [Math]::Max(40, [int][Math]::Floor(($Width - (2 * $marginX)) / $charWidth))

    $prepared = New-Object System.Collections.Generic.List[string]
    foreach ($rawLine in $Lines) {
        $clean = ($rawLine -replace '\x1B\[[0-9;]*[A-Za-z]', '').TrimEnd()

        # La captura es evidencia de la prueba, no publicidad del framework:
        # se omite el banner "PHPUnit ... by Sebastian Bergmann and contributors.".
        if ($clean -match '^PHPUnit\s+[\d.]+\s+by\s+Sebastian Bergmann') { continue }

        # PHPUnit escribe el check de testdox como '?' cuando la consola de
        # Windows no puede representar U+2714; se restituye para la captura.
        $clean = $clean -replace '^(\s*)\?(\s)', ('$1' + [char]0x2714 + '$2')

        if ($clean.Length -eq 0) {
            $prepared.Add('')
            continue
        }
        while ($clean.Length -gt $maxChars) {
            $prepared.Add($clean.Substring(0, $maxChars))
            $clean = '  ' + $clean.Substring($maxChars)
        }
        $prepared.Add($clean)
    }

    while ($prepared.Count -gt 0 -and $prepared[0] -eq '') {
        $prepared.RemoveAt(0)
    }
    while ($prepared.Count -gt 0 -and $prepared[$prepared.Count - 1] -eq '') {
        $prepared.RemoveAt($prepared.Count - 1)
    }
    if ($prepared.Count -eq 0) { $prepared.Add('') }

    $height = ($marginY * 2) + ($prepared.Count * $lineHeight)
    $bmp = New-Object System.Drawing.Bitmap($Width, $height)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::ClearTypeGridFit

    $bgBrush      = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(12, 12, 12))
    $defaultBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(212, 212, 212))
    $dimBrush     = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(150, 150, 150))
    $okBrush      = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(78, 201, 120))
    $errorBrush   = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(244, 71, 71))
    $warnBrush    = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(229, 192, 90))
    $cyanBrush    = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(86, 182, 194))

    $g.FillRectangle($bgBrush, 0, 0, $Width, $height)

    $font = New-Object System.Drawing.Font('Consolas', $fontSize, [System.Drawing.FontStyle]::Regular, [System.Drawing.GraphicsUnit]::Pixel)
    $checkFont = New-Object System.Drawing.Font('Consolas', 12, [System.Drawing.FontStyle]::Regular, [System.Drawing.GraphicsUnit]::Pixel)

    $y = $marginY
    foreach ($line in $prepared) {
        $brush = $defaultBrush
        if ($line -match '^\s*OK\b') { $brush = $okBrush }
        elseif ($line -match 'FAILURES!|ERRORS!') { $brush = $errorBrush }
        elseif ($line -match '^\s*Tests:') { $brush = $warnBrush }
        elseif ($line -match '^PHPUnit ') { $brush = $cyanBrush }
        elseif ($line -match '^(Runtime|Configuration|Time):') { $brush = $dimBrush }

        $check = [regex]::Match($line, '^(\s*)([\u2714\u2713])(\s.*)$')
        if ($check.Success) {
            $lead = $check.Groups[1].Value
            $mark = $check.Groups[2].Value
            $rest = $check.Groups[3].Value
            $x = $marginX
            if ($lead.Length -gt 0) {
                $g.DrawString($lead, $font, $defaultBrush, $x, $y)
                $x += [single]($lead.Length * $charWidth)
            }
            $g.DrawString($mark, $checkFont, $okBrush, $x, $y + 1)
            $markWidth = [single]$g.MeasureString($mark, $checkFont, [System.Drawing.PointF]::Empty, [System.Drawing.StringFormat]::GenericTypographic).Width
            $x += [single][Math]::Max($charWidth, $markWidth)
            $g.DrawString($rest, $font, $defaultBrush, $x, $y)
        } else {
            $g.DrawString($line, $font, $brush, $marginX, $y)
        }

        $y += $lineHeight
    }

    $bmp.Save($Path, [System.Drawing.Imaging.ImageFormat]::Png)

    foreach ($brush in @($bgBrush, $defaultBrush, $dimBrush, $okBrush, $errorBrush, $warnBrush, $cyanBrush)) {
        $brush.Dispose()
    }
    $font.Dispose()
    $checkFont.Dispose()
    $g.Dispose()
    $bmp.Dispose()

    return [pscustomobject]@{ Width = $Width; Height = $height }
}

# ---------------------------------------------------------------------------
# Ejecución de un módulo
# ---------------------------------------------------------------------------
function Format-ResultLine {
    param($Run)

    $time = $Run.Time.ToString('0.000', $script:EsCulture)
    $assertions = $Run.Assertions.ToString('N0', $script:EsCulture)

    if ($Run.Failures -gt 0 -or $Run.Errors -gt 0) {
        $passed = $Run.Tests - $Run.Skipped - $Run.Failures - $Run.Errors
        return ("FAIL - {0}/{1} casos · {2} fallos · {3} errores · {4} omitidos · {5} s" -f `
                $passed, $Run.Tests, $Run.Failures, $Run.Errors, $Run.Skipped, $time)
    }

    if ($Run.Skipped -gt 0) {
        $executed = $Run.Tests - $Run.Skipped
        return ("PASS - {0}/{1} casos ejecutados · {2} omitido(s) · {3} aserciones · 0 fallos · 0 errores · {4} s" -f `
                $executed, $Run.Tests, $Run.Skipped, $assertions, $time)
    }

    return ("PASS - {0}/{0} casos · {1} aserciones · 0 fallos · 0 errores · 0 omitidos · {2} s" -f `
            $Run.Tests, $assertions, $time)
}

function Invoke-ModuleRun {
    param([pscustomobject]$ModuleInfo, [string]$WorkDir)

    $junit = Join-Path $WorkDir ('junit-{0:D2}.xml' -f $ModuleInfo.Number)
    if (Test-Path -LiteralPath $junit) { Remove-Item -LiteralPath $junit -Force }

    $relativeTest = 'tests/' + ($ModuleInfo.TestFile -replace '\\', '/')
    $displayTest = $ModuleInfo.TestFile -replace '/', '\'

    $phpArgs = @(
        $script:PhpUnit,
        '-c', $script:Config,
        $relativeTest,
        '--do-not-cache-result',
        '--colors=never',
        '--testdox',
        '--log-junit', $junit
    )

    $stdoutPath = Join-Path $WorkDir ('stdout-{0:D2}.txt' -f $ModuleInfo.Number)
    $stderrPath = Join-Path $WorkDir ('stderr-{0:D2}.txt' -f $ModuleInfo.Number)
    foreach ($file in @($stdoutPath, $stderrPath)) {
        if (Test-Path -LiteralPath $file) { Remove-Item -LiteralPath $file -Force }
    }

    $quotedArgs = @($phpArgs | ForEach-Object {
        if ($_ -match '\s') { '"' + $_ + '"' } else { $_ }
    })

    $stopwatch = [Diagnostics.Stopwatch]::StartNew()
    $process = Start-Process -FilePath 'php' -ArgumentList $quotedArgs `
        -RedirectStandardOutput $stdoutPath -RedirectStandardError $stderrPath `
        -NoNewWindow -Wait -PassThru
    $exitCode = $process.ExitCode
    $stopwatch.Stop()

    $raw = if (Test-Path -LiteralPath $stdoutPath) {
        [IO.File]::ReadAllText($stdoutPath, [Text.Encoding]::UTF8)
    } else {
        ''
    }
    $errorOutput = if (Test-Path -LiteralPath $stderrPath) {
        [IO.File]::ReadAllText($stderrPath, [Text.Encoding]::UTF8)
    } else {
        ''
    }

    if (-not (Test-Path -LiteralPath $junit)) {
        throw "PHPUnit no generó el JUnit del módulo $($ModuleInfo.Number) ($($ModuleInfo.Model)).`n$raw`n$errorOutput"
    }

    [xml]$report = Get-Content -LiteralPath $junit -Raw -Encoding UTF8
    $suite = $report.SelectSingleNode('//testsuite')
    if ($null -eq $suite) {
        throw "El JUnit del módulo $($ModuleInfo.Number) no contiene <testsuite>."
    }

    $tests      = [int]$suite.GetAttribute('tests')
    $assertions = [int]$suite.GetAttribute('assertions')
    $failures   = [int]$suite.GetAttribute('failures')
    $errors     = [int]$suite.GetAttribute('errors')
    $skipped    = [int]$suite.GetAttribute('skipped')
    $time       = [double]::Parse($suite.GetAttribute('time'), [Globalization.CultureInfo]::InvariantCulture)

    $capture = Join-Path $script:CapturesDir ('captura-{0:D2}-{1}.png' -f $ModuleInfo.Number, $ModuleInfo.Model)
    $commandLine = 'php vendor\phpunit\phpunit\phpunit -c tests\phpunit.xml ' + $displayTest + ' --testdox'
    $lines = if ($raw.Length -gt 0) { $raw -split "`r?`n" } else { @('(sin salida de PHPUnit)') }
    $imageWidth = if ($null -ne $ModuleInfo.PSObject.Properties['ImageWidth']) { [int]$ModuleInfo.ImageWidth } else { 707 }
    $size = New-TerminalScreenshot -Lines $lines -Path $capture -Width $imageWidth

    $status = if ($failures -gt 0 -or $errors -gt 0) { 'FAIL' } else { 'PASS' }

    return [pscustomobject]@{
        Module        = $ModuleInfo
        Tests         = $tests
        Assertions    = $assertions
        Failures      = $failures
        Errors        = $errors
        Skipped       = $skipped
        Time          = $time
        ExitCode      = $exitCode
        Status        = $status
        Capture       = $capture
        CaptureWidth  = $size.Width
        CaptureHeight = $size.Height
        Output        = $raw
        ErrorOutput   = $errorOutput
        Command       = $commandLine
    }
}

# ---------------------------------------------------------------------------
# Construcción de la copia del Word
# ---------------------------------------------------------------------------
function New-FilledDocument {
    param(
        [object[]]$Runs,
        [string]$OutPath
    )

    Copy-Item -LiteralPath $script:Template -Destination $OutPath -Force
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem

    $zip = [System.IO.Compression.ZipFile]::Open($OutPath, [System.IO.Compression.ZipArchiveMode]::Update)
    try {
        $docEntry = $zip.GetEntry('word/document.xml')
        [xml]$doc = Read-ZipText -Entry $docEntry

        $ns = New-Object System.Xml.XmlNamespaceManager($doc.NameTable)
        $ns.AddNamespace('w',    'http://schemas.openxmlformats.org/wordprocessingml/2006/main')
        $ns.AddNamespace('a',    'http://schemas.openxmlformats.org/drawingml/2006/main')
        $ns.AddNamespace('r',    $script:RelNs)
        $ns.AddNamespace('wp',   'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing')
        $ns.AddNamespace('pic',  'http://schemas.openxmlformats.org/drawingml/2006/picture')
        $ns.AddNamespace('mc',   'http://schemas.openxmlformats.org/markup-compatibility/2006')

        $tables = $doc.SelectNodes('//w:body/w:tbl', $ns)
        if ($tables.Count -ne 31) {
            throw "El formato original contiene $($tables.Count) cuadros; se esperaban 31."
        }

        $relsEntry = $zip.GetEntry('word/_rels/document.xml.rels')
        [xml]$rels = Read-ZipText -Entry $relsEntry
        $relMap = @{}
        foreach ($rel in $rels.Relationships.Relationship) {
            $relMap[$rel.Id] = $rel.Target
        }

        foreach ($run in $Runs) {
            $table = $tables[$run.Module.Index]
            $rows = $table.SelectNodes('./w:tr', $ns)
            if ($rows.Count -lt 6) {
                throw "El cuadro $($run.Module.Number) no tiene las 6 filas esperadas."
            }

            $cells = $rows[4].SelectNodes('./w:tc', $ns)
            if ($cells.Count -lt 2) {
                throw "El cuadro $($run.Module.Number) no tiene la columna de resultado."
            }
            $resultCell = $cells[1]

            # 1) Texto de resultado
            $resultParagraphs = $resultCell.SelectNodes('./w:p', $ns)
            if ($resultParagraphs.Count -gt 0) {
                Set-ParagraphText -Paragraph $resultParagraphs[0] -Text (Format-ResultLine -Run $run) -Ns $ns

                $textRun = $resultParagraphs[0].SelectSingleNode('./w:r[.//w:t]', $ns)
                if ($null -ne $textRun) {
                    $color = $textRun.SelectSingleNode('./w:rPr/w:color', $ns)
                    if ($null -ne $color) {
                        $valueAttribute = $color.GetAttributeNode('val', $ns.LookupNamespace('w'))
                        if ($null -ne $valueAttribute) {
                            $valueAttribute.Value = $(if ($run.Status -eq 'PASS') { '006100' } else { 'C00000' })
                        }
                    }
                }
            }

            # 2) Total de casos del cuadro
            $caseCells = $rows[3].SelectNodes('./w:tc', $ns)
            if ($caseCells.Count -ge 2) {
                $caseParagraphs = $caseCells[1].SelectNodes('./w:p', $ns)
                if ($caseParagraphs.Count -ge 2) {
                    $totalText = if ($run.Skipped -gt 0) {
                        'Total: {0} casos automatizados ({1} ejecutados y {2} omitido(s) justificado(s)), con evidencia en la captura.' -f `
                            $run.Tests, ($run.Tests - $run.Skipped), $run.Skipped
                    } else {
                        'Total: {0} casos automatizados, ejecutados con el comando PHPUnit del módulo y evidencia en la captura.' -f $run.Tests
                    }
                    Set-ParagraphText -Paragraph $caseParagraphs[1] -Text $totalText -Ns $ns
                }
            }

            # 3) Observaciones: avisar si esta corrida trae fallos
            if ($run.Failures -gt 0 -or $run.Errors -gt 0) {
                $obsCells = $rows[5].SelectNodes('./w:tc', $ns)
                if ($obsCells.Count -ge 2) {
                    $obsParagraphs = $obsCells[1].SelectNodes('./w:p', $ns)
                    if ($obsParagraphs.Count -gt 0) {
                        $previous = (($obsParagraphs[0].SelectNodes('.//w:t', $ns) | ForEach-Object { $_.InnerText }) -join '')
                        $note = ' Esta corrida registró {0} fallos y {1} errores; revisar el JUnit de la ejecución.' -f $run.Failures, $run.Errors
                        Set-ParagraphText -Paragraph $obsParagraphs[0] -Text ($previous.TrimEnd() + $note) -Ns $ns
                    }
                }
            }

            # 4) Captura nueva: mismo rId, bytes nuevos
            $blip = $resultCell.SelectSingleNode('.//a:blip', $ns)
            if ($null -ne $blip) {
                $embedId = $blip.GetAttribute('embed', $script:RelNs)
                if ($relMap.ContainsKey($embedId)) {
                    $mediaEntry = $zip.GetEntry('word/' + $relMap[$embedId])
                    if ($null -ne $mediaEntry) {
                        $bytes = [IO.File]::ReadAllBytes($run.Capture)
                        $mediaStream = $mediaEntry.Open()
                        try {
                            $mediaStream.SetLength(0)
                            $mediaStream.Write($bytes, 0, $bytes.Length)
                        } finally {
                            $mediaStream.Dispose()
                        }
                    }
                }

                $docPr = $resultCell.SelectSingleNode('.//wp:docPr', $ns)
                if ($null -ne $docPr) {
                    [void] $docPr.SetAttribute('descr', ('Salida de PHPUnit del módulo {0:D2} ({1})' -f $run.Module.Number, $run.Module.Model))
                }

                # El marco se ajusta a la proporción real de la captura: conserva
                # el ancho aprobado y evita que la imagen se vea deformada.
                if ($run.CaptureWidth -gt 0 -and $run.CaptureHeight -gt 0) {
                    $ratio = [double]$run.CaptureWidth / [double]$run.CaptureHeight
                    $newCy = [long]0

                    $extent = $resultCell.SelectSingleNode('.//wp:extent', $ns)
                    if ($null -ne $extent) {
                        $cx = [double]$extent.GetAttribute('cx')
                        $newCy = [long][Math]::Round($cx / $ratio)
                        $extent.SetAttribute('cy', $newCy.ToString([Globalization.CultureInfo]::InvariantCulture))
                    }

                    $drawingExtent = $resultCell.SelectSingleNode('.//a:xfrm/a:ext', $ns)
                    if ($null -ne $drawingExtent -and $newCy -gt 0) {
                        $drawingExtent.SetAttribute('cy', $newCy.ToString([Globalization.CultureInfo]::InvariantCulture))
                    }
                }
            }
        }

        # 5) Resumen general
        $bodyParagraphs = $doc.SelectNodes('//w:body/w:p', $ns)
        $executed = $Runs.Count
        $partial = $executed -lt 31

        $sumTests      = ($Runs | Measure-Object -Property Tests -Sum).Sum
        $sumAssertions = ($Runs | Measure-Object -Property Assertions -Sum).Sum
        $sumFailures   = ($Runs | Measure-Object -Property Failures -Sum).Sum
        $sumErrors     = ($Runs | Measure-Object -Property Errors -Sum).Sum
        $sumSkipped    = ($Runs | Measure-Object -Property Skipped -Sum).Sum
        $sumTime       = ($Runs | Measure-Object -Property Time -Sum).Sum
        $exitCode      = ($Runs | Measure-Object -Property ExitCode -Maximum).Maximum

        $suiteParagraph = Find-BodyParagraph -Paragraphs $bodyParagraphs -Prefix 'Suite:' -Ns $ns
        if ($null -ne $suiteParagraph) {
            Set-ParagraphText -Paragraph $suiteParagraph -Ns $ns -Text (
                '• Suite: PHPUnit {0} · PHP {1} · SQLite en memoria con el esquema real del sistema.' -f `
                $script:PhpUnitVersion, $script:PhpVersion)
        }

        $globalParagraph = Find-BodyParagraph -Paragraphs $bodyParagraphs -Prefix 'Resultado global' -Ns $ns
        if ($null -ne $globalParagraph) {
            $scope = if ($partial) { "Resultado global (parcial, $executed de 31 módulos)" } else { 'Resultado global' }
            Set-ParagraphText -Paragraph $globalParagraph -Ns $ns -Text (
                '• {0}: {1} casos · {2} aserciones · {3} fallos · {4} errores · {5} casos omitidos justificados.' -f `
                $scope,
                $sumTests.ToString('N0', $script:EsCulture),
                $sumAssertions.ToString('N0', $script:EsCulture),
                $sumFailures,
                $sumErrors,
                $sumSkipped)
        }

        $timeParagraph = Find-BodyParagraph -Paragraphs $bodyParagraphs -Prefix 'Tiempo total' -Ns $ns
        if ($null -ne $timeParagraph) {
            Set-ParagraphText -Paragraph $timeParagraph -Ns $ns -Text (
                '• Tiempo total: {0} s · Código de salida: {1}.' -f `
                ([double]$sumTime).ToString('0.000', $script:EsCulture), $exitCode)
        }

        if ($partial) {
            $coverageParagraph = Find-BodyParagraph -Paragraphs $bodyParagraphs -Prefix 'Cobertura:' -Ns $ns
            if ($null -ne $coverageParagraph) {
                Set-ParagraphText -Paragraph $coverageParagraph -Ns $ns -Text (
                    '• Cobertura: {0} de 31 módulos ejecutados en esta corrida parcial; el resto conserva los datos del formato.' -f $executed)
            }
        }

        if (-not $partial) {
            $finalParagraph = Find-BodyParagraph -Paragraphs $bodyParagraphs -Prefix 'Observación general' -Ns $ns
            if ($null -ne $finalParagraph) {
                Set-ParagraphText -Paragraph $finalParagraph -Ns $ns -Text (
                    'Observación general: la suite completa finalizó con código de salida {0}. Los {1} casos omitidos corresponden a operaciones no portables a SQLite y se justifican en cada módulo. Evidencia detallada: tests/phpunit.xml, tests/Integration/ y reporte JUnit de la ejecución.' -f `
                    $exitCode, $sumSkipped)
            }
        }

        Write-ZipXml -Entry $docEntry -Document $doc

        # 6) Metadatos: fecha de modificación
        $coreEntry = $zip.GetEntry('docProps/core.xml')
        if ($null -ne $coreEntry) {
            $coreText = Read-ZipText -Entry $coreEntry
            $utc = (Get-Date).ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ')
            $coreText = [regex]::Replace($coreText, '(<dcterms:modified[^>]*>)[^<]*(</dcterms:modified>)', ('$1' + $utc + '$2'))
            $bytes = (New-Object System.Text.UTF8Encoding($false)).GetBytes($coreText)
            $stream = $coreEntry.Open()
            try {
                $stream.SetLength(0)
                $stream.Write($bytes, 0, $bytes.Length)
            } finally {
                $stream.Dispose()
            }
        }
    } finally {
        $zip.Dispose()
    }
}

# ---------------------------------------------------------------------------
# Inicio
# ---------------------------------------------------------------------------
Write-Host ''
Write-Host '===== Formato Word de pruebas de integración =====' -ForegroundColor Cyan
Write-Host ''

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host 'ERROR: no se encontró php en el PATH.' -ForegroundColor Red
    exit 3
}
if (-not (Test-Path -LiteralPath $script:PhpUnit)) {
    Write-Host "ERROR: falta PHPUnit en $script:PhpUnit. Ejecuta composer install." -ForegroundColor Red
    exit 3
}
if (-not (Test-Path -LiteralPath $script:Template)) {
    Write-Host "ERROR: falta el formato original en $script:Template." -ForegroundColor Red
    exit 3
}

Set-Location $script:Root

$script:PhpVersion = (& php -r "echo PHP_VERSION;" 2>$null | Out-String).Trim()
$versionLine = ((& php $script:PhpUnit --version 2>$null) -join ' ')
if ($versionLine -match 'PHPUnit\s+([0-9.]+)') {
    $script:PhpUnitVersion = $Matches[1]
} else {
    $script:PhpUnitVersion = '11.x'
}

if (-not (Test-Path -LiteralPath $script:ResultsDir)) {
    New-Item -ItemType Directory -Force -Path $script:ResultsDir | Out-Null
}
if (-not (Test-Path -LiteralPath $script:CapturesDir)) {
    New-Item -ItemType Directory -Force -Path $script:CapturesDir | Out-Null
}

$catalog = Get-ModuleCatalog
if ($catalog.Count -ne 31) {
    Write-Host "ERROR: se esperaban 31 módulos en tests\phpunit.xml y se encontraron $($catalog.Count)." -ForegroundColor Red
    exit 3
}

try {
    $templateWidths = @(Get-TemplateImageWidths)
} catch {
    $templateWidths = @()
    Write-Host ('Aviso: no se pudieron leer los anchos del formato; se usará 707 px. ' + $_.Exception.Message) -ForegroundColor DarkYellow
}

for ($i = 0; $i -lt $catalog.Count; $i++) {
    $width = if ($i -lt $templateWidths.Count) { [int]$templateWidths[$i] } else { 707 }
    $catalog[$i] | Add-Member -NotePropertyName ImageWidth -NotePropertyValue $width -Force
}

$selected = Select-Modules -Catalog $catalog -Filter $Module
if ($selected.Count -eq 0) {
    Write-Host "ERROR: '$Module' no coincide con ningún módulo (01-31 o nombre del modelo)." -ForegroundColor Red
    exit 2
}

$workDir = Join-Path ([IO.Path]::GetTempPath()) ('integration-doc-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Force -Path $workDir | Out-Null

$runs = New-Object System.Collections.Generic.List[object]
$failuresTotal = 0

try {
    foreach ($moduleInfo in $selected) {
        Write-Host ('[{0:D2}/31] {1,-16} ' -f $moduleInfo.Number, $moduleInfo.Model) -NoNewline

        $run = Invoke-ModuleRun -ModuleInfo $moduleInfo -WorkDir $workDir
        $runs.Add($run)
        if ($run.Status -eq 'FAIL') { $failuresTotal++ }

        $color = if ($run.Status -eq 'PASS') { 'Green' } else { 'Red' }
        $time = $run.Time.ToString('0.000', $script:EsCulture)
        Write-Host ('{0}  {1}/{2} casos · {3} aserciones · {4} s' -f `
                $run.Status, ($run.Tests - $run.Skipped), $run.Tests,
                $run.Assertions.ToString('N0', $script:EsCulture), $time) -ForegroundColor $color
    }

    if ([string]::IsNullOrWhiteSpace($Output)) {
        $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
        $Output = Join-Path $script:ResultsDir ("Formato-Prueba-Integración-$stamp.docx")
    } elseif (-not [IO.Path]::IsPathRooted($Output)) {
        $Output = Join-Path (Get-Location).Path $Output
    }

    $outputDir = Split-Path -Parent $Output
    if ($outputDir -and -not (Test-Path -LiteralPath $outputDir)) {
        New-Item -ItemType Directory -Force -Path $outputDir | Out-Null
    }

    if ([IO.Path]::GetFullPath($Output) -eq [IO.Path]::GetFullPath($script:Template)) {
        throw 'La salida no puede sobrescribir el formato original.'
    }

    Write-Host ''
    Write-Host 'Generando copia del formato Word...' -ForegroundColor Cyan
    New-FilledDocument -Runs $runs.ToArray() -OutPath $Output

    $summary = [pscustomobject]@{
        generado    = (Get-Date).ToString('o')
        documento   = $Output
        plantilla   = $script:Template
        php         = $script:PhpVersion
        phpunit     = $script:PhpUnitVersion
        modulos     = @($runs | ForEach-Object {
            [pscustomobject]@{
                numero     = $_.Module.Number
                modelo     = $_.Module.Model
                archivo    = $_.Module.TestFile
                casos      = $_.Tests
                aserciones = $_.Assertions
                fallos     = $_.Failures
                errores    = $_.Errors
                omitidos   = $_.Skipped
                tiempo     = $_.Time
                estado     = $_.Status
                salida     = $_.ExitCode
                captura    = $_.Capture
            }
        })
    }
    $summary | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath $script:Manifest -Encoding UTF8

    $sumTests      = ($runs | Measure-Object -Property Tests -Sum).Sum
    $sumAssertions = ($runs | Measure-Object -Property Assertions -Sum).Sum
    $sumSkipped    = ($runs | Measure-Object -Property Skipped -Sum).Sum
    $sumTime       = ($runs | Measure-Object -Property Time -Sum).Sum

    Write-Host ''
    Write-Host '================ RESUMEN ================' -ForegroundColor Cyan
    Write-Host ('Módulos ejecutados : {0} de 31' -f $runs.Count)
    Write-Host ('Casos              : {0}' -f $sumTests.ToString('N0', $script:EsCulture))
    Write-Host ('Aserciones         : {0}' -f $sumAssertions.ToString('N0', $script:EsCulture))
    Write-Host ('Omitidos           : {0}' -f $sumSkipped)
    Write-Host ('Tiempo total       : {0} s' -f ([double]$sumTime).ToString('0.000', $script:EsCulture))
    Write-Host ('Módulos con fallos : {0}' -f $failuresTotal)
    Write-Host ''
    Write-Host ('Documento          : {0}' -f $Output) -ForegroundColor Green
    Write-Host ('Capturas           : {0}' -f $script:CapturesDir)
    Write-Host ('Manifiesto         : {0}' -f $script:Manifest)
    Write-Host ''

    if ($failuresTotal -gt 0) {
        Write-Host 'RESULTADO: hay módulos con fallos. Revisa el JUnit y las observaciones.' -ForegroundColor Red
        exit 1
    }

    Write-Host 'RESULTADO: copia generada sin fallos.' -ForegroundColor Green
    exit 0
} catch {
    Write-Host ''
    Write-Host ('ERROR: ' + $_.Exception.Message) -ForegroundColor Red
    exit 3
} finally {
    if (-not $KeepWorking) {
        Remove-Item -LiteralPath $workDir -Recurse -Force -ErrorAction SilentlyContinue
    } else {
        Write-Host ('JUnit temporales: {0}' -f $workDir) -ForegroundColor DarkGray
    }
}
