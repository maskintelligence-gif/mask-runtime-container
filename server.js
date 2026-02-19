const express = require('express');
const http = require('http');
const WebSocket = require('ws');
const cors = require('cors');

const app = express();
const server = http.createServer(app);
const wss = new WebSocket.Server({ server });

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// In-memory database
let users = [
    { id: 1, name: 'John Doe', email: 'john@example.com' },
    { id: 2, name: 'Jane Smith', email: 'jane@example.com' }
];
let usersCounter = 2;

// Root endpoint
app.get('/', (req, res) => {
    res.json({
        service: 'Node.js Express',
        status: 'running',
        timestamp: new Date().toISOString(),
        endpoints: {
            'GET /health': 'Health check',
            'GET /users': 'List all users',
            'POST /users': 'Create new user',
            'GET /users/:id': 'Get user by ID',
            'PUT /users/:id': 'Update user',
            'DELETE /users/:id': 'Delete user',
            'WS /ws': 'WebSocket connection'
        }
    });
});

// Health check
app.get('/health', (req, res) => {
    res.json({
        status: 'healthy',
        service: 'node',
        timestamp: new Date().toISOString()
    });
});

// Get all users
app.get('/users', (req, res) => {
    res.json(users);
});

// Create new user
app.post('/users', (req, res) => {
    const { name, email } = req.body;
    if (!name || !email) {
        return res.status(400).json({ error: 'Name and email are required' });
    }
    
    usersCounter++;
    const newUser = {
        id: usersCounter,
        name,
        email,
        created_at: new Date().toISOString()
    };
    users.push(newUser);
    res.status(201).json(newUser);
});

// Get user by ID
app.get('/users/:id', (req, res) => {
    const id = parseInt(req.params.id);
    const user = users.find(u => u.id === id);
    
    if (!user) {
        return res.status(404).json({ error: 'User not found' });
    }
    res.json(user);
});

// Update user
app.put('/users/:id', (req, res) => {
    const id = parseInt(req.params.id);
    const userIndex = users.findIndex(u => u.id === id);
    
    if (userIndex === -1) {
        return res.status(404).json({ error: 'User not found' });
    }
    
    const { name, email } = req.body;
    users[userIndex] = {
        ...users[userIndex],
        ...(name && { name }),
        ...(email && { email }),
        updated_at: new Date().toISOString()
    };
    
    res.json(users[userIndex]);
});

// Delete user
app.delete('/users/:id', (req, res) => {
    const id = parseInt(req.params.id);
    const userIndex = users.findIndex(u => u.id === id);
    
    if (userIndex === -1) {
        return res.status(404).json({ error: 'User not found' });
    }
    
    users.splice(userIndex, 1);
    res.json({ message: 'User deleted successfully' });
});

// WebSocket connection
wss.on('connection', (ws) => {
    console.log('WebSocket client connected');
    
    // Send welcome message
    ws.send(JSON.stringify({
        type: 'connection',
        message: 'Connected to Node.js WebSocket server',
        timestamp: new Date().toISOString()
    }));
    
    // Handle incoming messages
    ws.on('message', (message) => {
        console.log('Received:', message.toString());
        
        // Echo back
        ws.send(JSON.stringify({
            type: 'echo',
            message: message.toString(),
            timestamp: new Date().toISOString()
        }));
    });
    
    // Send periodic updates (every 10 seconds)
    const interval = setInterval(() => {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'ping',
                timestamp: new Date().toISOString(),
                usersCount: users.length
            }));
        }
    }, 10000);
    
    // Clean up on close
    ws.on('close', () => {
        clearInterval(interval);
        console.log('WebSocket client disconnected');
    });
});

// Error handling middleware
app.use((err, req, res, next) => {
    console.error(err.stack);
    res.status(500).json({ error: 'Something went wrong!' });
});

// Start server
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`Node.js server running on port ${PORT}`);
    console.log(`WebSocket server available at ws://localhost:${PORT}/ws`);
});

module.exports = app;
