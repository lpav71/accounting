document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById("fast-message-sms")) {
        let Vue = require("vue");
        let Axios = require("axios");

        let messengers = new Vue({
            el: '#fast-message-sms',
            name: 'fast-message-sms',
            data: {
                messages: null,
                type: null,
                url: null,
                destination: null,
                order_id: null,
                success: false,
                error: false,
                msg_error:"Ошибка",
                is_blocked_button: false,
                currentMessage: {
                    name: 'Своё сообщение',
                    message: ''
                }
            },
            methods: {
                send() {
                    this.is_blocked_button = true;
                    Axios.post(this.url, {
                        message: this.currentMessage.message,
                        destination: this.destination,
                        type: this.type,
                        order_id: this.order_id,
                    }).then((response) => {
                        this.success = true;
                        this.error = false;
                        this.is_blocked_button = false;
                    }).catch((error) => {
                        if(error.response.data.msg.length>0){
                            this.msg_error = error.response.data.msg;
                        }
                        this.success = false;
                        this.error = true;
                        this.is_blocked_button = false;
                    });
                }
            },
            created() {
                sms_data = JSON.parse(document.body.querySelector(this.$options.el).getAttribute('data-message-templates'));
                this.type = sms_data.type;
                this.messages = sms_data.messages;
                this.messages.unshift(this.currentMessage);
                this.url = document.body.querySelector(this.$options.el).getAttribute('data-message-url');
                this.destination = document.body.querySelector(this.$options.el).getAttribute('data-user-destination');
                this.order_id = document.body.querySelector(this.$options.el).getAttribute('data-order_id');
            },
            watch: {
                currentMessage(val, oldVal) {
                    this.success = false;
                    this.error = false;
                }
            },
            template:
                `<div>
                    <div class="form-group">
                        <select class="form-control selectpicker" v-model="currentMessage">
                            <option v-for="(val,key) in messages" :key="key" :value="val">{{val.name}}</option>
                        </select>
                    </div>
                    <textarea class="form-control" rows="4" v-model="currentMessage.message"></textarea>
                    <button :disabled="is_blocked_button" @click="send" type="button" class="btn p-1 btn-primary mt-2 mb-2">Отправить</button>
                    <div v-if="success" class="alert alert-success" role="alert">Отправлено</div>
                    <div v-if="error" class="alert alert-danger" role="alert">{{msg_error}}</div>
                </div>`
        });
    }
});