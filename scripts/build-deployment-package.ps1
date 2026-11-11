[CmdletBinding()]
param(
    [ValidateSet('central', 'branch', 'both')]
    [string] $Target = 'both',
    [string] $OutputDirectory = '',
    [switch] $SkipFrontendBuild
)

$ErrorActionPreference = 'Stop'
$projectRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
if ([string]::IsNullOrWhiteSpace($OutputDirectory)) {
    $OutputDirectory = Join-Path $projectRoot 'dist'
}
$OutputDirectory = [System.IO.Path]::GetFullPath($OutputDirectory)
$stagingRoot = Join-Path $projectRoot ('storage\framework\deployment-release-' + [guid]::NewGuid().ToString('N'))
$releaseId = Get-Date -Format 'yyyyMMdd-HHmmss'
$targets = if ($Target -eq 'both') { @('central', 'branch') } else { @($Target) }

function Invoke-CheckedCommand {
    param([string] $Executable, [string[]] $Arguments, [string] $WorkingDirectory)
    Push-Location $WorkingDirectory
    try {
        & $Executable @Arguments
        if ($LASTEXITCODE -ne 0) {
            throw "$Executable exited with code $LASTEXITCODE."
        }
    } finally {
        Pop-Location
    }
}

function Copy-RequiredItem {
    param([string] $RelativePath, [string] $DestinationRoot)
    $source = Join-Path $projectRoot $RelativePath
    if (-not (Test-Path -LiteralPath $source)) {
        throw "Required release source is missing: $RelativePath"
    }
    $destination = Join-Path $DestinationRoot $RelativePath
    $parent = Split-Path -Parent $destination
    New-Item -ItemType Directory -Force -Path $parent | Out-Null
    Copy-Item -LiteralPath $source -Destination $destination -Recurse -Force
}

function New-StorageSkeleton {
    param([string] $DestinationRoot)
    foreach ($relative in @(
        'storage\app\private',
        'storage\app\public',
        'storage\framework\cache\data',
        'storage\framework\sessions',
        'storage\framework\testing',
        'storage\framework\views',
        'storage\logs',
        'bootstrap\cache'
    )) {
        $directory = Join-Path $DestinationRoot $relative
        New-Item -ItemType Directory -Force -Path $directory | Out-Null
        Set-Content -LiteralPath (Join-Path $directory '.gitignore') -Value "*`n!.gitignore`n" -NoNewline
    }
}

function Assert-ReleaseSafe {
    param([string] $DestinationRoot)
    $forbiddenNames = @('.env', 'database.sqlite', 'phpunit-events.txt')
    $forbidden = Get-ChildItem -LiteralPath $DestinationRoot -Recurse -Force -File | Where-Object {
        $_.Name -in $forbiddenNames -or
        $_.Extension -in @('.sql', '.pem', '.pfx') -or
        ($_.Extension -eq '.key' -and $_.FullName -notlike '*vendor*')
    }
    if ($forbidden) {
        throw 'Release contains forbidden runtime data: ' + (($forbidden.FullName | ForEach-Object { $_.Substring($DestinationRoot.Length + 1) }) -join ', ')
    }
    foreach ($required in @('vendor\autoload.php', 'public\build\manifest.json', 'public\vendor\offline\fonts.css', '.env.deploy.example')) {
        if (-not (Test-Path -LiteralPath (Join-Path $DestinationRoot $required))) {
            throw "Release is missing required file: $required"
        }
    }
}

