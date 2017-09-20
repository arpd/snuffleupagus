Configuration
=============

Since PHP *ini-like* configuration model isn't flexible enough,
Snuffleupagus is using its own format.

Options are chainable by using dots (``.``), and string parameters
**must** be quoted, while booleans and integers aren't.

Comments are prefixed either with ``#``, or ``;``.

Some rules applies in a specific ``function`` (context), on a specific ``variable``
(data), like ``disable_functions``, others can only be enabled/disabled, like
``harden_random``.

.. warning::

  Careful, a wrongly configured Snuffleupagus might break your website.
  It's up to you to understand its :doc:`features <features>`,
  read the present documentation about how to configure them,
  evaluate your threat model, and write your configuration file accordingly.

The rules are evaluated in the order that they are written, and the **first** one
to match will terminate the evaluation (except for rules in simulation mode).

Bugclass-killer features
------------------------

global_strict
^^^^^^^^^^^^^
`default: disabled`

``global_strict`` will enable the `strict <https://secure.php.net/manual/en/functions.arguments.php#functions.arguments.type-declaration.strict>`_ mode globally, 
forcing PHP to throw a `TypeError <https://secure.php.net/manual/en/class.typeerror.php>`_
exception if an argument type being passed to a function does not match its corresponding declared parameter type.

It can either be ``enabled`` or ``disabled``

::

  sp.global_strict.disable();
  sp.global_strict.enable();

harden_random
^^^^^^^^^^^^^
 * `default: enabled`
 * `more <features.html#weak-prng-via-rand-mt-rand>`__
 
``harden_random`` will silently replace the insecure `rand <https://secure.php.net/manual/en/function.rand.php>`_
and `mt_rand <https://secure.php.net/manual/en/function.mt-rand.php>`_ functions with
the secure PRNG `random_int <https://secure.php.net/manual/en/function.random-int.php>`_.

It can either be ``enabled`` or ``disabled``.

::

  sp.harden_random.enable();
  sp.harden_random.disable();

.. _config_global:

global
^^^^^^

This configuration variable contain parameters that are used by other ones:

- ``secret_key``: A secret key used by various cryptographic features,
  like `cookies protection <features.html#session-cookie-stealing-via-xss>`__ or `unserialize protection <features.html#unserialize-related-magic>`__,
  so do make sure that it's random and long enough.
  You can generate it with something like this: ``head -c 256 /dev/urandom | tr -dc 'a-zA-Z0-9'``.

::

  sp.global.secret_key("44239bd400aa82e125337c9d4eb8315767411ccd");

unserialize_hmac
^^^^^^^^^^^^^^^^
 * `default: disabled`
 * `more <features.html#unserialize-related-magic>`__
 
``unserialize_hmac`` will add integrity check to ``unserialize`` calls, preventing
abritrary code execution in their context.

::

  sp.unserialize_hmac.enable();
  sp.unserialize_hmac.disable();


auto_cookie_secure
^^^^^^^^^^^^^^^^^^
 * `default: disabled`
 * `more <features.html#session-cookie-stealing-via-xss>`__
 
``auto_cookie_secure`` will automatically mark cookies as `secure <https://en.wikipedia.org/wiki/HTTP_cookie#Secure_cookie>`_
when the web page is requested over HTTPS.

It can either be ``enabled`` or ``disabled``.

::

  sp.auto_cookie_secure.enable();
  sp.auto_cookie_secure.disable();

cookie_encryption
^^^^^^^^^^^^^^^^^
 * `default: disabled`
 * `more <features.html#session-cookie-stealing-via-xss>`__
   
.. warning::

  To use this feature, you **must** set the :ref:`global.secret_key <config_global>` variable.
  This design decision prevents attacker from
  `trivially bruteforcing <https://www.idontplaydarts.com/2011/11/decrypting-suhosin-sessions-and-cookies/>`_
  session cookies.

``cookie_secure`` will activate transparent encryption of specific cookies.

It can either be ``enabled`` or ``disabled``.

::

  sp.cookie_encryption.cookie("my_cookie_name");
  sp.cookie_encryption.cookie("another_cookie_name");


readonly_exec
^^^^^^^^^^^^^
 * `default: disabled`

``readonly_exec`` will prevent the execution of writable PHP files.

It can either be ``enabled`` or ``disabled``.

::

  sp.readonly_exec.enable();

upload_validation
^^^^^^^^^^^^^^^^^
 * `default: disabled`
 * `more <features.html#remote-code-execution-via-file-upload>`__

``upload_validation`` will call a given script upon a file upload, with the path 
to the file being uploaded as argument, and various information about it in the environment:

* ``SP_FILENAME``: the name of the uploaded file
* ``SP_FILESIZE``: the size of the file being uploaded
* ``SP_REMOTE_ADDR``: the ip address of the uploader
* ``SP_CURRENT_FILE``: the current file being executed

