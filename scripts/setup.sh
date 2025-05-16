#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Starting development environment setup...${NC}"

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker is not installed. Please install Docker first.${NC}"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Docker Compose is not installed. Please install Docker Compose first.${NC}"
    exit 1
fi

# Create necessary directories
echo -e "${YELLOW}Creating necessary directories...${NC}"
mkdir -p docker/mysql
mkdir -p docker/nginx/conf.d
mkdir -p docker/php

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    echo -e "${YELLOW}Creating .env file...${NC}"
    cp .env.example .env
fi

# Build and start containers
echo -e "${YELLOW}Building and starting Docker containers...${NC}"
docker-compose up -d --build

# Install dependencies
echo -e "${YELLOW}Installing PHP dependencies...${NC}"
docker-compose exec app composer install

# Generate application key
echo -e "${YELLOW}Generating application key...${NC}"
docker-compose exec app php artisan key:generate

# Run migrations
echo -e "${YELLOW}Running database migrations...${NC}"
docker-compose exec app php artisan migrate

# Install Node.js dependencies
echo -e "${YELLOW}Installing Node.js dependencies...${NC}"
docker-compose exec app npm install

# Build assets
echo -e "${YELLOW}Building assets...${NC}"
docker-compose exec app npm run build

# Set permissions
echo -e "${YELLOW}Setting permissions...${NC}"
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache

echo -e "${GREEN}Setup completed successfully!${NC}"
echo -e "${YELLOW}Your application is now running at: http://localhost:8000${NC}"
echo -e "${YELLOW}MailHog interface is available at: http://localhost:8025${NC}" 