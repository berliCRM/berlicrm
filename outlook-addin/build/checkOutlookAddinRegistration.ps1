<#
Run in an existing Exchange Online PowerShell session with permission to read
the affected mailbox and organization configuration. This script only reads
configuration; it does not connect, install modules, or change any settings.

Example:
  .\outlook-addin\build\checkOutlookAddinRegistration.ps1 -MailboxIdentity 'user@example.org' |
    Format-List
#>
[CmdletBinding()]
param (
    [Parameter(Mandatory = $true)]
    [ValidateNotNullOrEmpty()]
    [string]$MailboxIdentity
)

try {
    $organization = Get-OrganizationConfig -ErrorAction Stop
    [pscustomobject]@{
        Check = 'Organization'
        AppsForOfficeEnabled = $organization.AppsForOfficeEnabled
    }
} catch {
    Write-Warning ('Organization check failed: ' + $_.Exception.Message)
}

try {
    $installedApps = @(Get-App -Mailbox $MailboxIdentity -ErrorAction Stop)
    $expectedApps = @(
        @{ Name = 'berliCRM Outlook'; Id = '4fbaf5e4-1f93-4c85-9cbf-128c7d8a1909' },
        @{ Name = 'berliCRM Befehlstest'; Id = '8e511bf7-4d56-464e-a9e5-334653d7853b' },
        @{ Name = 'berliCRM Kalender Minimaltest'; Id = 'c20becc2-18ca-45a3-b0bc-9ebfd4e7c648' }
    )
    foreach ($expectedApp in $expectedApps) {
        $matches = @($installedApps | Where-Object {
            ([string]$_.AppId).Trim('{}') -eq $expectedApp.Id
        })
        if ($matches.Count -eq 0) {
            [pscustomobject]@{
                Check = 'MailboxApp'
                DisplayName = $expectedApp.Name
                AppId = $expectedApp.Id
                Found = $false
            }
        } else {
            foreach ($app in $matches) {
                [pscustomobject]@{
                    Check = 'MailboxApp'
                    DisplayName = $app.DisplayName
                    AppId = $app.AppId
                    Found = $true
                    Enabled = $app.Enabled
                    AppVersion = $app.AppVersion
                }
            }
        }
    }
} catch {
    Write-Warning ('Mailbox add-in check failed: ' + $_.Exception.Message)
}

try {
    $mailbox = Get-Mailbox -Identity $MailboxIdentity -ErrorAction Stop
    if (-not $mailbox.RoleAssignmentPolicy) {
        throw 'The mailbox has no RoleAssignmentPolicy value.'
    }
    $policy = Get-RoleAssignmentPolicy -Identity $mailbox.RoleAssignmentPolicy -ErrorAction Stop
    [pscustomobject]@{
        Check = 'RoleAssignmentPolicy'
        Name = $policy.Name
        AssignedRoles = @($policy.AssignedRoles | ForEach-Object { [string]$_ }) -join '; '
    }
} catch {
    Write-Warning ('Role policy check failed: ' + $_.Exception.Message)
}
