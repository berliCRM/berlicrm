param([Parameter(Mandatory=$true)][string]$ArtifactDirectory)
$ErrorActionPreference = 'Stop'
$wordDocxTest = $null
try {
    $wordDocxTest = New-Object -ComObject Word.Application
    $wordDocxTest.Visible = $false
    $wordDocxTest.DisplayAlerts = 0
    $wordDocxTest.AutomationSecurity = 3
    foreach ($testCase in @(
        @{ Name='multipage-template.docx'; Pages=2; Sections=2; Images=1 },
        @{ Name='multipage-single.docx'; Pages=2; Sections=2; Images=1 },
        @{ Name='multipage-combined.docx'; Pages=4; Sections=4; Images=2 },
        @{ Name='zip-letter-1.docx'; Pages=2; Sections=2; Images=1; Recipient='Lovelace' },
        @{ Name='zip-letter-2.docx'; Pages=2; Sections=2; Images=1; Recipient='Hopper' }
    )) {
        $testDocument = $null
        try {
            $testPath = Join-Path (Resolve-Path -LiteralPath $ArtifactDirectory) $testCase.Name
            # Read-only, no conversion confirmation, no recent-files entry.
            $testDocument = $wordDocxTest.Documents.Open($testPath, $false, $true, $false)
            $testDocument.Repaginate()
            $testPages = $testDocument.ComputeStatistics(2)
            $testSections = $testDocument.Sections.Count
            $testImages = $testDocument.InlineShapes.Count
            if ($testPages -ne $testCase.Pages -or $testSections -ne $testCase.Sections -or $testImages -ne $testCase.Images) {
                throw "$($testCase.Name): pages=$testPages sections=$testSections images=$testImages"
            }
            if ($testCase.Name -ne 'multipage-template.docx' -and $testDocument.Content.Text -match 'CONTACT_(FIRST|LAST)') { throw 'Unmerged field.' }
            if ($testCase.Recipient -and !$testDocument.Content.Text.Contains($testCase.Recipient)) { throw 'Recipient not present in extracted ZIP letter.' }
            Write-Output "$($testCase.Name): OK; pages=$testPages sections=$testSections images=$testImages; opened by Word $($wordDocxTest.Version)"
        } finally {
            if ($null -ne $testDocument) { $testDocument.Close(0); [void][Runtime.InteropServices.Marshal]::FinalReleaseComObject($testDocument) }
        }
    }
} finally {
    if ($null -ne $wordDocxTest) { $wordDocxTest.Quit(); [void][Runtime.InteropServices.Marshal]::FinalReleaseComObject($wordDocxTest) }
}
