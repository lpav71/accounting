/*$(function () {
    autocompliteCustomer($('#async-select').attr('data-async-select-id'));
    function autocompliteCustomer(customerId){
        axios.get('/autocomplite/customers')
            .then((response)=>{
            let string = '<option value="0">--</option>';
            for (let value of response.data) {
                if(value.id == customerId){
                    string = string + `<option selected value="${value.id}">${value.full_name}</option>`
                }else{
                    string = string + `<option value="${value.id}">${value.full_name}</option>`
                }
            }
            $('#async-select').html(string);
            $('#async-select').selectpicker({
                liveSearch:true
            });
        })
    }
});*/

//версия с загрузкой только при клике на список клиентов
$(function () {
    $('.async-select').click(function(){
        autocompliteCustomer($(this));
    });
    $('.async-select').each(function(){
        if($(this).attr('data-async-select-id') != 0){
            autocompliteCustomer($(this));
        }
    })
    function autocompliteCustomer(selectorItem){
        if(selectorItem.attr('data-async-select-loaded') == 0){
            selectorItem.attr('data-async-select-loaded', 1)
            selectorItem.addClass('spinner-grow text-muted');
        }else{
            return;
        }
        axios.get(selectorItem.attr('data-async-select-url'))
            .then((response)=>{
            let string = '<option value="0">--</option>';
            for (let option of response.data) {
                if(option.value == selectorItem.attr('data-async-select-id')){
                    string = string + `<option selected value="${option.value}">${option.name}</option>`
                }else{
                    string = string + `<option value="${option.value}">${option.name}</option>`
                }
            }
            selectorItem.html(string);
            selectorItem.removeClass('spinner-grow text-muted');
            selectorItem.selectpicker({
                liveSearch:true
            });
            })
    }
});
