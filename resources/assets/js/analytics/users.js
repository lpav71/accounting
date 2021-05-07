$(function(){
    let $role;
    let $user;
    let rolesUserList;
    let userSelected;

    $('#user-selector').each(function(){
        $role=$('#role-selector');
        $user=$('#user-selector');
        rolesUserList=$.parseJSON($user.attr("data-users"));
        userSelected=$user.find("option:selected").val();
        changeSelectors();

        $role.change(function(){
            userSelected=0;
            changeSelectors();
        });
    });

    function changeSelectors(){
        roleSelected=$role.find("option:selected").text();
        $user.empty();
        if(rolesUserList[roleSelected]!=undefined){
            for (let i=0;i<rolesUserList[roleSelected].length;i++){
                $user.append('<option value="' + i + '">' + rolesUserList[roleSelected][i] + '</option>');
            } 
            $user.find("[value="+userSelected+"]").attr("selected", "selected");
        }else{
            $user.empty();
        }        
    }
});