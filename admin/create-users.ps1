param($path)
Set-Content -Path "$path\users.json" -Value @'
{
  "users": [
    {
      "email": "admin@example.com",
      "alliances": ["*"],
      "role": "admin"
    }
  ]
}
'@