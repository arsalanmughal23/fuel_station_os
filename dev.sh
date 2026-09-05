#!/bin/bash
# Start backend services
docker-compose up -d

# Wait for services to be ready
echo "Waiting for services to start..."
sleep 5

# Launch Tauri in development mode
cd frontend
pnpm tauri dev