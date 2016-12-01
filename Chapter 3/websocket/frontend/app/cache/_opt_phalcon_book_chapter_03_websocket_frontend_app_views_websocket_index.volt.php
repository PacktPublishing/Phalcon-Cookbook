<h1>Websocket Chat Server</h1>

<script>
// Note: You may need to change the domain name and host port depending
//       upon how you setup your testing system.
var conn = new WebSocket('ws://phalcon-book.ubuntu-vm:8080');

conn.addEventListener('open', function(e) {
  console.log("Connection established!");
  helloWorldPing()
})

// conn.onopen = function(e) {
//   console.log("Connection established!");
//   helloWorldPing()
// };

conn.onmessage = function(e) {
  console.log(e.data);
};

function helloWorldPing() {
  if (conn.readyState === conn.OPEN) {
    conn.send('Hello World!');
    setTimeout(helloWorldPing, 3000);
  }
}
</script>
