# Multi-Runtime Container

A production-ready Docker container supporting PHP, Python, Node.js, and static files.

## Features

- **PHP 8.3** with extensive extensions
- **Python 3.12** with FastAPI, Flask, Django
- **Node.js 20** with Express, WebSocket
- **Nginx** with advanced configuration
- **Supervisor** for process management
- **Multiple databases** support (PostgreSQL, MySQL, MongoDB, Redis)
- **Production optimizations** (caching, compression, security headers)

## Quick Start

### Deploy on Railway

1. Fork this repository
2. Connect to Railway
3. Deploy!

### Local Development

```bash
# Clone repo
git clone https://github.com/yourusername/multi-runtime-container
cd multi-runtime-container

# Start with docker-compose
docker-compose up -d

# Visit http://localhost