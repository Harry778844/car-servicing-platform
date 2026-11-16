<#
.SYNOPSIS
    Automated GitHub Repository Creator & Pusher for AutoPro Car Servicing Platform
.DESCRIPTION
    Works both with Git CLI (if installed) or directly via GitHub REST API (zero Git installation needed).
#>

param (
    [Parameter(Mandatory=$false)]
    [string]$RepoName = "car-servicing-platform",

    [Parameter(Mandatory=$false)]
    [string]$GithubToken = "",

    [Parameter(Mandatory=$false)]
    [switch]$Private = $false
)

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " AutoPro - GitHub Repository Creator & Deployment Helper" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan

# Check if git binary is available
$hasGit = (Get-Command git -ErrorAction SilentlyContinue) -ne $null

if ($hasGit) {
    Write-Host "[✓] Git CLI detected on your system!" -ForegroundColor Green

    # Check if remote exists
    if (-not (Test-Path ".git")) {
        Write-Host "Initializing local git repository..." -ForegroundColor Yellow
        git init
        git branch -M main
    }

    git add .
    git commit -m "Initial commit: Complete AutoPro Car Servicing & Maintenance Platform"

    if ([string]::IsNullOrWhiteSpace($GithubToken)) {
        $GithubUsername = Read-Host "Enter your GitHub Username"
        $RemoteUrl = "https://github.com/$GithubUsername/$RepoName.git"
        Write-Host "Configuring remote: $RemoteUrl" -ForegroundColor Cyan
        git remote remove origin -ErrorAction SilentlyContinue
        git remote add origin $RemoteUrl
        Write-Host ""
        Write-Host "Run the following command to push to your repository:" -ForegroundColor Yellow
        Write-Host "git push -u origin main" -ForegroundColor White
    } else {
        # Using token directly
        $headers = @{
            "Authorization" = "Bearer $GithubToken"
            "Accept" = "application/vnd.github+json"
            "User-Agent" = "AutoPro-Deployer"
        }

        # Create repo via API
        $body = @{
            name = $RepoName
            description = "AutoPro - Full-stack Car Servicing & Maintenance Platform with Doorstep Pickup, 12-Stage Tracking, and Role-Based Portals"
            private = $Private.IsPresent
        } | ConvertTo-Json

        try {
            Write-Host "Creating GitHub repository '$RepoName'..." -ForegroundColor Cyan
            $newRepo = Invoke-RestMethod -Uri "https://api.github.com/user/repos" -Method Post -Headers $headers -Body $body
            $pushUrl = "https://$($GithubToken)@github.com/$($newRepo.full_name).git"
            git remote remove origin -ErrorAction SilentlyContinue
            git remote add origin $pushUrl
            git push -u origin main
            Write-Host "Successfully pushed to: $($newRepo.html_url)" -ForegroundColor Green
        } catch {
            Write-Host "GitHub API error: $_" -ForegroundColor Red
        }
    }
} else {
    Write-Host "[!] Git CLI is not detected in your PATH." -ForegroundColor Yellow
    Write-Host "Using GitHub REST API to create repository and upload files..." -ForegroundColor Cyan

    if ([string]::IsNullOrWhiteSpace($GithubToken)) {
        Write-Host ""
        Write-Host "To push your files to GitHub without Git installed:" -ForegroundColor Yellow
        Write-Host "1. Create a GitHub Personal Access Token (classic) with 'repo' scope at:" -ForegroundColor White
        Write-Host "   https://github.com/settings/tokens" -ForegroundColor Cyan
        Write-Host "2. Then run: .\deploy-github.ps1 -GithubToken 'your_token_here'" -ForegroundColor Green
        Write-Host ""
        $GithubToken = Read-Host "Or paste your GitHub Personal Access Token here"
    }

    if (-not [string]::IsNullOrWhiteSpace($GithubToken)) {
        $headers = @{
            "Authorization" = "Bearer $GithubToken"
            "Accept" = "application/vnd.github+json"
            "User-Agent" = "AutoPro-Uploader"
        }

        # Create Repo
        $body = @{
            name = $RepoName
            description = "AutoPro - Full-stack Car Servicing & Maintenance Platform with Doorstep Pickup, 12-Stage Tracking, and Role-Based Portals"
            private = $Private.IsPresent
        } | ConvertTo-Json

        try {
            Write-Host "Creating repository '$RepoName' on your GitHub..." -ForegroundColor Cyan
            $repo = Invoke-RestMethod -Uri "https://api.github.com/user/repos" -Method Post -Headers $headers -Body $body
            $owner = $repo.owner.login
            $repoName = $repo.name
            Write-Host "[✓] Repository created: $($repo.html_url)" -ForegroundColor Green

            # Upload all files
            $files = Get-ChildItem -Recurse -File | Where-Object { 
                $_.FullName -notmatch '\\\.git\\' -and 
                $_.FullName -notmatch '\\\.vscode\\' -and 
                $_.Extension -ne '.sqlite' 
            }

            $count = 0
            foreach ($file in $files) {
                $count++
                $relativePath = $file.FullName.Substring($PWD.Path.Length + 1).Replace('\', '/')
                Write-Host "[$count/$($files.Count)] Uploading $relativePath..." -ForegroundColor Gray
                
                $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
                $base64 = [Convert]::ToBase64String($bytes)

                $fileBody = @{
                    message = "Add $relativePath"
                    content = $base64
                } | ConvertTo-Json

                $uploadUri = "https://api.github.com/repos/$owner/$repoName/contents/$relativePath"
                Invoke-RestMethod -Uri $uploadUri -Method Put -Headers $headers -Body $fileBody | Out-Null
            }

            Write-Host ""
            Write-Host "============================================================" -ForegroundColor Green
            Write-Host " All files committed & pushed to GitHub successfully!" -ForegroundColor Green
            Write-Host " View repository: $($repo.html_url)" -ForegroundColor Yellow
            Write-Host "============================================================" -ForegroundColor Green

        } catch {
            Write-Host "Error creating or uploading: $_" -ForegroundColor Red
        }
    }
}
