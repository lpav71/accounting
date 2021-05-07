document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById("ticket")) {
        let Vue = require("vue");
        let Axios = require("axios");

        let ticket = new Vue({
            el: "#ticket",
            name: "ticket",
            data() {
                return {
                    messages: [],
                    ticketId: null,
                    userId: null,
                    errorMessage: null,
                    messageInput: null
                }
            },
            created() {
                this.ticketId = parseInt(document.body.querySelector(this.$options.el).getAttribute('data-id'));
                this.userId = parseInt(document.body.querySelector(this.$options.el).getAttribute('data-user-id'));
                Axios.get(`/ticket-messages?filter[ticket]=${this.ticketId}`).then((response) => {
                    this.messages = response.data.data;
                }).then((response)=>{
                    window.scrollTo(0,document.body.scrollHeight);
                }).catch((error) => {
                    this.errorMessage = error.response.data.errorMessage;
                })
                setInterval(() => {
                    this.refresh();
                }, 2000);
            },
            methods: {
                send() {
                    Axios.post('/ticket-messages', {
                        'text': this.messageInput,
                        'ticket_id': this.ticketId
                    }).then((response) => {
                        this.messageInput = null;
                        this.refresh();
                    }).catch((error) => {
                        this.errorMessage = error.response.data.errorMessage
                    })
                },
                refresh() {
                    Axios.get(`/ticket-messages?filter[ticket]=${this.ticketId}`).then((response) => {
                        this.messages = response.data.data;
                    }).catch((error) => {
                        this.errorMessage = error.response.data.errorMessage;
                    })
                }
            },
            template: `
                <div class="container mt-3">
                    <div v-if="errorMessage!==null">
                        <alert :errors="errorMessage"></alert>
                    </div>
                    <div class="row mb-5">
                        <div class="col-lg-10 pl-0 pr-0 row">
                            <div v-for="message in messages" class="col-11"
                                 v-bind:class="{ 'offset-2': message.user.id==userId,
                                                'offset2': message.user.id!=userId}">
                                <h5 v-if="message.user.id!=userId">{{message.user.name}}</h5>
                                <div class=" alert alert-secondary p-2">
                                    <p class="mb-1"
                                       v-bind:class="{ 'text-right': message.user.id==userId }">{{message.text}}
                                    </p>
                                    <p class="mb-0" v-bind:class="{ 'text-right': message.user.id==userId }">
                                        <small>
                                            {{message.created_at}}
                                        </small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-5 fixed-bottom mx-auto " style="max-width:800px">
                        <textarea @keyup.enter="send()" v-model="messageInput" type="text" class="form-control"
                                  placeholder="Input Message"
                                  aria-label="Recipient's username" aria-describedby="button-addon2"> </textarea>
                        <div class="input-group-append">
                            <button @click="send" class="btn btn-outline-secondary" type="button" id="button-addon2">
                                Отправить
                            </button>
                        </div>
                    </div>
                </div>`
        })

    }
});