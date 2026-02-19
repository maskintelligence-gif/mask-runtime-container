const express = require('express');
const http = require('http');
const WebSocket = require('ws');
const cors = require('cors');
const helmet = require('helmet');
const compression = require('compression');

const app = express();
const server = http.createServer(app);
const wss = new WebSocket.Server({ server });

// Middleware
app.use(helmet());
app.use(compression());
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// REST API routes
app.get('/health', (req, res) => {
    res.json({ status: 'healthy', service: 'node' });
});

app.get('/users', (req, res) => {
    res.json({ users: ['user1', 'user2'] });
});

app.post('/api/data', (req, res) => {
    res.json({ received: req.body });
});

// WebSocket connections
wss.on('connection', (ws) => {
    console.log('WebSocket connected');
    
    ws.on('message', (message) => {
        console.log('Received:', message.toString());
        ws.send(`Echo: ${message}`);
    });
    
    ws.send('Welcome to WebSocket server');
});

// Error handling
app.use((err, req, res, next) => {
    console.error(err.stack);
    res.status(500).json({ error: 'Something went wrong!' });
});

// Start server
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`Node.js server running on port ${PORT}`);
});

module.exports = app;
