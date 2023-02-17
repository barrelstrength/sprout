/*
 * @link https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license https://craftcms.github.io/license
 */let n=window.Sprout||{};n.renderDynamicCsrfInputs=function(){let o=document.querySelectorAll('input[name="SPROUT_CSRF_TOKEN"]'),r={headers:{Accept:"application/json"}};fetch(n.sessionInfoActionUrl,r).then(e=>e.json()).then(e=>{o.forEach(function(t){t.name=e.csrfTokenName,t.value=e.csrfTokenValue})})};document.readyState!=="loading"?n.renderDynamicCsrfInputs():document.addEventListener("DOMContentLoaded",n.renderDynamicCsrfInputs);
//# sourceMappingURL=DynamicCsrfInput-097d6ec0.js.map
