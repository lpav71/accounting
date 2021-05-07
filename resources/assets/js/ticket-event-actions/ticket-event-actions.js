import TicketEventActionUsers from "./ticket-event-action.vue";

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById("ticket-event-actions")) {
        let Vue = require("vue");
        new Vue({
            render: h => h(TicketEventActionUsers)
        }).$mount('#ticket-event-actions')
    }
})