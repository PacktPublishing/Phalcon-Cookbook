{% if secure %}
  You were protected from XSS with proper escaping.
  <p>{{ post | escape }}</p>
{% else %}
  You were just hacked with XSS!
  <p>{{ post }}</p>
{% endif %}
