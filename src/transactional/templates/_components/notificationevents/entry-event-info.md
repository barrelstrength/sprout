#### {{ "Notification Event Variables"|t('sprout-module-transactional') }}

<pre><code>{% verbatim %}{entry.title}
{entry.getCpEditUrl()}
{entry.customFieldHandle}{% endverbatim %}</code></pre>

#### {{ "Recipient Variables"|t('sprout-module-transactional') }}

<pre><code>{% verbatim %}{recipient.name}
{recipient.email}
{% endverbatim %}</code></pre>

#### {{ "Templates Variables"|t('sprout-module-transactional') }}

<pre><code>{% verbatim %}{{ recipient }}
{{ email }}
{{ entry }}{% endverbatim %}</code></pre>