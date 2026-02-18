# Multi-stage build for optimal size and performance
# Stage 1: Python dependencies
FROM python:3.12-slim AS python-builder

WORKDIR /deps
COPY requirements.txt .
RUN if [ -f requirements.txt ]; then \
        pip install --user -r requirements.txt; \
    fi

# Stage 2: React builder (COMMENTED OUT - Uncomment when you have React app)
# FROM node:20-slim AS react-builder
# 
# WORKDIR /build
# 
# # Copy package files first (better caching)
# COPY frontend/package*.json ./frontend/
# RUN if [ -f frontend/package.json ]; then \
#         cd frontend && npm ci --only=production; \
#     fi
# 
# # Copy and build frontend if exists
# COPY frontend/ ./frontend/
# RUN if [ -d frontend ] && [ -f frontend/package.json ]; then \
#         cd frontend && npm run build && \
#         mkdir -p /app/public && \
#         cp -r dist/* /app/public/ 2>/dev/null || \
#         cp -r build/* /app/public/ 2>/dev/null || true; \
#     fi

# Stage 3: Main runtime image
FROM debian:bookworm-slim

# Set environment variables
ENV DEBIAN_FRONTEND=noninteractive \
    TZ=UTC \
    COMPOSER_ALLOW_SUPERUSER=1 \
    NODE_ENV=production \
    PYTHONUNBUFFERED=1

# Install system dependencies and runtimes
RUN apt-get update && apt-get install -y --no-install-recommends \
    # System tools
    ca-certificates \
    curl \
    wget \
    git \
    unzip \
    zip \
    supervisor \
    procps \
    cron \
    nano \
    htop \
    # PHP 8.3 and extensions
    php8.3 \
    php8.3-fpm \
    php8.3-cli \
    php8.3-common \
    php8.3-mysql \
    php8.3-pgsql \
    php8.3-mongodb \
    php8.3-redis \
    php8.3-memcached \
    php8.3-curl \
    php8.3-gd \
    php8.3-xml \
    php8.3-mbstring \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-sqlite3 \
    php8.3-imagick \
    php8.3-soap \
    # Python 3
    python3 \
    python3-pip \
    python3-venv \
    python3-dev \
    # Nginx
    nginx \
    # Cleanup
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && apt-get clean

# Install Composer (PHP package manager)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install additional Python packages globally
RUN pip3 install --upgrade pip \
    && pip3 install gunicorn uvicorn fastapi flask django celery redis

# Create necessary directories
RUN mkdir -p /var/log/supervisor \
    /var/run/php \
    /var/run/nginx \
    /app \
    /app/static \
    /app/media \
    /app/logs \
    /app/tmp \
    /app/public \
    /app/frontend-builds

# Copy Python dependencies from builder
COPY --from=python-builder /root/.local /root/.local
ENV PATH=/root/.local/bin:$PATH

# Copy React build if exists (COMMENTED OUT - Uncomment when you have React app)
# COPY --from=react-builder /app/public /app/public

# Copy configuration files
COPY nginx.conf /etc/nginx/nginx.conf
COPY php-fpm.conf /etc/php/8.3/fpm/php-fpm.conf
COPY php.ini /etc/php/8.3/fpm/php.ini
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application code
WORKDIR /app
COPY . .

# Install PHP dependencies if composer.json exists
RUN if [ -f composer.json ]; then \
        composer install --no-dev --optimize-autoloader; \
    fi

# Install Python dependencies if requirements.txt exists
RUN if [ -f requirements.txt ]; then \
        pip3 install -r requirements.txt; \
    fi

# Install Node dependencies if package.json exists in root
RUN if [ -f package.json ]; then \
        npm ci --only=production; \
    fi

# Set permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app \
    && chown -R www-data:www-data /var/log/nginx \
    && chown -R www-data:www-data /var/lib/nginx \
    && chown -R www-data:www-data /app/tmp \
    && chown -R www-data:www-data /app/logs

# Create a healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Expose port
EXPOSE 80

# Start services with supervisor
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
