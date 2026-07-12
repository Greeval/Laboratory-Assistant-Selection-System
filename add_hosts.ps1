$hostsPath = "C:\Windows\System32\drivers\etc\hosts"
$current = Get-Content $hostsPath -Raw
if ($current -notmatch "saslab\.com") {
    $current += "`r`n127.0.0.1 saslab.com`r`n127.0.0.1 www.saslab.com`r`n"
    Set-Content -Path $hostsPath -Value $current -NoNewline -Encoding ASCII
    Write-Host "saslab.com berhasil ditambahkan ke hosts file!"
} else {
    Write-Host "saslab.com sudah ada di hosts file."
}
