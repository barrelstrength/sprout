let i=document.querySelector('[data-attribute="notificationEvent"] select'),c=document.querySelectorAll(".notification-event-tip");i.addEventListener("change",function(t){c.forEach(function(e){e.classList.add("hidden")}),o(t.target.value)});const o=function(t){let e="notification-event-tip-"+t.replace(/\\/g,"-"),n=document.getElementById(e);n&&n.classList.toggle("hidden")};let a=i.options[i.selectedIndex].value;o(a);
//# sourceMappingURL=transactionalEmail-7eaf5ef0.js.map
