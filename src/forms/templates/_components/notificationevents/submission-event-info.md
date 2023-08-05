#### {{ "Notification Event Variables"|t('sprout-module-transactional') }}

<pre><code>{% verbatim %}{submission.title}
{submission.getCpEditUrl()}
{submission.customFieldHandle}{% endverbatim %}</code></pre>

#### {{ "Recipient Variables"|t('sprout-module-transactional') }}

<pre><code>{% verbatim %}{recipient.name}
{recipient.email}
{% endverbatim %}</code></pre>

#### {{ "Templates Variables"|t('sprout-module-transactional') }}

<pre><code>{% verbatim %}{{ recipient }}
{{ email }}
{{ submission }}{% endverbatim %}</code></pre>