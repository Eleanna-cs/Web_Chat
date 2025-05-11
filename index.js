import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';
import path from 'path';
import { dirname } from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const PORT = process.env.PORT || 5500;
const ADMIN = "Admin";
const USERS_FILE = path.join(__dirname, '../data/users.json');
const ROOMS_FILE = path.join(__dirname, '../data/rooms.json');

const app = express();
app.use(express.static(path.join(__dirname, 'public')));

const expressServer = app.listen(PORT, () => {
  console.log(`Listening on port ${PORT}`);
});

const io = new Server(expressServer, {
  cors: {
    origin: process.env.NODE_ENV === "production"
      ? false
      : ["http://localhost:8000", "http://localhost:5500", "http://127.0.0.1:5500"]
  }
});

const UsersState = {
  users: [],
  rooms: {},
  setUsers(newUsersArray) {
    this.users = newUsersArray;
    fs.writeFileSync(USERS_FILE, JSON.stringify(newUsersArray, null, 2));
  },
  setRooms(newRoomsObject) {
    this.rooms = newRoomsObject;
    fs.writeFileSync(ROOMS_FILE, JSON.stringify(newRoomsObject, null, 2));
  }
};

const loadState = () => {
  if (fs.existsSync(USERS_FILE)) {
    const usersData = fs.readFileSync(USERS_FILE);
    UsersState.setUsers(JSON.parse(usersData));
  }
  if (fs.existsSync(ROOMS_FILE)) {
    const roomsData = fs.readFileSync(ROOMS_FILE);
    UsersState.setRooms(JSON.parse(roomsData));
  }
};

loadState();

io.on('connection', socket => {
  console.log(`User ${socket.id} connected`);
  socket.emit('message', buildMsg(ADMIN, "Welcome to chat App!"));

  socket.on('enterRoom', ({ name, room }) => {
    const prevRoom = getUser(socket.id)?.room;

    if (prevRoom) {
      socket.leave(prevRoom);
      io.to(prevRoom).emit('message', buildMsg(ADMIN, `${name} has left the room`));
    }

    const user = activateUser(socket.id, name); // Don't store room in user file

    if (!UsersState.rooms[room]) {
      UsersState.rooms[room] = [];
    }

    socket.join(room);
    socket.emit('message', buildMsg(ADMIN, `You have joined the ${room} chat room`));
    socket.broadcast.to(room).emit('message', buildMsg(ADMIN, `${name} has joined the room`));

    io.to(room).emit('userList', {
      users: getUsersInRoom(room)
    });

    io.emit('roomList', {
      rooms: Object.keys(UsersState.rooms)
    });

    // Store room info in memory only
    socket.data.room = room;
    socket.data.name = name;
  });

  socket.on('message', ({ name, text }) => {
    const room = socket.data.room;
    const messageData = buildMsg(name, text);
    io.to(room).emit('message', messageData);

    if (!UsersState.rooms[room]) {
      UsersState.rooms[room] = [];
    }
    UsersState.rooms[room].push(messageData);
    UsersState.setRooms(UsersState.rooms);
  });

  socket.on('disconnect', () => {
    console.log(`User ${socket.id} disconnected`);
    const user = removeUser(socket.id);
    const room = socket.data?.room;

    if (user && room) {
      io.to(room).emit('message', buildMsg(ADMIN, `${user.name} has left the chat`));
      io.to(room).emit('userList', {
        users: getUsersInRoom(room)
      });
    }

    UsersState.setUsers(UsersState.users);
  });

  socket.on('activity', (name) => {
    socket.broadcast.emit('activity', name);
  });
});

const activateUser = (socketId, name) => {
  const user = { id: socketId, name }; // room is excluded from storage
  UsersState.users.push(user);
  UsersState.setUsers(UsersState.users);
  return user;
};

const removeUser = socketId => {
  const index = UsersState.users.findIndex(user => user.id === socketId);
  if (index !== -1) {
    return UsersState.users.splice(index, 1)[0];
  }
};

const getUser = socketId => UsersState.users.find(user => user.id === socketId);
const getUsersInRoom = room => UsersState.users.filter(user => {
  const socket = [...io.sockets.sockets.values()].find(s => s.id === user.id);
  return socket?.data?.room === room;
});
const buildMsg = (name, text) => {
  const time = new Date().toLocaleTimeString();
  return { name, text, time };
};
