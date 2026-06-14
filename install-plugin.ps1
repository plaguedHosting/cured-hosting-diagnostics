# Copy this repo's plugin into the local WordPress plugins folder (for testing)
param(
    [string]$SourcePlugin = 'mu-plugins\cured-hosting-diagnostics-package',
    [string]$Dest = '.\plugins\cured-hosting-diagnostics-package'
)

if (-Not (Test-Path -Path $SourcePlugin)) {
    Write-Error "Source plugin folder '$SourcePlugin' not found. Run this from the repository root."
    exit 1
}

New-Item -ItemType Directory -Path (Split-Path $Dest) -Force | Out-Null
Copy-Item -Path $SourcePlugin -Destination $Dest -Recurse -Force
Write-Host "Plugin copied to $Dest. Activate under /wp-admin/plugins.php"
