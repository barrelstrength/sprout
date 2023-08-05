#### {{ "Notification Event Variables"|t('sprout-module-transactional') }}

<pre><code>{% verbatim %}{user.title}
{user.getCpEditUrl()}
{user.customFieldHandle}{% endverbatim %}</code></pre>

#### {{ "Recipient Variables"|t('sprout-module-transactional') }}

<pre><code>{% verbatim %}{recipient.name}
{recipient.email}
{% endverbatim %}</code></pre>

#### {{ "Templates Variables"|t('sprout-module-transactional') }}

<pre><code>{% verbatim %}{{ recipient }}
{{ email }}
{{ user }}{% endverbatim %}</code></pre>