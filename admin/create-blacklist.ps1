param($path)
Set-Content -Path "$path\token_blacklist.json" -Value @'
{
  "jti": []
}
'@