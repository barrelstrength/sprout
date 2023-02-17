/*
 * @link https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license https://craftcms.github.io/license
 */class i{constructor(t){this.formId=t,this.form=document.getElementById(this.formId),this.submitButtons=this.form.querySelectorAll('[type="submit"]'),this.setDuplicateSubmissionEventListeners()}setDuplicateSubmissionEventListeners(){let t=this;this.form.addEventListener("beforeSproutFormsSubmit",function(s){t.submitButtons.forEach(function(e){e.setAttribute("disabled","disabled")})},!1),this.form.addEventListener("afterSproutFormsSubmit",function(s){t.submitButtons.forEach(function(e){setTimeout(()=>{e.removeAttribute("disabled")},500)})},!1),this.form.addEventListener("onSproutFormsSubmitCancelled",function(s){t.submitButtons.forEach(function(e){setTimeout(()=>{e.removeAttribute("disabled")},500)})},!1)}}window.SproutFormsDisableSubmitButton=i;
//# sourceMappingURL=disableSubmitButton-3d41d585.js.map
