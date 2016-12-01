
## Preventing Cross Site Scripting (XSS) attacks

In this recipe we will demonstrate simple techniques to stopping XSS attacks.  These attacks are the result of trusting user generated values directly in the output of either CSS, Javascript or HTML.  Although these values are typically stored and then later retrieved from the database we don't actually need to use a database to illustrate these attacks.  This recipe is rather simple in that in our controller we have stored snippets of code that when used directly in our Volt views will cause the browser to run our malicious Javascript code.  Today we are hackers.

#### Getting Ready...

This recipe uses the **Phalcon Developer Tools** which we will use to setup a project skeleton.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "simple" template:

`phalcon project xss_protection simple`

2) Create controller `app/controllers/HackedController.php`:

```php
<?php

class HackedController extends Phalcon\Mvc\Controller
{
    protected function onConstruct()
    {
        $this->hack = "<script>confirm('Transfer $10,000 to Nigeria for miracle hair treatment.')</script>";
    }

    public function htmlAction()
    {
        $this->view->setVars([
            'secure' => $this->request->getQuery('secure') === 'yes',
            'post'   => $this->hack
        ]);
    }

    public function javascriptAction()
    {
        $this->view->setVars([
            'secure' => $this->request->getQuery('secure') === 'yes',
            'title'  => "';</script>{$this->hack}<script>var blah='"
        ]);
    }

    public function cssAction()
    {
        $this->view->setVars([
            'secure' => $this->request->getQuery('secure') === 'yes',
            'style'  => "\"> {$this->hack} <p id=\""
        ]);
    }
}
```

3) Create view `app/views/hacked/css.volt`:

```
{% if secure %}
  <p style="{{ style | escape_css }}">The world is a big place</p>
{% else %}
  <p style="{{ style }}">The world is a big place</p>
{% endif %}
```


Create view `app/views/hacked/html.volt`:

```
{% if secure %}
  You were protected from XSS with proper escaping.
  <p>{{ post | escape }}</p>
{% else %}
  You were just hacked with XSS!
  <p>{{ post }}</p>
{% endif %}
```


Create view `app/views/hacked/javascript.volt`:

```
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
```

4) In the browser go to the following paths off of the root path of the project.

* `/hacked/css`
* `/hacked/css?secure=yes`
* `/hacked/html`
* `/hacked/html?secure=yes`
* `/hacked/javascript`
* `/hacked/javascript?secure=yes`

For each path without the secure query variable a rather interesting message should appear and when "?secure=yes" is added it should escape the bad characters that cause the XSS attack.

#### How it works...

In `HackedController` class we are storing a malicious Javascript segment in the "onConstruct" event that we will use later in the `htmlAction`, `javascriptAction` and `cssAction` methods.  In each of these three action methods we simple pass into the view the "secure" value based upon the query variable and then our script hack wrapped in various control characters that will allow us to break out of that environment to then run our malicious script.  In this instance this script is merely a popup but it could be something much worse like actually calling out to a remote site and executing foreign Javascript right within the context of our website.  The sky is the limit for this sort of attack and once something can be run then anything is possible.
