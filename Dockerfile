# Multi-stage build for optimal size and performance
# Stage 1: Python dependencies (with build tools)
FROM python:3.12-slim AS python-builder

# Install build dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    gcc \
    g++ \
    make \
    libffi-dev \
    libssl-dev \
    libpq-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /deps

# Copy requirements.txt
COPY requirements.txt ./

# Install dependencies with verbose output
RUN echo "📦 Installing Python dependencies..." && \
    pip install --user --no-cache-dir -r requirements.txt || \
    echo "⚠️ Some packages failed, but continuing..."

# Ensure the .local directory exists (even if no packages installed)
RUN mkdir -p /root/.local

# Create a flag file to indicate completion
RUN touch /root/.deps_installed

# Stage 2: Main runtime image
FROM debian:bookworm-slim

# Set environment variables
ENV DEBIAN_FRONTEND=noninteractive \
    TZ=UTC \
    COMPOSER_ALLOW_SUPERUSER=1 \
    NODE_ENV=production \
    PYTHONUNBUFFERED=1

# Install system dependencies and add PHP repository
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    wget \
    gnupg \
    lsb-release \
    && apt-get clean

# Add PHP repository
RUN curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

# Install base runtimes
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
    # PHP 8.3 core
    php8.3 \
    php8.3-fpm \
    php8.3-cli \
    php8.3-common \
    php8.3-mysql \
    php8.3-curl \
    php8.3-gd \
    php8.3-xml \
    php8.3-mbstring \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-sqlite3 \
    # Python 3
    python3 \
    python3-pip \
    python3-venv \
    python3-dev \
    # Nginx
    nginx \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && apt-get clean

# Install additional PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    php8.3-redis \
    php8.3-mongodb \
    php8.3-memcached \
    php8.3-imagick \
    php8.3-soap \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* || true

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

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
        echo "📦 Installing PHP dependencies..." && \
        composer install --no-dev --optimize-autoloader; \
    else \
        echo "📝 No composer.json found, skipping PHP dependencies"; \
    fi

# Install Python dependencies (second attempt in main image if needed)
RUN if [ -f requirements.txt ]; then \
        echo "📦 Installing Python dependencies in main image..." && \
        pip3 install --no-cache-dir -r requirements.txt || \
        echo "⚠️ Some Python packages failed, but continuing..."; \
    fi

# Install Node dependencies if package.json exists
RUN if [ -f package.json ]; then \
        echo "📦 Installing Node dependencies..." && \
        npm install --only=production --no-package-lock; \
    else \
        echo "📝 No package.json found, skipping Node dependencies"; \
    fi

# Set permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app \
    && chown -R www-data:www-data /var/log/nginx \
    && chown -R www-data:www-data /var/lib/nginx \
    && chown -R www-data:www-data /app/tmp \
    && chown -R www-data:www-data /app/logs

# Create a simple test script
RUN echo '#!/bin/bash\npython3 -c "import sys; print(f\"Python {sys.version}\")"\nphp -v | head -1\nnode -v\nnginx -v' > /app/version-check.sh && chmod +x /app/version-check.sh

# Create healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Expose port
EXPOSE 80

# Start services with supervisor
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
