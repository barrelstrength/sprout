{{ "Redirect multiple URLs with a similar pattern with the ‘Regular Expression’
Match Strategy."|t('sprout-module-redirects') }}

#### {{ "Old URL - Regular Expressions"|t('sprout-module-redirects') }}

<pre><code>{{ "old-location/(.*)"|t('sprout-module-redirects') }}
{{ "old-location/(\\d{4})/(\\d{2})"|t('sprout-module-redirects') }}
</code></pre>

#### {{ "New URL - Capture Groups"|t('sprout-module-redirects') }}

<pre><code>{{ "new-location/$1"|t('sprout-module-redirects') }}
{{ "new-location/$1/$2"|t('sprout-module-redirects') }}
</code></pre>


