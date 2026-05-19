const http = require('http');
const { Server } = require('socket.io');

const PORT = process.env.SOCKET_IO_PORT || 3000;
const SECRET = process.env.SOCKET_IO_SECRET || 'default-secret';

const server = http.createServer((req, res) => {
  if (req.method === 'POST' && req.url === '/emit') {
    let body = '';
    req.on('data', chunk => (body += chunk));
    req.on('end', () => {
      try {
        const payload = JSON.parse(body);

        if (payload.secret !== SECRET) {
          res.writeHead(403);
          return res.end('Forbidden');
        }

        const event = payload.event || 'notification';
        const data = payload.data || {};

        const rooms = [];
        if (data.user_ids && Array.isArray(data.user_ids)) {
          data.user_ids.forEach(id => rooms.push(`user:${id}`));
        }
        rooms.push('global');

        rooms.forEach(room => {
          io.to(room).emit(event, data);
        });

        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ ok: true, rooms, event }));
      } catch (e) {
        res.writeHead(400);
        res.end('Bad Request');
      }
    });
  } else {
    res.writeHead(404);
    res.end('Not Found');
  }
});

const io = new Server(server, {
  cors: {
    origin: '*',
    methods: ['GET', 'POST'],
  },
});

io.on('connection', (socket) => {
  const userId = socket.handshake.query.userId;

  if (userId) {
    socket.join(`user:${userId}`);
  }
  socket.join('global');

  socket.on('disconnect', () => {
    // cleanup if needed
  });
});

server.listen(PORT, () => {
  console.log(`Socket.IO notification server running on port ${PORT}`);
});
