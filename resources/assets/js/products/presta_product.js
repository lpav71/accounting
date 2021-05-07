document.addEventListener('DOMContentLoaded', function(){
    if(document.getElementById("presta_product")){
    let Vue = require("vue");
    let Axios = require("axios");

let presta_product = new Vue({
    el: '#presta_product',
    name:'presta_product',
    data: {
        product:null,
        channels:null,
        channel:null,
        productId:null,
        message:null,
        errors:null
    },
    methods:{
        save(){            
            if(this.product.id==null){
                Axios.post('/presta-product',{
                    product:this.product
                }).then((response)=>{
                        this.product=response.data.data.product;
                        this.message=response.data.message;
                        setTimeout(()=>{
                            this.message=null;
                        },3000)
                        this.errors=null
                }).catch((error)=>{
                    this.errors=error.response.data.errors
                    this.message=error.response.data.message
                })
            }else{
                Axios.put(`/presta-product/${this.product.id}`,
                    {
                        product:this.product
                    }
                ).then((response)=>{
                    this.product=response.data.data.product;
                    this.message=response.data.message;
                    setTimeout(()=>{
                        this.message=null;
                    },3000)
                    this.errors=null
                }).catch((error)=>{
                    this.errors=error.response.data.errors
                    this.message=error.response.data.message
                })
            }
        },
        download(update_main) {
            Axios.post('/presta-product/update-from-channel', {
                channel_id: this.channel,
                product_id: this.productId,
                update_main: update_main
            }).then((response)=>{
                if(update_main){
                    document.location.reload();
                }
                this.product=response.data.data.product;
                this.message=response.data.message;
                setTimeout(()=>{
                    this.message=null;
                },3000)
                this.errors=null
            }).catch((error)=>{
                this.errors=error.response.data.errors
                this.message=error.response.data.message
            })
        },
        downloadWithoutMain(){
            this.download(false);
        },
        downloadWithMain(){
            this.download(true);
        },

    },
    created(){
        this.productId=parseInt(document.body.querySelector(this.$options.el).getAttribute('data-id'));
        Axios.get('/channelsAjax').then((response)=>{
            this.channels=response.data.data;
            this.errors=null
            if(response.data.data[0]!=undefined){
                this.channel=response.data.data[0].id
            }
        }).catch((error)=>{
            this.errors=error.response.data.errors
            this.message=error.response.data.message
        })
    },   
    watch:{ 
        channel(val, oldVal){
            this.message=null;
            Axios.get(`/presta-product?channel_id=${val}&product_id=${this.productId}`).then((response)=>{
                this.product=response.data.data.product;
                this.errors=null;
            }).then((response)=>{
                $('#description').summernote('destroy');
                $('#description').summernote({
                    lang: 'ru-RU',
                    htmlMode: true,
                    shortcuts: false,
                    airMode: false,
                    minHeight: 200,
                    maxHeight: null,
                    focus: false, 
                    disableDragAndDrop: false,
                    callbacks: {
                        onChange: (contents, $editable)=>{
                            this.product.description=contents;
                        },
                        onImageUpload: function (files) {
                            uploadFile(files, $(this));
                        },
                        onMediaDelete: function (target) {
                            let fileURL = target[0].src;
                            deleteFile(fileURL);
                            target.remove();
                        }
                    }
                });
            }).catch((error)=>{
                this.errors=error.response.data.errors
                this.message=error.response.data.message
            });
        },
    },
    template: `
    <div class="wrapper mt-2">
    <div v-if="message" class="alert alert-primary" role="alert">
        {{message}} 
    </div>
    <div v-for="(error,field) in errors" :key="field" class="alert alert-danger" role="alert">
        <p v-for="(text,key2) in error" :key="key2">{{field}} - {{text}}</p>
    </div>
        <div class="form-group">
            <label for="sel1">Источник</label>
            <select class="form-control" v-model="channel">
                <option v-for="(val,key) in channels" :key="key" :value="val.id">{{val.name}}</option>
            </select>
        </div>
    <div>
        <button @click="save" type="button" class="btn p-0 btn-primary">Сохранить на источник</button>
        <button @click="downloadWithoutMain" type="button" class="btn p-0 btn-primary">Обновить с источника</button>
        <button @click="downloadWithMain" type="button" class="btn p-0 btn-primary">Обновить вместе с товаром</button>
    </div>
    <table class="table">
    <thead>
      <tr>
        <th>Атрибут</th>
        <th>Значение</th>
      </tr>
    </thead>
    <tbody v-if="product">
        <tr v-if="product.is_blocked">
            <td colspan="2">
                <div class="alert alert-danger" role="alert">
                    <h5>Товар заблокирован</h5>
                </div>
            </td>
        </tr>
        <tr>
            <td scope="row">Заблокирован</td>
            <td>
                <input scope="row" class="form-control" type="checkbox" v-model="product.is_blocked">
            </td>
        </tr>
        <tr>
            <td scope="row">Включен</td>
            <td>
            <input scope="row" class="form-control" type="checkbox" v-model="product.is_active">
            </td>
        </tr>
        <tr>
            <td scope="row">Цена</td>
            <td><input class="form-control" v-model="product.price"></td>
        </tr>
        <tr>
            <td scope="row">Цена со скидкой</td>
            <td><input class="form-control" v-model="product.price_discount"></td>
        </tr>
        <tr>
          <td scope="row">Рейтинг</td>
          <td><input class="form-control" v-model="product.rating"></td>
        </tr>
        <tr>
            <td scope="row">Описание</td>
            <td>
            <textarea id="description" v-model="product.description" class="form-control editor-body">
                    Enter your name
            </textarea>
            </td>
        </tr>
    </tbody>
    </table>
  </div>
  `
  })
}
});