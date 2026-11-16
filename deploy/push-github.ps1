param(
    [Parameter(Mandatory=$true)]
    [string]$RepoUrl
)

Set-Location $PSScriptRoot\..
git branch -M main
if (git remote | Select-String "origin") {
    git remote set-url origin $RepoUrl
} else {
    git remote add origin $RepoUrl
}
git push -u origin main
