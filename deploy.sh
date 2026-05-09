#!/bin/bash

# ===========================================
# SCRIPT DE DEPLOIEMENT - AGREE SIKUL
# ===========================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "=============================================="
echo "       AGREE SIKUL - Deployment Script        "
echo "=============================================="
echo -e "${NC}"

log_info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

check_command() {
    if ! command -v $1 &> /dev/null; then
        log_error "$1 is not installed. Please install it first."
        exit 1
    fi
}

check_prerequisites() {
    log_info "Checking prerequisites..."

    check_command docker
    if ! docker compose version >/dev/null 2>&1 && ! command -v docker-compose >/dev/null 2>&1; then
        log_error "docker compose (v2 ou docker-compose v1) requis."
        exit 1
    fi

    if [ ! -f ".env" ]; then
        log_warn ".env non trouvé. Copie depuis .env.production..."
        cp .env.production .env
        log_warn "Edite .env (DB_USERNAME, DB_PASSWORD, secrets…) avant de continuer."
        exit 1
    fi

    log_info "Prerequisites OK!"
}

generate_keys() {
    log_info "Generating application keys..."

    if grep -q "CHANGEME_GENERATE_WITH_php_artisan_jwt_secret" .env 2>/dev/null; then
        JWT_SECRET=$(docker run --rm php:8.3-cli php -r "echo bin2hex(random_bytes(32));")
        sed -i "s|CHANGEME_GENERATE_WITH_php_artisan_jwt_secret|$JWT_SECRET|g" .env
        log_info "JWT_SECRET generated!"
    fi
}

dc() {
    if docker compose version >/dev/null 2>&1; then
        docker compose "$@"
    else
        docker-compose "$@"
    fi
}

build_images() {
    log_info "Building Docker images..."
    dc build --no-cache
    log_info "Images built successfully!"
}

start_services() {
    log_info "Starting services..."
    dc up -d
    log_info "All services started!"
}

run_setup() {
    log_info "Running Laravel setup..."
    sleep 10
    dc exec -T app php artisan migrate --force
    dc exec -T app php artisan storage:link || true
    dc exec -T app php artisan optimize
    log_info "Setup completed!"
}

verify_deployment() {
    log_info "Verifying deployment..."
    dc ps

    if curl -s http://localhost:8091/health | grep -q "OK"; then
        log_info "Application is healthy!"
    else
        log_warn "Health check failed. Check logs: ./deploy.sh logs"
    fi

    echo ""
    log_info "=============================================="
    log_info "Deployment completed successfully!"
    log_info "=============================================="
    echo ""
    log_info "Access: http://localhost:8091"
    echo ""
    log_info "Useful commands:"
    echo "  - View logs:  ./deploy.sh logs"
    echo "  - Stop:       ./deploy.sh stop"
    echo "  - Restart:    ./deploy.sh restart"
    echo "  - Shell:      ./deploy.sh shell"
    echo "  - Fresh seed: ./deploy.sh fresh"
}

case "$1" in
    "build")
        check_prerequisites
        build_images
        ;;
    "start")
        check_prerequisites
        start_services
        ;;
    "deploy")
        check_prerequisites
        generate_keys
        build_images
        start_services
        run_setup
        verify_deployment
        ;;
    "stop")
        dc down
        log_info "Services stopped."
        ;;
    "restart")
        dc restart
        log_info "Services restarted."
        ;;
    "logs")
        dc logs -f ${2:-}
        ;;
    "shell")
        dc exec app sh
        ;;
    "migrate")
        dc exec app php artisan migrate --force
        ;;
    "seed")
        dc exec app php artisan db:seed --force
        ;;
    "fresh")
        dc exec app php artisan migrate:fresh --seed --force
        ;;
    "optimize")
        dc exec app php artisan optimize
        ;;
    "clear")
        dc exec app php artisan optimize:clear
        ;;
    "prod")
        check_prerequisites
        generate_keys
        build_images
        dc up -d
        run_setup
        verify_deployment
        ;;
    *)
        echo "Usage: $0 {deploy|build|start|stop|restart|logs|shell|migrate|seed|fresh|optimize|clear|prod}"
        echo ""
        echo "Commands:"
        echo "  deploy   - Full deployment (build + start + setup)"
        echo "  build    - Build Docker images"
        echo "  start    - Start all services"
        echo "  stop     - Stop all services"
        echo "  restart  - Restart all services"
        echo "  logs     - View logs (optionally specify service)"
        echo "  shell    - Open shell in app container"
        echo "  migrate  - Run database migrations"
        echo "  seed     - Run database seeders"
        echo "  fresh    - Fresh migration with seeds"
        echo "  optimize - Optimize Laravel"
        echo "  clear    - Clear all caches"
        echo "  prod     - Production deployment"
        exit 1
        ;;
esac
