# =============================================================================
# sync-errors.ps1 — matthummel-theme error tracker
# Reads WP debug.log, extracts new unique errors, appends to error-tracking.log
# and commits + pushes to GitHub.
#
# Usage:
#   .\sync-errors.ps1              # run once
#   .\sync-errors.ps1 -Watch       # loop every 5 min
# =============================================================================

param(
    [switch]$Watch,
    [int]$IntervalSeconds = 300
)

$ThemeDir  = $PSScriptRoot
$DebugLog  = "C:\Users\mhumm\wordpress\app\public\wp-content\debug.log"
$TrackLog  = Join-Path $ThemeDir "error-tracking.log"
$StateFile = Join-Path $ThemeDir ".error-sync-state"   # last-processed line count

function Sync-Errors {
    if (-not (Test-Path $DebugLog)) {
        Write-Host "debug.log not found at $DebugLog — skipping." -ForegroundColor Yellow
        return
    }

    # ── Read debug.log ────────────────────────────────────────────────────────
    $allLines   = Get-Content $DebugLog -Encoding UTF8
    $lastLine   = if (Test-Path $StateFile) { [int](Get-Content $StateFile) } else { 0 }
    $newLines   = $allLines | Select-Object -Skip $lastLine

    if ($newLines.Count -eq 0) {
        Write-Host "No new log entries since last sync." -ForegroundColor Cyan
        return
    }

    # ── Extract unique error signatures ──────────────────────────────────────
    $seen    = @{}
    $entries = @()

    foreach ($line in $newLines) {
        if ($line -notmatch '^\[') { continue }

        # Normalise: strip timestamp so identical errors collapse
        $sig = $line -replace '^\[\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2} UTC\]\s*', ''

        if ($seen.ContainsKey($sig)) {
            $seen[$sig]++
            continue
        }
        $seen[$sig] = 1

        # Classify severity
        $level = switch -Regex ($sig) {
            'PHP Fatal'       { 'FATAL'      }
            'PHP Parse'       { 'FATAL'      }
            'PHP Warning'     { 'WARNING'    }
            'PHP Notice'      { 'NOTICE'     }
            'PHP Deprecated'  { 'DEPRECATED' }
            default           { 'INFO'       }
        }

        $entries += [PSCustomObject]@{
            Timestamp = (Get-Date -Format 'yyyy-MM-dd HH:mm') + ' local'
            Level     = $level
            Message   = $sig.Trim()
        }
    }

    if ($entries.Count -eq 0) {
        Write-Host "No new unique errors found in $($newLines.Count) new lines." -ForegroundColor Green
        $allLines.Count | Set-Content $StateFile
        return
    }

    # ── Append to error-tracking.log ─────────────────────────────────────────
    $stamp   = Get-Date -Format 'yyyy-MM-dd HH:mm'
    $divider = "`n================================================================================`n"
    $block   = "$divider SESSION SYNC: $stamp`n$divider`n"

    foreach ($e in $entries) {
        $count = $seen[$e.Message]
        $block += "[$($e.Level)] $($e.Message)`n"
        if ($count -gt 1) { $block += "  Occurrences: $count times in this batch`n" }
        $block += "`n"
    }

    Add-Content -Path $TrackLog -Value $block -Encoding UTF8
    Write-Host "Appended $($entries.Count) unique error(s) to error-tracking.log" -ForegroundColor Green

    # ── Update state ──────────────────────────────────────────────────────────
    $allLines.Count | Set-Content $StateFile

    # ── Git commit + push ──────────────────────────────────────────────────────
    Push-Location $ThemeDir
    try {
        git add error-tracking.log
        $msg = "chore: sync error log $stamp ($($entries.Count) new entries)"
        git commit -m $msg
        git push origin main
        Write-Host "Pushed to GitHub: $msg" -ForegroundColor Magenta
    } catch {
        Write-Host "Git push failed: $_" -ForegroundColor Red
    }
    Pop-Location
}

# ── Run once or in watch loop ─────────────────────────────────────────────────
if ($Watch) {
    Write-Host "Watching debug.log every $IntervalSeconds seconds. Ctrl+C to stop." -ForegroundColor Cyan
    while ($true) {
        Sync-Errors
        Start-Sleep -Seconds $IntervalSeconds
    }
} else {
    Sync-Errors
}
