##PHP chat SERVER
This is modified PHP server from https://github.com/Flynsarmy/PHPWebSocket-Chat. This IRC server was created as part of Hackathon week at TripAdvisor during my internship (summer '13).

To run, open a terminal and type:
php5 ./fserver.php

Then visit websocket.html in your browser. 
(You'll need IE10+, FF7+ or Chrome 14+ to run this example)

Details:
This server is mainly built to work with iPhone client. The websocket.html was mainly used for debigging purpose. Features include:
- Creates IRC like chat platform.
- Groups connected users based on location.
- Can join a different location (room) on demand

Goal is to enable a live chat between travellers and know a place talking to local people.
