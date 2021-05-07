$(function () {
    $('[name="is_composite"]').on('change', function () {
        $('#part-products').toggle(this.checked);
    }).change();

    $("#product-pictures").each(function(){
        let Axios = require("axios");
        let productId=$(this).attr('data-id');
        Axios.get('/product-pictures?product_id='+productId).then((response=>{
            let init=[];
            let initConf=[];
            
            response.data.data.forEach((element)=>{
                    init.push(element.url);
                    let conf={
                        url:`/product-pictures?id=${element.id}&delete=true`
                    }
                    initConf.push(conf)
              });
            $("#product-pictures").fileinput({
                theme: 'fa',
                uploadUrl: "/product-pictures",
                uploadAsync: false,
                multiple:true,
                minFileCount: 0,
                maxFileCount: 10,
                overwriteInitial: false,
                language: "ru",
                initialPreview: init,
                initialPreviewAsData: true, 
                allowedFileExtensions:['jpg', 'png', 'jpeg'],
                initialPreviewFileType: 'image', 
                autoOrientImage: true,
                dragSettings:{
                    disabled: false
                },
                initialPreviewConfig: initConf,
                uploadExtraData: {
                    product_id: productId,
                }
            }).on('filesorted', function(e, params) {
                console.log('File sorted params', params);
            }).on('fileuploaded', function(e, params) {
                console.log('File uploaded params', params);
            });
        }))
    });


    $('.delete-product').on('click', function () {
        let Axios = require("axios");
        url = $(this).attr('data-del-url');
        Axios.delete(url).then((response)=>{
            document.location.reload();
        }).catch((error)=>{
        });
    });

    
    $('#select-all').on('click',function(event){
        event.preventDefault();
        if($(this).hasClass('selected')){
            $('.product-select').prop('checked', false);
        }else{
            $('.product-select').prop('checked', true);
        }
        $(this).toggleClass('selected');
    })
});

