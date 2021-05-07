$(function () {
    $('.my_form').submit(function () {

        let name = $('#name').val();
        let namelen = $('#name').val().length;
        let user = $('.user > select').val();
        let role = $('.role > select').val();
        let carier = $('.carier > select').val();
        let carrier_group = $('.carrier_group_id > select').val();

        if ((user.length !== 0 || role.length !== 0) && name.length !== 0  && namelen <= 255)
        {
            if (carier.length !== 0 && carrier_group.length !== 0)
            {
                alert('Нужно выбрать службу доставки или группу служб');
                return false;
            }

            if (carier.length === 0 && carrier_group.length === 0)
            {
                alert('Нужно выбрать что-то одно: службу доставки или группу служб');
                return false;
            }
        }
        else
        {
            alert('Заполнены не все поля либо поле Имя слишком длинное');
            return false;
        }
        return true;
    });
});