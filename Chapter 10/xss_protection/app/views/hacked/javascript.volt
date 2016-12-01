{% if secure %}
  You were protected from XSS with proper escaping.
  <script>
    window.title = '{{ title | escape_js}}'
  </script>
{% else %}
  You were just hacked with XSS!
  <script>
    window.title = '{{ title }}'
  </script>
{% endif %}
