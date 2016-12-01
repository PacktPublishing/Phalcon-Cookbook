{% if secure %}
  <p style="{{ style | escape_css }}">The world is a big place</p>
{% else %}
  <p style="{{ style }}">The world is a big place</p>
{% endif %}
