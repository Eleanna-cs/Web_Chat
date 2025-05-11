const socket = io('ws://localhost:5500');

const msgInput = document.querySelector('#message');
const nameInput = document.querySelector('#name');
const chatRoom = document.querySelector('#room');
const activity = document.querySelector('.activity');
const userList = document.querySelector('.user-list');
const roomList = document.querySelector('.room-list');
const chatDisplay = document.querySelector('.chat-display');

const urlParams = new URLSearchParams(window.location.search);
const user = urlParams.get('user');
const target = urlParams.get('target');

if (user) {
  nameInput.value = user;

  if (user){
    chatRoom.value = generateRoomBetween(user, target);
    }
    else{ 
      chatRoom.value = generateRoomNumber(user);
    }

  
}

document.querySelector('.form-join').style.display = 'none';

window.addEventListener('load', () => {
  enterRoom();
});

document.querySelector('.form-msg').addEventListener('submit', sendMessage);
msgInput.addEventListener('keypress', () => {
  socket.emit('activity', nameInput.value);
});

function sendMessage(e) {
  e.preventDefault();
  const name = nameInput.value.trim();
  const text = msgInput.value.trim();
  const room = chatRoom.value.trim();

  if (name && text && room) {
    socket.emit('message', { name, text });
    msgInput.value = '';
    msgInput.focus();
  }
}

function enterRoom() {
  const name = nameInput.value.trim();
  let room = chatRoom.value.trim();

  if (!room) {
    room = generateRoomNumber(name);
  }

  function generateRoomBetween(user1, user2) {
  return [user1, user2].sort().join('-');
}

function generateRoomNumber(name) {
  return `room-${name}-${Date.now()}`;
}


  if (name && room) {
    console.log(`Connecting ${name} to room: ${room}`);
    socket.emit('enterRoom', { name, room });
  }
}

function generateRoomNumber(name) {
  return `room-${name}-${Date.now()}`;
}

socket.on('message', ({ name, text, time }) => {
  activity.textContent = '';
  const li = document.createElement('li');
  li.className = 'post';

  if (name === nameInput.value) {
    li.classList.add('post--right');
  } else if (name !== 'Admin') {
    li.classList.add('post--left');
  }

  li.innerHTML = name === 'Admin'
    ? `<div class="post__text">${text}</div>`
    : `
      <div class="post__header ${name === nameInput.value ? 'post__header--user' : 'post__header--reply'}">
        <span class="post__header--name">${name}</span>
        <span class="post__header--time">${time}</span>
      </div>
      <div class="post__text">${text}</div>
    `;

  chatDisplay.appendChild(li);
  chatDisplay.scrollTop = chatDisplay.scrollHeight;
});

let activityTimer;
socket.on('activity', (name) => {
  activity.textContent = `${name} is typing...`;
  clearTimeout(activityTimer);
  activityTimer = setTimeout(() => {
    activity.textContent = '';
  }, 3000);
});

socket.on('userList', ({ users }) => {
  userList.innerHTML = '';
  if (users?.length) {
    const title = document.createElement('em');
    title.textContent = `Users in ${chatRoom.value}:`;
    userList.appendChild(title);

    const names = users.map(u => `${u.name}`).join(', ');
    userList.appendChild(document.createTextNode(names));
  }
});

socket.on('roomList', ({ rooms }) => {
  roomList.innerHTML = '';
  if (rooms?.length) {
    const title = document.createElement('em');
    title.textContent = 'Active Rooms:';
    roomList.appendChild(title);

    const names = rooms.map(r => `${r}`).join(', ');
    roomList.appendChild(document.createTextNode(names));
  }
});