function New-PortableZip {
    param([string] $SourceDirectory, [string] $DestinationPath)
    Add-Type -AssemblyName System.IO.Compression
    if (Test-Path -LiteralPath $DestinationPath) {
        Remove-Item -LiteralPath $DestinationPath -Force
    }
    $zipStream = [System.IO.File]::Open($DestinationPath, [System.IO.FileMode]::CreateNew)
    $archive = [System.IO.Compression.ZipArchive]::new(
        $zipStream,
        [System.IO.Compression.ZipArchiveMode]::Create,
        $false
    )
    try {
        foreach ($file in Get-ChildItem -LiteralPath $SourceDirectory -Recurse -Force -File) {
            $relative = $file.FullName.Substring($SourceDirectory.Length).TrimStart('\', '/').Replace('\', '/')
            $entry = $archive.CreateEntry($relative, [System.IO.Compression.CompressionLevel]::Optimal)
            $entry.LastWriteTime = $file.LastWriteTime
            $sourceStream = $file.OpenRead()
            $entryStream = $entry.Open()
            try {
                $sourceStream.CopyTo($entryStream)
            } finally {
                $entryStream.Dispose()
                $sourceStream.Dispose()
            }
        }
    } finally {
        $archive.Dispose()
        $zipStream.Dispose()
    }
}

if (-not $SkipFrontendBuild) {
    Invoke-CheckedCommand -Executable 'npm.cmd' -Arguments @('run', 'build') -WorkingDirectory $projectRoot
}

New-Item -ItemType Directory -Force -Path $OutputDirectory | Out-Null
New-Item -ItemType Directory -Force -Path $stagingRoot | Out-Null

try {
    foreach ($deploymentTarget in $targets) {
        $stage = Join-Path $stagingRoot $deploymentTarget
        New-Item -ItemType Directory -Force -Path $stage | Out-Null

        foreach ($item in @(
            'app', 'bootstrap\app.php', 'composer.json', 'composer.lock', 'config',
            'database\migrations', 'public\.htaccess', 'public\favicon.ico', 'public\images',
            'public\index.php', 'public\robots.txt', 'public\build', 'public\vendor\offline',
            'resources', 'routes', 'artisan', 'docs\offline-branch-deployment.md',
            'docs\offline-branch-acceptance.md', 'docs\offline-branch-lan-vhost.example.conf',
            'scripts\offline-preflight.php', 'deploy\README.md'
        )) {
            Copy-RequiredItem -RelativePath $item -DestinationRoot $stage
        }

        Copy-Item -LiteralPath (Join-Path $projectRoot "deploy\env\.env.$deploymentTarget.example") `
            -Destination (Join-Path $stage '.env.deploy.example') -Force
        New-StorageSkeleton -DestinationRoot $stage

        Invoke-CheckedCommand -Executable 'composer' -Arguments @(
            'install', '--no-dev', '--prefer-dist', '--no-interaction', '--optimize-autoloader'
        ) -WorkingDirectory $stage

        Get-ChildItem -LiteralPath (Join-Path $stage 'bootstrap\cache') -File -Filter '*.php' -ErrorAction SilentlyContinue |
            Remove-Item -Force

        $revision = (& git -C $projectRoot rev-parse --short HEAD 2>$null)
        $dirty = [bool](& git -C $projectRoot status --porcelain 2>$null)
        $manifest = [ordered]@{
            target = $deploymentTarget
            built_at = (Get-Date).ToUniversalTime().ToString('o')
            source_revision = ($revision | Select-Object -First 1)
            source_has_uncommitted_changes = $dirty
            php = (& php -r 'echo PHP_VERSION;')
            contains_secrets = $false
            next_step = 'Copy .env.deploy.example to .env, replace placeholders, then follow deploy/README.md.'
        }
        $manifest | ConvertTo-Json -Depth 3 | Set-Content -LiteralPath (Join-Path $stage 'RELEASE-MANIFEST.json') -Encoding UTF8

        Assert-ReleaseSafe -DestinationRoot $stage

        $zipPath = Join-Path $OutputDirectory "funeral-system-$deploymentTarget-$releaseId.zip"
        New-PortableZip -SourceDirectory $stage -DestinationPath $zipPath
        $hash = Get-FileHash -LiteralPath $zipPath -Algorithm SHA256
        Set-Content -LiteralPath ($zipPath + '.sha256') -Value ($hash.Hash.ToLowerInvariant() + '  ' + [System.IO.Path]::GetFileName($zipPath))
        Write-Host "Created $zipPath"
    }
} finally {
    $resolvedStorage = [System.IO.Path]::GetFullPath((Join-Path $projectRoot 'storage\framework'))
    $resolvedStaging = [System.IO.Path]::GetFullPath($stagingRoot)
    if ($resolvedStaging.StartsWith($resolvedStorage, [System.StringComparison]::OrdinalIgnoreCase) -and (Test-Path -LiteralPath $resolvedStaging)) {
        Remove-Item -LiteralPath $resolvedStaging -Recurse -Force
    }
}