This feature can be used, for example, to check if an uploaded file contains php
code, with something like `vld <https://derickrethans.nl/projects.html#vld>`_
(``php -d vld.execute=0 -d vld.active=1 -d extension=vld.so yourfile.php``).

The upload will be **allowed** if the script return the value ``0``. Every other
value will prevent the file from being uploaded.

::

  sp.upload_validation.script("/var/www/is_valid_php.py").enable();


disable_xxe
^^^^^^^^^^^
 * `default: enabled`
 * `more <features.html#xxe>`__

``disable_xxe`` will prevent XXE attacks by disabling the loading of external entities (``libxml_disable_entity_loader``) in the XML parser.

::

  sp.disable_xxe.enable();


Virtual-patching
----------------

Snuffleupagus provides virtual-patching, via the ``disable_functions`` directive, allowing you to stop or control dangerous behaviours.
Admitting you have a call to ``system()`` that lacks proper user-input validation, thus leading to an **RCE**, this might be the right tool.

::
   
  # Allow `id.php` to restrict system() calls to `id`
  sp.disable_functions.function("system").filename("id.php").param("cmd").value("id").allow();
  sp.disable_functions.function("system").filename("id.php").drop()

Of course, this is a trivial example, and a lot can be achieved with this feature, as you will see below.


Filters
^^^^^^^

- ``alias(:str)``: human-readable description of the rule
- ``cidr(ip/mask:str)``: match on the client's `cidr <https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing>`_
- ``filename(name:str)``: match in the file ``name``
- ``filename_r(regexp:str)``: the file name matching the ``regexp``
- ``function(name:str)``: match on function ``name``
- ``function_r(regexp:str)``: the function matching the ``regexp``
- ``hash(:str)``: match on the file's `sha256 <https://en.wikipedia.org/wiki/SHA-2>`_ sum
- ``param(name:str)``: match on the function's parameter ``name``
- ``param_r(regexp:str)``: match on the function's parameter ``regexp``
- ``param_type(type:str)``: match on the function's parameter ``type``
- ``ret(value:str)``: match on the function's return ``value``
- ``ret_r(regexp:str)``: match with a ``regexp`` on the function's return
- ``ret_type(type_name:str)``: match on the ``type_name`` of the function's return value
- ``value(:str)``: match on a litteral value
- ``value_r(:regexp)``: match on a value matching the ``regexp``
- ``var(name:str)``: match on a **local variable** ``name``

The ``type`` must be one of the following values:

- ``FALSE``: for boolean false
- ``TRUE``: for boolean true
- ``NULL``: for the **null** value
- ``LONG``: for a long (also know as ``integer``) value
- ``DOUBLE``: for a **double** (also known as ``float``) value
- ``STRING``: for a string
- ``OBJECT``: for a object
- ``ARRAY``: for an array
- ``RESOURCE``: for a resource

Actions
^^^^^^^

- ``allow()``: **allow** the request if the rule matches
- ``drop()``: **drop** the request if the rule matches
- ``dump(directory:str)``: dump the request in the ``directory`` if it matches the rule
- ``simulation()``: enabled the simulation mode

Details
^^^^^^^

The ``function`` filter is able to do various dereferencing:

- ``function("AwesomeClass::my_method")`` will match in the method ``my_method`` in the class ``AwesomeClass``

The ``param`` filter is also able to do some dereferencing:

- ``param(foo[bar])`` will get match on the value corresponding to the ``bar`` key in the hashtable ``foo``.
  Remember that in PHP, almost every data structure is a hashtable. You can of course nest this like
  ``param(foo[bar][baz][batman])``.
- The ``var`` filter will walk the calltrace until it finds the variable's name, or the end of it,
  allowing to match on global variables: ``.var("_GET[param]")`` will match on the GET parameter ``param``.

For clarity's sake, the presence of the ``allow`` or ``drop`` action is **mandatory**.

.. warning::

  When you're writing rules, please do keep in mind that the **order matters**.
  For example, if you're denying a call to ``system()`` and then allowing it in a
  more narrowed way later, the call will be denied,
  because it'll match the deny first.

If you're paranoid, we're providing a php script to automatically generate
hash of files containing dangerous functions,
and blacklisting them everywhere else.

Examples
^^^^^^^^

Evaluation order of rules
"""""""""""""""""""""""""

The following rules will:

1. Allow calls to ``system("id")``
2. Issue a trace in the logs on calls to ``system`` with its parameters starting with ``ping``,
   and pursuing evaluation of the remaining rules.
3. Drop calls to ``system``.


::

  sp.disable_functions.function("system").param("cmd").value("id").allow();
  sp.disable_functions.function("system").param("cmd").value_r("^ping").drop().simulation();
  sp.disable_functions.function("system").param("cmd").drop();

Miscellaneous examples
""""""""""""""""""""""

.. literalinclude:: ../../config/examples.ini
   :language: python