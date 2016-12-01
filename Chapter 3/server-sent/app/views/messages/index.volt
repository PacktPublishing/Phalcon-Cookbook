<div id="messages"></div>

<script>
  var messages = document.getElementById('messages')

  var evtSource = new EventSource("messages/retrieve");
  evtSource.addEventListener("phalcon-message", function(e) {
    var data = JSON.parse(e.data);

    var newElement = document.createElement("li");
    newElement.innerHTML = data.time;
    messages.appendChild(newElement);
  }, false);
</script>
