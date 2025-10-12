param($path)
Set-Content -Path "$path\alliances.json" -Value @'
{
  "alliances": [
    {
      "slug": "uvvu",
      "name": "UVVU Alliance",
      "description": "Example description",
      "members": ["A", "B", "C"]
    }
  ]
}
'@