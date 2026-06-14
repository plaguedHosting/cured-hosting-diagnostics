# Start the local WordPress playground using Docker Compose
docker compose up -d

# Wait a short moment for containers to initialize
Start-Sleep -Seconds 4

Write-Host "WordPress should be available at http://localhost:8000"
Start-Process "http://localhost:8000"
